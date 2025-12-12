<?php

namespace App\Filament\Pages;

use UnitEnum;
use BackedEnum;
use App\Models\Sale;
use App\Models\Unit;
use App\Models\Product;
use App\Models\Category;
use App\Models\SaleItem;
use Filament\Pages\Page;
use App\Models\CashSession;
use App\Models\DiscountCode;
use App\Models\StockMovement;
use App\Services\OrderPrintService;
use Illuminate\Support\Facades\Log;
use App\Services\ReceiptPrintService;
use Filament\Notifications\Notification;
use Filament\Support\Facades\FilamentAsset;
use App\Services\UnitConversionService;
use Livewire\WithPagination;
use Illuminate\Pagination\LengthAwarePaginator;

class Pos extends Page
{
    use WithPagination;
    use Concerns\HasCart;

    protected string $view = 'filament.pages.pos';

    // Gunakan layout custom
    protected static string $layout = 'layouts.pos-layout';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    protected static string|UnitEnum|null $navigationGroup = 'Transaksi';

    protected static ?string $navigationLabel = 'POS';

    protected $listeners = [
        'closeCashSessionFromLayout' => 'closeCashSession',
        'cashInConfirmed' => 'handleCashInConfirmed',
        'cashInCancelled' => 'handleCashInCancelled',
        'saleLoaded' => 'handleSaleLoaded',
        'paymentRequested' => 'handlePaymentRequested',
        'paymentProcessed' => 'handlePaymentProcessed',
        // 'printReceipt' => 'handlePrintReceipt',
        'printCompleted' => 'handlePrintCompleted',
        'showSection' => 'handleShowSection',
        'openMergeModal' => 'openMergeModal',
        'mergeConfirmed' => 'handleMergeConfirmed',
        'mergeCancelled' => 'handleMergeCancelled',
        'refreshSalesList' => 'refreshSalesList',
    ];

    public $showCashInModal = true;
    public $cashInHand = 0;
    public $cashSessionId = null;
    public $saleId = null;
    public $items = [];
    public $total = 0;
    public $tax = 0;
    public $discount = 0;
    public $orderType = 'Dine In';
    public $orderNumber = '';
    public $customerName = '';
    public $selectedCategory = 'Semua';
    public $discountCodeInput = '';
    public $discountMessage = '';
    public $discountApplied = false;
    public $showLoadModal = false;
    public $savedSales = [];
    public $showPaymentModal = false;
    public $payment_method = 'cash';
    public $finalTotal = 0;
    public $amount_paid = 0;
    public $outOfStock = 0;
    public $isPrinting = false;
    public $previousItems = [];
    public $showMergeModal = false;
    public $selectedSalesToMerge = [];
    public $availableSales = [];
    public $mergeTargetSale = null;
    public $editingNotesIndex = null;
    public $itemNotes = '';
    public $searchQuery = '';
    public $perPage = 12; // Default per page

    public function mount()
    {

        // ✅ INISIALISASI ITEMS SEBAGAI ARRAY KOSONG
        $this->items = [];
        $this->outOfStock = 0;
        $this->total = 0;
        $this->tax = 0;
        $this->discount = 0;
        $this->finalTotal = 0;
        $this->generateOrderNumber();
        $this->searchQuery = '';

        // Cek apakah user sudah punya sesi kas terbuka
        $session = CashSession::where('user_id', auth()->id())
            ->where('status', 'open')
            ->first();

        if ($session) {
            $this->showCashInModal = false;
            $this->cashSessionId = $session->id;
            session(['cash_session_id' => $session->id]);
        } else {
            $this->dispatch('openCashInModal');
        }
    }

    protected function getAssets(): array
    {
        return [
            FilamentAsset::makeStyle(
                'pos-theme',
                resource_path('css/filament/admin/theme.css')
            ),
        ];
    }

    public function handleCashInConfirmed($cashInHand)
    {
        // Logic untuk handle cash in confirmed
        $session = CashSession::create([
            'user_id' => auth()->id(),
            'cash_in_hand' => $cashInHand,
            'opened_at' => now(),
            'status' => 'open',
        ]);

        $this->cashSessionId = $session->id;
        session(['cash_session_id' => $session->id]);

        $this->dispatch('show-notification', message: 'Kas awal Rp ' . number_format($cashInHand, 0, ',', '.') . ' berhasil diset.', type: 'success');
    }

    public function handleCashInCancelled()
    {
        redirect()->route('filament.admin.pages.dashboard');
    }

    // Handler untuk modal load
    public function handleSaleLoaded($saleId)
    {
        $this->loadSale($saleId);
    }

    // Handler untuk payment request
    public function handlePaymentRequested($saleId)
    {
        $this->openPaymentModal($saleId);
    }

    // Handler untuk payment processed
    public function handlePaymentProcessed($saleId, $paymentMethodId, $amountPaid)
    {
        // DEBUG: Cek nilai yang diterima
        // dd([
        //     'saleId' => $saleId,
        //     'paymentMethodId' => $paymentMethodId,
        //     'amountPaid' => $amountPaid
        // ]);

        try {
            $sale = Sale::findOrFail($saleId);

            // Use OrderService to mark as paid
            app(\App\Services\OrderService::class)->markAsPaid($sale, $paymentMethodId, $amountPaid);

            // Auto print receipt setelah pembayaran berhasil
            $this->printReceipt($saleId);

            $this->dispatch('show-notification', message: 'Pembayaran berhasil diproses.', type: 'success');
            $this->showPaymentModal = false;
            $this->showLoadModal = false;

            $this->resetPos();

        } catch (\Exception $e) {
            // DEBUG: Tampilkan error
            // dd($e->getMessage());

            $this->dispatch('show-notification', message: 'Error: ' . $e->getMessage(), type: 'error');
        }
    }

    public function confirmCashIn()
    {
        if ($this->cashInHand <= 0) {
            Notification::make()
                ->title('Input tidak valid')
                ->body('Masukkan nominal kas awal yang benar.')
                ->danger()
                ->send();
            return;
        }

        $session = CashSession::create([
            'user_id' => auth()->id(),
            'cash_in_hand' => $this->cashInHand,
            'opened_at' => now(),
            'status' => 'open',
        ]);

        $this->cashSessionId = $session->id;
        session(['cash_session_id' => $session->id]);

        $this->showCashInModal = false;

        $this->dispatch('show-notification', message: 'Kas awal Rp ' . number_format($this->cashInHand, 0, ',', '.') . ' berhasil diset.', type: 'success');
    }

    public function cancelCashIn()
    {
        // langsung redirect ke dashboard
        redirect()->route('filament.admin.pages.dashboard');
    }

    public function closeCashSession($cashOut = null)
    {
        $session = CashSession::find(session('cash_session_id'));

        if (!$session) {
            Notification::make()
                ->title('Tidak ada sesi aktif')
                ->body('Tidak ada sesi kas yang sedang berjalan.')
                ->warning()
                ->send();
            return;
        }

        // Jika user isi manual, pakai itu, kalau kosong tetap fallback ke default
        $cashOutValue = $cashOut ?? ($session->cash_in_hand + $session->sales()
            ->where('status', 'completed')
            ->whereHas('paymentMethod', fn($q) => $q->where('code', 'cash'))
            ->sum('final_total'));

        // Hitung TOTAL penjualan CASH yang status COMPLETED
        $totalCashSales = $session->sales()
            ->where('status', 'completed')
            ->where('payment_method', 'cash')
            ->sum('final_total');

        $expectedCash = $session->cash_in_hand + $totalCashSales;

        $session->update([
            'cash_out' => $cashOutValue,
            'closed_at' => now(),
            'status' => 'closed',
        ]);

        $difference = $cashOut - $expectedCash;

        Notification::make()
            ->title('Shift Ditutup')
            ->body('Shift kasir telah ditutup. Selisih: Rp ' . number_format($difference, 0, ',', '.'))
            ->success()
            ->send();

        session()->forget('cash_session_id');


        redirect()->route('filament.admin.pages.dashboard');
    }

    public function cancelSale(): void
    {
        $this->resetPos();
        $this->dispatch('show-notification', message: 'Transaksi dibatalkan.', type: 'info');
    }

    public function getNameUserLogin(): string
    {
        return auth()->user()->name;
    }

    public function setOrderType(string $args): void
    {
        $this->orderType = $args;
    }

    /**
     * Handle search updates
     */
    public function updatedSearchQuery($value)
    {
        // 🔹 RESET PAGINATION KE HALAMAN 1
        $this->resetPage();

        // Clear cache ketika search berubah
        $cacheKey = $this->getProductsCacheKey();
        cache()->forget($cacheKey);

        // Log untuk debugging
        \Log::info('Search updated', ['query' => $value]);
    }

    /**
     * Handle category updates
     */
    public function updatedSelectedCategory($value)
    {
        // 🔹 RESET PAGINATION KE HALAMAN 1
        $this->resetPage();
    }

    /**
     * Update per page count from frontend
     */
    public function updatePerPage($count)
    {
        $this->perPage = $count;
        $this->resetPage();
    }

    /**
     * Clear search
     */
    public function clearSearch()
    {
        $this->searchQuery = '';
        $this->resetPage();
        $this->dispatch('show-notification', message: 'Pencarian dibersihkan', type: 'info');
    }

    public function getCategoriesProperty()
    {
        // Ambil semua kategori dari DB + tambah "Semua"
        $categories = Category::pluck('name')->toArray();
        array_unshift($categories, 'Semua');
        return $categories;
    }

    public function getProductsProperty()
    {
        $query = Product::where('is_sellable', true)
            ->with(['recipes.ingredient.unit', 'recipes.unit', 'unit']) // Eager load unit
            ->where(function ($q) {
                $q->where('stock', '>', 0)
                    ->orWhereIn('type', ['produced', 'bar'])
                    ->orWhereNull('stock');
            });

        // 🔍 Filter Search
        if (!empty($this->searchQuery)) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->searchQuery . '%');
            });
        }

        // Filter Kategori
        if ($this->selectedCategory !== 'Semua') {
            $category = Category::where('name', $this->selectedCategory)->first();
            if ($category) {
                $query->where('category_id', $category->id);
            }
        }

        // Use DB Pagination (Optimized)
        // Use DB Pagination (Optimized)
        return $query->orderBy('name', 'asc')->paginate($this->perPage);
    }

    /**
     * Check if a product is available (stock > 0 or produced ingredients available)
     * This is used for UI state only
     */
    public function checkProductAvailability($product)
    {
        // 1. Simple Stock
        if (in_array($product->type, ['raw', 'retail'])) {
            return $product->stock > 0;
        }

        // 2. Produced / Bar items
        if (in_array($product->type, ['produced', 'bar'])) {
            // Use existing logic (or optimization service if needed)
            return $this->isProducedProductAvailable($product, app(UnitConversionService::class));
        }

        return true;
    }

    /**
     * Cache key yang include search query (Removed/Deperecated for now)
     */
    protected function getProductsCacheKey(): string
    {
        return 'pos_products_' .
            $this->selectedCategory . '_' .
            md5($this->searchQuery) . '_' .
            auth()->id();
    }

    /**
     * Cek apakah produk produced bisa dibuat - DENGAN KONVERSI UNIT
     */
    private function isProducedProductAvailable(Product $product, UnitConversionService $conversionService): bool
    {
        if (!$product->recipes || $product->recipes->isEmpty()) {
            return false;
        }

        foreach ($product->recipes as $recipe) {
            $ingredient = $recipe->ingredient;
            if (!$ingredient) {
                return false;
            }

            // Gunakan Service untuk konversi (Memory-based, no DB Auto-query)
            $requiredInIngredientUnit = $conversionService->convert(
                $recipe->quantity,
                $recipe->unit_id,
                $ingredient->unit_id
            );

            if ($ingredient->stock < $requiredInIngredientUnit) {
                return false;
            }
        }

        return true;
    }

    // Old recursive conversion methods removed and replaced by UnitConversionService

    public function getImageUrlAttribute(): string
    {
        if (!$this->image) {
            return 'https://placehold.co/200x150?text=No+Image';
        }

        // Jika file tersimpan di storage/app/public
        return asset('storage/' . $this->image);
    }



    /**
     * Open edit notes modal untuk item tertentu
     */
    public function openEditNotes($index)
    {
        if (isset($this->items[$index])) {
            $this->editingNotesIndex = $index;
            $this->itemNotes = $this->items[$index]['notes'] ?? '';
            $this->dispatch('openNotesModal');
        }
    }

    /**
     * Save notes untuk item
     */
    public function saveItemNotes()
    {
        if ($this->editingNotesIndex !== null && isset($this->items[$this->editingNotesIndex])) {
            $this->items[$this->editingNotesIndex]['notes'] = trim($this->itemNotes);
            $this->editingNotesIndex = null;
            $this->itemNotes = '';

            $this->dispatch('closeNotesModal');
            $this->dispatch('show-notification', message: 'Catatan berhasil disimpan!', type: 'success');
        }
    }

    /**
     * Cancel edit notes
     */
    public function cancelEditNotes()
    {
        $this->editingNotesIndex = null;
        $this->itemNotes = '';
        $this->dispatch('closeNotesModal');
    }







    // public function saveSale()
    // {
    //     // 🔹 Cek keranjang kosong
    //     if (empty($this->items)) {
    //         $this->dispatch('show-notification', message: 'Keranjang kosong!', type: 'error');
    //         return;
    //     }

    //     // 🔹 Cek nama pelanggan
    //     if (empty(trim($this->customerName))) {
    //         $this->dispatch('show-notification', message: 'Nama pelanggan harus diisi!', type: 'error');
    //         return;
    //     }

    //     try {
    //         \DB::beginTransaction();

    //         $subtotal = $this->total ?? 0;
    //         $tax      = $this->tax ?? 0;
    //         $discount = $this->discount ?? 0;
    //         $final    = $this->finalTotal ?? ($subtotal + $tax - $discount);

    //         // 🔹 LOGIKA YANG BENAR:
    //         // - Jika $this->saleId ADA → UPDATE sale yang diload
    //         // - Jika $this->saleId NULL → CREATE sale baru

    //         if ($this->saleId) {
    //             // 🔹 UPDATE: Sale yang diload dari modal
    //             $sale = Sale::findOrFail($this->saleId);

    //             $sale->update([
    //                 'customer_name' => $this->customerName ?? 'Umum',
    //                 'order_type'    => $this->orderType,
    //                 'subtotal'      => $subtotal,
    //                 'tax'           => $tax,
    //                 'discount'      => $discount,
    //                 'final_total'   => $final,
    //                 'total'         => $final,
    //                 'updated_at'    => now(),
    //             ]);

    //             // Hapus item lama agar bisa simpan item baru
    //             $sale->items()->delete();

    //         } else {
    //             // 🔹 CREATE: Transaksi baru dengan order number baru
    //             $sale = Sale::create([
    //                 'cash_session_id' => $this->cashSessionId ?? session('cash_session_id'),
    //                 'user_id'         => auth()->id(),
    //                 'invoice_number'  => $this->generateOrderNumber(),
    //                 'customer_name'   => $this->customerName ?? 'Umum',
    //                 'order_type'      => $this->orderType,
    //                 'subtotal'        => $subtotal,
    //                 'tax'             => $tax,
    //                 'discount'        => $discount,
    //                 'final_total'     => $final,
    //                 'total'           => $final,
    //                 'payment_method'  => '',
    //                 'status'          => 'draft',
    //             ]);

    //             // Set saleId untuk transaksi baru
    //             $this->saleId = $sale->id;
    //         }

    //         // 🔹 Simpan items (sama untuk kedua kasus)
    //         foreach ($this->items as $item) {
    //             $saleItem = SaleItem::create([
    //                 'sale_id'    => $sale->id,
    //                 'product_id' => $item['product_id'],
    //                 'quantity'   => $item['quantity'],
    //                 'unit_price' => $item['price'],
    //                 'subtotal'   => $item['subtotal'],
    //             ]);

    //             $product = Product::find($item['product_id']);

    //             if (!$product) continue;

    //             // 🔹 Kurangi stok (sama untuk kedua kasus)
    //             if ($product->recipes()->exists()) {
    //                 $recipes = $product->recipes()->with('ingredient')->get();

    //                 foreach ($recipes as $recipe) {
    //                     if (! $recipe->ingredient) continue;

    //                     $recipeRate     = max($recipe->unit->conversion_rate ?? 1, 0.0001);
    //                     $ingredientRate = max($recipe->ingredient->unit->conversion_rate ?? 1, 0.0001);

    //                     $conversion = $ingredientRate / $recipeRate;
    //                     $totalUsed = $recipe->quantity * $item['quantity'] * $conversion;

    //                     $recipe->ingredient->decrement('stock', $totalUsed);

    //                     StockMovement::create([
    //                         'product_id' => $recipe->ingredient->id,
    //                         'quantity'   => -$totalUsed,
    //                         'type'       => 'decrease',
    //                         'reason'     => 'POS Sale #' . $sale->invoice_number,
    //                         'notes'      => 'Bahan untuk produk ' . $product->name . ' dijual (' . auth()->user()->name . ')',
    //                     ]);
    //                 }

    //             } else {
    //                 $product->decrement('stock', $item['quantity']);

    //                 StockMovement::create([
    //                     'product_id' => $product->id,
    //                     'quantity'   => -$item['quantity'],
    //                     'type'       => 'decrease',
    //                     'reason'     => 'POS Sale #' . $sale->invoice_number,
    //                     'notes'      => 'Penjualan langsung produk oleh ' . auth()->user()->name,
    //                 ]);
    //             }
    //         }

    //         \DB::commit();

    //         // 🔹 TAMPILKAN NOTIFIKASI BERBEDA
    //         if ($this->saleId) {
    //             $this->dispatch('show-notification', message: 'Transaksi #' . $sale->invoice_number . ' berhasil diupdate!', type: 'success');
    //         } else {
    //             $this->dispatch('show-notification', message: 'Transaksi baru #' . $sale->invoice_number . ' berhasil disimpan!', type: 'success');
    //         }

    //         $this->resetPos();

    //         // 🔹 PENTING: JANGAN resetPos() setelah save!
    //         // Biarkan saleId tetap ada agar jika user tambah menu lagi, akan update sale yang sama
    //         // Hanya reset jika memang ingin mulai transaksi baru

    //     } catch (\Exception $e) {
    //         \DB::rollBack();
    //         $this->dispatch('show-notification', message: 'Gagal menyimpan penjualan: ' . $e->getMessage(), type: 'error');
    //     }
    // }

    public function saveSale()
    {
        // 🔹 Cek keranjang kosong
        if (empty($this->items)) {
            $this->dispatch('show-notification', message: 'Keranjang kosong!', type: 'error');
            return;
        }

        // 🔹 Cek nama pelanggan
        if (empty(trim($this->customerName))) {
            $this->dispatch('show-notification', message: 'Nama pelanggan harus diisi!', type: 'error');
            return;
        }

        try {
            $subtotal = $this->total ?? 0;
            $tax = $this->tax ?? 0;
            $discount = $this->discount ?? 0;
            $final = $this->finalTotal ?? ($subtotal + $tax - $discount);

            $isUpdate = !empty($this->saleId);
            $existingSale = $isUpdate ? Sale::find($this->saleId) : null;

            // Consistency check
            if ($isUpdate && !$existingSale) {
                $isUpdate = false;
            }

            $orderData = [
                'cash_session_id' => $this->cashSessionId ?? session('cash_session_id'),
                'user_id' => auth()->id(),
                'invoice_number' => $isUpdate ? $existingSale->invoice_number : $this->generateOrderNumber(),
                'customer_name' => $this->customerName ?? 'Umum',
                'order_type' => $this->orderType,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'discount' => $discount,
                'final_total' => $final,
            ];

            // Use Service
            $orderService = app(\App\Services\OrderService::class);
            $sale = $orderService->processOrder($orderData, $this->items, $isUpdate, $existingSale);

            // Set saleId if new
            if (!$isUpdate) {
                $this->saleId = $sale->id;
            }

            // 🔹 TENTUKAN APAKAH INI UPDATE
            if ($isUpdate) {
                // 🔹 DAPATKAN ITEM BARU/TAMBAHAN
                $newItems = $this->getNewOrUpdatedItems();

                \Log::info('🔄 Update order detected', [
                    'sale_id' => $sale->id,
                    'new_items_count' => count($newItems)
                ]);

                // 🔹 PRINT HANYA ITEM BARU JIKA ADA
                if (!empty($newItems)) {
                    try {
                        $orderPrintService = new OrderPrintService();
                        $printResult = $orderPrintService->printNewItemsOnly($sale, $newItems);

                        $this->dispatch('show-notification', message: '✅ Item berhasil ditambah & tambahan order dicetak!', type: 'success');

                    } catch (\Exception $e) {
                        $this->dispatch('show-notification', message: '⚠️ Order tersimpan tapi gagal print tambahan: ' . $e->getMessage(), type: 'warning');
                    }
                } else {
                    $this->dispatch('show-notification', message: '✅ Order berhasil diupdate!', type: 'success');
                }
            } else {
                // 🔹 NEW ORDER - PRINT SEMUA
                try {
                    $orderPrintService = new OrderPrintService();
                    $printResult = $orderPrintService->printOrderByProductType($sale);
                    $this->dispatch('show-notification', message: '✅ Order baru berhasil dikirim ke divisi!', type: 'success');

                } catch (\Exception $e) {
                    $this->dispatch('show-notification', message: '⚠️ Order tersimpan tapi gagal print: ' . $e->getMessage(), type: 'warning');
                }
            }

            // 🔹 RESET previousItems
            $this->previousItems = [];

            // 🔹 TAMPILKAN NOTIFIKASI BERBEDA
            if ($isUpdate) {
                $this->dispatch('show-notification', message: 'Transaksi #' . $sale->invoice_number . ' berhasil diupdate!', type: 'success');
            } else {
                $this->dispatch('show-notification', message: 'Transaksi baru #' . $sale->invoice_number . ' berhasil disimpan!', type: 'success');
            }

            $this->resetPos();

        } catch (\Exception $e) {
            \Log::error('💥 Gagal menyimpan penjualan: ' . $e->getMessage());
            $this->dispatch('show-notification', message: 'Gagal menyimpan penjualan: ' . $e->getMessage(), type: 'error');
        }
    }

    /**
     * Dapatkan item baru atau yang quantity-nya berubah DENGAN NOTES
     */
    protected function getNewOrUpdatedItems(): array
    {
        $newItems = [];

        foreach ($this->items as $currentItem) {
            $foundInPrevious = false;

            // Cek apakah item ada di previous items
            foreach ($this->previousItems as $previousItem) {
                if ($previousItem['product_id'] == $currentItem['product_id']) {
                    $foundInPrevious = true;

                    // Jika quantity berubah atau notes berbeda
                    $quantityDiff = $currentItem['quantity'] - $previousItem['quantity'];
                    $notesChanged = isset($currentItem['notes']) &&
                        ($currentItem['notes'] !== ($previousItem['notes'] ?? ''));

                    if ($quantityDiff > 0 || $notesChanged) {
                        $newItems[] = [
                            'product_id' => $currentItem['product_id'],
                            'name' => $currentItem['name'],
                            'quantity' => $quantityDiff > 0 ? $quantityDiff : $currentItem['quantity'],
                            'price' => $currentItem['price'],
                            'notes' => $currentItem['notes'] ?? '',
                            'is_update' => true
                        ];
                    }
                    break;
                }
            }

            // Jika item benar-benar baru (tidak ada di previous)
            if (!$foundInPrevious) {
                $newItems[] = [
                    'product_id' => $currentItem['product_id'],
                    'name' => $currentItem['name'],
                    'quantity' => $currentItem['quantity'],
                    'price' => $currentItem['price'],
                    'notes' => $currentItem['notes'] ?? '',
                    'is_update' => false
                ];
            }
        }

        return $newItems;
    }

    public function printReceipt($saleId)
    {
        // 🔹 CEK APAKAH SEDANG PRINT
        if ($this->isPrinting) {
            Log::warning('Print receipt skipped - already printing', ['saleId' => $saleId]);
            return;
        }

        try {
            $this->isPrinting = true;

            if (!$saleId) {
                $this->dispatch('show-notification', message: 'Sale ID tidak valid untuk print.', type: 'error');
                $this->isPrinting = false;
                return;
            }

            logger('Print Receipt - Searching Sale:', ['saleId' => $saleId]);

            $sale = Sale::with(['items.product', 'user', 'paymentMethod'])->find($saleId);

            if (!$sale) {
                logger('Sale not found:', ['saleId' => $saleId]);
                $this->dispatch('show-notification', message: 'Transaksi tidak ditemukan untuk dicetak.', type: 'error');
                $this->isPrinting = false;
                return;
            }

            logger('Sale found, proceeding to print:', ['invoice' => $sale->invoice_number]);

            // ✅ GUNAKAN RECEIPT PRINT SERVICE YANG DIPERBAIKI
            $printService = new ReceiptPrintService($sale);
            $printService->printReceipt();

            $this->dispatch('showNotification', '✅ Struk berhasil dicetak!', 'success');
            $this->dispatch('printCompleted');

        } catch (\Exception $e) {
            Log::error('❌ Print receipt failed: ' . $e->getMessage());
            $this->dispatch('showNotification', '❌ Gagal mencetak struk: ' . $e->getMessage(), 'error');
            $this->dispatch('printFailed');
        } finally {
            $this->isPrinting = false;
        }
    }

    // 🔹 TAMBAHKAN METHOD UNTUK DEBUG PRINTER
    public function debugPrinter()
    {
        try {
            $printService = new ReceiptPrintService();

            // 1. Get available printers
            $printers = $printService->getAvailablePrinters();

            // 2. Test printer connection
            $testResult = $printService->testPrinter();

            $message = "Printers tersedia: " . implode(', ', $printers) . "\n";
            $message .= "Test result: " . ($testResult['success'] ? '✅ BERHASIL' : '❌ GAGAL: ' . $testResult['error']);

            $this->dispatch('showNotification', $message, $testResult['success'] ? 'success' : 'error');

        } catch (\Exception $e) {
            $this->dispatch('showNotification', '❌ Debug error: ' . $e->getMessage(), 'error');
        }
    }

    /**
     * Decrement quantity dengan debounce
     */
    public function decrementQuantity($index)
    {
        if (isset($this->items[$index])) {
            $newQuantity = $this->items[$index]['quantity'] - 1;

            if ($newQuantity < 1) {
                $this->removeItem($index);
            } else {
                $this->items[$index]['quantity'] = $newQuantity;
                $this->items[$index]['subtotal'] = $this->items[$index]['price'] * $newQuantity;
                $this->recalculateTotals();
            }
        }
    }

    /**
     * Increment quantity dengan debounce
     */
    public function incrementQuantity($index)
    {
        if (isset($this->items[$index])) {
            $this->items[$index]['quantity'] += 1;
            $this->items[$index]['subtotal'] = $this->items[$index]['price'] * $this->items[$index]['quantity'];
            $this->recalculateTotals();
        }
    }

    /**
     * Update quantity dari input text
     */
    public function updateQuantityFromInput($index, $quantity)
    {
        $quantity = intval($quantity);

        if ($quantity < 1) {
            $this->removeItem($index);
            return;
        }

        if (isset($this->items[$index])) {
            $this->items[$index]['quantity'] = $quantity;
            $this->items[$index]['subtotal'] = $this->items[$index]['price'] * $quantity;
            $this->recalculateTotals();
        }
    }

    /**
     * Optimized recalculate totals
     */
    protected function recalculateTotals()
    {
        // Gunakan array_sum untuk performance lebih baik
        $this->total = array_sum(array_column($this->items, 'subtotal'));
        $this->tax = $this->total * 0.10;
        $this->finalTotal = $this->total + $this->tax - $this->discount;
    }

    public function openLoadModal()
    {
        // $this->savedSales = Sale::where('status', 'draft')
        //     ->latest()
        //     ->take(20)
        //     ->get();

        // $this->showLoadModal = true;
        $this->dispatch('openLoadModal');
    }

    public function loadSale($saleId)
    {
        $sale = Sale::with('items.product')->findOrFail($saleId);

        $this->saleId = $sale->id;
        $this->orderNumber = $sale->invoice_number;
        $this->customerName = $sale->customer_name ?? '';
        $this->orderType = $sale->order_type ?? 'Dine In';
        $this->discount = $sale->discount ?? 0;

        // 🔹 SIMPAN ITEMS SEBELUMNYA untuk tracking (dengan notes)
        $this->previousItems = $sale->items->map(function ($item) {
            return [
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'notes' => $item->notes ?? '' // ✅ TAMBAHKAN NOTES
            ];
        })->toArray();

        // Map ulang items untuk tampilan
        $this->items = $sale->items->map(function ($item) {
            $product = $item->product;
            return [
                'product_id' => $item->product_id,
                'name' => $product?->name ?? '(Produk dihapus)',
                'quantity' => $item->quantity,
                'price' => $item->unit_price,
                'subtotal' => $item->subtotal,
                'notes' => $item->notes ?? '', // ✅ TAMBAHKAN NOTES
            ];
        })->toArray();

        $this->recalculateTotals();
        $this->showLoadModal = false;
        $this->dispatch('showNotification', 'Transaksi berhasil dimuat.', 'success');
    }

    public function openPaymentModal($saleId = null)
    {
        // Jika saleId tidak dikirim, gunakan current saleId
        $targetSaleId = $saleId ?? $this->saleId;

        if (!$targetSaleId) {
            $this->dispatch('showNotification', 'Simpan transaksi terlebih dahulu!', 'error');
            return;
        }

        // Dispatch event ke modal payment
        $this->dispatch('openPaymentModal', saleId: $targetSaleId);
    }

    public function processPayment()
    {
        $sale = Sale::findOrFail($this->saleId);

        $sale->update([
            'is_paid' => true,
            'payment_method' => $this->payment_method,
            'amount_paid' => $this->amount_paid,
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $this->showPaymentModal = false;
        $this->showLoadModal = false;

        $this->dispatch('show-notification', message: 'Pembayaran berhasil diproses.', type: 'success');
        $this->resetPos();
    }

    // Handler untuk print receipt
    public function handlePrintReceipt($saleId)
    {
        logger('Handle Print Receipt - Sale ID:', ['saleId' => $saleId]);
        $this->printReceipt($saleId);
    }

    // Handler untuk print completed
    public function handlePrintCompleted()
    {
        // Tidak perlu melakukan apa-apa di sini, ini hanya untuk menerima event
        logger('Print completed event received in POS');
    }

    protected function generateReceiptContent(Sale $sale): string
    {
        $content = "";

        // Header
        $content .= "<div class='text-center'>";
        $content .= "<h1 class='font-bold text-lg uppercase'>STRUK PEMBAYARAN</h1>";
        $content .= "<p class='text-sm'>" . config('app.name') . "</p>";
        $content .= "<p class='text-xs'>" . $sale->created_at->format('d/m/Y H:i') . "</p>";
        $content .= "</div>";

        $content .= "<div class='border-t border-dashed border-gray-300 my-2'></div>";

        // Info Transaksi
        $content .= "<div class='space-y-1 text-sm'>";
        $content .= "<div class='flex justify-between'><span>No. Transaksi:</span><span class='font-semibold'>" . $sale->invoice_number . "</span></div>";
        $content .= "<div class='flex justify-between'><span>Kasir:</span><span>" . ($sale->user->name ?? 'System') . "</span></div>";
        $content .= "<div class='flex justify-between'><span>Customer:</span><span>" . ($sale->customer_name ?? 'Umum') . "</span></div>";
        $content .= "</div>";

        $content .= "<div class='border-t border-dashed border-gray-300 my-2'></div>";

        // Items
        $content .= "<div class='space-y-2'>";
        foreach ($sale->items as $item) {
            $content .= "<div class='flex justify-between items-start'>";
            $content .= "<div class='flex-1'>";
            $content .= "<div class='font-semibold'>" . $item->product->name . "</div>";
            $content .= "<div class='text-xs text-gray-600'>" . $item->quantity . " × Rp" . number_format($item->unit_price, 0, ',', '.') . "</div>";
            $content .= "</div>";
            $content .= "<div class='font-semibold'>Rp" . number_format($item->subtotal, 0, ',', '.') . "</div>";
            $content .= "</div>";
        }
        $content .= "</div>";

        $content .= "<div class='border-t border-dashed border-gray-300 my-2'></div>";

        // Summary
        $content .= "<div class='space-y-1 text-sm'>";
        $content .= "<div class='flex justify-between'><span>Subtotal:</span><span>Rp" . number_format($sale->subtotal, 0, ',', '.') . "</span></div>";
        $content .= "<div class='flex justify-between'><span>Pajak (10%):</span><span>Rp" . number_format($sale->tax, 0, ',', '.') . "</span></div>";
        if ($sale->discount > 0) {
            $content .= "<div class='flex justify-between text-green-600'><span>Diskon:</span><span>- Rp" . number_format($sale->discount, 0, ',', '.') . "</span></div>";
        }
        $content .= "<div class='border-t border-gray-300 pt-1'>";
        $content .= "<div class='flex justify-between font-bold'><span>TOTAL:</span><span>Rp" . number_format($sale->final_total, 0, ',', '.') . "</span></div>";
        $content .= "</div>";
        $content .= "</div>";

        $content .= "<div class='border-t border-dashed border-gray-300 my-2'></div>";

        // Payment Info
        $content .= "<div class='space-y-1 text-sm'>";
        $content .= "<div class='flex justify-between'><span>Metode:</span><span class='font-semibold'>" . ($sale->paymentMethod->name ?? 'Cash') . "</span></div>";
        $content .= "<div class='flex justify-between'><span>Bayar:</span><span>Rp" . number_format($sale->amount_paid, 0, ',', '.') . "</span></div>";
        if ($sale->paymentMethod?->code === 'cash') {
            $change = $sale->amount_paid - $sale->final_total;
            $content .= "<div class='flex justify-between'><span>Kembali:</span><span class='font-semibold'>Rp" . number_format($change, 0, ',', '.') . "</span></div>";
        }
        $content .= "</div>";

        $content .= "<div class='border-t border-dashed border-gray-300 my-2'></div>";

        // Footer
        $content .= "<div class='text-center text-xs'>";
        $content .= "<p>Terima kasih atas kunjungan Anda</p>";
        $content .= "<p class='font-semibold'>*** SELAMAT MENIKMATI ***</p>";
        $content .= "</div>";

        return $content;
    }

    public function testWebhookPrinting()
    {
        try {
            $orderPrintService = new OrderPrintService();
            $result = $orderPrintService->testWebhookConnection();

            if ($result['success']) {
                $this->dispatch('showNotification', '✅ Webhook connection successful!', 'success');
            } else {
                $this->dispatch('showNotification', '❌ Webhook connection failed: ' . $result['error'], 'error');
            }
        } catch (\Exception $e) {
            $this->dispatch('showNotification', '❌ Webhook test error: ' . $e->getMessage(), 'error');
        }
    }

    public function toggleWebhookPrinting()
    {
        $current = config('app.use_webhook_printing', false);
        $newValue = !$current;

        // Update config temporarily
        config(['app.use_webhook_printing' => $newValue]);

        $status = $newValue ? 'ENABLED' : 'DISABLED';
        $this->dispatch('showNotification', "🔄 Webhook printing {$status}", 'info');
    }

    protected function resetPos(): void
    {
        $this->saleId = null;
        $this->orderNumber = '';
        $this->items = [];
        $this->total = 0;
        $this->tax = 0;
        $this->discount = 0;
        $this->finalTotal = 0;
        $this->orderType = 'Dine In';
        $this->generateOrderNumber();
        $this->customerName = '';
        $this->discountCodeInput = '';
        $this->discountMessage = '';
        $this->discountApplied = false;
        $this->editingNotesIndex = null;
        $this->itemNotes = '';
        // jangan ubah $showCashInModal agar modal hanya dikontrol saat mount/cek session
    }


    protected function generateOrderNumber()
    {
        // generate order number unik, random nomor dan tanggal saat ini
        $this->orderNumber = '#' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
        return $this->orderNumber;

    }

    public function setCategory($category)
    {
        $this->selectedCategory = $category;
    }

    /**
     * Handle section switching dari JavaScript
     */
    public function handleShowSection($section)
    {
        $this->switchToSection($section);
    }

    /**
     * Switch section untuk mobile - FIXED
     */
    public function switchToSection($section)
    {
        // Validasi section yang diperbolehkan
        $allowedSections = ['products', 'cart'];

        if (!in_array($section, $allowedSections)) {
            return;
        }

        // Update state jika diperlukan
        $this->dispatch('updateMobileNav', section: $section);

        \Log::info("Switching to section: {$section}");
    }

    /**
     * Open load modal untuk tombol Order
     */
    public function openLoadOrderModal()
    {
        $this->openLoadModal();
    }

    /**
     * Handle mobile payment modal
     */
    public function openPaymentModalMobile()
    {
        if (!$this->saleId) {
            // Jika tidak ada saleId, coba simpan dulu
            if (!empty($this->items)) {
                $this->saveSale();
            } else {
                $this->dispatch('showNotification', 'Keranjang kosong! Tambahkan produk terlebih dahulu.', 'error');
                return;
            }
        }

        $this->dispatch('openPaymentModal', saleId: $this->saleId);
    }

    /**
     * Quick add product untuk mobile - IMPROVED
     */
    public function quickAddProduct($productId)
    {
        $this->addProduct($productId);

        // Auto switch to cart section setelah add product
        $this->switchToSection('cart');

        // Show success feedback
        // $this->dispatch('showNotification', 'Produk ditambahkan ke keranjang!', 'success');
    }

    /**
     * Mobile-optimized remove item
     */
    public function mobileRemoveItem($index)
    {
        $itemName = $this->items[$index]['name'] ?? 'Item';
        $this->removeItem($index);

        $this->dispatch('showNotification', $itemName . ' dihapus dari keranjang', 'info');
    }

    /**
     * Mobile-optimized update quantity
     */
    public function mobileUpdateQuantity($index, $quantity)
    {
        if ($quantity < 1) {
            $this->mobileRemoveItem($index);
            return;
        }

        $this->updateQuantity($index, $quantity);
    }

    /**
     * Get cart count untuk mobile badge
     */
    public function getCartItemsCountProperty()
    {
        return is_array($this->items) ? count($this->items) : 0;
    }

    /**
     * Get cart items dengan safe check
     */
    public function getCartItemsProperty()
    {
        return is_array($this->items) ? $this->items : [];
    }

    /**
     * Save sale untuk mobile - simplified
     */
    public function mobileSaveSale()
    {
        if (empty($this->items)) {
            $this->dispatch('showNotification', 'Keranjang kosong! Tambahkan produk terlebih dahulu.', 'error');
            return;
        }

        if (empty(trim($this->customerName))) {
            $this->dispatch('show-notification', message: 'Nama pelanggan harus diisi!', type: 'error');
            return;
        }

        $this->saveSale();
    }

    // Handler untuk merge confirmed
    public function handleMergeConfirmed($selectedSales, $mergeTarget)
    {
        $this->selectedSalesToMerge = $selectedSales;
        $this->mergeTargetSale = $mergeTarget;
        $this->processMergeBill();
    }

    // Handler untuk merge cancelled
    public function handleMergeCancelled()
    {
        $this->showMergeModal = false;
        $this->selectedSalesToMerge = [];
        $this->mergeTargetSale = null;
    }

    /**
     * Get merge sales data
     */
    public function getMergeSalesProperty()
    {
        if (!$this->showMergeModal) {
            return collect();
        }

        return Sale::where('status', 'draft')
            ->where('cash_session_id', $this->cashSessionId ?? session('cash_session_id'))
            ->with(['items.product', 'user'])
            ->latest()
            ->take(20)
            ->get()
            ->filter(function ($sale) {
                return $sale->items->isNotEmpty();
            });
    }

    // Method untuk menghitung merge totals
    public function getMergeTotalsProperty()
    {
        if (empty($this->selectedSalesToMerge)) {
            return [
                'count' => 0,
                'total' => 0,
                'items' => 0,
                'has_target' => false
            ];
        }

        try {
            $sales = Sale::whereIn('id', $this->selectedSalesToMerge)->get();

            $total = 0;
            $items = 0;

            foreach ($sales as $sale) {
                $total += $sale->final_total;
                $items += $sale->items->sum('quantity');
            }

            return [
                'count' => $sales->count(),
                'total' => $total,
                'items' => $items,
                'has_target' => !empty($this->mergeTargetSale)
            ];
        } catch (\Exception $e) {
            return [
                'count' => 0,
                'total' => 0,
                'items' => 0,
                'has_target' => false
            ];
        }
    }

    // Method untuk mendapatkan info target sale
    public function getTargetSaleInfoProperty()
    {
        if (!$this->mergeTargetSale) {
            return null;
        }

        try {
            return Sale::with('items')->find($this->mergeTargetSale);
        } catch (\Exception $e) {
            return null;
        }
    }

    // Method untuk mendapatkan sales yang tersedia untuk merge
    public function getAvailableMergeSalesProperty()
    {
        if (!$this->showMergeModal) {
            return collect();
        }

        try {
            return Sale::where('status', 'draft')
                ->where('cash_session_id', $this->cashSessionId ?? session('cash_session_id'))
                ->with(['items.product', 'user'])
                ->latest()
                ->take(20)
                ->get()
                ->filter(function ($sale) {
                    return $sale->items->isNotEmpty();
                });
        } catch (\Exception $e) {
            return collect();
        }
    }

    /**
     * Open merge bill modal - DENGAN DEBUG DETAIL
     */
    public function openMergeModal()
    {
        \Log::info('🔄 openMergeModal dipanggil');

        try {
            // Ambil data sales
            $availableSales = Sale::where('status', 'draft')
                ->where('cash_session_id', $this->cashSessionId ?? session('cash_session_id'))
                ->whereHas('items')
                ->with(['items', 'user'])
                ->latest()
                ->take(20)
                ->get();

            \Log::info('📊 Data sales ditemukan DETAIL', [
                'count' => $availableSales->count(),
                'sales' => $availableSales->map(function ($sale) {
                    return [
                        'id' => $sale->id,
                        'invoice_number' => $sale->invoice_number,
                        'customer_name' => $sale->customer_name,
                        'final_total' => $sale->final_total,
                        'items_count' => $sale->items->count(),
                        'has_user' => !is_null($sale->user)
                    ];
                })->toArray()
            ]);

            // Convert ke array sederhana untuk Livewire
            $this->availableSales = $availableSales->map(function ($sale) {
                return [
                    'id' => $sale->id,
                    'invoice_number' => $sale->invoice_number,
                    'customer_name' => $sale->customer_name,
                    'final_total' => $sale->final_total,
                    'order_type' => $sale->order_type,
                    'created_at' => $sale->created_at,
                    'items' => $sale->items->map(function ($item) {
                        return [
                            'quantity' => $item->quantity
                        ];
                    })->toArray(),
                    'user' => $sale->user ? [
                        'name' => $sale->user->name
                    ] : null
                ];
            })->toArray();

            \Log::info('✅ Data dikonversi ke array untuk Livewire');

            $this->selectedSalesToMerge = [];
            $this->mergeTargetSale = null;
            $this->showMergeModal = true;

        } catch (\Exception $e) {
            \Log::error('❌ Error openMergeModal: ' . $e->getMessage());
            $this->dispatch('showNotification', 'Gagal membuka modal merge: ' . $e->getMessage(), 'error');
        }
    }

    /**
     * Toggle select sale untuk merge
     */
    public function toggleSelectSale($saleId)
    {
        $index = array_search($saleId, $this->selectedSalesToMerge);

        if ($index !== false) {
            // Unselect jika sudah terpilih
            unset($this->selectedSalesToMerge[$index]);
            $this->selectedSalesToMerge = array_values($this->selectedSalesToMerge);

            // Jika sale yang di-unselect adalah merge target, reset merge target
            if ($this->mergeTargetSale == $saleId) {
                $this->mergeTargetSale = null;
            }
        } else {
            // Select sale baru
            $this->selectedSalesToMerge[] = $saleId;

            // Auto-set sale pertama sebagai merge target jika belum ada
            if (count($this->selectedSalesToMerge) === 1) {
                $this->mergeTargetSale = $saleId;
            }
        }
    }

    /**
     * Set merge target sale
     */
    public function setMergeTarget($saleId)
    {
        if (in_array($saleId, $this->selectedSalesToMerge)) {
            $this->mergeTargetSale = $saleId;
        }
    }

    /**
     * Proses merge bill - DENGAN LOG DETAIL
     */
    public function processMergeBill()
    {
        \Log::info('🔄 Memulai proses merge bill');

        if (empty($this->selectedSalesToMerge) || count($this->selectedSalesToMerge) < 2) {
            $this->dispatch('showNotification', 'Pilih minimal 2 transaksi untuk digabung!', 'error');
            return;
        }

        if (!$this->mergeTargetSale) {
            $this->dispatch('showNotification', 'Pilih transaksi tujuan!', 'error');
            return;
        }

        try {
            $orderService = app(\App\Services\OrderService::class);
            $targetSale = $orderService->mergeSales($this->mergeTargetSale, $this->selectedSalesToMerge);

            \Log::info('✅ Merge bill berhasil', ['target_sale_id' => $targetSale->id]);

            $this->dispatch('showNotification', 'Transaksi berhasil digabungkan ke #' . $targetSale->invoice_number, 'success');

            // Reset state
            $this->showMergeModal = false;
            $this->selectedSalesToMerge = [];
            $this->mergeTargetSale = null;
            $this->dispatch('refreshSalesList'); // Important for UI update

        } catch (\Exception $e) {
            \Log::error('💥 Gagal merge bill: ' . $e->getMessage());
            $this->dispatch('showNotification', 'Gagal menggabungkan transaksi: ' . $e->getMessage(), 'error');
        }
    }


    /**
     * Cancel merge process
     */
    public function cancelMerge()
    {
        $this->showMergeModal = false;
        $this->selectedSalesToMerge = [];
        $this->mergeTargetSale = null;
    }

    /**
     * Safe get merge totals
     */
    public function safeGetMergeTotals()
    {
        try {
            return $this->mergeTotals;
        } catch (\Exception $e) {
            return [
                'count' => 0,
                'total' => 0,
                'items' => 0,
                'has_target' => false
            ];
        }
    }

    /**
     * Safe get target sale info
     */
    public function safeGetTargetSaleInfo()
    {
        try {
            return $this->targetSaleInfo;
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function getViewData(): array
    {
        return [
            'orderNumber' => $this->orderNumber,
            'categories' => $this->categories,
            'products' => $this->products,
        ];
    }
}

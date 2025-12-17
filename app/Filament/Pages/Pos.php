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
use App\Livewire\PosLoadModal;

class Pos extends Page
{
    use WithPagination;
    use Concerns\HasCart;
    use Concerns\HasPayment;
    use Concerns\HasPrinting;

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
        'printReceipt' => 'handlePrintReceipt',
        'printCompleted' => 'handlePrintCompleted',

        'openMergeModal' => 'openMergeModal',
        'mergeConfirmed' => 'handleMergeConfirmed',
        'mergeCancelled' => 'handleMergeCancelled',
        'refreshSalesList' => 'refreshSalesList',
        'applyManualDiscount' => 'applyManualDiscount',
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
    public $selectedCategory = 'SEMUA';
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
    public $perPage = 12; // Restore to original 12 if that was the intent, or keep 10. Let's use 12 as per previous context.
    // New Properties
    public $tableNumber = '';
    public $discountType = 'fixed'; // fixed or percentage
    public $manualDiscountValue = 0;

    /**
     * Cetak Ulang Order (Kitchen/Bar)
     */
    public function reprintOrder()
    {
        if (!$this->saleId) {
            $this->dispatch('show-notification', message: 'Tidak ada transaksi aktif untuk dicetak ulang.', type: 'warning');
            return;
        }

        try {
            $sale = Sale::findOrFail($this->saleId);
            $service = new OrderPrintService();
            $service->printOrderByProductType($sale);

            $this->dispatch('show-notification', message: 'Print job ulang berhasil dikirim ke Dapur/Bar.', type: 'success');
        } catch (\Exception $e) {
            \Log::error('Reprint failed: ' . $e->getMessage());
            $this->dispatch('show-notification', message: 'Gagal mencetak ulang: ' . $e->getMessage(), type: 'error');
        }
    }

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

        // Load Table Number Setting
        $settings = new \App\Settings\GeneralSettings();
        // $this->showTableNumber = $settings->enable_table_number; // Logic moved to view for simplicity

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

    /**
     * Refresh Sales List trigger from Frontend after Offline Sync
     */
    public function refreshSalesList()
    {
        // Dispatch event to PosLoadModal to refresh its data
        $this->dispatch('refreshSalesList')->to(PosLoadModal::class);

        // Notify user
        $this->dispatch('show-notification', message: 'Data penjualan offline berhasil dimuat.', type: 'info');
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
        array_unshift($categories, 'SEMUA');
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
        if ($this->selectedCategory !== 'SEMUA') {
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

        // Final Total Calculation with discount validation
        $final = $this->total + $this->tax - $this->discount;
        $this->finalTotal = max(0, $final);
    }

    /**
     * Apply Manual Discount from Modal
     */
    public function applyManualDiscount($type, $value, $reason = null)
    {
        $this->discountType = $type;
        $this->manualDiscountValue = $value;
        $discountAmount = 0;

        if ($type === 'percentage') {
            $percentage = min(100, max(0, $value));
            $discountAmount = $this->total * ($percentage / 100);
            $this->discountMessage = "Diskon Manual: {$percentage}%" . ($reason ? " ({$reason})" : "");
        } else {
            $discountAmount = min($this->total, max(0, $value));
            $this->discountMessage = "Diskon Manual: Rp " . number_format($discountAmount, 0, ',', '.') . ($reason ? " ({$reason})" : "");
        }

        $this->discount = $discountAmount;
        $this->discountApplied = true;
        // Reset Code Input to avoid confusion
        $this->discountCodeInput = '';

        $this->recalculateTotals();

        $this->dispatch('close-modal', id: 'manual-discount-modal');
        $this->dispatch('show-notification', message: 'Diskon manual berhasil diterapkan.', type: 'success');
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
        $this->tableNumber = $sale->table_number ?? '';
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
        $this->dispatch('show-notification', message: 'Transaksi berhasil dimuat.', type: 'success');
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
            $content .= "<div class='flex justify-between text-green-600'><span>Potongan:</span><span>- Rp" . number_format($sale->discount, 0, ',', '.') . "</span></div>";
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
                $this->dispatch('show-notification', message: '✅ Webhook connection successful!', type: 'success');
            } else {
                $this->dispatch('show-notification', message: '❌ Webhook connection failed: ' . $result['error'], type: 'error');
            }
        } catch (\Exception $e) {
            $this->dispatch('show-notification', message: '❌ Webhook test error: ' . $e->getMessage(), type: 'error');
        }
    }

    public function toggleWebhookPrinting()
    {
        $current = config('app.use_webhook_printing', false);
        $newValue = !$current;

        // Update config temporarily
        config(['app.use_webhook_printing' => $newValue]);

        $status = $newValue ? 'ENABLED' : 'DISABLED';
        $this->dispatch('show-notification', "🔄 Webhook printing {$status}", 'info');
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
        $this->tableNumber = '';
        $this->discountCodeInput = '';
        $this->discountMessage = '';
        $this->discountApplied = false;
        $this->editingNotesIndex = null;
        $this->itemNotes = '';

        // Reset Search & Pagination
        $this->searchQuery = '';
        $this->selectedCategory = 'SEMUA';
        // $this->resetPage(); // Disabled to prevent Alpine/dom-morph error

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
     * Open load modal untuk tombol Order
     */
    public function openLoadOrderModal()
    {
        $this->openLoadModal();
    }



    /**
     * Quick add product untuk mobile - IMPROVED
     */
    public function quickAddProduct($productId)
    {
        $this->addProduct($productId);

    }

    /**
     * Mobile-optimized remove item
     */
    public function mobileRemoveItem($index)
    {
        $itemName = $this->items[$index]['name'] ?? 'Item';
        $this->removeItem($index);

        $this->dispatch('show-notification', $itemName . ' dihapus dari keranjang', 'info');
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





    protected function getViewData(): array
    {
        return [
            'orderNumber' => $this->orderNumber,
            'categories' => $this->categories,
            'products' => $this->products,
        ];
    }
}

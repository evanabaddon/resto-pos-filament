<?php

namespace App\Filament\Pages;

use UnitEnum;
use BackedEnum;
use App\Models\Sale;
use App\Models\Product;
use App\Models\Category;
use App\Models\SaleItem;
use Filament\Pages\Page;
use App\Models\CashSession;
use App\Models\DiscountCode;
use App\Models\StockMovement;
use Filament\Notifications\Notification;
use Filament\Support\Facades\FilamentAsset;

class Pos extends Page
{
    protected string $view = 'filament.pages.pos';

    // Gunakan layout custom
    protected static string $layout = 'layouts.pos-layout';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    protected static string | UnitEnum | null $navigationGroup = 'Transaksi';

    protected static ?string $navigationLabel = 'POS';

    protected $listeners = [
        'closeCashSessionFromLayout' => 'closeCashSession',
        'cashInConfirmed' => 'handleCashInConfirmed',
        'cashInCancelled' => 'handleCashInCancelled',
        'saleLoaded' => 'handleSaleLoaded',
        'paymentRequested' => 'handlePaymentRequested',
        'paymentProcessed' => 'handlePaymentProcessed',
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
    public $selectedCategory = 'All';
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

    public function mount()
    {
        FilamentAsset::register([
            'pos-theme' => [
                'path' => resource_path('css/filament/admin/theme.css'),
                'type' => 'style',
            ],
        ]);
        $this->outOfStock = 0;
        $this->items = [];
        $this->total = 0;
        $this->tax = 0;
        $this->discount = 0;
        $this->finalTotal = 0;
        $this->generateOrderNumber();

        // Cek apakah user sudah punya sesi kas terbuka
        $session = CashSession::where('user_id', auth()->id())
                    ->where('status', 'open')
                    ->first();

        if ($session) {
            $this->showCashInModal = false;
            $this->cashSessionId = $session->id;
            session(['cash_session_id' => $session->id]);
        } else {
            // $this->showCashInModal = true;
            $this->dispatch('openCashInModal');
        }
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

        $this->dispatch('showNotification', 'Kas awal Rp ' . number_format($cashInHand, 0, ',', '.') . ' berhasil diset.', 'success');
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
    public function handlePaymentProcessed($saleId, $paymentMethod, $amountPaid)
    {
        $sale = Sale::findOrFail($saleId);

        $sale->update([
            'is_paid' => true,
            'payment_method' => $paymentMethod,
            'amount_paid' => $amountPaid,
            'paid_at' => now(),
            'status' => 'completed',
        ]);

        $this->dispatch('showNotification', 'Pembayaran berhasil diproses.', 'success');
        $this->showPaymentModal = false;
        $this->showLoadModal = false;

        $this->resetPos();
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

        $this->dispatch('showNotification', 'Kas awal Rp ' . number_format($this->cashInHand, 0, ',', '.') . ' berhasil diset.', 'success');
    }

    public function cancelCashIn()
    {
        // langsung redirect ke dashboard
        redirect()->route('filament.admin.pages.dashboard');
    }

    public function closeCashSession()
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

        // Hitung TOTAL penjualan CASH yang status COMPLETED
        $totalCashSales = $session->sales()
            ->where('status', 'completed')
            ->where('payment_method', 'cash')
            ->sum('final_total');

        $session->update([
            'cash_out' => $session->cash_in_hand + $totalCashSales, // ✅ BENAR
            'closed_at' => now(),
            'status' => 'closed',
        ]);

        session()->forget('cash_session_id');

        Notification::make()
            ->title('Shift Ditutup')
            ->body('Shift kasir telah ditutup. Total penjualan cash: Rp ' . number_format($totalCashSales, 0, ',', '.'))
            ->success()
            ->send();

        redirect()->route('filament.admin.pages.dashboard');
    }

    public function cancelSale(): void
    {
        $this->resetPos(); 
        $this->dispatch('showNotification', 'Transaksi dibatalkan.', 'info');
    }

    public function getNameUserLogin(): string
    {
        return auth()->user()->name;
    }

    public function setOrderType(string $args): void
    {
        $this->orderType = $args;
    }

    public function getCategoriesProperty()
    {
        // Ambil semua kategori dari DB + tambah "All"
        $categories = Category::pluck('name')->toArray();
        array_unshift($categories, 'All');
        return $categories;
    }

    public function getProductsProperty()
    {
        $query = Product::where('is_sellable', true)
            ->where(function($q) {
                $q->where('stock', '>', 0)
                  ->orWhere('type', 'produced')
                  ->orWhereNull('stock');
            });

        if ($this->selectedCategory !== 'All') {
            // Cari category_id berdasarkan nama kategori yang dipilih
            $category = Category::where('name', $this->selectedCategory)->first();
            if ($category) {
                $query->where('category_id', $category->id);
            }
        }

        return $query->get();
    }

    public function getImageUrlAttribute(): string
    {
        if (!$this->image) {
            return 'https://placehold.co/200x150?text=No+Image';
        }

        // Jika file tersimpan di storage/app/public
        return asset('storage/' . $this->image);
    }

    public function addProduct($productId)
    {
        $product = Product::find($productId);
        if (!$product) return;

        $foundKey = null;
        foreach ($this->items as $key => $item) {
            if ($item['product_id'] == $productId) {
                $foundKey = $key;
                break;
            }
        }

        if ($foundKey !== null) {
            $this->items[$foundKey]['quantity'] += 1;
            $this->items[$foundKey]['subtotal'] = $this->items[$foundKey]['price'] * $this->items[$foundKey]['quantity'];
        } else {
            $this->items[] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'price' => $product->sell_price,
                'quantity' => 1,
                'subtotal' => $product->sell_price,
            ];
        }

        $this->recalculateTotals();
    }

    public function updateQuantity($index, $quantity)
    {
        if ($quantity < 1) {
            unset($this->items[$index]);
            $this->items = array_values($this->items);
        } else {
            $this->items[$index]['quantity'] = $quantity;
            $this->items[$index]['subtotal'] = $this->items[$index]['price'] * $quantity;
        }
        
        $this->recalculateTotals();
    }

    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
        $this->recalculateTotals();
    }

    public function calculateTotals()
    {
        $this->subtotal = collect($this->items)->sum('subtotal');
        $this->tax = $this->subtotal * 0.10; // misal pajak 10%
        $this->finalTotal = max(0, $this->subtotal + $this->tax - $this->discount);
    }

    public function applyDiscountCode()
    {
        $code = trim($this->discountCodeInput);

        if ($code === '') {
            $this->discountMessage = 'Silakan masukkan kode diskon.';
            $this->discountApplied = false;
            return;
        }

        $discount = \App\Models\DiscountCode::where('code', $code)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('valid_from')
                ->orWhereDate('valid_from', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('valid_until')
                ->orWhereDate('valid_until', '>=', now());
            })
            ->first();

        if (! $discount) {
            $this->discountMessage = 'Kode diskon tidak valid atau sudah kedaluwarsa.';
            $this->discountApplied = false;
            $this->discount = 0;
            $this->calculateTotals();
            return;
        }

        if ($discount->min_purchase && $this->total < $discount->min_purchase) {
            $this->discountMessage = 'Transaksi belum memenuhi minimal pembelian Rp' . number_format($discount->min_purchase, 0, ',', '.');
            $this->discountApplied = false;
            $this->discount = 0;
            $this->calculateTotals();
            return;
        }

        // Hitung nilai diskon
        $discountValue = 0;
        if ($discount->type === 'percentage') {
            $discountValue = $this->total * ($discount->value / 100);
            if ($discount->max_discount && $discountValue > $discount->max_discount) {
                $discountValue = $discount->max_discount;
            }
        } else {
            $discountValue = $discount->value;
        }

        $this->discount = $discountValue;
        $this->discountApplied = true;
        $this->discountMessage = 'Kode diskon "' . $discount->code . '" berhasil diterapkan!';
        $this->calculateTotals();
    }

    public function saveSale()
    {
        // 🔹 Cek keranjang kosong
        if (empty($this->items)) {
            $this->dispatch('showNotification', 'Keranjang kosong!', 'error');
            return;
        }

        // 🔹 Cek nama pelanggan
        if (empty(trim($this->customerName))) {
            $this->dispatch('showNotification', 'Nama pelanggan harus diisi!', 'error');
            return;
        }

        try {
            \DB::beginTransaction();

            $subtotal = $this->total ?? 0;
            $tax      = $this->tax ?? 0;
            $discount = $this->discount ?? 0;
            $final    = $this->finalTotal ?? ($subtotal + $tax - $discount);

            // 🔹 LOGIKA YANG BENAR:
            // - Jika $this->saleId ADA → UPDATE sale yang diload
            // - Jika $this->saleId NULL → CREATE sale baru

            if ($this->saleId) {
                // 🔹 UPDATE: Sale yang diload dari modal
                $sale = Sale::findOrFail($this->saleId);

                $sale->update([
                    'customer_name' => $this->customerName ?? 'Umum',
                    'order_type'    => $this->orderType,
                    'subtotal'      => $subtotal,
                    'tax'           => $tax,
                    'discount'      => $discount,
                    'final_total'   => $final,
                    'total'         => $final,
                    'updated_at'    => now(),
                ]);

                // Hapus item lama agar bisa simpan item baru
                $sale->items()->delete();

            } else {
                // 🔹 CREATE: Transaksi baru dengan order number baru
                $sale = Sale::create([
                    'cash_session_id' => $this->cashSessionId ?? session('cash_session_id'),
                    'user_id'         => auth()->id(),
                    'invoice_number'  => $this->generateOrderNumber(),
                    'customer_name'   => $this->customerName ?? 'Umum',
                    'order_type'      => $this->orderType,
                    'subtotal'        => $subtotal,
                    'tax'             => $tax,
                    'discount'        => $discount,
                    'final_total'     => $final,
                    'total'           => $final,
                    'payment_method'  => '',
                    'status'          => 'draft',
                ]);

                // Set saleId untuk transaksi baru
                $this->saleId = $sale->id;
            }

            // 🔹 Simpan items (sama untuk kedua kasus)
            foreach ($this->items as $item) {
                $saleItem = SaleItem::create([
                    'sale_id'    => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity'   => $item['quantity'],
                    'unit_price' => $item['price'],
                    'subtotal'   => $item['subtotal'],
                ]);

                $product = Product::find($item['product_id']);

                if (!$product) continue;

                // 🔹 Kurangi stok (sama untuk kedua kasus)
                if ($product->recipes()->exists()) {
                    $recipes = $product->recipes()->with('ingredient')->get();

                    foreach ($recipes as $recipe) {
                        if (! $recipe->ingredient) continue;

                        $recipeRate     = max($recipe->unit->conversion_rate ?? 1, 0.0001);
                        $ingredientRate = max($recipe->ingredient->unit->conversion_rate ?? 1, 0.0001);

                        $conversion = $ingredientRate / $recipeRate;
                        $totalUsed = $recipe->quantity * $item['quantity'] * $conversion;

                        $recipe->ingredient->decrement('stock', $totalUsed);

                        StockMovement::create([
                            'product_id' => $recipe->ingredient->id,
                            'quantity'   => -$totalUsed,
                            'type'       => 'decrease',
                            'reason'     => 'POS Sale #' . $sale->invoice_number,
                            'notes'      => 'Bahan untuk produk ' . $product->name . ' dijual (' . auth()->user()->name . ')',
                        ]);
                    }

                } else {
                    $product->decrement('stock', $item['quantity']);

                    StockMovement::create([
                        'product_id' => $product->id,
                        'quantity'   => -$item['quantity'],
                        'type'       => 'decrease',
                        'reason'     => 'POS Sale #' . $sale->invoice_number,
                        'notes'      => 'Penjualan langsung produk oleh ' . auth()->user()->name,
                    ]);
                }
            }

            \DB::commit();

            // 🔹 TAMPILKAN NOTIFIKASI BERBEDA
            if ($this->saleId) {
                $this->dispatch('showNotification', 'Transaksi #' . $sale->invoice_number . ' berhasil diupdate!', 'success');
            } else {
                $this->dispatch('showNotification', 'Transaksi baru #' . $sale->invoice_number . ' berhasil disimpan!', 'success');
            }

            $this->resetPos();

            // 🔹 PENTING: JANGAN resetPos() setelah save!
            // Biarkan saleId tetap ada agar jika user tambah menu lagi, akan update sale yang sama
            // Hanya reset jika memang ingin mulai transaksi baru

        } catch (\Exception $e) {
            \DB::rollBack();
            $this->dispatch('showNotification', 'Gagal menyimpan penjualan: ' . $e->getMessage(), 'error');
        }
    }

    protected function recalculateTotals()
    {
        $this->total = collect($this->items)->sum(fn($i) => $i['subtotal']);
        $this->tax = $this->total * 0.10; // 10% tax
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
        $this->orderType = $sale->order_type ?? 'dine_in';
        $this->discount = $sale->discount ?? 0; // set discount dulu

        // Map ulang items
        $this->items = $sale->items->map(function ($item) {
            $product = $item->product;
            return [
                'product_id' => $item->product_id,
                'name'       => $product?->name ?? '(Produk dihapus)',
                'quantity'   => $item->quantity,
                'price'      => $item->unit_price,
                'subtotal'   => $item->subtotal,
            ];
        })->toArray();

        // Hitung total terbaru
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

        $this->dispatch('showNotification', 'Pembayaran berhasil diproses.', 'success');
        $this->showPaymentModal = false;
        $this->showLoadModal = false;
        $this->resetPos();
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
        $this->orderType = 'dine_in';
        $this->generateOrderNumber();
        $this->customerName = '';
        $this->discountCodeInput = '';
        $this->discountMessage = '';
        $this->discountApplied = false;
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

    protected function getViewData(): array
    {
        return [
            'orderNumber' => $this->orderNumber,
            'categories' => $this->categories,
            'products' => $this->products,
        ];
    }
}
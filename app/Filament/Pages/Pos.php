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
use Illuminate\Support\Facades\Log;
use App\Services\ReceiptPrintService;
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
        // 'printReceipt' => 'handlePrintReceipt',
        'printCompleted' => 'handlePrintCompleted',
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
    public $isPrinting = false;
    public $previousItems = [];

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

            $updateData = [
                'is_paid' => true,
                'payment_method_id' => $paymentMethodId, // Pastikan menggunakan payment_method_id
                'amount_paid' => $amountPaid,
                'paid_at' => now(),
                'status' => 'completed',
            ];

            // DEBUG: Cek data sebelum update
            // dd($updateData);

            $sale->update($updateData);

            // Auto print receipt setelah pembayaran berhasil
            $this->printReceipt($saleId);

            $this->dispatch('showNotification', 'Pembayaran berhasil diproses.', 'success');
            $this->showPaymentModal = false;
            $this->showLoadModal = false;

            $this->resetPos();

        } catch (\Exception $e) {
            // DEBUG: Tampilkan error
            // dd($e->getMessage());
            
            $this->dispatch('showNotification', 'Error: ' . $e->getMessage(), 'error');
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
            ->with(['recipes.ingredient', 'recipes.unit']) // Eager loading
            ->where(function($q) {
                $q->where('stock', '>', 0)
                ->orWhereIn('type', ['produced', 'bar']) // Tambahkan 'bar' di sini
                ->orWhereNull('stock');
            });

        if ($this->selectedCategory !== 'All') {
            $category = Category::where('name', $this->selectedCategory)->first();
            if ($category) {
                $query->where('category_id', $category->id);
            }
        }

        $products = $query->get();
        
        return $products->filter(function ($product) {
            // Untuk produk raw dan retail, cek stok biasa
            if (in_array($product->type, ['raw', 'retail'])) {
                return $product->stock > 0;
            }
            
            // Untuk produk produced dan bar, cek ketersediaan bahan
            if (in_array($product->type, ['produced', 'bar'])) {
                return $this->isProducedProductAvailable($product);
            }
            
            return true;
        });
    }
    
    /**
     * Cek apakah produk produced bisa dibuat - DENGAN KONVERSI UNIT
     */
    private function isProducedProductAvailable(Product $product): bool
    {
        // Log::info("=== CHECKING PRODUCT: {$product->name} ===");
        
        if (!$product->recipes || $product->recipes->isEmpty()) {
            // Log::info("❌ NO RECIPES for {$product->name}");
            return false;
        }

        foreach ($product->recipes as $recipe) {
            $ingredient = $recipe->ingredient;
            if (!$ingredient) {
                Log::info("❌ INGREDIENT NOT FOUND for recipe");
                return false;
            }

            // Log::info("Ingredient: {$ingredient->name}");
            // Log::info("Required: {$recipe->quantity} {$recipe->unit->name}");
            // Log::info("Stock: {$ingredient->stock} {$ingredient->unit->name}");

            // KONVERSI UNIT: Resep (Gram) → Bahan (Kilogram)
            $requiredInIngredientUnit = $this->convertQuantityToMaterialUnit(
                $recipe->quantity,
                $recipe->unit_id, 
                $ingredient->unit_id
            );

            // Log::info("After conversion - Required: {$requiredInIngredientUnit} {$ingredient->unit->name}");

            if ($ingredient->stock < $requiredInIngredientUnit) {
                // Log::info("❌ INSUFFICIENT STOCK for {$ingredient->name}");
                // Log::info("Need: {$requiredInIngredientUnit}, Has: {$ingredient->stock}");
                return false;
            }
        }

        // Log::info("✅ ALL INGREDIENTS AVAILABLE for {$product->name}");
        return true;
    }

    /**
     * Konversi quantity dari unit resep ke unit bahan baku - VERSI DINAMIS
     */
    private function convertQuantityToMaterialUnit($quantity, $fromUnitId, $toUnitId): float
    {
        // Jika unit sama, tidak perlu konversi
        if ($fromUnitId == $toUnitId) {
            return $quantity;
        }
        
        $fromUnit = Unit::find($fromUnitId);
        $toUnit = Unit::find($toUnitId);
        
        if (!$fromUnit || !$toUnit) {
            // \Log::info("UNIT NOT FOUND - From: {$fromUnitId}, To: {$toUnitId}");
            return $quantity;
        }
        
        // \Log::info("Converting from {$fromUnit->name} to {$toUnit->name}");
        
        // Cari base unit chain untuk kedua unit
        $fromBaseUnit = $this->getBaseUnit($fromUnit);
        $toBaseUnit = $this->getBaseUnit($toUnit);
        
        // \Log::info("From base unit: {$fromBaseUnit->name}, To base unit: {$toBaseUnit->name}");
        
        // Jika base unit sama, bisa konversi
        if ($fromBaseUnit->id == $toBaseUnit->id) {
            // Step 1: Konversi dari unit resep ke base unit
            $quantityInBaseUnit = $this->convertToBaseUnit($quantity, $fromUnit);
            // \Log::info("To base unit: {$quantity} {$fromUnit->name} = {$quantityInBaseUnit} {$fromBaseUnit->name}");
            
            // Step 2: Konversi dari base unit ke unit bahan baku
            $quantityInMaterialUnit = $this->convertFromBaseUnit($quantityInBaseUnit, $toUnit);
            // \Log::info("From base unit: {$quantityInBaseUnit} {$fromBaseUnit->name} = {$quantityInMaterialUnit} {$toUnit->name}");
            
            return $quantityInMaterialUnit;
        }
        
        // Jika base unit berbeda, tidak bisa konversi
        // \Log::info("DIFFERENT BASE UNITS - Cannot convert {$fromBaseUnit->name} to {$toBaseUnit->name}");
        return $quantity;
    }

    /**
     * Cari base unit dari suatu unit (recursive)
     */
    private function getBaseUnit(Unit $unit): Unit
    {
        // Jika ini sudah base unit (tidak punya parent)
        if (!$unit->base_unit_id) {
            return $unit;
        }
        
        // Cari parent unit
        $parentUnit = Unit::find($unit->base_unit_id);
        if (!$parentUnit) {
            return $unit;
        }
        
        // Rekursif cari sampai top
        return $this->getBaseUnit($parentUnit);
    }

    /**
     * Konversi quantity ke base unit
     */
    private function convertToBaseUnit($quantity, Unit $unit): float
    {
        // Jika ini sudah base unit
        if (!$unit->base_unit_id) {
            return $quantity;
        }
        
        // Konversi ke parent unit: quantity / conversion_rate
        // Karena conversion_rate = "1 base = x unit ini"
        // Maka: quantity (base) = quantity (unit ini) / conversion_rate
        $converted = $quantity / $unit->conversion_rate;
        
        // Jika parent unit bukan base unit, konversi recursive
        $parentUnit = Unit::find($unit->base_unit_id);
        if ($parentUnit->base_unit_id) {
            return $this->convertToBaseUnit($converted, $parentUnit);
        }
        
        return $converted;
    }

    /**
     * Konversi quantity dari base unit ke unit target
     */
    private function convertFromBaseUnit($quantity, Unit $unit): float
    {
        // Jika ini sudah base unit
        if (!$unit->base_unit_id) {
            return $quantity;
        }
        
        // Konversi dari parent unit ke unit ini: quantity × conversion_rate
        // Karena conversion_rate = "1 base = x unit ini"  
        // Maka: quantity (unit ini) = quantity (base) × conversion_rate
        $parentUnit = Unit::find($unit->base_unit_id);
        
        // Konversi recursive dulu ke parent unit
        $quantityInParentUnit = $this->convertFromBaseUnit($quantity, $parentUnit);
        
        // Kemudian konversi ke unit ini
        return $quantityInParentUnit * $unit->conversion_rate;
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

    // public function saveSale()
    // {
    //     // 🔹 Cek keranjang kosong
    //     if (empty($this->items)) {
    //         $this->dispatch('showNotification', 'Keranjang kosong!', 'error');
    //         return;
    //     }

    //     // 🔹 Cek nama pelanggan
    //     if (empty(trim($this->customerName))) {
    //         $this->dispatch('showNotification', 'Nama pelanggan harus diisi!', 'error');
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
    //             $this->dispatch('showNotification', 'Transaksi #' . $sale->invoice_number . ' berhasil diupdate!', 'success');
    //         } else {
    //             $this->dispatch('showNotification', 'Transaksi baru #' . $sale->invoice_number . ' berhasil disimpan!', 'success');
    //         }

    //         $this->resetPos();

    //         // 🔹 PENTING: JANGAN resetPos() setelah save!
    //         // Biarkan saleId tetap ada agar jika user tambah menu lagi, akan update sale yang sama
    //         // Hanya reset jika memang ingin mulai transaksi baru

    //     } catch (\Exception $e) {
    //         \DB::rollBack();
    //         $this->dispatch('showNotification', 'Gagal menyimpan penjualan: ' . $e->getMessage(), 'error');
    //     }
    // }

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

            // 🔹 TENTUKAN APAKAH INI UPDATE
            $isUpdate = !is_null($this->saleId);
            
            if ($isUpdate) {
                // 🔹 DAPATKAN ITEM BARU/TAMBAHAN
                $newItems = $this->getNewOrUpdatedItems();
                
                \Log::info('🔄 Update order detected', [
                    'sale_id' => $sale->id,
                    'previous_items_count' => count($this->previousItems),
                    'current_items_count' => count($this->items),
                    'new_items_count' => count($newItems)
                ]);

                // 🔹 PRINT HANYA ITEM BARU JIKA ADA
                if (!empty($newItems)) {
                    try {
                        $orderPrintService = new \App\Services\OrderPrintService();
                        $printResult = $orderPrintService->printNewItemsOnly($sale, $newItems);
                        
                        $this->dispatch('showNotification', '✅ Item berhasil ditambah & tambahan order dicetak!', 'success');
                        
                    } catch (\Exception $e) {
                        $this->dispatch('showNotification', 
                            '⚠️ Order tersimpan tapi gagal print tambahan: ' . $e->getMessage(), 
                            'warning'
                        );
                    }
                } else {
                    $this->dispatch('showNotification', '✅ Order berhasil diupdate!', 'success');
                }
            } else {
                // 🔹 NEW ORDER - PRINT SEMUA
                try {
                    $orderPrintService = new \App\Services\OrderPrintService();
                    $printResult = $orderPrintService->printOrderByProductType($sale);
                    $this->dispatch('showNotification', '✅ Order baru berhasil dikirim ke divisi!', 'success');
                    
                } catch (\Exception $e) {
                    $this->dispatch('showNotification', 
                        '⚠️ Order tersimpan tapi gagal print: ' . $e->getMessage(), 
                        'warning'
                    );
                }
            }

            // 🔹 RESET previousItems
            $this->previousItems = [];

            // 🔹 TAMPILKAN NOTIFIKASI BERBEDA
            if ($this->saleId) {
                $this->dispatch('showNotification', 'Transaksi #' . $sale->invoice_number . ' berhasil diupdate!', 'success');
            } else {
                $this->dispatch('showNotification', 'Transaksi baru #' . $sale->invoice_number . ' berhasil disimpan!', 'success');
            }

            $this->resetPos();

        } catch (\Exception $e) {
            \DB::rollBack();
            
            // 🔹 DEBUG: Log error utama
            \Log::error('💥 Gagal menyimpan penjualan: ' . $e->getMessage(), [
                'customer_name' => $this->customerName,
                'items_count' => count($this->items),
                'error_trace' => $e->getTraceAsString()
            ]);
            
            $this->dispatch('showNotification', 'Gagal menyimpan penjualan: ' . $e->getMessage(), 'error');
        }
    }

    /**
     * Dapatkan item baru atau yang quantity-nya berubah
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
                    
                    // Jika quantity berubah, hitung selisihnya
                    $quantityDiff = $currentItem['quantity'] - $previousItem['quantity'];
                    if ($quantityDiff > 0) {
                        $newItems[] = [
                            'product_id' => $currentItem['product_id'],
                            'name' => $currentItem['name'],
                            'quantity' => $quantityDiff, // Hanya selisihnya
                            'price' => $currentItem['price'],
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
                $this->dispatch('showNotification', 'Sale ID tidak valid untuk print.', 'error');
                $this->isPrinting = false;
                return;
            }

            logger('Print Receipt - Searching Sale:', ['saleId' => $saleId]);
            
            $sale = Sale::with(['items.product', 'user', 'paymentMethod'])->find($saleId);
            
            if (!$sale) {
                logger('Sale not found:', ['saleId' => $saleId]);
                $this->dispatch('showNotification', 'Transaksi tidak ditemukan untuk dicetak.', 'error');
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
    
    // public function printReceipt($saleId)
    // {
    //     // 🔹 CEK APAKAH SEDANG PRINT
    //     if ($this->isPrinting) {
    //         Log::warning('Print receipt skipped - already printing', ['saleId' => $saleId]);
    //         return;
    //     }

    //     try {
    //         $this->isPrinting = true; // SET FLAG
            
    //         // Validasi saleId
    //         if (!$saleId) {
    //             $this->dispatch('showNotification', 'Sale ID tidak valid untuk print.', 'error');
    //             $this->isPrinting = false;
    //             return;
    //         }

    //         logger('Print Receipt - Searching Sale:', ['saleId' => $saleId]);
            
    //         $sale = Sale::with(['items.product', 'user', 'paymentMethod'])->find($saleId);
            
    //         if (!$sale) {
    //             logger('Sale not found:', ['saleId' => $saleId]);
    //             $this->dispatch('showNotification', 'Transaksi tidak ditemukan untuk dicetak.', 'error');
    //             $this->isPrinting = false;
    //             return;
    //         }

    //         logger('Sale found, proceeding to print:', ['invoice' => $sale->invoice_number]);
            
    //         $printService = new ReceiptPrintService($sale);
    //         $printService->printReceipt();
            
    //         $this->dispatch('showNotification', 'Struk berhasil dicetak!', 'success');
            
    //         // Kirim event ke PosPaymentModal bahwa print sudah selesai
    //         $this->dispatch('printCompleted');
            
    //     } catch (\Exception $e) {
    //         Log::error('Print receipt failed: ' . $e->getMessage());
    //         $this->dispatch('showNotification', 'Gagal mencetak struk: ' . $e->getMessage(), 'error');
    //         $this->dispatch('printFailed');
    //     } finally {
    //         // 🔹 RESET FLAG di finally block
    //         $this->isPrinting = false;
    //     }
    // }

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
        $this->orderType = $sale->order_type ?? 'Dine In';
        $this->discount = $sale->discount ?? 0;

        // 🔹 SIMPAN ITEMS SEBELUMNYA untuk tracking
        $this->previousItems = $sale->items->map(function ($item) {
            return [
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
            ];
        })->toArray();

        // Map ulang items untuk tampilan
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
        
        $this->dispatch('showNotification', 'Pembayaran berhasil diproses.', 'success');
        $this->resetPos();
    }

    // Handler untuk print receipt
    public function handlePrintReceipt($saleId)
    {
        logger('Handle Print Receipt - Sale ID:', ['saleId' => $saleId]);
        $this->printReceipt($saleId);
    }

    // Method untuk print receipt
    // public function printReceipt($saleId)
    // {
    //     try {
    //         // Validasi saleId
    //         if (!$saleId) {
    //             $this->dispatch('showNotification', 'Sale ID tidak valid untuk print.', 'error');
    //             return;
    //         }

    //         logger('Print Receipt - Searching Sale:', ['saleId' => $saleId]);
            
    //         $sale = Sale::with(['items.product', 'user', 'paymentMethod'])->find($saleId);
            
    //         if (!$sale) {
    //             logger('Sale not found:', ['saleId' => $saleId]);
    //             $this->dispatch('showNotification', 'Transaksi tidak ditemukan untuk dicetak.', 'error');
    //             return;
    //         }

    //         logger('Sale found, proceeding to print:', ['invoice' => $sale->invoice_number]);
            
    //         $printService = new ReceiptPrintService($sale);
    //         $printService->printReceipt();
            
    //         $this->dispatch('showNotification', 'Struk berhasil dicetak!', 'success');
            
    //         // Kirim event ke PosPaymentModal bahwa print sudah selesai
    //         $this->dispatch('printCompleted');
            
    //     } catch (\Exception $e) {
    //         Log::error('Print receipt failed: ' . $e->getMessage());
    //         $this->dispatch('showNotification', 'Gagal mencetak struk: ' . $e->getMessage(), 'error');
            
    //         // Kirim event error ke PosPaymentModal
    //         $this->dispatch('printFailed');
    //     }
    // }

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
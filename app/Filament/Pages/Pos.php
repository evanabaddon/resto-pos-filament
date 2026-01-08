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
use Livewire\Attributes\Locked;
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

    // RBAC: super_admin, admin, cashier, waiter
    public static function canAccess(): bool
    {
        return auth()->user()->role === \App\Enums\UserRole::SuperAdmin || auth()->user()->role === \App\Enums\UserRole::Admin || auth()->user()->role === \App\Enums\UserRole::Cashier || auth()->user()->role === \App\Enums\UserRole::Waiter;
    }

    protected $listeners = [
        'refreshCart' => '$refresh',
        'scanProduct' => 'scanProduct',
        'update-cart-totals' => 'updateCartTotals',
        'checkout-success' => 'handleCheckoutSuccess',
        'refresh-page' => '$refresh',
        'memberCreated' => 'selectMember', // Re-use selectMember logic
        'closeCashSessionFromLayout' => 'closeCashSession',
        'cashInConfirmed' => 'handleCashInConfirmed',
        'cashInCancelled' => 'handleCashInCancelled',
        'cashOutConfirmed' => 'handleCashOutConfirmed',
        'cashOutCancelled' => 'handleCashOutCancelled',
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

    /**
     * Override handlePaymentRequested to block Waiter
     */
    public function handlePaymentRequested($saleId)
    {
        if (auth()->user()->role === \App\Enums\UserRole::Waiter) {
            $this->dispatch('show-notification', message: 'Akses Ditolak: Waiter tidak dapat memproses pembayaran (hanya input order).', type: 'error');
            return;
        }

        $this->openPaymentModal($saleId);
    }

    public $showCashInModal = true;
    public $cashInHand = 0;

    #[Locked]
    public $cashSessionId = null;

    #[Locked]
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
    // savedSales removed (unused)
    public $showPaymentModal = false;
    public $payment_method = 'cash';
    public $finalTotal = 0;
    public $amount_paid = 0;
    // outOfStock removed (unused)
    public $isPrinting = false;

    #[Locked]
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

    // Member Integration properties
    public $memberId = null;
    public $selectedMember = null;
    public $memberSearchQuery = '';
    public $foundMembers = [];
    public $showRewardModal = false;
    public $pointRedemptionAmount = 0;

    // Cache Control
    public $cacheVersion = 1;

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

            // 🔹 Handle Deep Link from Notification (Load Sale ID)
            if (request()->has('sale_id')) {
                $saleId = request()->query('sale_id');
                // Ensure we call this AFTER setting cash session, as loading sale might depend on it
                $this->loadSale($saleId);
            }
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

        // Clear product cache when search changes
        $this->clearProductCache();

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

        // Clear product cache when category changes
        $this->clearProductCache();
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
        // Cache categories for 10 minutes (rarely change)
        return \Illuminate\Support\Facades\Cache::remember('pos_categories', 600, function () {
            return Category::select('id', 'name')->orderBy('name')->get();
        });
    }

    public function getProductsProperty()
    {
        // Cache products for 5 minutes to improve performance
        // Get current page from Livewire pagination (default to 1 if not set)
        $currentPage = $this->paginators['page'] ?? 1;

        // Include perPage in cache key to handle different screen sizes
        $cacheKey = 'pos_products_' .
            $this->selectedCategory . '_' .
            md5($this->searchQuery) . '_' .
            $currentPage . '_' .
            $this->perPage . '_' .
            $this->cacheVersion; // Add cacheVersion to force refresh

        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 300, function () {
            // MEMORY OPTIMIZATION: Select specific columns only
            $query = Product::select([
                'id',
                'name',
                'sell_price',
                'stock',
                'type',
                'category_id',
                'image',
                'is_sellable',
                'unit_id'
            ])
                ->where('is_sellable', true)
                ->where('name', '!=', 'Down Payment (DP)')
                ->with([
                    // Optimize eager loading: select specific columns for relations too
                    'recipes:id,product_id,ingredient_id,quantity,unit_id',
                    'recipes.ingredient:id,name,stock,unit_id',
                    'recipes.ingredient.unit:id,symbol,name',
                    'recipes.unit:id,symbol,name',
                    'unit:id,symbol,name'
                ])
                ->where(function ($q) {
                    $q->where('stock', '>', 0)
                        ->orWhereIn('type', ['produced', 'bar'])
                        ->orWhereNull('stock');
                });

            // 🔍 Filter Search - Use full wildcard search for better UX
            if (!empty($this->searchQuery)) {
                $query->where('name', 'like', '%' . $this->searchQuery . '%');
            }

            // Filter Kategori
            if ($this->selectedCategory !== 'SEMUA') {
                $query->where('category_id', $this->selectedCategory);
            }

            // Use DB Pagination (Optimized)
            return $query->orderBy('name', 'asc')->paginate($this->perPage);
        });
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

        // 2. Produced / Bar items - check remaining portions after cart
        if (in_array($product->type, ['produced', 'bar'])) {
            $maxPortions = $product->max_portions;

            // Calculate quantity already in cart
            $qtyInCart = 0;
            foreach ($this->items as $item) {
                if ($item['product_id'] == $product->id) {
                    $qtyInCart += $item['quantity'];
                }
            }

            // Remaining portions = max - already in cart
            $remainingPortions = $maxPortions - $qtyInCart;

            return $remainingPortions > 0;
        }

        return true;
    }

    /**
     * Get products with pre-calculated availability (OPTIMIZED)
     * This reduces N+1 queries to just 2-3 queries total
     */
    public function getProductsWithAvailabilityProperty()
    {
        $products = $this->products;

        if ($products->isEmpty()) {
            return collect([]);
        }

        // Cache availability check for 2 minutes
        $productIds = $products->pluck('id')->toArray();
        $cacheKey = 'pos_availability_' . md5(json_encode($productIds) . json_encode($this->items));

        $availability = \Illuminate\Support\Facades\Cache::remember($cacheKey, 120, function () use ($productIds) {
            $stockChecker = app(\App\Services\RecipeStockChecker::class);

            // Batch calculate untuk semua produk sekaligus (2-3 queries total)
            // Pass saleId to exclude current sale from draft calculation (prevents double counting)
            return $stockChecker->batchCheckAvailability(
                $productIds,
                $this->items,
                $this->saleId // Exclude current sale from draft count
            );
        });

        // Map ke products (in-memory operation, no queries)
        return $products->map(function ($product) use ($availability) {
            $avail = $availability[$product->id] ?? [
                'available' => false,
                'max_portions' => 0,
                'remaining' => 0,
                'limiting_ingredient' => null
            ];

            $product->is_available = $avail['available'];
            $product->max_portions_display = $avail['max_portions'];
            $product->remaining_portions = $avail['remaining'];
            $product->limiting_ingredient = $avail['limiting_ingredient'];

            return $product;
        });
    }

    /**
     * Get cart quantities pre-calculated (in-memory)
     */
    public function getCartQuantitiesProperty()
    {
        $quantities = [];
        foreach ($this->items as $item) {
            $productId = $item['product_id'];
            $quantities[$productId] = ($quantities[$productId] ?? 0) + $item['quantity'];
        }
        return $quantities;
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
     * Clear product cache when filters change
     */
    protected function clearProductCache(): void
    {
        // Clear all product cache keys for current user
        // Since we can't easily iterate cache keys, we'll rely on cache expiration
        // For immediate effect, you can use cache tags (Redis) or clear all cache

        // Option 1: Clear specific pattern (if using Redis with tags)
        // Cache::tags(['pos_products'])->flush();

        // Option 2: Just let cache expire naturally (5 minutes)
        // The new search/category will create new cache key anyway
    }

    /**
     * Manual Cache Refresh Trigger
     */
    public function refreshProducts()
    {
        $this->cacheVersion++;
        $this->resetPage(); // Reset pagination to page 1

        $this->dispatch('show-notification', message: 'Data produk berhasil diperbarui!', type: 'success');
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



    public function getEditingItemProperty()
    {
        if ($this->editingNotesIndex !== null && isset($this->items[$this->editingNotesIndex])) {
            return $this->items[$this->editingNotesIndex];
        }
        return null;
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

    // --- Member Integration Methods ---

    /**
     * Handle updating member search query
     */
    public function updatedMemberSearchQuery($value)
    {
        if (strlen($value) < 2) {
            $this->foundMembers = [];
            return;
        }

        // OPTIMIZATION: Use prefix search for better index usage (already optimized)
        $this->foundMembers = \App\Models\Member::select('id', 'name', 'phone', 'email', 'points_balance', 'tier_id')
            ->where(function ($q) use ($value) {
                $q->where('name', 'like', "{$value}%")
                    ->orWhere('phone', 'like', "{$value}%")
                    ->orWhere('email', 'like', "{$value}%");
            })
            ->with('tier:id,name')
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * Select a member from search results
     */
    public function selectMember($memberId)
    {
        $member = \App\Models\Member::with('tier')->find($memberId);

        if ($member) {
            $this->memberId = $member->id;
            $this->selectedMember = $member;

            // Auto-fill customer name if empty or generic
            if (empty(trim($this->customerName)) || $this->customerName === 'Umum') {
                $this->customerName = $member->name;
            }

            $this->memberSearchQuery = '';
            $this->foundMembers = [];

            $this->dispatch('show-notification', message: "Member terpilih: {$member->name}", type: 'success');
        }
    }

    /**
     * Remove selected member
     */
    public function removeMember()
    {
        $this->memberId = null;
        $this->selectedMember = null;
        $this->customerName = ''; // Optional reset
        $this->dispatch('show-notification', message: 'Member dihapus dari transaksi.', type: 'info');
    }



    public function getAvailableRewardsProperty()
    {
        return \App\Models\LoyaltyReward::where('is_active', true)
            ->with('product')
            ->get();
    }

    public function openRewardModal()
    {
        if (!$this->selectedMember) {
            $this->dispatch('show-notification', message: 'Pilih member terlebih dahulu!', type: 'error');
            return;
        }
        $this->showRewardModal = true;
    }

    public function redeemReward($rewardId)
    {
        if (!$this->selectedMember)
            return;

        $reward = \App\Models\LoyaltyReward::with('product')->find($rewardId);

        if (!$reward || !$reward->product) {
            $this->dispatch('show-notification', message: 'Reward tidak valid.', type: 'error');
            return;
        }

        if ($this->selectedMember->points_balance < $reward->points_required) {
            $this->dispatch('show-notification', message: 'Poin tidak mencukupi!', type: 'error');
            return;
        }

        // Add to cart as free item
        $this->addItemToCart($reward->product, 1, 0, "🎁 Reward: " . $reward->name);

        $this->showRewardModal = false;
        $this->dispatch('show-notification', message: 'Reward berhasil ditambahkan ke keranjang!', type: 'success');
    }

    public function updatedPointRedemptionAmount()
    {
        if ($this->selectedMember && $this->pointRedemptionAmount > $this->selectedMember->points_balance) {
            $this->dispatch('show-notification', message: 'Jumlah poin melebihi saldo member!', type: 'error');
        }
    }

    public function redeemPointsForDiscount()
    {
        if (!$this->selectedMember)
            return;

        $pointsToRedeem = (int) $this->pointRedemptionAmount;

        if ($pointsToRedeem <= 0) {
            $this->dispatch('show-notification', message: 'Jumlah poin harus lebih dari 0.', type: 'error');
            return;
        }

        // 1. Calculate points already used in cart
        $existingPointsUsed = 0;
        $existingDiscountIndex = null;

        foreach ($this->items as $index => $item) {
            if (($item['price'] < 0) && str_contains($item['notes'] ?? '', 'Redeemed:')) {
                if (preg_match('/Redeemed: (\d+) Pts/', $item['notes'], $matches)) {
                    $existingPointsUsed += (int) $matches[1];
                    $existingDiscountIndex = $index; // Track the last discount item to merge
                }
            }
        }

        // 2. Validate total against balance
        $totalPointsNeeded = $existingPointsUsed + $pointsToRedeem;
        if ($this->selectedMember->points_balance < $totalPointsNeeded) {
            $this->dispatch(
                'show-notification',
                message: "Poin tidak cukup! Sisa: " . ($this->selectedMember->points_balance - $existingPointsUsed),
                type: 'error'
            );
            return;
        }

        $settings = app(\App\Settings\GeneralSettings::class);
        $pointValue = $settings->loyalty_point_value ?? 1;

        // 3. Merge or Add
        if ($existingDiscountIndex !== null) {
            // Update existing item
            $newTotalPoints = $existingPointsUsed + $pointsToRedeem;
            $newTotalDiscount = $newTotalPoints * $pointValue;

            $this->items[$existingDiscountIndex]['name'] = '✨ Diskon Poin (' . number_format($newTotalPoints) . ' Pts)';
            $this->items[$existingDiscountIndex]['price'] = -$newTotalDiscount;
            $this->items[$existingDiscountIndex]['subtotal'] = -$newTotalDiscount;
            $this->items[$existingDiscountIndex]['notes'] = 'Redeemed: ' . $newTotalPoints . ' Pts';
        } else {
            // Add new item
            $discountAmount = $pointsToRedeem * $pointValue;
            $this->items[] = [
                'product_id' => null,
                'name' => '✨ Diskon Poin (' . number_format($pointsToRedeem) . ' Pts)',
                'quantity' => 1,
                'price' => -$discountAmount,
                'subtotal' => -$discountAmount,
                'notes' => 'Redeemed: ' . $pointsToRedeem . ' Pts',
            ];
        }

        $this->recalculateTotals();
        $this->showRewardModal = false;
        $this->pointRedemptionAmount = 0;
        $this->dispatch('show-notification', message: "Diskon berhasil ditambahkan!", type: 'success');
    }

    protected function addItemToCart($product, $qty, $price, $note = '')
    {
        // Check if item exists (with same note/price)
        $existingIndex = null;
        foreach ($this->items as $index => $item) {
            if ($item['product_id'] == $product->id && ($item['notes'] ?? '') === $note && $item['price'] == $price) {
                $existingIndex = $index;
                break;
            }
        }

        if ($existingIndex !== null) {
            $this->items[$existingIndex]['quantity'] += $qty;
            $this->items[$existingIndex]['subtotal'] = $this->items[$existingIndex]['quantity'] * $price;
        } else {
            $this->items[] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'quantity' => $qty,
                'price' => $price,
                'subtotal' => $qty * $price,
                'notes' => $note,
            ];
        }
        $this->recalculateTotals();
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
            $productId = $this->items[$index]['product_id'];
            $product = Product::find($productId);

            if ($product && in_array($product->type, ['produced', 'bar'])) {
                // Check if we can add one more
                $newQuantity = $this->items[$index]['quantity'] + 1;
                $stockChecker = app(\App\Services\RecipeStockChecker::class);
                $availability = $stockChecker->checkAvailability($product, $newQuantity);

                if (!$availability['available']) {
                    $this->dispatch(
                        'show-notification',
                        message: "⚠️ Hanya tersedia {$availability['max_portions']} porsi {$product->name}.",
                        type: 'warning'
                    );
                    return;
                }
            }

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

        $settings = app(\App\Settings\GeneralSettings::class);
        $taxRate = $settings->enable_tax ? ($settings->tax_percentage / 100) : 0;
        $this->tax = $this->total * $taxRate;

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
        // Dispatches event to open load modal (handled by other component or simple view toggle)
        $this->dispatch('openLoadModal');
    }

    public function loadSale($saleId)
    {
        $sale = Sale::with(['items.product', 'member'])->findOrFail($saleId);

        $this->saleId = $sale->id;
        $this->orderNumber = $sale->invoice_number;
        $this->customerName = $sale->customer_name ?? '';
        $this->tableNumber = $sale->table_number ?? '';
        $this->orderType = $sale->order_type ?? 'Dine In';
        $this->orderType = $sale->order_type ?? 'Dine In';
        $this->discount = $sale->discount ?? 0;

        // Load Member
        if ($sale->member_id) {
            $this->memberId = $sale->member_id;
            $this->selectedMember = $sale->member; // Relasi sudah ada di Sale model
        } else {
            $this->memberId = null;
            $this->selectedMember = null;
        }

        // 🔹 GROUP ITEMS BY PRODUCT & NOTES (Merging DB splits for POS UI)
        $groupedItems = $sale->items->groupBy(function ($item) {
            return $item->product_id . '-' . ($item->notes ?? '');
        });

        // 🔹 SIMPAN ITEMS SEBELUMNYA untuk tracking (Merged)
        $this->previousItems = $groupedItems->map(function ($group) {
            $first = $group->first();
            return [
                'product_id' => $first->product_id,
                'quantity' => $group->sum('quantity'),
                'notes' => $first->notes ?? ''
            ];
        })->values()->toArray();

        // Map ulang items untuk tampilan (Merged)
        $this->items = $groupedItems->map(function ($group) {
            $first = $group->first();
            $product = $first->product;
            $qty = $group->sum('quantity');
            $price = $first->unit_price;

            return [
                'product_id' => $first->product_id,
                'name' => $product?->name ?? $first->product_name ?? '(Produk dihapus)',
                'quantity' => $qty,
                'price' => $price,
                'subtotal' => $price * $qty,
                'notes' => $first->notes ?? '',
            ];
        })->values()->toArray();

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
        $this->editingNotesIndex = null;
        $this->itemNotes = '';

        // Reset Member
        $this->memberId = null;
        $this->selectedMember = null;
        $this->memberSearchQuery = '';
        $this->foundMembers = [];

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

    public function setCategory($categoryId)
    {
        $this->selectedCategory = $categoryId;
        $this->resetPage(); // Critical for performance/correctness
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
            'productsWithAvailability' => $this->productsWithAvailability,
            'cartQuantities' => $this->cartQuantities,
        ];
    }
}

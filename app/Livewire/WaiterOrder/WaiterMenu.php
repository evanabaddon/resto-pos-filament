<?php

namespace App\Livewire\WaiterOrder;

use App\Models\Category;
use App\Models\Product;
use Livewire\Component;

class WaiterMenu extends Component
{
    public $cart = [];
    public $categories = [];
    public $selectedCategoryId = 'all';
    public $search = '';
    public $perPage = 12; // Initial load count

    // 🚀 OPTIMIZATION: Cache key versioning for instant invalidation
    public $stockVersion;

    // 🚀 OPTIMIZATION: Per-request caching (safe - no stale data)
    protected $productCache = [];
    protected $availabilityCache = [];

    public function mount()
    {
        $this->categories = Category::orderBy('name')->get();
        // Load initial state from Cache
        $userId = auth()->id();
        $cacheKey = 'waiter_cart_' . $userId;
        $this->cart = \Illuminate\Support\Facades\Cache::get($cacheKey, []);

        // Load current stock version
        $this->stockVersion = \Illuminate\Support\Facades\Cache::get('global_stock_version', time());
    }

    #[\Livewire\Attributes\On('echo:products,ProductStockUpdated')]
    public function handleStockUpdate($event)
    {
        // Update local version to trigger cache refresh
        $this->stockVersion = time();
        \Illuminate\Support\Facades\Cache::put('global_stock_version', $this->stockVersion, now()->addDays(1));

        // Also clear availability cache since it's specific to products
        $this->availabilityCache = [];
    }

    public function loadMore()
    {
        $this->perPage += 12;
    }

    public function addToCartBatch($productId, $quantity = 1)
    {
        $userId = auth()->id();
        $cacheKey = 'waiter_cart_' . $userId;

        // Always reload from cache first to ensure sync
        $this->cart = \Illuminate\Support\Facades\Cache::get($cacheKey, []);

        // 🚀 OPTIMIZATION: Use cached product (per-request only)
        $product = $this->getProduct($productId);
        if (!$product) {
            $this->dispatch('notify', message: 'Produk tidak ditemukan', type: 'error');
            return;
        }

        // Calculate Target Qty (Current + Added)
        $currentCartQty = isset($this->cart[$productId]) ? $this->cart[$productId]['qty'] : 0;
        $requestedQty = $currentCartQty + $quantity;

        // Check stock availability
        if (in_array($product->type, ['produced', 'bar'])) {
            // Pass the CURRENT CART (excluding self)
            $cartForCheck = $this->cart;
            if (isset($cartForCheck[$productId])) {
                unset($cartForCheck[$productId]);
            }

            // 🚀 OPTIMIZATION: Use memoized availability check (per-request only)
            // Note: Availability check always hits DB/Service, which is good for accuracy at "Add" time
            $availability = $this->checkStockAvailability($product, $requestedQty, $cartForCheck);

            if (!$availability['available']) {
                $maxPortions = $availability['max_portions'];
                $this->dispatch('notify', message: "⚠️ Stok tidak cukup. Maksimal: {$maxPortions}", type: 'warning');
                return;
            }
        }
        // Retail/Raw check
        else if ($product->stock !== null && $product->stock < $requestedQty) {
            $this->dispatch('notify', message: "⚠️ Stok tidak cukup. Sisa: {$product->stock}", type: 'warning');
            return;
        }

        // Update Cart
        if (isset($this->cart[$productId])) {
            $this->cart[$productId]['qty'] = $requestedQty;
        } else {
            $this->cart[$productId] = [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->sell_price,
                'image' => $product->image,
                'qty' => $requestedQty, // Set directly
                'note' => ''
            ];
        }

        \Illuminate\Support\Facades\Cache::put($cacheKey, $this->cart, now()->addHours(12));

        $this->dispatch('cart-updated');
    }

    // Keep original single add for fallback or other calls
    public function addToCart($productId)
    {
        $this->addToCartBatch($productId, 1);
    }

    // 🚀 OPTIMIZATION: Product caching (per-request only - safe)
    protected function getProduct($productId)
    {
        if (isset($this->productCache[$productId])) {
            return $this->productCache[$productId];
        }

        // ALWAYS fresh query - includes current stock
        $product = Product::with(['recipes.ingredient', 'recipes.unit'])->find($productId);
        $this->productCache[$productId] = $product;

        return $product;
    }

    // 🚀 OPTIMIZATION: Availability memoization (per-request only - safe)
    protected function checkStockAvailability($product, $qty, $cart)
    {
        $cacheKey = "{$product->id}_{$qty}_" . md5(json_encode($cart));

        if (isset($this->availabilityCache[$cacheKey])) {
            return $this->availabilityCache[$cacheKey];
        }

        // ALWAYS fresh check from database
        $stockChecker = app(\App\Services\RecipeStockChecker::class);
        $result = $stockChecker->checkAvailability($product, $qty, $cart);

        $this->availabilityCache[$cacheKey] = $result;

        return $result;
    }

    public function render()
    {
        // 🚀 OPTIMIZATION: Cache featured products (5 minutes - changes rarely)
        // Add stockVersion to key to invalidate on update
        $featuredProducts = \Illuminate\Support\Facades\Cache::remember(
            'waiter_featured_products_' . $this->stockVersion,
            300,
            function () {
                return Product::select([
                    'id',
                    'name',
                    'sell_price',
                    'stock',
                    'type',
                    'category_id',
                    'image',
                    'is_sellable'
                ])
                    ->where('is_favorite', true)
                    ->where('is_sellable', true)
                    ->where('name', '!=', 'Down Payment (DP)')
                    ->where(function ($q) {
                        $q->where('stock', '>', 0)
                            ->orWhereIn('type', ['produced', 'bar'])
                            ->orWhereNull('stock');
                    })
                    ->with([
                        'category:id,name',
                        'unit:id,symbol,name'
                    ])
                    ->limit(10)
                    ->get();
            }
        );

        // 🚀 OPTIMIZATION: Cache product list (5 minutes - safe for display)
        // KEY CHANGE: Included $this->stockVersion in cache key
        $cacheKey = 'waiter_products_' .
            $this->selectedCategoryId . '_' .
            md5($this->search) . '_' .
            $this->perPage . '_' .
            $this->stockVersion;

        $products = \Illuminate\Support\Facades\Cache::remember($cacheKey, 300, function () {
            $query = Product::select([
                'id',
                'name',
                'sell_price',
                'stock',
                'type',
                'category_id',
                'image',
                'is_sellable'
            ])
                ->where('is_sellable', true)
                ->where('name', '!=', 'Down Payment (DP)')
                ->where(function ($q) {
                    $q->where('stock', '>', 0)
                        ->orWhereIn('type', ['produced', 'bar'])
                        ->orWhereNull('stock');
                })
                ->when($this->selectedCategoryId !== 'all', function ($query) {
                    $query->where('category_id', $this->selectedCategoryId);
                })
                ->when($this->search, function ($query) {
                    $query->where('name', 'like', '%' . $this->search . '%');
                })
                ->with([
                    'category:id,name',
                    'unit:id,symbol,name'
                ])
                ->orderBy('name')
                ->take($this->perPage);

            $fetchedProducts = $query->get();

            // 🚀 OPTIMIZATION: Batch Calculate Max Portions (Avoid N+1)
            $productIds = $fetchedProducts->pluck('id')->toArray();
            if (!empty($productIds)) {
                $stockChecker = app(\App\Services\RecipeStockChecker::class);
                // Check without cart items to get absolute max portions available
                $availabilityMap = $stockChecker->batchCheckAvailability($productIds, []);

                foreach ($fetchedProducts as $product) {
                    if (isset($availabilityMap[$product->id])) {
                        // Manually set the attribute to avoid re-querying property access
                        $product->setAttribute('max_portions', $availabilityMap[$product->id]['max_portions']);
                    }
                }
            }

            return $fetchedProducts;
        });

        // 🚀 OPTIMIZATION: Use collection count instead of separate query
        $hasMore = $products->count() >= $this->perPage;

        return view('livewire.waiter-order.menu', [
            'products' => $products,
            'featuredProducts' => $featuredProducts,
            'hasMore' => $hasMore
        ])->layout('components.layouts.waiter');
    }
}

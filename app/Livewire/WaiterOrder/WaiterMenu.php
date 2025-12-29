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

    public function mount()
    {
        $this->categories = Category::orderBy('name')->get();
        // Load initial state from Cache
        $userId = auth()->id();
        $cacheKey = 'waiter_cart_' . $userId;
        $this->cart = \Illuminate\Support\Facades\Cache::get($cacheKey, []);
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

        $product = Product::with(['recipes.ingredient'])->find($productId);
        if (!$product) {
            $this->dispatch('notify', message: 'Produk tidak ditemukan', type: 'error');
            return;
        }

        // Calculate Target Qty (Current + Added)
        $currentCartQty = isset($this->cart[$productId]) ? $this->cart[$productId]['qty'] : 0;
        $requestedQty = $currentCartQty + $quantity;

        // Check stock availability
        if (in_array($product->type, ['produced', 'bar'])) {
            $stockChecker = app(\App\Services\RecipeStockChecker::class);

            // Pass the CURRENT CART (excluding self)
            $cartForCheck = $this->cart;
            if (isset($cartForCheck[$productId])) {
                unset($cartForCheck[$productId]);
            }

            $availability = $stockChecker->checkAvailability($product, $requestedQty, $cartForCheck);

            if (!$availability['available']) {
                $maxPortions = $availability['max_portions'];

                // If we can add SOME, but not all? 
                // For simplicity, failing if total requested exceeds max.
                // Or: Add only what's left? 
                // Let's strict fail to avoid confusion, or notify max.

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

        // Don't refresh whole component if possible, but badges might need it. 
        // Pagination state must be preserved.
        // $this->dispatch('$refresh'); 
    }

    // Keep original single add for fallback or other calls
    public function addToCart($productId)
    {
        $this->addToCartBatch($productId, 1);
    }

    public function render()
    {
        // 1. Get Featured Products (Upselling) - Limited
        $featuredProducts = Product::select([
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

        // 2. Get Standard Products - PAGINATED / LIMITED
        $products = Product::select([
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
                'unit:id,symbol,name',
                'recipes.ingredient', // Eager load for MaxPortions calculation
                'recipes.unit'
            ])
            ->orderBy('name') // Optimization: Ensure consistent order for pagination
            ->take($this->perPage) // Pagination Limit
            ->get();

        // Check if there are more products to load
        $totalProducts = Product::where('is_sellable', true)
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
            ->count();

        $hasMore = $totalProducts > $this->perPage;

        return view('livewire.waiter-order.menu', [
            'products' => $products,
            'featuredProducts' => $featuredProducts,
            'hasMore' => $hasMore
        ])->layout('components.layouts.waiter');
    }
}

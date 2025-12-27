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

    public function mount()
    {
        $this->categories = Category::orderBy('name')->get();
        // Load initial state from Cache
        $userId = auth()->id();
        $cacheKey = 'waiter_cart_' . $userId;
        $this->cart = \Illuminate\Support\Facades\Cache::get($cacheKey, []);
    }

    public function addToCart($productId)
    {
        $userId = auth()->id();
        $cacheKey = 'waiter_cart_' . $userId;

        // Always reload from cache first to ensure sync
        $this->cart = \Illuminate\Support\Facades\Cache::get($cacheKey, []);

        \Illuminate\Support\Facades\Log::info('Adding to cart (Waiter): ' . $productId);
        \Illuminate\Support\Facades\Log::info('Current Cart Before: ' . json_encode($this->cart));

        $product = Product::with(['recipes.ingredient'])->find($productId);
        if (!$product) {
            $this->dispatch('notify', message: 'Produk tidak ditemukan', type: 'error');
            return;
        }

        // Check stock availability for produced items with recipes
        $requestedQty = isset($this->cart[$productId]) ? $this->cart[$productId]['qty'] + 1 : 1;

        if (in_array($product->type, ['produced', 'bar'])) {
            $stockChecker = app(\App\Services\RecipeStockChecker::class);
            $availability = $stockChecker->checkAvailability($product, $requestedQty);

            if (!$availability['available']) {
                $maxPortions = $availability['max_portions'];
                $limitingIngredient = $availability['limiting_ingredient'];

                if ($maxPortions === 0) {
                    $this->dispatch('notify', message: "❌ {$product->name} tidak tersedia. Bahan baku '{$limitingIngredient}' habis.", type: 'error');
                    return;
                } else {
                    $this->dispatch('notify', message: "⚠️ Stok terbatas. Hanya tersedia {$maxPortions} porsi {$product->name}.", type: 'warning');
                    return;
                }
            }
        }

        if (isset($this->cart[$productId])) {
            $this->cart[$productId]['qty']++;
        } else {
            $this->cart[$productId] = [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->sell_price,
                'image' => $product->image,
                'qty' => 1,
                'note' => ''
            ];
        }

        \Illuminate\Support\Facades\Cache::put($cacheKey, $this->cart, now()->addHours(12));

        \Illuminate\Support\Facades\Log::info('Cart After: ' . json_encode($this->cart));

        $this->dispatch('cart-updated');

        // Trigger component refresh to update badges
        $this->dispatch('$refresh');

        $this->dispatch('notify', message: 'Berhasil ditambahkan ke keranjang', type: 'success');
    }

    public function render()
    {
        // 1. Get Featured Products (Upselling) - with eager loading
        $featuredProducts = Product::with(['category', 'unit'])
            ->where('is_favorite', true)
            ->where('is_sellable', true)
            ->where('name', '!=', 'Down Payment (DP)')
            ->where(function ($q) {
                $q->where('stock', '>', 0)
                    ->orWhereIn('type', ['produced', 'bar'])
                    ->orWhereNull('stock');
            })
            ->limit(10)
            ->get();

        // 2. Get Standard Products - with eager loading
        $products = Product::with(['category', 'unit'])
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
            ->orderBy('name')
            ->get();

        return view('livewire.waiter-order.menu', [
            'products' => $products,
            'featuredProducts' => $featuredProducts
        ])->layout('components.layouts.waiter');
    }
}

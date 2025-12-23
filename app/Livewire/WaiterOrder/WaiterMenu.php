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

        if (isset($this->cart[$productId])) {
            $this->cart[$productId]['qty']++;
        } else {
            $product = Product::find($productId);
            if ($product) {
                $this->cart[$productId] = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->sell_price,
                    'image' => $product->image,
                    'qty' => 1,
                    'note' => ''
                ];
            }
        }

        \Illuminate\Support\Facades\Cache::put($cacheKey, $this->cart, now()->addHours(12));

        \Illuminate\Support\Facades\Log::info('Cart After: ' . json_encode($this->cart));

        $this->dispatch('cart-updated');

        \Filament\Notifications\Notification::make()
            ->title('Berhasil ditambahkan')
            ->success()
            ->duration(1000)
            ->send();
    }

    public function render()
    {
        // 1. Get Featured Products (Upselling)
        $featuredProducts = Product::where('is_favorite', true)
            ->where('is_sellable', true)
            ->where('name', '!=', 'Down Payment (DP)')
            ->where(function ($q) {
                $q->where('stock', '>', 0)
                    ->orWhereIn('type', ['produced', 'bar'])
                    ->orWhereNull('stock');
            })
            ->limit(10)
            ->get();

        // 2. Get Standard Products
        $products = Product::where('is_sellable', true)
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

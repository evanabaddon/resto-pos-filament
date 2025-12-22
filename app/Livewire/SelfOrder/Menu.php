<?php

namespace App\Livewire\SelfOrder;

use App\Models\Category;
use App\Models\Product;
use Livewire\Component;

class Menu extends Component
{
    public $categories = [];
    public $selectedCategoryId = 'all';
    public $search = '';

    public function mount()
    {
        // Fetch categories from the database
        $this->categories = Category::orderBy('name')->get();
    }

    public function addToCart($productId)
    {
        $cart = session()->get('cart', []);

        if (isset($cart[$productId])) {
            $cart[$productId]['qty']++;
        } else {
            $product = Product::find($productId);
            if ($product) {
                $cart[$productId] = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->sell_price,
                    'image' => $product->image,
                    'qty' => 1,
                    'note' => ''
                ];
            }
        }

        session()->put('cart', $cart);
        $this->dispatch('cart-updated');
    }

    public function render()
    {
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

        return view('livewire.self-order.menu', [
            'products' => $products
        ])->layout('components.layouts.mobile');
    }
}

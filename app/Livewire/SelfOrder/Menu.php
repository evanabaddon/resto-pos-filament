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

        $product = Product::find($productId);
        if (!$product) {
            $this->dispatch('notify', message: 'Produk tidak ditemukan', type: 'error');
            return;
        }

        // Check stock availability for produced items with recipes
        $requestedQty = isset($cart[$productId]) ? $cart[$productId]['qty'] + 1 : 1;

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

        if (isset($cart[$productId])) {
            $cart[$productId]['qty']++;
        } else {
            $cart[$productId] = [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->sell_price,
                'image' => $product->image,
                'qty' => 1,
                'note' => ''
            ];
        }

        session()->put('cart', $cart);
        $this->dispatch('cart-updated');

        // Trigger component refresh to update badges
        $this->dispatch('$refresh');
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

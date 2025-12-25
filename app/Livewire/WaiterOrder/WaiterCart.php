<?php

namespace App\Livewire\WaiterOrder;

use Livewire\Component;

class WaiterCart extends Component
{
    public $cartItems = [];
    public $subtotal = 0;
    public $tax = 0;
    public $total = 0;

    protected $listeners = ['cart-updated' => 'loadCart'];

    public function mount()
    {
        $this->loadCart();
    }

    public function loadCart()
    {
        $userId = auth()->id();
        $cacheKey = 'waiter_cart_' . $userId;
        $driver = config('cache.default');

        $this->cartItems = \Illuminate\Support\Facades\Cache::get($cacheKey, []);

        \Illuminate\Support\Facades\Log::info('----------------------------------------------');
        \Illuminate\Support\Facades\Log::info('WAITER CART LOADED');
        \Illuminate\Support\Facades\Log::info('User ID: ' . $userId);
        \Illuminate\Support\Facades\Log::info('Cache Driver: ' . $driver);
        \Illuminate\Support\Facades\Log::info('Cache Key: ' . $cacheKey);
        \Illuminate\Support\Facades\Log::info('Cart Content: ' . json_encode($this->cartItems));
        \Illuminate\Support\Facades\Log::info('----------------------------------------------');

        $this->calculateTotal();
    }

    public function increment($productId)
    {
        if (isset($this->cartItems[$productId])) {
            $product = \App\Models\Product::find($productId);

            if ($product && in_array($product->type, ['produced', 'bar'])) {
                // Check if we can add one more
                $newQuantity = $this->cartItems[$productId]['qty'] + 1;
                $stockChecker = app(\App\Services\RecipeStockChecker::class);
                $availability = $stockChecker->checkAvailability($product, $newQuantity);

                if (!$availability['available']) {
                    $this->dispatch('notify', message: "⚠️ Stok terbatas. Hanya tersedia {$availability['max_portions']} porsi {$product->name}.", type: 'warning');
                    return;
                }
            }

            $this->cartItems[$productId]['qty']++;
            $this->saveCart();
            $this->calculateTotal();
        }
    }

    public function decrement($productId)
    {
        if (isset($this->cartItems[$productId])) {
            if ($this->cartItems[$productId]['qty'] > 1) {
                $this->cartItems[$productId]['qty']--;
            } else {
                unset($this->cartItems[$productId]);
            }
            $this->saveCart();
            $this->calculateTotal(); // Re-calculate after potential unset
        }
    }

    public function removeItem($productId)
    {
        if (isset($this->cartItems[$productId])) {
            unset($this->cartItems[$productId]);
            $this->saveCart();
            $this->calculateTotal();
        }
    }

    public function updateNote($productId, $note)
    {
        if (isset($this->cartItems[$productId])) {
            $this->cartItems[$productId]['note'] = $note;
            $this->saveCart();
        }
    }

    protected function saveCart()
    {
        $userId = auth()->id();
        \Illuminate\Support\Facades\Cache::put('waiter_cart_' . $userId, $this->cartItems, now()->addHours(12));
    }

    public function calculateTotal()
    {
        $this->subtotal = collect($this->cartItems)->sum(fn($item) => $item['price'] * $item['qty']);

        // Dynamic Tax from Settings
        $settings = app(\App\Settings\GeneralSettings::class);
        $taxRate = $settings->enable_tax ? ($settings->tax_percentage / 100) : 0;

        $this->tax = $this->subtotal * $taxRate;
        $this->total = $this->subtotal + $this->tax;

        // If cart is empty, redirect to menu (optional, but good UX)
        // if (empty($this->cartItems)) {
        //     return redirect()->route('waiter.order');
        // }
    }

    public function render()
    {
        return view('livewire.waiter-order.cart')
            ->layout('components.layouts.waiter');
    }
}

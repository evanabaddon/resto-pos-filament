<?php

namespace App\Livewire\SelfOrder;

use Livewire\Component;

class Cart extends Component
{
    public $cartItems = [];
    public $subtotal = 0;
    public $tax = 0;
    public $total = 0;

    public function mount()
    {
        $this->loadCart();
    }

    public function loadCart()
    {
        $this->cartItems = session()->get('cart', []);
        $this->calculateTotal();
    }

    public function calculateTotal()
    {
        $this->subtotal = collect($this->cartItems)->sum(fn($item) => $item['price'] * $item['qty']);
        $settings = app(\App\Settings\GeneralSettings::class);
        $taxRate = $settings->enable_tax ? ($settings->tax_percentage / 100) : 0;
        $this->tax = $this->subtotal * $taxRate;
        $this->total = $this->subtotal + $this->tax;
    }

    public function updateQty($id, $change)
    {
        if (isset($this->cartItems[$id])) {
            $this->cartItems[$id]['qty'] += $change;
            if ($this->cartItems[$id]['qty'] <= 0) {
                unset($this->cartItems[$id]);
            }
            session()->put('cart', $this->cartItems);
            $this->calculateTotal();
            $this->dispatch('cart-updated');
        }
    }

    public function removeItem($id)
    {
        unset($this->cartItems[$id]);
        session()->put('cart', $this->cartItems);
        $this->calculateTotal();
        $this->dispatch('cart-updated');
    }

    public function render()
    {
        return view('livewire.self-order.cart')
            ->layout('components.layouts.mobile');
    }
}

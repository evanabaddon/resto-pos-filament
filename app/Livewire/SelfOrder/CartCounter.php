<?php

namespace App\Livewire\SelfOrder;

use Livewire\Component;
use Livewire\Attributes\On;

class CartCounter extends Component
{
    public $count = 0;

    public function mount()
    {
        $this->updateCount();
    }

    #[On('cart-updated')]
    public function updateCount()
    {
        $this->count = count(session('cart', []));
    }

    public function render()
    {
        return view('livewire.self-order.cart-counter');
    }
}

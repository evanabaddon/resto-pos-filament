<?php

namespace App\Livewire\WaiterOrder;

use Livewire\Component;

class WaiterCartCounter extends Component
{
    public $count = 0;

    protected $listeners = ['cart-updated' => 'refreshCount'];

    public function mount()
    {
        $this->refreshCount();
    }

    public function refreshCount()
    {
        $userId = auth()->id();
        $cart = \Illuminate\Support\Facades\Cache::get('waiter_cart_' . $userId, []);
        $this->count = count($cart);
    }

    public function render()
    {
        return <<<'BLADE'
            <a href="{{ route('waiter.cart') }}" wire:navigate class="flex flex-col items-center justify-center w-full py-1 {{ request()->routeIs('waiter.cart') ? 'text-primary-600' : 'text-gray-400' }} transition-colors relative group">
                <div class="{{ request()->routeIs('waiter.cart') ? 'bg-primary-50' : 'group-hover:bg-gray-50' }} p-1.5 rounded-xl transition-colors relative">
                    <x-heroicon-o-shopping-bag class="w-6 h-6" />
                    @if($count > 0)
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-[9px] font-bold w-4 h-4 flex items-center justify-center rounded-full border border-white">
                            {{ $count }}
                        </span>
                    @endif
                </div>
                <span class="text-[10px] font-bold mt-0.5">Keranjang</span>
            </a>
        BLADE;
    }
}

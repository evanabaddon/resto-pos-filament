<a href="{{ route('order.cart') }}" wire:navigate class="flex flex-col items-center justify-center w-full py-1 text-gray-400 hover:text-primary-600 {{ request()->routeIs('order.cart') ? 'text-primary-600' : '' }} transition-colors relative group">
    <div class="{{ request()->routeIs('order.cart') ? 'bg-primary-50' : 'group-hover:bg-gray-50' }} p-1.5 rounded-xl transition-colors relative">
        <x-heroicon-o-shopping-bag class="w-6 h-6" />

        @if($count > 0)
        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-[9px] font-bold px-1.5 py-0.5 rounded-full border-2 border-white shadow-sm transform scale-100 transition-transform duration-300">
            {{ $count }}
        </span>
        @endif
    </div>
    <span class="text-[10px] font-bold mt-0.5">Keranjang</span>
</a>
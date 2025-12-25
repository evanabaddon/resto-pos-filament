<div class="pb-32 bg-gray-50 min-h-screen font-sans">
    <!-- Header / Sticky Search -->
    <div
        class="sticky top-14 z-30 bg-white/80 backdrop-blur-md border-b border-gray-200/50 px-5 py-4 transition-all duration-300">
        <div class="relative group">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <x-heroicon-o-magnifying-glass
                    class="h-5 w-5 text-gray-400 group-focus-within:text-primary-500 transition-colors" />
            </div>
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Mau makan apa hari ini?"
                class="block w-full pl-11 pr-4 py-3 border-0 bg-gray-100/80 text-gray-900 placeholder-gray-400 rounded-2xl focus:ring-2 focus:ring-primary-500 focus:bg-white text-sm font-medium transition-all shadow-inner">
        </div>
    </div>

    <div class="sticky top-[80px] z-20 bg-gray-50/95 backdrop-blur-sm py-2">
        <div class="overflow-x-auto whitespace-nowrap pb-2 no-scrollbar flex gap-3 snap-x scroll-pl-5">
            <button wire:click="$set('selectedCategoryId', 'all')"
                class="snap-start flex-shrink-0 ml-5 px-5 py-2 rounded-full text-xs font-bold transition-all duration-300 {{ $selectedCategoryId === 'all' ? 'bg-primary-600 text-white shadow-lg shadow-primary-500/30 scale-105' : 'bg-white text-gray-500 border border-gray-200 hover:border-gray-300 hover:bg-gray-50' }}">
                Semua
            </button>
            @foreach($categories as $cat)
                <button wire:click="$set('selectedCategoryId', {{ $cat->id }})"
                    class="snap-start flex-shrink-0 px-5 py-2 rounded-full text-xs font-bold transition-all duration-300 {{ $selectedCategoryId === $cat->id ? 'bg-primary-600 text-white shadow-lg shadow-primary-500/30 scale-105' : 'bg-white text-gray-500 border border-gray-200 hover:border-gray-300 hover:bg-gray-50' }} {{ $loop->last ? 'mr-5' : '' }}">
                    {{ $cat->name }}
                </button>
            @endforeach
        </div>
    </div>

    <!-- Product Grid -->
    <!-- Poll every 10s for cross-channel updates -->
    <div wire:poll.5s class="px-5 py-4 grid grid-cols-2 gap-4">
        @forelse($products as $product)
            @php
                // Check if product is out of stock
                $isOutOfStock = false;
                if (in_array($product->type, ['produced', 'bar'])) {
                    $cart = session()->get('cart', []);
                    $maxPortions = $product->max_portions;
                    $qtyInCart = isset($cart[$product->id]) ? $cart[$product->id]['qty'] : 0;
                    $remainingPortions = max(0, $maxPortions - $qtyInCart);
                    $isOutOfStock = $remainingPortions <= 0;
                }
            @endphp
            <div
                class="bg-white rounded-3xl p-3 shadow-[0_8px_30px_rgb(0,0,0,0.04)] transition-all duration-300 group flex flex-col h-full border border-gray-100/50 relative
                    {{ $isOutOfStock ? 'opacity-60 grayscale' : 'hover:shadow-[0_8px_30px_rgb(0,0,0,0.08)]' }}">
                <!-- Image -->
                <div class="aspect-square w-full rounded-2xl overflow-hidden bg-gray-100 relative mb-3">
                    <img src="{{ $product->image_url }}" loading="lazy"
                        class="w-full h-full object-cover transition duration-500 group-hover:scale-110">
                    <!-- Floating Price Tag -->
                    <div class="absolute bottom-2 left-2 bg-white/90 backdrop-blur-sm px-2.5 py-1 rounded-lg shadow-sm">
                        <span class="text-xs font-bold text-gray-900">
                            Rp {{ number_format($product->sell_price / 1000, 0) }}<span
                                class="text-[10px] text-gray-500">rb</span>
                        </span>
                    </div>
                </div>

                {{-- OUT OF STOCK OVERLAY --}}
                @if($isOutOfStock)
                <div class="absolute inset-0 z-20 flex items-center justify-center bg-white/40 backdrop-blur-[1px] rounded-3xl pointer-events-none">
                    <span class="px-2 py-0.5 bg-slate-800 text-white text-[9px] font-bold rounded shadow-lg transform -rotate-6 tracking-wider">HABIS</span>
                </div>
                @endif

                <!-- Content -->
                <div class="flex flex-col flex-1">
                    <h3
                        class="font-bold text-gray-800 text-sm leading-snug line-clamp-2 mb-1 group-hover:text-primary-600 transition-colors">
                        {{ $product->name }}
                    </h3>

                    <div class="mt-auto pt-3">
                        <button wire:click="addToCart({{ $product->id }})" 
                            wire:loading.attr="disabled"
                            {{ $isOutOfStock ? 'disabled' : '' }}
                            class="w-full py-2.5 rounded-xl font-bold text-sm transition-all duration-200 flex items-center justify-center gap-2 group/btn relative overflow-hidden
                                {{ $isOutOfStock ? 'bg-gray-200 text-gray-400 cursor-not-allowed border border-gray-300' : 'bg-gray-50 hover:bg-primary-50 text-gray-700 hover:text-primary-700 active:scale-95 border border-gray-200 hover:border-primary-200' }}">

                            <span wire:loading.remove wire:target="addToCart({{ $product->id }})"
                                class="flex items-center gap-1.5 z-10">
                                Tambah <x-heroicon-m-plus class="w-4 h-4" />
                            </span>

                            <span wire:loading wire:target="addToCart({{ $product->id }})" class="z-10">
                                <svg class="animate-spin h-4 w-4 text-primary-600" xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-2 py-20 text-center">
                <div class="bg-gray-100 rounded-full w-24 h-24 flex items-center justify-center mx-auto mb-6 animate-pulse">
                    <x-heroicon-o-magnifying-glass class="w-10 h-10 text-gray-400" />
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Belum ada menu, nih</h3>
                <p class="text-gray-500 text-sm max-w-[200px] mx-auto">Coba cari pakai kata kunci lain ya.</p>
                <button wire:click="$set('search', '')" class="mt-6 text-primary-600 font-bold text-sm hover:underline">
                    Lihat Semua Menu
                </button>
            </div>
        @endforelse
    </div>
</div>
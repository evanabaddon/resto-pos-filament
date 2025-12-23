<div class="pb-32 bg-gray-50 min-h-screen font-sans">
    <!-- Header / Sticky Search -->
    <div
        class="sticky top-0 z-30 bg-white/80 backdrop-blur-md border-b border-gray-200/50 px-4 py-3 transition-all duration-300">
        <div class="flex items-center justify-between mb-2">
            <h1 class="text-sm font-black text-gray-900 uppercase tracking-tighter">Waiter Order</h1>
            <span class="bg-red-50 text-red-600 text-[9px] px-2 py-0.5 rounded-full font-black uppercase">
                Items: {{ count($cart) }}
            </span>
        </div>
        <div class="relative group">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                <x-heroicon-o-magnifying-glass
                    class="h-4 w-4 text-gray-400 group-focus-within:text-primary-500 transition-colors" />
            </div>
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Cari menu..."
                class="block w-full pl-9 pr-3 py-2 border-0 bg-gray-100/60 text-gray-900 placeholder-gray-400 rounded-xl focus:ring-1 focus:ring-primary-500 focus:bg-white text-[11px] font-medium transition-all shadow-inner">
        </div>
    </div>

    <!-- Categories -->
    <div class="sticky top-[86px] z-20 bg-gray-50/95 backdrop-blur-sm py-1">
        <div class="overflow-x-auto whitespace-nowrap pb-1 no-scrollbar flex gap-2 snap-x scroll-pl-4">
            <button wire:click="$set('selectedCategoryId', 'all')"
                class="snap-start flex-shrink-0 ml-4 px-4 py-1.5 rounded-full text-[10px] font-bold transition-all duration-300 {{ $selectedCategoryId === 'all' ? 'bg-primary-600 text-white shadow-lg shadow-primary-500/30' : 'bg-white text-gray-500 border border-gray-200 hover:border-gray-300' }}">
                Semua
            </button>
            @foreach($categories as $cat)
                <button wire:click="$set('selectedCategoryId', {{ $cat->id }})"
                    class="snap-start flex-shrink-0 px-4 py-1.5 rounded-full text-[10px] font-bold transition-all duration-300 {{ $selectedCategoryId === $cat->id ? 'bg-primary-600 text-white shadow-lg shadow-primary-500/30' : 'bg-white text-gray-500 border border-gray-200 hover:border-gray-300' }} {{ $loop->last ? 'mr-4' : '' }}">
                    {{ $cat->name }}
                </button>
            @endforeach
        </div>
    </div>

    <!-- Featured Products (Upselling) -->
    @if($selectedCategoryId === 'all' && empty($search) && $featuredProducts->isNotEmpty())
        <div class="px-5 pt-2 pb-4">
            <div class="flex items-center gap-2 mb-3">
                <x-heroicon-m-sparkles class="w-5 h-5 text-yellow-500" />
                <h2 class="font-bold text-gray-800 text-sm">Rekomendasi / Upselling</h2>
            </div>

            <div class="overflow-x-auto whitespace-nowrap pb-4 no-scrollbar flex gap-4 snap-x scroll-pl-5">
                @foreach($featuredProducts as $product)
                    <div wire:key="featured-{{ $product->id }}"
                        class="snap-start shrink-0 w-40 bg-white rounded-2xl p-3 shadow-md border border-yellow-100 relative group overflow-hidden">
                        <!-- Image -->
                        <div class="aspect-square w-full rounded-xl overflow-hidden bg-gray-100 relative mb-2">
                            <img src="{{ $product->image_url }}" loading="lazy"
                                class="w-full h-full object-cover transition duration-500">
                        </div>

                        <h3 class="font-bold text-gray-800 text-xs leading-snug line-clamp-2 mb-1 whitespace-normal">
                            {{ $product->name }}
                        </h3>
                        <p class="text-xs font-bold text-primary-600 mb-2">
                            Rp {{ number_format($product->sell_price / 1000, 0) }}rb
                        </p>

                        <button wire:click="addToCart({{ $product->id }})" wire:loading.attr="disabled"
                            class="w-full bg-yellow-50 hover:bg-yellow-100 text-yellow-700 hover:text-yellow-800 py-2 rounded-lg font-bold text-xs flex items-center justify-center gap-1">
                            <span wire:loading.remove wire:target="addToCart({{ $product->id }})">
                                Tambah +
                            </span>
                            <span wire:loading wire:target="addToCart({{ $product->id }})">
                                ...
                            </span>
                        </button>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Product Grid -->
    <div class="px-5 py-4 grid grid-cols-2 gap-4">
        @forelse($products as $product)
            <div wire:key="product-{{ $product->id }}"
                class="bg-white rounded-3xl p-3 shadow-[0_8px_30px_rgb(0,0,0,0.04)] hover:shadow-[0_8px_30px_rgb(0,0,0,0.08)] transition-all duration-300 group flex flex-col h-full border border-gray-100/50">
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

                <!-- Content -->
                <div class="flex flex-col flex-1">
                    <h3
                        class="font-bold text-gray-800 text-sm leading-snug line-clamp-2 mb-1 group-hover:text-primary-600 transition-colors">
                        {{ $product->name }}
                    </h3>

                    <div class="mt-auto pt-3">
                        <button wire:click="addToCart({{ $product->id }})" wire:loading.attr="disabled"
                            class="w-full bg-gray-50 hover:bg-primary-50 text-gray-700 hover:text-primary-700 active:scale-95 border border-gray-200 hover:border-primary-200 py-2.5 rounded-xl font-bold text-sm transition-all duration-200 flex items-center justify-center gap-2 group/btn relative overflow-hidden">

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
                <h3 class="text-lg font-bold text-gray-900 mb-2">Menu tidak ditemukan</h3>
            </div>
        @endforelse
    </div>

</div>
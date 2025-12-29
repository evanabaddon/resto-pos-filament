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
    <!-- Poll every 30s for realtime stock updates (longer interval to reduce server load) -->
    <div wire:poll.30s class="px-5 py-4 grid grid-cols-2 gap-4 relative">
        <!-- Loading Overlay for Category Changes (NOT for polling) -->
        <div wire:loading wire:target="selectedCategoryId,search" 
            class="absolute inset-0 z-50 bg-white/60 backdrop-blur-sm flex items-center justify-center">
            <div class="bg-white p-4 rounded-xl shadow-lg flex items-center gap-3">
                <svg class="animate-spin h-5 w-5 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-sm font-bold text-gray-700">Memuat...</span>
            </div>
        </div>
        
        @forelse($products as $product)
            @php
                // Check if product is out of stock
                $isOutOfStock = false;
                if (in_array($product->type, ['produced', 'bar'])) {
                    $maxPortions = $product->max_portions;
                    $qtyInCart = isset($cart[$product->id]) ? $cart[$product->id]['qty'] : 0;
                    $remainingPortions = max(0, $maxPortions - $qtyInCart);
                    $isOutOfStock = $remainingPortions <= 0;
                }
            @endphp
            <div wire:key="product-{{ $product->id }}"
                @if(!$isOutOfStock)
                x-data="{
                    clickCount: 0,
                    timeout: null,
                    addToCart() {
                        this.clickCount++;
                        
                        // Visual Pop (vibro if mobile)
                        if(navigator.vibrate) navigator.vibrate(50);

                        clearTimeout(this.timeout);
                        this.timeout = setTimeout(() => {
                            if (this.clickCount > 0) {
                                $wire.addToCartBatch({{ $product->id }}, this.clickCount);
                                this.clickCount = 0;
                            }
                        }, 300); // 300ms buffer
                    }
                }"
                @endif
                class="bg-white rounded-3xl p-3 shadow-[0_8px_30px_rgb(0,0,0,0.04)] transition-all duration-300 group flex flex-col h-full border border-gray-100/50 relative
                    {{ $isOutOfStock ? 'opacity-60 grayscale' : 'hover:shadow-[0_8px_30px_rgb(0,0,0,0.08)]' }}">
                
                {{-- Helper Badge Count (Optimistic) --}}
                @if(!$isOutOfStock)
                <div x-show="clickCount > 0" x-transition.scale
                    class="absolute -top-1 -right-1 bg-yellow-500 text-white text-[10px] font-bold w-5 h-5 flex items-center justify-center rounded-full shadow-md z-20 border-2 border-white">
                    <span x-text="'+' + clickCount"></span>
                </div>
                @endif

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

                    @if(in_array($product->type, ['produced', 'bar']))
                        @php
                            $maxPortions = $product->max_portions;
                            // Calculate quantity already in cart
                            $qtyInCart = 0;
                            if (isset($cart[$product->id])) {
                                $qtyInCart = $cart[$product->id]['qty'];
                            }
                            // Remaining portions = max - already in cart
                            $remainingPortions = max(0, $maxPortions - $qtyInCart);
                        @endphp
                        @if($maxPortions < 10)
                            <!-- Availability Badge -->
                            <div class="absolute top-2 right-2">
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-bold shadow-sm border
                                                    {{ $remainingPortions > 5 ? 'bg-emerald-500 text-white border-emerald-600' :
                            ($remainingPortions > 0 ? 'bg-amber-500 text-white border-amber-600' : 'bg-rose-500 text-white border-rose-600') }}">
                                    {{ $remainingPortions }} porsi
                                </span>
                            </div>
                        @endif
                    @endif
                    
                    {{-- OUT OF STOCK OVERLAY --}}
                    @if($isOutOfStock)
                    <div class="absolute inset-0 z-20 flex items-center justify-center bg-white/40 backdrop-blur-[1px] rounded-2xl pointer-events-none">
                        <span class="px-2 py-0.5 bg-slate-800 text-white text-[9px] font-bold rounded shadow-lg transform -rotate-6 tracking-wider">HABIS</span>
                    </div>
                    @endif
                </div>

                <!-- Content -->
                <div class="flex flex-col flex-1">
                    <h3
                        class="font-bold text-gray-800 text-sm leading-snug line-clamp-2 mb-1 group-hover:text-primary-600 transition-colors">
                        {{ $product->name }}
                    </h3>

                    <div class="mt-auto pt-3">
                        <button 
                            @if(!$isOutOfStock) @click="addToCart()" @endif
                            wire:loading.attr="disabled"
                            {{ $isOutOfStock ? 'disabled' : '' }}
                            class="w-full py-2.5 rounded-xl font-bold text-sm transition-all duration-200 flex items-center justify-center gap-2 group/btn relative overflow-hidden active:scale-95
                                {{ $isOutOfStock ? 'bg-gray-200 text-gray-400 cursor-not-allowed border border-gray-300' : 'bg-gray-50 hover:bg-primary-50 text-gray-700 hover:text-primary-700 border border-gray-200 hover:border-primary-200' }}">

                            <span wire:loading.remove wire:target="addToCartBatch({{ $product->id }})"
                                class="flex items-center gap-1.5 z-10">
                                Tambah <x-heroicon-m-plus class="w-4 h-4" />
                            </span>

                            <span wire:loading wire:target="addToCartBatch({{ $product->id }})" class="z-10">
                                <x-filament::loading-indicator class="h-4 w-4 text-primary-600" />
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
    
    {{-- Pagination Load More --}}
    @if($hasMore)
    <div class="px-5 pb-5 pt-0">
        <button wire:click="loadMore" wire:loading.attr="disabled"
            class="w-full bg-white border border-gray-200 text-gray-600 font-bold py-3 rounded-xl shadow-sm hover:bg-gray-50 flex items-center justify-center gap-2">
            <span wire:loading.remove wire:target="loadMore">Muat Lebih Banyak...</span>
            <span wire:loading wire:target="loadMore">Memuat...</span>
        </button>
    </div>
    @endif

</div>
{{-- resources/views/filament/pages/pos.blade.php --}}
<div class="h-full flex flex-col lg:flex-row bg-gray-50 overflow-hidden min-h-0">

    {{-- 💰 PRODUK SECTION --}}
    <div id="mobile-products-section" class="mobile-section lg:flex-1 lg:min-w-[60%] w-full h-full flex flex-col border-r border-gray-200 bg-white shadow-sm min-h-0 overflow-hidden">
        {{-- Header Produk --}}
        <div class="px-5 py-4 border-b border-gray-100 bg-white flex-shrink-0 z-10 relative">
            <div class="flex items-center justify-between">
                <div class="flex-1 min-w-0">
                    <h1 class="text-xl font-bold text-gray-900 tracking-tight mobile-text-base">Menu Produk</h1>
                    <p class="text-xs text-slate-500 mt-0.5 truncate mobile-text-xs font-medium">Pilih produk untuk ditambahkan</p>
                </div>
                <div class="text-right ml-2 flex-shrink-0 pl-4 border-l border-gray-100">
                    <p class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Kategori</p>
                    <p class="text-sm font-bold text-violet-600 mobile-text-sm">{{ $selectedCategory }}</p>
                </div>
            </div>
        </div>

        {{-- 🔍 SEARCH BAR --}}
        <div class="px-3 py-2 bg-white border-b border-gray-100 flex-shrink-0">
            <div class="relative">
                <input 
                    id="product-search-input"
                    type="text"
                    wire:model.live.debounce.500ms="searchQuery"
                    placeholder="Cari produk..."
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 pl-11 pr-20 text-sm focus:ring-2 focus:ring-violet-500 focus:border-violet-500 focus:outline-none transition shadow-sm mobile-text-sm font-medium placeholder-slate-400"
                    autocomplete="off">
                
                {{-- Search Icon --}}
                <div class="absolute left-3 top-1/2 transform -translate-y-1/2">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                
                {{-- Clear Button (tampil jika ada query) --}}
                @if(!empty($searchQuery))
                    <button 
                        wire:click="$set('searchQuery', '')"
                        class="absolute right-10 top-1/2 transform -translate-y-1/2 cursor-pointer text-gray-400 hover:text-gray-600 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                @endif
                
                {{-- Keyboard Shortcut Hint --}}
                <div class="absolute right-3 top-1/2 transform -translate-y-1/2">
                    <span class="text-xs text-gray-400 hidden sm:inline-block">
                        <kbd class="px-1.5 py-0.5 bg-gray-100 rounded border border-gray-300 text-xs">/</kbd>
                    </span>
                </div>
            </div>
            
            {{-- Search status --}}
            @if(!empty($searchQuery))
                <div class="mt-1 text-xs text-gray-500">
                    <span>Mencari: </span>
                    <span class="font-semibold text-blue-600">{{ $searchQuery }}</span>
                    <span class="mx-2">•</span>
                    <span>{{ count($products) }} hasil ditemukan</span>
                </div>
            @endif
        </div>

        {{-- Filter Kategori - Horizontal Scroll Mobile --}}
        <div class="px-4 py-3 bg-white/80 backdrop-blur-sm border-b border-gray-100 flex-shrink-0 sticky top-0 z-20">
            <div class="flex space-x-2 overflow-x-auto hide-scrollbar pb-1">
                @foreach ($categories as $category)
                    <button wire:click="setCategory('{{ $category }}')"
                        class="cursor-pointer flex-shrink-0 px-4 py-2 rounded-full text-xs font-bold transition-all duration-200 border whitespace-nowrap touch-target
                            {{ $selectedCategory === $category 
                                ? 'bg-violet-600 text-white border-violet-600 shadow-md shadow-violet-200 transform scale-105' 
                                : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50 hover:border-slate-300' }}">
                        {{ $category }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Grid Produk - Modern Design --}}
        {{-- Grid Produk - Modern Design --}}
        <div class="flex-1 overflow-y-auto p-2 sm:p-4 bg-slate-50 relative" id="product-grid-container">
            {{-- Compact Grid: Mobile 2, Tablet 3, Desktop 4/5, Large 6/7 --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 2xl:grid-cols-7 gap-2 sm:gap-3 pb-24 lg:pb-0">
                @forelse ($products as $index => $product)
                    @php
                        $isAvailable = $this->checkProductAvailability($product);
                    @endphp
                    <div @if($isAvailable) wire:click="quickAddProduct({{ $product->id }})" @endif
                        class="group relative bg-white rounded-xl p-2 flex flex-col items-stretch transition-all duration-200 select-none touch-manipulation
                        {{ $isAvailable 
                            ? 'cursor-pointer shadow-sm hover:shadow-md hover:-translate-y-0.5 active:scale-95 border border-slate-100 hover:border-violet-200' 
                            : 'cursor-not-allowed opacity-60 grayscale bg-slate-50 border border-slate-100' }}">
                        
                        {{-- Stock Badge (Refined) --}}
                        @if($product->type !== 'produced' && $product->type !== 'bar')
                            <div class="absolute top-1.5 right-1.5 z-10 pointer-events-none">
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold tracking-tight shadow-sm border
                                    {{ $product->stock > 10 ? 'bg-white/90 text-emerald-700 border-emerald-100' : 
                                        ($product->stock > 0 ? 'bg-white/90 text-amber-700 border-amber-100' : 'bg-white/90 text-rose-700 border-rose-100') }}">
                                    {{ intval($product->stock) }}
                                </span>
                            </div>
                        @endif

                        {{-- OUT OF STOCK OVERLAY --}}
                        @if(!$isAvailable)
                            <div class="absolute inset-0 z-20 flex items-center justify-center bg-white/40 backdrop-blur-[1px] rounded-xl pointer-events-none">
                                <span class="px-2 py-0.5 bg-slate-800 text-white text-[9px] font-bold rounded shadow-lg transform -rotate-6 tracking-wider">HABIS</span>
                            </div>
                        @endif
                        
                        {{-- Product Image (Compact 4:3) --}}
                        <div class="aspect-[4/3] w-full mb-2 rounded-lg bg-slate-100 overflow-hidden relative shadow-inner">
                            @if($product->image)
                                <img src="{{ $product->image_url }}"
                                    alt="{{ $product->name }}"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300 ease-out"
                                    loading="lazy">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-slate-300">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                </div>
                            @endif
                            
                            {{-- Add Button Overlay (Mobile Visual Cue - Minimal) --}}
                            @if($isAvailable)
                            <div class="absolute bottom-1 right-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <div class="bg-violet-600/90 p-1 rounded-full shadow-sm text-white">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                                </div>
                            </div>
                            @endif
                        </div>

                        {{-- Product Info (Compact) --}}
                        <div class="flex-1 flex flex-col min-h-0">
                            <h3 class="text-[11px] sm:text-xs font-bold text-slate-700 leading-tight line-clamp-2 mb-1 group-hover:text-violet-700 transition-colors">
                                {{ $product->name }}
                            </h3>
                            <div class="mt-auto pt-0.5">
                                <p class="text-xs sm:text-sm font-black text-violet-600 leading-none">
                                    <span class="text-[9px] font-normal text-violet-400 mr-0.5">Rp</span>{{ number_format($product->sell_price, 0, ',', '.') }}
                                </p>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full flex flex-col items-center justify-center py-12 text-center text-slate-400">
                        <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mb-3">
                            <svg class="w-8 h-8 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-sm font-bold text-slate-600">Produk tidak ditemukan</h3>
                        <p class="text-xs mt-1">Coba kata kunci lain atau pilih kategori berbeda.</p>
                    </div>
                @endforelse
            </div>
            
            {{-- Pagination Links (Responsive Scrollable) --}}
            <div class="mt-8 px-2 sm:px-4 pb-24 lg:pb-8 flex justify-center w-full">
                <div class="bg-white/60 backdrop-blur-md rounded-full px-4 py-2 shadow-sm border border-slate-200/50 overflow-x-auto max-w-full no-scrollbar">
                    {{ $products->onEachSide(1)->links() }}
                </div>
            </div>
            
            {{-- Search Results Info --}}
            @if(!empty($searchQuery))
            <div class="py-2 text-center">
                <div class="inline-flex items-center px-3 py-1 rounded-full bg-violet-50 border border-violet-100">
                     <span class="w-1.5 h-1.5 bg-violet-500 rounded-full mr-2 animate-pulse"></span>
                    <p class="text-[10px] text-violet-700 font-medium">
                        Found {{ count($products) }} results for "<span class="font-bold">{{ $searchQuery }}</span>"
                    </p>
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- 🧺 KERANJANG SECTION - FIXED FOR DESKTOP & MOBILE --}}
    <div id="mobile-cart-section" class="mobile-section lg:flex lg:w-[400px] xl:w-[450px] w-full h-full flex-col bg-white shadow-lg border-l border-gray-200 flex-shrink-0 min-h-0 overflow-hidden">
        {{-- Header Keranjang --}}
        <div class="p-5 bg-white border-b border-gray-100 flex-shrink-0 z-20 shadow-[0_4px_20px_-10px_rgba(0,0,0,0.05)]">
            <div class="flex items-center justify-between mb-4">
                <div class="flex-1 min-w-0">
                    <h1 class="text-xl font-bold text-gray-900 tracking-tight mobile-text-lg">Keranjang</h1>
                    <div class="flex items-center text-xs text-slate-500 mt-0.5">
                        <span class="font-medium">No. Pesanan:</span>
                        <span class="ml-1 font-mono font-bold text-violet-600 bg-violet-50 px-1.5 py-0.5 rounded">{{ $orderNumber }}</span>
                    </div>
                </div>
                <div class="text-right ml-2 flex-shrink-0">
                    <div class="text-xs font-semibold text-slate-500 text-right">Kasir</div>
                    <div class="font-bold text-slate-800 text-sm truncate">{{ $this->getNameUserLogin() }}</div>
                </div>
            </div>

            {{-- Customer & Order Info --}}
            <div class="space-y-3">
                {{-- Tipe Order (Segmented Control) --}}
                <div class="bg-slate-100 p-1 rounded-xl flex shadow-inner">
                    <button 
                        wire:click="setOrderType('Dine In')"
                        class="flex-1 px-4 py-2 rounded-lg text-xs font-bold transition-all duration-200 focus:outline-none touch-target flex gap-2 items-center justify-center
                            {{ $orderType === 'Dine In'
                                ? 'bg-white text-violet-600 shadow-sm ring-1 ring-black/5 scale-[1.02]'
                                : 'text-slate-500 hover:text-slate-700 hover:bg-slate-200/50' }}">
                        <span>🍽️</span> Dine In
                    </button>
                    <button 
                        wire:click="setOrderType('Take Away')"
                        class="flex-1 px-4 py-2 rounded-lg text-xs font-bold transition-all duration-200 focus:outline-none touch-target flex gap-2 items-center justify-center
                            {{ $orderType === 'Take Away'
                                ? 'bg-white text-orange-600 shadow-sm ring-1 ring-black/5 scale-[1.02]'
                                : 'text-slate-500 hover:text-slate-700 hover:bg-slate-200/50' }}">
                        <span>🥡</span> Take Away
                    </button>
                </div>

                {{-- Nama Customer --}}
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-violet-500 transition-colors">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    </div>
                    <input 
                        type="text" 
                        wire:model="customerName"
                        class="block w-full pl-9 pr-3 py-2.5 border border-slate-200 rounded-xl leading-5 bg-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-violet-500 sm:text-sm transition shadow-sm mobile-text-sm"
                        placeholder="Nama Pelanggan (Optional)">
                </div>
            </div>
        </div>

        {{-- List Item --}}
        <div class="flex-1 overflow-y-auto p-4 space-y-3 min-h-0 bg-slate-50/50">
            @forelse ($items as $index => $item)
                <div class="group bg-white border border-slate-100/80 rounded-2xl p-3 shadow-sm hover:shadow-md transition-all duration-200 relative overflow-hidden">
                    {{-- Left Accent Strip --}}
                    <div class="absolute left-0 top-0 bottom-0 w-1 bg-violet-500 rounded-l-md opacity-0 group-hover:opacity-100 transition-opacity"></div>

                    <div class="flex items-start justify-between mb-2 pl-2">
                        <div class="flex-1 min-w-0 mr-2">
                            <h3 class="font-bold text-slate-800 text-sm leading-snug break-words mobile-text-sm">{{ $item['name'] }}</h3>
                            <div class="text-xs text-slate-400 mt-0.5 flex flex-wrap gap-2">
                                <span>Rp{{ number_format($item['price'], 0, ',', '.') }}</span>
                            </div>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <p class="text-sm font-black text-violet-700 mobile-text-sm">
                                Rp{{ number_format($item['subtotal'], 0, ',', '.') }}
                            </p>
                        </div>
                    </div>
                    
                    {{-- NOTES SECTION --}}
                    @if(!empty($item['notes']))
                        <div class="mb-3 ml-2 p-2 bg-amber-50 border border-amber-100 rounded-lg flex items-start">
                            <svg class="w-3 h-3 text-amber-500 mr-1.5 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            <span class="text-xs text-amber-800 font-medium break-words leading-relaxed">{{ $item['notes'] }}</span>
                        </div>
                    @endif
                    
                    {{-- CONTROLS ROW --}}
                    <div class="flex items-center justify-between pl-2">
                        <div class="flex items-center bg-slate-100 rounded-lg p-1 shadow-inner">
                             {{-- Decrement --}}
                            <button wire:click="decrementQuantity({{ $index }})"
                                class="w-7 h-7 flex items-center justify-center bg-white rounded-md text-slate-600 font-bold shadow-sm hover:text-rose-600 active:scale-95 transition touch-target">
                                −
                            </button>
                            
                            {{-- Input --}}
                            <input 
                                type="number" 
                                wire:model.lazy="items.{{ $index }}.quantity"
                                wire:change="updateQuantityFromInput({{ $index }}, $event.target.value)"
                                min="1"
                                class="w-10 text-center bg-transparent border-none text-sm font-black text-slate-800 focus:ring-0 p-0 mx-1"
                                onfocus="this.select()">
                            
                             {{-- Increment --}}
                            <button wire:click="incrementQuantity({{ $index }})"
                                class="w-7 h-7 flex items-center justify-center bg-white rounded-md text-slate-600 font-bold shadow-sm hover:text-emerald-600 active:scale-95 transition touch-target">
                                +
                            </button>
                        </div>
                        
                        <div class="flex items-center space-x-2">
                             {{-- Note Button --}}
                            <button wire:click="openEditNotes({{ $index }})"
                                class="p-2 rounded-lg text-slate-400 hover:text-amber-500 hover:bg-amber-50 transition active:scale-95 touch-target"
                                title="Edit Catatan">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            </button>

                             {{-- Delete Button --}}
                            <button wire:click="removeItem({{ $index }})"
                                class="p-2 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition active:scale-95 touch-target"
                                title="Hapus Item">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="flex flex-col items-center justify-center h-48 text-center text-slate-400 px-4 mt-8">
                     <div class="w-20 h-20 bg-slate-100 rounded-full flex items-center justify-center mb-4 shadow-sm animate-pulse-slow">
                        <svg class="w-10 h-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                     </div>
                     <h3 class="font-bold text-slate-600 text-sm">Keranjang Kosong</h3>
                     <p class="text-xs mt-1 leading-relaxed">Pilih produk dari menu untuk memulai pesanan.</p>
                </div>
            @endforelse
            
            {{-- Spacer for Mobile Bottom Nav --}}
            <div class="h-20 lg:hidden"></div>
        </div>

        {{-- Ringkasan & Aksi --}}
         <div class="border-t border-gray-200 bg-white flex-shrink-0 z-20 pb-safe shadow-[0_-4px_20px_-10px_rgba(0,0,0,0.05)]">
            {{-- Input Diskon --}}
            <div class="p-4 border-b border-gray-100 bg-slate-50/50">
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5 mobile-text-xs">Kode Diskon / Voucher</label>
                <div class="flex space-x-2">
                    <input 
                        type="text" 
                        wire:model.defer="discountCodeInput"
                        class="flex-1 bg-white border border-slate-300 rounded-lg py-2 px-3 text-sm focus:ring-2 focus:ring-violet-500 focus:border-violet-500 focus:outline-none transition min-w-0 mobile-text-sm"
                        placeholder="Masukkan kode...">
                    <button 
                        wire:click="applyDiscountCode"
                        class="cursor-pointer bg-slate-800 hover:bg-slate-900 text-white px-4 py-2 rounded-lg text-xs font-bold shadow-sm hover:shadow-md transition whitespace-nowrap flex-shrink-0 touch-target active:scale-95">
                        Pakai
                    </button>
                </div>
                @if ($discountMessage)
                    <p class="text-xs mt-2 font-medium {{ $discountApplied ? 'text-emerald-600' : 'text-rose-600' }} mobile-text-xs flex items-center">
                        @if($discountApplied)
                            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        @endif
                        {{ $discountMessage }}
                    </p>
                @endif
            </div>

            {{-- Ringkasan Harga --}}
            <div class="p-5 space-y-1.5 bg-white">
                <div class="flex justify-between text-xs text-slate-600 mobile-text-xs">
                    <span>Subtotal</span>
                    <span class="font-mono">Rp{{ number_format($total, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between text-xs text-slate-600 mobile-text-xs">
                    <span>Pajak (10%)</span>
                    <span class="font-mono">Rp{{ number_format($tax, 0, ',', '.') }}</span>
                </div>
                @if($discount > 0)
                    <div class="flex justify-between text-xs text-emerald-600 mobile-text-xs font-bold">
                        <span>Diskon</span>
                        <span class="font-mono">- Rp{{ number_format($discount, 0, ',', '.') }}</span>
                    </div>
                @endif
                <div class="border-t border-dashed border-gray-200 pt-3 mt-2">
                    <div class="flex justify-between items-end">
                        <span class="text-sm font-bold text-slate-800 mobile-text-sm">Total Tagihan</span>
                        <span class="text-2xl font-black text-violet-600 mobile-text-lg leading-none">Rp{{ number_format($finalTotal, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            {{-- Tombol Aksi untuk Mobile (FIXED BOTTOM ABOVE NAV) --}}
            <div class="lg:hidden p-4 space-y-3 bg-white border-t border-slate-100 pb-safe">
                <button wire:click="openPaymentModalMobile" 
                        class="cursor-pointer w-full bg-gradient-to-r from-violet-600 to-indigo-600 text-white py-3.5 rounded-xl font-bold text-sm shadow-lg shadow-violet-200 active:scale-[0.98] transition touch-target flex justify-center items-center gap-2 group"
                        {{ !$saleId ? 'disabled' : '' }}>
                    <span>💳 Bayar Sekarang</span>
                    <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </button>
                
                <button wire:click="mobileSaveSale"
                    class="cursor-pointer w-full bg-slate-100 text-slate-700 hover:bg-slate-200 py-3 rounded-xl font-bold text-sm transition touch-target flex justify-center items-center gap-2">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                    <span>Simpan Draft</span>
                </button>
            </div>

            {{-- Tombol Aksi untuk Desktop --}}
            <div class="hidden lg:block p-4 border-t border-gray-100 bg-slate-50/80 space-y-3">
                <div class="grid grid-cols-2 gap-3">
                    <button wire:click="saveSale"
                        class="cursor-pointer w-full bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 hover:border-slate-300 py-3 rounded-xl font-bold text-sm shadow-sm transition flex items-center justify-center gap-2">
                        <svg class="w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                        Simpan
                    </button>
                    <button wire:click="openPaymentModal({{ $saleId }})" 
                            class="cursor-pointer w-full bg-violet-600 hover:bg-violet-700 text-white py-3 rounded-xl font-bold text-sm shadow-lg shadow-violet-200 transition flex items-center justify-center gap-2 hover:-translate-y-0.5"
                            {{ !$saleId ? 'disabled' : '' }}>
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        Bayar
                    </button>
                </div>
                <div class="grid grid-cols-3 gap-2">
                    {{-- TOMBOL MERGE BILL --}}
                    <button wire:click="openMergeModal"
                        class="cursor-pointer bg-fuchsia-600 hover:bg-fuchsia-700 text-white py-2 rounded-lg font-bold text-xs shadow-sm hover:shadow-md transition flex items-center justify-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                        </svg>
                        Merge
                    </button>
                    
                    <button wire:click="openLoadModal"
                        class="cursor-pointer bg-amber-500 hover:bg-amber-600 text-white py-2 rounded-lg font-bold text-xs shadow-sm hover:shadow-md transition flex items-center justify-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                        List
                    </button>
                    
                    <button wire:click="cancelSale"
                        class="cursor-pointer bg-slate-200 hover:bg-slate-300 text-slate-700 py-2 rounded-lg font-bold text-xs transition flex items-center justify-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        Batal
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- 🧭 MOBILE BOTTOM NAVIGATION --}}
    <nav class="mobile-bottom-nav lg:hidden fixed bottom-0 left-0 right-0 bg-white/95 backdrop-blur-xl border-t border-slate-200 z-50 safe-area-bottom pb-safe shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
        <div class="grid grid-cols-3 h-16 max-w-md mx-auto relative group">
            
            {{-- Products Button --}}
            <button onclick="switchSection('products')"
                class="nav-button flex flex-col items-center justify-center h-full text-slate-400 hover:text-violet-600 transition-colors active:scale-95"
                data-section="products">
                <div class="p-1 rounded-xl transition-all duration-200 nav-icon-bg">
                    <svg class="w-6 h-6 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                    </svg>
                </div>
                <span class="text-[10px] font-bold tracking-wide">Menu</span>
            </button>
            
            {{-- Cart Button --}}
            <button onclick="switchSection('cart')"
                class="nav-button flex flex-col items-center justify-center h-full text-slate-400 hover:text-violet-600 transition-colors active:scale-95"
                data-section="cart">
                <div class="relative p-1 rounded-xl transition-all duration-200 nav-icon-bg">
                    <svg class="w-6 h-6 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                    </svg>
                    
                    @if($this->cartItemsCount > 0)
                        <span class="absolute -top-1 -right-1 bg-rose-500 text-white rounded-full w-5 h-5 text-[10px] flex items-center justify-center font-bold border-2 border-white shadow-sm ring-1 ring-rose-500/20 animate-pulse">
                            {{ min($this->cartItemsCount, 99) }}
                        </span>
                    @endif
                </div>
                <span class="text-[10px] font-bold tracking-wide">Keranjang</span>
            </button>

            {{-- Transactions Button --}}
            <button wire:click="openLoadModal"
                class="nav-button flex flex-col items-center justify-center h-full text-slate-400 hover:text-orange-500 transition-colors active:scale-95"
                data-section="order">
                 <div class="p-1 rounded-xl transition-all duration-200 nav-icon-bg">
                    <svg class="w-6 h-6 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                 </div>
                <span class="text-[10px] font-bold tracking-wide">Transaksi</span>
            </button>
        </div>
    </nav>

    {{-- MODAL EDIT CATATAN --}}
    @if($editingNotesIndex !== null && isset($items[$editingNotesIndex]))
    <!-- Modal container -->
    <div class="fixed inset-0 z-[9999] overflow-y-auto" 
        x-data="{ open: true }"
        x-show="open"
        style="position: fixed; z-index: 9999;">
        
        <!-- Overlay -->
        <div class="fixed inset-0 backdrop-blur-sm bg-black/20" 
            @click="$wire.cancelEditNotes()"></div>

        <!-- Modal content -->
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0"
            style="position: relative; z-index: 10000;">
            
            <!-- Spacer untuk alignment -->
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <!-- Modal panel -->
            <div class="inline-block w-full max-w-md my-8 text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-16 sm:align-middle"
                @click.stop>
                
                <!-- Header -->
                <div class="px-6 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-t-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-white">
                                <span class="flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                    Catatan untuk {{ $items[$editingNotesIndex]['name'] ?? 'Item' }}
                                </span>
                            </h3>
                            <p class="text-sm text-blue-100 mt-1">
                                Tambahkan catatan khusus untuk item ini
                            </p>
                        </div>
                        <button @click="$wire.cancelEditNotes()" 
                                class="text-white hover:text-gray-200 transition cursor-pointer">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Content -->
                <div class="px-6 py-4 bg-gray-50">
                    <!-- Input Notes -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Catatan Khusus
                            <span class="text-xs text-gray-500">(contoh: "Pedas", "Tanpa acar", "Tambah keju")</span>
                        </label>
                        <textarea 
                            wire:model="itemNotes"
                            rows="3"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:outline-none text-sm"
                            placeholder="Masukkan catatan khusus untuk item ini..."
                            autofocus></textarea>
                    </div>
                    
                    <!-- Examples -->
                    <div class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <p class="text-xs font-medium text-yellow-800 mb-2 flex items-center">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Contoh catatan populer:
                        </p>
                        <div class="grid grid-cols-2 gap-2 text-xs text-yellow-700">
                            <span class="bg-yellow-100 px-2 py-1 rounded">• Pedas level 3</span>
                            <span class="bg-yellow-100 px-2 py-1 rounded">• Tanpa acar</span>
                            <span class="bg-yellow-100 px-2 py-1 rounded">• Kurangi garam</span>
                            <span class="bg-yellow-100 px-2 py-1 rounded">• Tambah telur</span>
                            <span class="bg-yellow-100 px-2 py-1 rounded">• Tanpa bawang</span>
                            <span class="bg-yellow-100 px-2 py-1 rounded">• Bungkus</span>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-3 bg-gray-100 border-t border-gray-200 rounded-b-lg flex justify-end space-x-3">
                    <button 
                        @click="$wire.cancelEditNotes()"
                        class="cursor-pointer px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition">
                        Batal
                    </button>
                    <button 
                        wire:click="saveItemNotes"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-50 cursor-not-allowed"
                        class="cursor-pointer px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
                        <span class="flex items-center">
                            <svg wire:loading.remove wire:target="saveItemNotes" 
                                class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                            </svg>
                            <svg wire:loading wire:target="saveItemNotes" 
                                class="w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Simpan Catatan
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- MODAL MERGE BILL - FIXED OVERLAY --}}
    @if($showMergeModal)
        <div class="fixed inset-0 z-[60] flex items-center justify-center p-4">
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" 
                 wire:click="$set('showMergeModal', false)"></div>

            <!-- Modal Box -->
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-5xl h-[85vh] flex flex-col overflow-hidden animate-in zoom-in-95 duration-200">
                
                <!-- Header -->
                <div class="px-6 py-4 bg-white border-b border-gray-100 flex justify-between items-center flex-shrink-0 z-10">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center text-purple-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-gray-900">Merger Bill</h2>
                            <p class="text-sm text-gray-500">Gabungkan beberapa transaksi draft menjadi satu invoice.</p>
                        </div>
                    </div>
                    <button wire:click="$set('showMergeModal', false)" class="text-gray-400 hover:text-gray-600 transition-colors p-2 hover:bg-gray-100 rounded-full cursor-pointer">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Body -->
                <div class="flex-1 overflow-hidden flex flex-col md:flex-row">
                    <!-- Left Panel: Draft List -->
                    <div class="flex-1 overflow-y-auto p-4 bg-gray-50/50 border-r border-gray-200">
                        @php
                            $salesData = is_array($availableSales) ? $availableSales : [];
                        @endphp

                        <div class="flex justify-between items-center mb-4 px-2">
                            <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wide flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path></svg>
                                Pilih Transaksi (Draft)
                            </h3>
                            <span class="text-xs px-2 py-1 bg-white border border-gray-200 rounded-md text-gray-500 font-mono">
                                {{ count($salesData) }} Available
                            </span>
                        </div>
                        
                        @if(empty($salesData))
                            <div class="flex flex-col items-center justify-center h-64 text-gray-400">
                                <svg class="w-16 h-16 mb-4 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                <p>Tidak ada transaksi draft tersedia.</p>
                            </div>
                        @else
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                                @foreach($salesData as $sale)
                                    @php
                                        $saleId = $sale['id'];
                                        $isSelected = in_array($saleId, $selectedSalesToMerge);
                                        $isTarget = $mergeTargetSale == $saleId;
                                    @endphp
                                    <div wire:click="toggleSelectSale({{ $saleId }})" 
                                         class="group cursor-pointer relative p-4 rounded-xl border-2 transition-all duration-200 
                                         {{ $isTarget ? 'border-green-500 bg-green-50/50 ring-1 ring-green-500 shadow-md' : 
                                            ($isSelected ? 'border-purple-500 bg-purple-50/50 shadow-sm' : 'border-gray-200 bg-white hover:border-purple-300 hover:shadow-md') }}">
                                        
                                        <!-- Checkbox Indicator -->
                                        <div class="absolute top-3 right-3 z-10">
                                            @if($isTarget)
                                                <span class="inline-flex items-center gap-1 bg-green-500 text-white text-[10px] font-bold px-2 py-1 rounded-full shadow-sm">
                                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                    TARGET
                                                </span>
                                            @elseif($isSelected)
                                                <div class="w-6 h-6 bg-purple-500 rounded-full flex items-center justify-center text-white shadow-sm transition-transform scale-100">
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                                </div>
                                            @else
                                                <div class="w-6 h-6 border-2 border-gray-300 rounded-full group-hover:border-purple-400 transition-colors"></div>
                                            @endif
                                        </div>

                                        <div class="pr-8">
                                            <div class="font-bold text-gray-900 text-base font-mono mb-1">
                                                {{ $sale['invoice_number'] }}
                                            </div>
                                            <div class="text-sm text-gray-600 font-medium truncate mb-2">
                                                {{ $sale['customer_name'] ?? 'Pelanggan Umum' }}
                                            </div>
                                            <div class="flex items-end justify-between">
                                                <div>
                                                    <span class="text-xs text-gray-400 block mb-0.5">Total</span>
                                                    <div class="font-bold text-purple-700 text-lg leading-none">
                                                        Rp{{ number_format($sale['final_total'] ?? 0, 0, ',', '.') }}
                                                    </div>
                                                </div>
                                                <div class="text-right">
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-gray-100 text-gray-600">
                                                        {{ isset($sale['items']) ? count($sale['items']) : 0 }} items
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Target Button Overlay -->
                                        @if($isSelected && !$isTarget)
                                            <div class="mt-3 pt-3 border-t border-purple-100/50 flex justify-end">
                                                <button wire:click.stop="setMergeTarget({{ $saleId }})" 
                                                    class="cursor-pointer text-xs font-bold text-green-700 bg-green-100 hover:bg-green-200 px-3 py-1.5 rounded-lg transition-colors flex items-center gap-1 shadow-sm">
                                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                                    Jadikan Tujuan
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <!-- Right Panel: Summary -->
                    <div class="w-full md:w-96 bg-white flex flex-col border-l border-gray-200 z-20 shadow-[-4px_0_15px_-3px_rgba(0,0,0,0.05)]">
                        <div class="p-6 flex-1 flex flex-col">
                            <h3 class="font-bold text-gray-900 mb-6 flex items-center gap-2 pb-4 border-b border-gray-100">
                                <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                Ringkasan Merge
                            </h3>

                            <!-- Flow Visualization -->
                            <div class="flex-1 space-y-4">
                                <!-- Source List -->
                                <div class="space-y-2">
                                    <div class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">
                                        Sumber ({{ max(0, count($selectedSalesToMerge) - ($mergeTargetSale ? 1 : 0)) }})
                                        <span class="font-normal normal-case text-red-400 ml-1 text-[10px]">(Akan Dihapus)</span>
                                    </div>
                                    <div class="bg-gray-50 rounded-lg p-3 space-y-2 max-h-40 overflow-y-auto border border-gray-100">
                                        @php $hasSources = false; @endphp
                                        @foreach($selectedSalesToMerge as $id)
                                            @if($id != $mergeTargetSale)
                                                @php 
                                                    $s = collect($salesData)->firstWhere('id', $id);
                                                    $hasSources = true;
                                                @endphp
                                                <div class="flex items-center justify-between text-sm group">
                                                    <div class="flex items-center gap-2 text-gray-600">
                                                        <span class="w-1.5 h-1.5 bg-red-400 rounded-full"></span>
                                                        <span class="font-mono">{{ $s['invoice_number'] ?? 'Unknown' }}</span>
                                                    </div>
                                                    <span class="text-xs text-gray-400">Rp{{ number_format($s['final_total'] ?? 0, 0, ',','.') }}</span>
                                                </div>
                                            @endif
                                        @endforeach
                                        
                                        @if(!$hasSources)
                                            <div class="text-xs text-gray-400 italic text-center py-2">Belum ada transaksi sumber</div>
                                        @endif
                                    </div>
                                </div>

                                <!-- Arrow Down -->
                                <div class="flex justify-center py-2">
                                    <div class="bg-gray-100 p-2 rounded-full text-gray-400 shadow-inner">
                                        <svg class="w-5 h-5 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                                    </div>
                                </div>

                                <!-- Target Display -->
                                <div>
                                    <div class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Target Invoice</div>
                                    @if($mergeTargetSale)
                                        @php $target = collect($salesData)->firstWhere('id', $mergeTargetSale); @endphp
                                        <div class="bg-green-50 border-2 border-green-500 border-dashed rounded-xl p-4 relative overflow-hidden">
                                            <div class="absolute top-0 right-0 p-2 opacity-10">
                                                <svg class="w-20 h-20 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                                            </div>
                                            <div class="relative z-10">
                                                <div class="text-xs text-green-600 font-bold mb-1">FINAL INVOICE</div>
                                                <div class="text-xl font-black text-gray-900 font-mono mb-1">{{ $target['invoice_number'] ?? '...' }}</div>
                                                <div class="text-sm text-green-800">{{ $target['customer_name'] ?? '...' }}</div>
                                            </div>
                                        </div>
                                    @else
                                        <div class="border-2 border-dashed border-gray-300 rounded-xl p-6 text-center">
                                            <span class="text-sm text-gray-400 font-medium">Pilih salah satu &<br>klik "Jadikan Tujuan"</span>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Footer Actions -->
                            <div class="mt-auto pt-6 border-t border-gray-100">
                                <!-- Est Total -->
                                <div class="flex justify-between items-end mb-4">
                                    <span class="text-sm text-gray-500 font-medium">Estimasi Total</span>
                                    <div class="text-2xl font-bold text-purple-600">
                                        Rp{{ number_format(collect($salesData)->whereIn('id', $selectedSalesToMerge)->sum('final_total'), 0, ',', '.') }}
                                    </div>
                                </div>

                                <button wire:click="processMergeBill"
                                    wire:loading.attr="disabled"
                                    class="w-full py-3.5 px-4 bg-purple-600 hover:bg-purple-700 active:bg-purple-800 text-white font-bold rounded-xl shadow-lg shadow-purple-200 transition-all transform hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none flex justify-center items-center gap-2 cursor-pointer
                                    {{ count($selectedSalesToMerge) < 2 || !$mergeTargetSale ? 'opacity-50 pointer-events-none' : '' }}">
                                    <svg wire:loading wire:target="processMergeBill" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    <span>GABUNGKAN SEKARANG</span>
                                </button>
                                <button wire:click="$set('showMergeModal', false)" class="w-full mt-3 py-2 text-sm text-gray-500 hover:text-gray-700 font-medium cursor-pointer">
                                    Batal
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
    {{-- Include Modal Components --}}
    <livewire:pos-cash-in-modal />
    <livewire:pos-load-modal />
    <livewire:pos-payment-modal />
    @livewire('pos-notification')

    {{-- SEACRH --}}
    <script>
        // ✅ SINGLE SCRIPT SOLUTION - NO DUPLICATES
        // Global variables
        let currentSection = 'products';
        let searchInput = null;
        let isSearchFocused = false;
        let resizeTimeout;
        
        // Function to switch section
        function switchSection(section) {
            if (currentSection === section) return;
            
            console.log(`🔄 Switching to: ${section}`);
            currentSection = section;
            
            // Hide all mobile sections
            document.querySelectorAll('.mobile-section').forEach(el => {
                el.style.display = 'none';
            });
            
            // Show selected section
            const targetSection = document.getElementById(`mobile-${section}-section`);
            if (targetSection) {
                targetSection.style.display = 'flex';
                
                // Auto focus ke search jika masuk ke products section
                if (section === 'products') {
                    setTimeout(() => {
                        focusSearchInput();
                    }, 150);
                } else {
                    isSearchFocused = false;
                }
            }
            
            // Update nav buttons
            updateNavButtons(section);
        }
        
        // Function to update nav buttons
        function updateNavButtons(activeSection) {
            document.querySelectorAll('.nav-button').forEach(btn => {
                const btnSection = btn.getAttribute('data-section');
                if (btnSection === activeSection) {
                    btn.classList.add('nav-active', 'bg-blue-600', 'text-white');
                    btn.classList.remove('bg-gray-100', 'text-gray-600');
                } else {
                    btn.classList.remove('nav-active', 'bg-blue-600', 'text-white');
                    btn.classList.add('bg-gray-100', 'text-gray-600');
                }
            });
        }
        
        // Function to focus search input
        function focusSearchInput() {
            searchInput = document.getElementById('product-search-input');
            if (searchInput) {
                searchInput.focus();
                isSearchFocused = true;
                console.log('✅ Search focused');
            }
        }
        
        // Function to handle keyboard shortcuts
        function handleKeyboardShortcuts(e) {
            const activeElement = document.activeElement;
            const isTextInput = activeElement.tagName === 'INPUT' || 
                            activeElement.tagName === 'TEXTAREA';
            
            // 🔍 SHORTCUT: '/' to focus search (only when not typing in input)
            if (e.key === '/' && !isTextInput) {
                e.preventDefault();
                
                if (currentSection !== 'products') {
                    switchSection('products');
                    setTimeout(focusSearchInput, 200);
                } else {
                    focusSearchInput();
                }
            }
            
            // 🔍 SHORTCUT: 'Escape' to clear or blur search
            if (e.key === 'Escape' && isSearchFocused) {
                e.preventDefault();
                if (searchInput && searchInput.value) {
                    searchInput.value = '';
                    searchInput.dispatchEvent(new Event('input', { bubbles: true }));
                    console.log('🗑️ Search cleared');
                } else if (searchInput) {
                    searchInput.blur();
                    isSearchFocused = false;
                }
            }
            
            // 🔍 SHORTCUT: 'Ctrl+K' or 'Cmd+K' for search
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                if (currentSection !== 'products') {
                    switchSection('products');
                    setTimeout(focusSearchInput, 200);
                } else {
                    focusSearchInput();
                }
            }
        }
        
        // Initialize everything
        function initPOS() {
            console.log('🚀 POS Navigation Initialized');
            
            // Default section for mobile/tablet (< 1024px)
            if (window.innerWidth < 1024) {
                switchSection('products');
            }
            
            // Get search input
            searchInput = document.getElementById('product-search-input');
            
            // Add event listeners
            if (searchInput) {
                searchInput.addEventListener('focus', () => {
                    isSearchFocused = true;
                });
                
                searchInput.addEventListener('blur', () => {
                    isSearchFocused = false;
                });
                
                // Auto focus on page load for products section
                if (currentSection === 'products') {
                    setTimeout(focusSearchInput, 300);
                }
            }
            
            // Add global keyboard listener
            document.addEventListener('keydown', handleKeyboardShortcuts);
            
            // Listen for Livewire events if available
            if (typeof Livewire !== 'undefined') {
                Livewire.hook('message.processed', () => {
                    if (currentSection === 'products' && isSearchFocused) {
                        setTimeout(() => {
                            focusSearchInput();
                        }, 50);
                    }
                });
            }
        }
        
        // Handle window resize
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(function() {
                if (window.innerWidth >= 1024) {
                    // Show all sections on desktop (LG+)
                    document.querySelectorAll('.mobile-section').forEach(el => {
                        el.style.display = 'flex';
                    });
                } else {
                    // Keep current section on mobile
                    switchSection(currentSection);
                }
            }, 100);
        });
        
        // Initialize when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initPOS);
        } else {
            initPOS();
        }
    </script>

    {{-- TAMBAHKAN INI DI BAWAH SCRIPT UTAMA --}}
    <script>
        // Handle Livewire not defined error
        if (typeof Livewire === 'undefined') {
            console.warn('⚠️ Livewire not loaded yet, retrying...');
            
            // Retry after a delay
            setTimeout(() => {
                if (typeof Livewire !== 'undefined') {
                    console.log('✅ Livewire loaded successfully');
                    // Re-init if needed
                    if (typeof initPOS === 'function') {
                        initPOS();
                    }
                }
            }, 1000);
        }
    </script>

    <style>
        /* 🔍 SEARCH INPUT STYLING */
        #product-search-input {
            transition: all 0.2s ease;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        #product-search-input:focus {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3), 0 1px 3px rgba(0,0,0,0.1);
            transform: translateY(-1px);
        }
        
        #product-search-input::placeholder {
            color: #9ca3af;
            font-size: 0.875rem;
        }
        
        /* SEARCH RESULTS COUNT */
        .search-results-count {
            font-size: 0.75rem;
            color: #6b7280;
            margin-top: 0.25rem;
            text-align: right;
        }
        
        /* QUICK ADD NUMBER BADGES - DISABLE FOR NOW */
        .quick-add-number {
            position: absolute;
            top: 2px;
            left: 2px;
            width: 18px;
            height: 18px;
            background: rgba(59, 130, 246, 0.9);
            color: white;
            border-radius: 50%;
            font-size: 10px;
            font-weight: bold;
            display: none; /* ❌ DISABLE QUICK ADD FOR NOW */
            align-items: center;
            justify-content: center;
            z-index: 5;
            opacity: 0.9;
        }
        
        /* Keyboard shortcut hint */
        kbd {
            font-family: monospace;
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            padding: 2px 6px;
            font-size: 0.75rem;
        }
    </style>

    <style>
        /* Loading states */
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }
        
        /* Better input styles */
        input[type="number"] {
            -moz-appearance: textfield;
        }
        
        input[type="number"]::-webkit-outer-spin-button,
        input[type="number"]::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        
        /* Smooth transitions */
        .mobile-section {
            transition: opacity 0.2s ease;
        }
        
        /* Optimized grid rendering */
        .mobile-grid {
            transform: translateZ(0);
            will-change: transform;
        }
    </style>

    <style>
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        /* Safe Area Utilities for Mobile */
        .safe-area-bottom {
            padding-bottom: constant(safe-area-inset-bottom); /* iOS 11.0 */
            padding-bottom: env(safe-area-inset-bottom); /* iOS 11.2+ */
        }
        .pb-safe {
            padding-bottom: constant(safe-area-inset-bottom);
            padding-bottom: env(safe-area-inset-bottom);
        }

            overflow: hidden;
        }
        
        .hide-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        .hide-scrollbar::-webkit-scrollbar {
            display: none;
        }

        /* Mobile grid adjustments */
        @media (max-width: 640px) {
            #mobile-cart-section {
                padding-bottom: 90px; /* Space untuk bottom nav */
            }
            
            .mobile-section {
                min-height: calc(100vh - 140px);
            }

            .mobile-grid {
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 8px !important;
            }
            
            .mobile-text-sm {
                font-size: 0.75rem !important;
            }
            
            .mobile-text-xs {
                font-size: 0.7rem !important;
            }
        }

        /* Desktop styles */
        @media (min-width: 1024px) {
            .mobile-section {
                display: flex !important;
            }
        }

        /* Fix untuk touch devices */
        @media (hover: none) and (pointer: coarse) {
            button, [role="button"] {
                min-height: 24px;
            }
            
            input, select, textarea {
                font-size: 16px;
            }
        }

        /* Better scroll behavior */
        .overflow-auto {
            scrollbar-width: thin;
            scrollbar-color: #cbd5e0 transparent;
        }

        .overflow-auto::-webkit-scrollbar {
            width: 4px;
        }

        .overflow-auto::-webkit-scrollbar-track {
            background: transparent;
        }

        .overflow-auto::-webkit-scrollbar-thumb {
            background-color: #cbd5e0;
            border-radius: 2px;
        }

    </style>

    <style>
        /* Mobile Bottom Navigation - FIXED */
        /* .mobile-bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            border-top: 1px solid #e5e7eb;
            z-index: 1000;
            padding: 12px 16px;
            box-shadow: 0 -4px 20px rgba(0,0,0,0.15);
        } */

        .safe-area-bottom {
            padding-bottom: max(12px, env(safe-area-inset-bottom));
        }

        /* Touch-friendly buttons */
        .touch-target {
            min-height: 10px;
        }

        /* Active state untuk nav buttons */
        .nav-active {
            background: #3b82f6 !important;
            color: white !important;
            transform: scale(0.98);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4) !important;
        }

        /* Adjust main content padding untuk mobile nav */
        @media (max-width: 640px) {
            .mobile-section {
                padding-bottom: 100px;
            }
        }
    </style>

    <style>
        /* Pastikan section keranjang memiliki padding bottom yang cukup */
        #mobile-cart-section {
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        /* Scrollable area untuk items */
        #mobile-cart-section .flex-1 {
            flex: 1;
            min-height: 0;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch; /* Smooth scrolling di iOS */
        }

        /* Fixed bottom action buttons untuk mobile */
        #mobile-cart-section .lg\\:hidden {
            position: sticky;
            bottom: 0;
            background: white;
            border-top: 1px solid #e5e7eb;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
        }

        /* Untuk desktop, hapus padding bottom */
        @media (min-width: 1024px) {
            #mobile-cart-section {
                padding-bottom: 0;
            }
        }

        /* Pastikan konten bisa discroll dengan baik */
        .mobile-section {
            min-height: calc(100vh - 140px); /* Kurangi lebih banyak untuk bottom nav */
        }

        /* Bottom nav yang fixed */
        /* Bottom nav yang fixed - Hanya muncul di mobile/tablet (<1024px) */
        @media (max-width: 1023px) {
            .mobile-bottom-nav {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                background: white;
                border-top: 1px solid #e5e7eb;
                z-index: 1000;
                padding: 12px 16px;
                box-shadow: 0 -4px 20px rgba(0,0,0,0.15);
                display: block; /* Ensure visible on mobile */
            }
        }
        
        @media (min-width: 1024px) {
            .mobile-bottom-nav {
                display: none !important; /* Force hide on desktop */
            }
        }
    </style>

</div>
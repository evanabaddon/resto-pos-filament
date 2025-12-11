{{-- resources/views/filament/pages/pos.blade.php --}}
<div class="h-full flex flex-col lg:flex-row bg-gray-50 overflow-hidden min-h-0">

    {{-- 💰 PRODUK SECTION --}}
    <div id="mobile-products-section" class="mobile-section lg:flex-1 lg:min-w-[60%] w-full h-full flex flex-col border-r border-gray-200 bg-white shadow-sm min-h-0 overflow-hidden">
        {{-- Header Produk --}}
        <div class="px-3 py-2 border-b border-gray-100 bg-gradient-to-r from-blue-50 to-indigo-50 flex-shrink-0">
            <div class="flex items-center justify-between">
                <div class="flex-1 min-w-0">
                    <h1 class="text-base font-bold text-gray-900 truncate mobile-text-sm">Menu Produk</h1>
                    <p class="text-xs text-gray-600 mt-1 truncate mobile-text-xs">Pilih produk untuk ditambahkan</p>
                </div>
                <div class="text-right ml-2 flex-shrink-0">
                    <p class="text-xs font-medium text-gray-500 mobile-text-xs">Kategori</p>
                    <p class="text-sm font-semibold text-blue-600 mobile-text-sm">{{ $selectedCategory }}</p>
                </div>
            </div>
        </div>

        {{-- 🔍 SEARCH BAR --}}
        <div class="px-3 py-2 bg-white border-b border-gray-100 flex-shrink-0">
            <div class="relative">
                <input 
                    id="product-search-input"
                    type="text"
                    wire:model.live="searchQuery"
                    placeholder="Cari produk (tekan / untuk langsung mencari...)"
                    class="w-full bg-gray-50 border border-gray-300 rounded-lg py-2.5 pl-10 pr-4 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:outline-none transition mobile-text-sm"
                    autocomplete="off"
                    x-data
                    @keydown.window.prevent.slash="$el.focus()"
                    x-ref="searchInput">
                
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
                        class="absolute right-3 top-1/2 transform -translate-y-1/2 cursor-pointer text-gray-400 hover:text-gray-600 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                @endif
                
                {{-- Debug info (optional) --}}
                <div class="absolute right-12 top-1/2 transform -translate-y-1/2">
                    <span class="text-xs text-gray-400 hidden lg:block">
                        <kbd class="px-1 py-0.5 bg-gray-100 rounded text-xs">/</kbd> to search
                    </span>
                </div>
            </div>
            
            {{-- Search status --}}
            @if(!empty($searchQuery))
                <div class="mt-1 text-xs text-gray-500">
                    <span>Mencari: </span>
                    <span class="font-semibold text-blue-600">{{ $searchQuery }}</span>
                    <span class="ml-2">•</span>
                    <span class="ml-2">{{ count($products) }} hasil</span>
                </div>
            @endif
        </div>

        {{-- Filter Kategori - Horizontal Scroll Mobile --}}
        <div class="px-3 py-2 bg-white border-b border-gray-100 flex-shrink-0">
            <div class="flex space-x-2 overflow-x-auto hide-scrollbar">
                @foreach ($categories as $category)
                    <button wire:click="setCategory('{{ $category }}')"
                        class="cursor-pointer flex-shrink-0 px-3 py-2 rounded-lg text-xs font-medium transition-all duration-200 border whitespace-nowrap touch-target
                            {{ $selectedCategory === $category 
                                ? 'bg-blue-600 text-white border-blue-600' 
                                : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                        {{ $category }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Grid Produk - Mobile Optimized --}}
        <div class="flex-1 overflow-auto p-2">
            <div class="grid mobile-grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-2 auto-rows-[7rem]">
                @forelse ($products as $index => $product)
                    <div wire:click="quickAddProduct({{ $product->id }})"
                        class="cursor-pointer group bg-white rounded-lg border border-gray-200 hover:border-blue-300 hover:shadow-sm transition-all duration-200 p-2 flex flex-col items-center relative overflow-hidden h-full touch-target search-result-item">
                        
                        {{-- 🔢 TAMBAHKAN NOMOR QUICK ADD (1-9) --}}
                        @if($index < 9 && !empty($searchQuery))
                            <div class="quick-add-number">
                                {{ $index + 1 }}
                            </div>
                        @endif
                        
                        {{-- Stock Badge --}}
                        @if($product->type !== 'produced' && $product->type !== 'bar')
                            <div class="absolute top-1 right-1 z-10">
                                <span class="inline-flex items-center px-1 py-0.5 rounded text-[10px] font-medium 
                                    {{ $product->stock > 10 ? 'bg-green-100 text-green-800' : 
                                        ($product->stock > 0 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                    {{ intval($product->stock) }}
                                </span>
                            </div>
                        @endif
                        
                        {{-- Product Image --}}
                        <div class="w-8 h-8 mb-1 rounded bg-gray-100 overflow-hidden flex items-center justify-center flex-shrink-0">
                            <img src="{{ $product->image_url }}"
                                alt="{{ $product->name }}"
                                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-200"
                                loading="lazy"
                                decoding="async">
                        </div>

                        {{-- Product Info --}}
                        <div class="text-center flex-1 flex flex-col justify-between w-full min-h-0">
                            <div class="flex-1">
                                <h3 class="text-xs font-semibold text-gray-900 line-clamp-2 leading-tight break-words mobile-text-xs">
                                    {{ $product->name }}
                                </h3>
                            </div>
                            <div class="mt-1">
                                <p class="text-xs font-bold text-blue-600 leading-none mobile-text-xs">
                                    Rp{{ number_format($product->sell_price, 0, ',', '.') }}
                                </p>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center py-8">
                        <div class="w-10 h-10 mx-auto mb-2 bg-gray-100 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                            </svg>
                        </div>
                        <h3 class="text-sm font-medium text-gray-900 mb-1 mobile-text-sm">Tidak ada produk</h3>
                        <p class="text-gray-500 text-xs mobile-text-xs">Tidak ada produk untuk kategori ini.</p>
                    </div>
                @endforelse
            </div>
            {{-- 🔍 TAMBAHKAN SEARCH RESULTS INFO --}}
            @if(!empty($searchQuery))
            <div class="px-2 py-1 text-center">
                <p class="text-xs text-gray-600 search-results-count">
                    Menampilkan {{ count($products) }} hasil untuk "<span class="font-semibold">{{ $searchQuery }}</span>"
                </p>
            </div>
            @endif
        </div>
    </div>

    {{-- 🧺 KERANJANG SECTION - FIXED FOR DESKTOP & MOBILE --}}
    <div id="mobile-cart-section" class="mobile-section lg:flex lg:w-[400px] xl:w-[450px] w-full h-full flex-col bg-white shadow-lg border-l border-gray-200 flex-shrink-0 min-h-0 overflow-hidden">
        {{-- Header Keranjang --}}
        <div class="p-3 bg-gradient-to-r from-green-50 to-emerald-50 border-b border-gray-100 flex-shrink-0">
            <div class="flex items-center justify-between mb-2">
                <div class="flex-1 min-w-0">
                    <h1 class="text-base font-bold text-gray-900 truncate mobile-text-sm">Keranjang</h1>
                    <p class="text-xs text-gray-600 mt-1 mobile-text-xs">Kelola pesanan customer</p>
                </div>
                <div class="text-right ml-2 flex-shrink-0">
                    <p class="text-xs font-medium text-gray-500 mobile-text-xs">No. Pesanan</p>
                    <p class="text-sm font-bold text-gray-900 mobile-text-sm">{{ $orderNumber }}</p>
                </div>
            </div>

            {{-- Customer & Order Info --}}
            <div class="bg-white rounded border border-gray-200 p-2 space-y-2">
                {{-- Tipe Order --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1 mobile-text-xs">Tipe Order</label>
                    <div class="grid grid-cols-2 gap-1">
                        <button 
                            wire:click="setOrderType('Dine In')"
                            class="w-full cursor-pointer px-2 py-2 rounded text-xs font-semibold transition-all duration-200 border touch-target
                                {{ $orderType === 'Dine In'
                                    ? 'bg-green-600 text-white border-green-600'
                                    : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                            🍽️ Dine In
                        </button>
                        <button 
                            wire:click="setOrderType('Take Away')"
                            class="w-full cursor-pointer px-2 py-2 rounded text-xs font-semibold transition-all duration-200 border touch-target
                                {{ $orderType === 'Take Away'
                                    ? 'bg-green-600 text-white border-green-600'
                                    : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                            🥡 Take Away
                        </button>
                    </div>
                </div>

                {{-- Nama Customer --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1 mobile-text-xs">
                        Nama Customer
                    </label>
                    <input 
                        type="text" 
                        wire:model="customerName"
                        class="w-full bg-gray-50 border border-gray-300 rounded py-2 px-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 focus:outline-none transition mobile-text-sm"
                        placeholder="Nama pelanggan...">
                </div>

                {{-- Info Kasir --}}
                <div class="flex items-center justify-between text-xs mobile-text-xs">
                    <span class="text-gray-600">Kasir:</span>
                    <span class="font-semibold text-gray-900 truncate ml-2">{{ $this->getNameUserLogin() }}</span>
                </div>
            </div>
        </div>

        {{-- List Item --}}
        <div class="flex-1 overflow-auto p-2 space-y-1 min-h-0">
            @forelse ($items as $index => $item)
                <div class="bg-white border border-gray-200 rounded p-2 hover:shadow-sm transition-all duration-200">
                    <div class="flex items-start justify-between">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between mb-1">
                                <h3 class="font-semibold text-gray-900 text-sm truncate flex-1 mr-2 mobile-text-sm">{{ $item['name'] }}</h3>
                                <p class="text-sm font-bold text-gray-900 whitespace-nowrap mobile-text-sm">
                                    Rp{{ number_format($item['subtotal'], 0, ',', '.') }}
                                </p>
                            </div>
                            <p class="text-xs text-gray-500 mb-2 mobile-text-xs">
                                Rp{{ number_format($item['price'], 0, ',', '.') }} × {{ $item['quantity'] }}
                            </p>
                            
                            {{-- TAMBAHKAN NOTES DISPLAY --}}
                            @if(!empty($item['notes']))
                                <div class="mb-2 p-2 bg-yellow-50 border border-yellow-200 rounded">
                                    <div class="flex items-start">
                                        <svg class="w-3 h-3 text-yellow-500 mr-1 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        <span class="text-xs text-yellow-700 font-medium break-words">{{ $item['notes'] }}</span>
                                    </div>
                                </div>
                            @endif
                            
                            {{-- PERBAIKAN: Input Quantity dengan Text Input --}}
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    {{-- Tombol Decrement --}}
                                    <button wire:click="decrementQuantity({{ $index }})"
                                        class="cursor-pointer w-6 h-6 flex items-center justify-center bg-gray-100 hover:bg-gray-200 rounded text-gray-600 font-semibold transition text-xs touch-target">
                                        −
                                    </button>
                                    
                                    {{-- Input Text untuk Quantity --}}
                                    <input 
                                        type="number" 
                                        wire:model.lazy="items.{{ $index }}.quantity"
                                        wire:change="updateQuantityFromInput({{ $index }}, $event.target.value)"
                                        min="1"
                                        class="w-12 text-center border border-gray-300 rounded py-1 text-xs font-semibold focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        onfocus="this.select()">
                                    
                                    {{-- Tombol Increment --}}
                                    <button wire:click="incrementQuantity({{ $index }})"
                                        class="cursor-pointer w-6 h-6 flex items-center justify-center bg-gray-100 hover:bg-gray-200 rounded text-gray-600 font-semibold transition text-xs touch-target">
                                        +
                                    </button>
                                    
                                    {{-- TOMBOL TAMBAH/EDIT CATATAN --}}
                                    <button wire:click="openEditNotes({{ $index }})"
                                        class="cursor-pointer px-2 py-1 bg-blue-50 hover:bg-blue-100 text-blue-600 rounded text-xs font-medium transition border border-blue-200 ml-2 touch-target">
                                        @if(empty($item['notes']))
                                            <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                            </svg>
                                            Catatan
                                        @else
                                            <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                            Edit
                                        @endif
                                    </button>
                                </div>
                                <button wire:click="removeItem({{ $index }})"
                                    class="cursor-pointer text-xs text-red-600 hover:text-red-700 font-medium flex items-center transition ml-2 mobile-text-xs touch-target">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                    Hapus
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                {{-- Empty state --}}
            @endforelse
        </div>

        {{-- Ringkasan & Aksi --}}
         <div class="border-t border-gray-200 bg-white flex-shrink-0">
            {{-- Input Diskon --}}
            <div class="p-2 border-b border-gray-100">
                <label class="block text-xs font-semibold text-gray-900 mb-1 mobile-text-xs">Kode Diskon</label>
                <div class="flex space-x-1">
                    <input 
                        type="text" 
                        wire:model.defer="discountCodeInput"
                        class="flex-1 bg-gray-50 border border-gray-300 rounded py-2 px-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:outline-none transition min-w-0 mobile-text-sm"
                        placeholder="Kode promo...">
                    <button 
                        wire:click="applyDiscountCode"
                        class="cursor-pointer bg-blue-600 hover:bg-blue-700 text-white px-2 py-2 rounded text-xs font-semibold shadow-sm hover:shadow-md transition whitespace-nowrap flex-shrink-0 touch-target">
                        Pakai
                    </button>
                </div>
                @if ($discountMessage)
                    <p class="text-xs mt-1 font-medium {{ $discountApplied ? 'text-green-600' : 'text-red-600' }} mobile-text-xs">
                        {{ $discountMessage }}
                    </p>
                @endif
            </div>

            {{-- Ringkasan Harga --}}
            <div class="p-2 space-y-1">
                <div class="flex justify-between text-xs text-gray-600 mobile-text-xs">
                    <span>Subtotal</span>
                    <span>Rp{{ number_format($total, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between text-xs text-gray-600 mobile-text-xs">
                    <span>Pajak (10%)</span>
                    <span>Rp{{ number_format($tax, 0, ',', '.') }}</span>
                </div>
                @if($discount > 0)
                    <div class="flex justify-between text-xs text-green-600 mobile-text-xs">
                        <span>Diskon</span>
                        <span>- Rp{{ number_format($discount, 0, ',', '.') }}</span>
                    </div>
                @endif
                <div class="border-t border-gray-200 pt-1">
                    <div class="flex justify-between text-sm font-bold text-gray-900 mobile-text-sm">
                        <span>Total</span>
                        <span>Rp{{ number_format($finalTotal, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            {{-- Tombol Aksi untuk Mobile --}}
            <div class="lg:hidden border-t border-gray-200 bg-white p-3 space-y-2 sticky bottom-0 z-10">
                <div class="grid grid-cols-2 gap-2">
                    <button wire:click="mobileSaveSale"
                        class="cursor-pointer w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded font-bold text-sm shadow-md hover:shadow-lg transition touch-target">
                        💾 SIMPAN
                    </button>
                    <button wire:click="openPaymentModalMobile" 
                            class="cursor-pointer w-full bg-green-600 hover:bg-green-700 text-white py-3 rounded font-bold text-sm shadow-md hover:shadow-lg transition touch-target"
                            {{ !$saleId ? 'disabled' : '' }}>
                        💳 BAYAR
                    </button>
                </div>
            </div>

            {{-- Tombol Aksi untuk Desktop --}}
            <div class="hidden lg:block p-3 border-t border-gray-100 bg-gray-50 space-y-2">
                <div class="grid grid-cols-2 gap-2">
                    <button wire:click="saveSale"
                        class="cursor-pointer w-full bg-blue-600 hover:bg-blue-700 text-white py-2.5 rounded font-bold text-sm shadow-md hover:shadow-lg transition">
                        SIMPAN
                    </button>
                    <button wire:click="openPaymentModal({{ $saleId }})" 
                            class="cursor-pointer w-full bg-green-600 hover:bg-green-700 text-white py-2.5 rounded font-bold text-sm shadow-md hover:shadow-lg transition"
                            {{ !$saleId ? 'disabled' : '' }}>
                        BAYAR
                    </button>
                </div>
                <div class="grid grid-cols-3 gap-2">
                    {{-- TOMBOL MERGE BILL --}}
                    <button wire:click="openMergeModal"
                        class="cursor-pointer bg-purple-600 hover:bg-purple-700 text-white py-2 rounded font-semibold text-xs shadow-sm hover:shadow-md transition flex items-center justify-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                        </svg>
                        Merge Bill
                    </button>
                    
                    <button wire:click="openLoadModal"
                        class="cursor-pointer bg-yellow-500 hover:bg-yellow-600 text-white py-2 rounded font-semibold text-xs shadow-sm hover:shadow-md transition flex items-center justify-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                        Transaksi
                    </button>
                    
                    <button wire:click="cancelSale"
                        class="cursor-pointer bg-gray-200 hover:bg-gray-300 text-gray-800 py-2 rounded font-semibold text-xs transition flex items-center justify-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        Batal
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- 🧭 MOBILE BOTTOM NAVIGATION - HANYA UNTUK MOBILE --}}
    <nav class="mobile-bottom-nav sm:hidden safe-area-bottom">
        <div class="grid grid-cols-3 gap-2">
            {{-- Products Button --}}
            <button onclick="switchSection('products')"
                class="nav-button touch-target flex flex-col items-center justify-center p-3 rounded-lg bg-blue-600 text-white transition-all duration-200 shadow-lg"
                data-section="products">
                <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                <span class="text-xs font-semibold">Produk</span>
            </button>
            
            {{-- Cart Button --}}
            <button onclick="switchSection('cart')"
                class="nav-button touch-target flex flex-col items-center justify-center p-3 rounded-lg bg-gray-100 text-gray-600 transition-all duration-200 shadow-lg"
                data-section="cart">
                <div class="relative">
                    <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    
                    {{-- Cart Badge --}}
                    @if($this->cartItemsCount > 0)
                        <span class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 text-xs flex items-center justify-center font-bold border-2 border-white">
                            {{ min($this->cartItemsCount, 99) }}
                        </span>
                    @endif
                </div>
                <span class="text-xs font-semibold">Keranjang</span>
            </button>

            {{-- Order Button --}}
            <button wire:click="openLoadModal"
                class="nav-button touch-target flex flex-col items-center justify-center p-3 rounded-lg bg-gray-100 text-gray-600 transition-all duration-200 shadow-lg"
                data-section="order">
                <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                <span class="text-xs font-semibold">Order</span>
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
        @php
            $salesData = is_array($availableSales) ? $availableSales : [];
            $mergeCount = count($selectedSalesToMerge);
        @endphp
        
        <!-- Modal container dengan z-index yang sangat tinggi -->
        <div class="fixed inset-0 z-[99999] overflow-y-auto" x-data 
            style="position: fixed; z-index: 99999;">
            
            <!-- Overlay dengan z-index lebih rendah dari modal content -->
            <div class="fixed inset-0 backdrop-blur-md" 
                style="position: fixed; z-index: 99998;"
                @click="$wire.set('showMergeModal', false)"></div>

            <!-- Modal content dengan z-index lebih tinggi -->
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0"
                style="position: relative; z-index: 99999;">
                
                <!-- Spacer untuk alignment -->
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                
                <!-- Modal panel -->
                <div class="inline-block w-full max-w-4xl my-8 text-left align-bottom transition-all transform bg-white rounded-lg shadow-2xl sm:my-16 sm:align-middle"
                    style="position: relative; z-index: 99999;"
                    @click.stop>
                    
                    <!-- Header -->
                    <div class="px-6 py-4 bg-gradient-to-r from-purple-600 to-indigo-600 rounded-t-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-bold text-white">
                                    <span class="flex items-center">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                        </svg>
                                        MERGER BILL
                                    </span>
                                </h3>
                                <p class="text-sm text-purple-100 mt-1">
                                    Gabungkan beberapa transaksi menjadi satu
                                </p>
                            </div>
                            <button @click="$wire.set('showMergeModal', false)" 
                                    class="text-white hover:text-gray-200 transition cursor-pointer">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="px-6 py-4 bg-gray-50 max-h-[70vh] overflow-y-auto">
                        <!-- Instructions -->
                        <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                            <div class="flex">
                                <svg class="w-5 h-5 text-blue-500 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-blue-800">
                                        Cara menggunakan Merger Bill:
                                    </p>
                                    <ol class="text-xs text-blue-600 mt-1 list-decimal pl-4 space-y-1">
                                        <li>Pilih minimal 2 transaksi dengan mengeklik kartu transaksi</li>
                                        <li>Tentukan transaksi tujuan dengan mengeklik tombol "Jadikan Tujuan"</li>
                                        <li>Transaksi lainnya akan digabungkan ke transaksi tujuan</li>
                                        <li>Transaksi asal akan dihapus setelah digabungkan</li>
                                    </ol>
                                </div>
                            </div>
                        </div>

                        <!-- Selected Info -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div class="bg-white p-3 rounded-lg border border-gray-200 shadow-sm">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-semibold text-gray-700">Terpilih:</span>
                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs font-bold rounded-full">
                                        {{ $mergeCount }} transaksi
                                    </span>
                                </div>
                                <div class="text-xs text-gray-600">
                                    @if($mergeCount > 0)
                                        @php
                                            $totalAmount = 0;
                                            $totalItems = 0;
                                            try {
                                                $selectedSales = \App\Models\Sale::whereIn('id', $selectedSalesToMerge)->get();
                                                foreach ($selectedSales as $sale) {
                                                    $totalAmount += $sale->final_total ?? 0;
                                                    $totalItems += $sale->items->sum('quantity') ?? 0;
                                                }
                                            } catch (\Exception $e) {
                                                // ignore
                                            }
                                        @endphp
                                        <p>Total nilai: <span class="font-bold">Rp{{ number_format($totalAmount, 0, ',', '.') }}</span></p>
                                        <p>Total items: <span class="font-bold">{{ $totalItems }}</span> produk</p>
                                    @else
                                        <p class="text-gray-400">Belum ada transaksi terpilih</p>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="bg-white p-3 rounded-lg border border-gray-200 shadow-sm">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-semibold text-gray-700">Transaksi Tujuan:</span>
                                    @if($mergeTargetSale)
                                        @php
                                            $targetSale = \App\Models\Sale::find($mergeTargetSale);
                                        @endphp
                                        @if($targetSale)
                                            <span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-bold rounded-full">
                                                #{{ $targetSale->invoice_number }}
                                            </span>
                                        @else
                                            <span class="text-xs text-gray-400">Belum dipilih</span>
                                        @endif
                                    @else
                                        <span class="text-xs text-gray-400">Belum dipilih</span>
                                    @endif
                                </div>
                                @if($mergeTargetSale && isset($targetSale))
                                    <div class="text-xs text-gray-600">
                                        <p>Customer: <span class="font-bold">{{ $targetSale->customer_name }}</span></p>
                                        <p>Items: <span class="font-bold">{{ $targetSale->items->sum('quantity') }}</span> produk</p>
                                        <p>Total: <span class="font-bold">Rp{{ number_format($targetSale->final_total, 0, ',', '.') }}</span></p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Sales List -->
                        <div class="mb-4">
                            <h4 class="text-sm font-semibold text-gray-700 mb-2">DAFTAR TRANSAKSI DRAFT</h4>
                            @if(!empty($salesData))
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 p-1">
                                    @foreach($salesData as $sale)
                                        @php
                                            // DEBUG: Lihat struktur data
                                            // \Log::info('📝 Sale item data', [
                                            //     'sale_id' => $sale['id'] ?? 'no_id',
                                            //     'invoice_number' => $sale['invoice_number'] ?? 'no_invoice',
                                            //     'has_items' => isset($sale['items']) && !empty($sale['items'])
                                            // ]);
                                            
                                            $saleId = $sale['id'] ?? null;
                                            $invoiceNumber = $sale['invoice_number'] ?? 'N/A';
                                            $customerName = $sale['customer_name'] ?? 'N/A';
                                            $finalTotal = $sale['final_total'] ?? 0;
                                            $orderType = $sale['order_type'] ?? 'N/A';
                                            $createdAt = isset($sale['created_at']) ? \Carbon\Carbon::parse($sale['created_at'])->format('H:i') : 'N/A';
                                            $userName = isset($sale['user']['name']) ? $sale['user']['name'] : (isset($sale['user_name']) ? $sale['user_name'] : 'N/A');
                                            
                                            // Hitung total items
                                            $totalItems = 0;
                                            if (isset($sale['items']) && is_array($sale['items'])) {
                                                foreach ($sale['items'] as $item) {
                                                    $totalItems += $item['quantity'] ?? 0;
                                                }
                                            }
                                            
                                            // Check if selected
                                            $isSelected = in_array($saleId, $selectedSalesToMerge);
                                            $isTarget = $mergeTargetSale == $saleId;
                                            
                                            // Styling classes
                                            $borderClass = $isTarget ? 'border-2 border-green-500' : 
                                                        ($isSelected ? 'border-2 border-blue-500' : 'border-gray-200');
                                            $bgClass = $isTarget ? 'bg-green-50' : 
                                                    ($isSelected ? 'bg-blue-50' : 'bg-white hover:bg-gray-50');
                                        @endphp
                                        
                                        <div wire:click="toggleSelectSale({{ $saleId }})"
                                            class="cursor-pointer border rounded-lg p-3 transition-all duration-200 {{ $borderClass }} {{ $bgClass }} shadow-sm"
                                            onclick="event.stopPropagation();">
                                            
                                            <div class="flex justify-between items-start mb-2">
                                                <div class="flex-1">
                                                    <div class="flex items-center mb-1">
                                                        @if($isTarget)
                                                            <span class="mr-2 px-1.5 py-0.5 bg-green-100 text-green-800 text-xs font-bold rounded whitespace-nowrap">
                                                                TUJUAN
                                                            </span>
                                                        @endif
                                                        <span class="font-bold text-sm text-gray-900 truncate">
                                                            {{ $invoiceNumber }}
                                                        </span>
                                                    </div>
                                                    <p class="text-xs text-gray-600 truncate">
                                                        {{ $createdAt }} • {{ $customerName }}
                                                    </p>
                                                </div>
                                                <span class="text-sm font-bold {{ $isSelected || $isTarget ? 'text-blue-600' : 'text-gray-700' }} whitespace-nowrap ml-2">
                                                    Rp{{ number_format($finalTotal, 0, ',', '.') }}
                                                </span>
                                            </div>

                                            <div class="text-xs text-gray-600 mb-2">
                                                <div class="flex items-center justify-between">
                                                    <span>Items: {{ $totalItems }}</span>
                                                    <span>{{ $orderType }}</span>
                                                </div>
                                                <div class="mt-1">
                                                    <span>Kasir: {{ $userName }}</span>
                                                </div>
                                            </div>

                                            @if($isSelected && !$isTarget)
                                                <div class="flex justify-end mt-2">
                                                    <button 
                                                        wire:click="setMergeTarget({{ $saleId }})"
                                                        onclick="event.stopPropagation();"
                                                        class="text-xs px-2 py-1 bg-green-100 hover:bg-green-200 text-green-800 font-medium rounded transition">
                                                        Jadikan Tujuan
                                                    </button>
                                                </div>
                                            @endif

                                            <!-- Selection indicator -->
                                            <div class="flex items-center justify-between mt-2 pt-2 border-t border-gray-100">
                                                <div class="flex items-center">
                                                    <div class="w-4 h-4 rounded-full border flex items-center justify-center mr-2
                                                        {{ $isSelected ? ($isTarget ? 'bg-green-500 border-green-500' : 'bg-blue-500 border-blue-500') : 'border-gray-300' }}">
                                                        @if($isSelected)
                                                            <svg class="w-2.5 h-2.5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                            </svg>
                                                        @endif
                                                    </div>
                                                    <span class="text-xs {{ $isSelected ? 'font-medium' : 'text-gray-500' }}">
                                                        {{ $isSelected ? ($isTarget ? 'Tujuan' : 'Terpilih') : 'Klik untuk pilih' }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-8 bg-white rounded-lg border border-gray-200">
                                    <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                    </svg>
                                    <p class="text-gray-500">Tidak ada transaksi draft</p>
                                    <p class="text-xs text-gray-400 mt-1">Buat transaksi draft terlebih dahulu</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="px-6 py-3 bg-gray-100 border-t border-gray-200 rounded-b-lg flex justify-between items-center">
                        <div class="text-sm text-gray-600">
                            <span class="font-medium">Info:</span> Transaksi yang digabung akan dihapus kecuali transaksi tujuan
                        </div>
                        <div class="flex space-x-2">
                            <button 
                                @click="$wire.set('showMergeModal', false)"
                                class="cursor-pointer px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition">
                                Batal
                            </button>
                            <button 
                                wire:click="processMergeBill"
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-50 cursor-not-allowed"
                                class="cursor-pointer px-4 py-2 text-sm font-medium text-white bg-purple-600 border border-transparent rounded-lg hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition
                                    {{ count($selectedSalesToMerge) < 2 || !$mergeTargetSale ? 'opacity-50 cursor-not-allowed' : '' }}"
                                {{ count($selectedSalesToMerge) < 2 || !$mergeTargetSale ? 'disabled' : '' }}>
                                <span class="flex items-center">
                                    <svg wire:loading.remove wire:target="processMergeBill" 
                                        class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                    </svg>
                                    <svg wire:loading wire:target="processMergeBill" 
                                        class="w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                    Gabungkan Transaksi
                                </span>
                            </button>
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

    <script>
        // ✅ FOCUS MANAGEMENT UNTUK SEARCH - FIXED VERSION
        let searchInput = null;
        let isSearchFocused = false;
        
        // Initialize search functionality
        function initSearchFocus() {
            searchInput = document.getElementById('product-search-input');
            
            if (!searchInput) {
                console.log('❌ Search input not found');
                return;
            }
            
            console.log('✅ Search input initialized');
            
            // Auto focus saat section products aktif
            if (currentSection === 'products') {
                setTimeout(() => {
                    searchInput.focus();
                    isSearchFocused = true;
                }, 100);
            }
            
            // Event listener untuk focus/blur
            searchInput.addEventListener('focus', function() {
                isSearchFocused = true;
                console.log('🔍 Search focused');
            });
            
            searchInput.addEventListener('blur', function() {
                isSearchFocused = false;
                console.log('🔍 Search blurred');
            });
        }
        
        // ✅ GLOBAL KEYBOARD SHORTCUTS - FIXED
        function initKeyboardShortcuts() {
            document.addEventListener('keydown', function(e) {
                // 🔍 SHORTCUT: TEKAN '/' UNTUK FOCUS KE SEARCH
                // Cegah trigger jika user sedang mengetik di input/textarea
                const activeElement = document.activeElement;
                const isTextInput = activeElement.tagName === 'INPUT' || 
                                   activeElement.tagName === 'TEXTAREA' ||
                                   activeElement.isContentEditable;
                
                if (e.key === '/' && !isTextInput) {
                    e.preventDefault();
                    console.log('🔍 / pressed - focusing search');
                    
                    // Switch ke products section jika belum
                    if (currentSection !== 'products') {
                        switchSection('products');
                        setTimeout(() => {
                            const searchInput = document.getElementById('product-search-input');
                            if (searchInput) {
                                searchInput.focus();
                                isSearchFocused = true;
                                console.log('✅ Switched to products and focused search');
                            }
                        }, 200);
                    } else {
                        const searchInput = document.getElementById('product-search-input');
                        if (searchInput) {
                            searchInput.focus();
                            isSearchFocused = true;
                            console.log('✅ Focused search in products section');
                        }
                    }
                    return;
                }
                
                // 🔍 SHORTCUT: ESC UNTUK CLEAR SEARCH ATAU BLUR
                if (e.key === 'Escape' && isSearchFocused) {
                    e.preventDefault();
                    const searchInput = document.getElementById('product-search-input');
                    if (searchInput) {
                        if (searchInput.value) {
                            // Clear search jika ada isi
                            searchInput.value = '';
                            searchInput.dispatchEvent(new Event('input', { bubbles: true }));
                            console.log('🗑️ Cleared search');
                        } else {
                            // Blur jika kosong
                            searchInput.blur();
                            isSearchFocused = false;
                            console.log('🔍 Search blurred');
                        }
                    }
                    return;
                }
                
                // 🔍 SHORTCUT: CTRL+K / CMD+K UNTUK SEARCH
                if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                    e.preventDefault();
                    console.log('🔍 Ctrl+K pressed - focusing search');
                    
                    if (currentSection !== 'products') {
                        switchSection('products');
                    }
                    
                    setTimeout(() => {
                        const searchInput = document.getElementById('product-search-input');
                        if (searchInput) {
                            searchInput.focus();
                            isSearchFocused = true;
                        }
                    }, 200);
                    return;
                }
                
                // 🔢 SHORTCUT: ANGKA 1-9 UNTUK QUICK ADD (jika di search mode)
                if (isSearchFocused && !e.ctrlKey && !e.metaKey && !e.altKey) {
                    if (e.key >= '1' && e.key <= '9') {
                        e.preventDefault();
                        const productIndex = parseInt(e.key) - 1;
                        const productCards = document.querySelectorAll('#mobile-products-section .cursor-pointer[wire\\:click*="quickAddProduct"]');
                        
                        if (productCards.length > productIndex) {
                            const productCard = productCards[productIndex];
                            const wireClick = productCard.getAttribute('wire:click');
                            const productIdMatch = wireClick.match(/quickAddProduct\((\d+)\)/);
                            
                            if (productIdMatch) {
                                const productId = parseInt(productIdMatch[1]);
                                
                                // Dispatch ke Livewire menggunakan metode yang benar
                                Livewire.dispatch('quickAddProduct', { id: productId });
                                
                                // Tampilkan feedback
                                showQuickAddFeedback(productCard);
                                console.log(`➕ Quick added product ${productIndex + 1} (ID: ${productId})`);
                            }
                        }
                    }
                }
            });
        }
        
        // ✅ LISTEN UNTUK LIVE WIRE EVENTS
        Livewire.on('searchUpdated', (event) => {
            console.log('Livewire search updated:', event);
        });
        
        // Initialize dengan cara yang lebih cepat
        document.addEventListener('DOMContentLoaded', function() {
            console.log('POS Navigation Initialized');
            
            if (window.innerWidth < 640) {
                switchSection('products');
            }
            
            // Initialize semua fungsi
            setTimeout(() => {
                initSearchFocus();
                initKeyboardShortcuts();
                console.log('✅ Keyboard shortcuts initialized');
            }, 500);
            
            // Listen untuk cart updates
            window.addEventListener('cartUpdated', function(event) {
                // Update cart badge secara real-time
                const cartBadge = document.querySelector('.nav-button[data-section="cart"] .bg-red-500');
                if (cartBadge) {
                    cartBadge.textContent = Math.min(event.detail.count, 99);
                }
            });
            
            // Re-init search focus setelah Livewire update
            Livewire.hook('message.processed', (message, component) => {
                setTimeout(() => {
                    if (currentSection === 'products' && isSearchFocused) {
                        const searchInput = document.getElementById('product-search-input');
                        if (searchInput) {
                            searchInput.focus();
                        }
                    }
                }, 50);
            });
        });
    
        // ✅ UPDATE switchSection FUNCTION UNTUK AUTO-FOCUS
        function switchSection(section) {
            if (currentSection === section) return;
            
            console.log(`🔄 Switching section from ${currentSection} to ${section}`);
            currentSection = section;
            
            // Hide all mobile sections
            document.querySelectorAll('.mobile-section').forEach(el => {
                el.style.display = 'none';
            });
            
            // Show selected section
            const targetSection = document.getElementById(`mobile-${section}-section`);
            if (targetSection) {
                targetSection.style.display = 'flex';
                
                // ✅ AUTO FOCUS KE SEARCH JIKA MASUK KE PRODUCTS SECTION
                if (section === 'products') {
                    setTimeout(() => {
                        const searchInput = document.getElementById('product-search-input');
                        if (searchInput) {
                            searchInput.focus();
                            isSearchFocused = true;
                            console.log('✅ Auto-focused to search');
                        }
                    }, 150);
                } else {
                    isSearchFocused = false;
                }
            }
            
            // Update nav buttons
            updateNavButtons(section);
        }
        
        // ✅ UPDATE resize handler
        let resizeTimeout;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(function() {
                if (window.innerWidth >= 640) {
                    // Show all sections on desktop
                    document.querySelectorAll('.mobile-section').forEach(el => {
                        el.style.display = 'flex';
                    });
                } else {
                    switchSection(currentSection);
                }
            }, 100);
        });
    
        // ✅ FEEDBACK VISUAL UNTUK QUICK ADD
        function showQuickAddFeedback(element) {
            if (!element) return;
            
            // Highlight efek
            const originalTransform = element.style.transform;
            const originalBoxShadow = element.style.boxShadow;
            
            element.style.transform = 'scale(0.95)';
            element.style.boxShadow = '0 0 0 3px rgba(34, 197, 94, 0.5)';
            element.style.transition = 'all 0.2s ease';
            
            // Reset setelah delay
            setTimeout(() => {
                element.style.transform = originalTransform;
                element.style.boxShadow = originalBoxShadow;
            }, 300);
        }
        
        // ✅ UPDATE NAV BUTTONS FUNCTION
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
        
        /* QUICK ADD NUMBER BADGES */
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
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 5;
            opacity: 0.9;
        }
        
        /* SEARCH MODE INDICATOR */
        .search-mode-active {
            background: linear-gradient(45deg, #3b82f6, #8b5cf6) !important;
            color: white !important;
        }
        
        /* ANIMATION FOR SEARCH RESULTS */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .search-result-item {
            animation: fadeInUp 0.3s ease forwards;
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
        }
    </style>

    {{-- <script>
        // ✅ SIMPLE JAVASCRIPT SOLUTION - PASTI BERFUNGSI
        function switchSection(section) {
            console.log('Switching to section:', section);
            
            // Hide all mobile sections
            document.querySelectorAll('.mobile-section').forEach(el => {
                el.classList.add('hidden');
            });
            
            // Show selected section
            const targetSection = document.getElementById(`mobile-${section}-section`);
            if (targetSection) {
                targetSection.classList.remove('hidden');
                console.log('✅ Section shown:', targetSection.id);
                
                // Scroll ke top
                targetSection.scrollTop = 0;
            } else {
                console.error('❌ Section not found:', `mobile-${section}-section`);
            }
            
            // Update nav buttons
            updateNavButtons(section);
        }
        
        function updateNavButtons(activeSection) {
            console.log('Updating nav to:', activeSection);
            
            // Reset all nav buttons
            document.querySelectorAll('.nav-button').forEach(btn => {
                btn.classList.remove('bg-blue-600', 'text-white', 'nav-active');
                btn.classList.add('bg-gray-100', 'text-gray-600');
            });
            
            // Activate current button (hanya untuk products dan cart)
            if (activeSection === 'products' || activeSection === 'cart') {
                const activeBtn = document.querySelector(`.nav-button[data-section="${activeSection}"]`);
                if (activeBtn) {
                    activeBtn.classList.remove('bg-gray-100', 'text-gray-600');
                    activeBtn.classList.add('bg-blue-600', 'text-white', 'nav-active');
                    console.log('✅ Nav button activated:', activeSection);
                }
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            console.log('POS Navigation Initialized');
            
            // Show products section by default on mobile
            if (window.innerWidth < 640) {
                switchSection('products');
            } else {
                // Pada desktop, show kedua section
                document.querySelectorAll('.mobile-section').forEach(el => {
                    el.classList.remove('hidden');
                });
            }
        });

        // Handle resize
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 640) {
                // Pada desktop, show kedua section
                document.querySelectorAll('.mobile-section').forEach(el => {
                    el.classList.remove('hidden');
                });
            } else {
                // Pada mobile, show section aktif terakhir
                const activeBtn = document.querySelector('.nav-button.nav-active');
                if (activeBtn) {
                    const activeSection = activeBtn.getAttribute('data-section');
                    switchSection(activeSection);
                } else {
                    switchSection('products');
                }
            }
        });

        // Improved scroll behavior untuk mobile
        document.addEventListener('DOMContentLoaded', function() {
            const cartScrollArea = document.querySelector('#mobile-cart-section .flex-1');
            if (cartScrollArea) {
                // Force redraw untuk memastikan scroll area berfungsi
                setTimeout(() => {
                    cartScrollArea.style.overflow = 'auto';
                }, 100);
            }
        });
    </script> --}}
    <script>
        // ✅ OPTIMIZED JAVASCRIPT - LEBIH CEPAT
        let currentSection = 'products';
        
        function switchSection(section) {
            if (currentSection === section) return;
            
            currentSection = section;
            
            // Hide all mobile sections
            document.querySelectorAll('.mobile-section').forEach(el => {
                el.style.display = 'none';
            });
            
            // Show selected section
            const targetSection = document.getElementById(`mobile-${section}-section`);
            if (targetSection) {
                targetSection.style.display = 'flex';
            }
            
            // Update nav buttons
            updateNavButtons(section);
        }
        
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

        // Initialize dengan cara yang lebih cepat
        document.addEventListener('DOMContentLoaded', function() {
            if (window.innerWidth < 640) {
                switchSection('products');
            }
            
            // Listen untuk cart updates
            window.addEventListener('cartUpdated', function(event) {
                // Update cart badge secara real-time
                const cartBadge = document.querySelector('.nav-button[data-section="cart"] .bg-red-500');
                if (cartBadge) {
                    cartBadge.textContent = Math.min(event.detail.count, 99);
                }
            });
        });

        // Debounced resize handler
        let resizeTimeout;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(function() {
                if (window.innerWidth >= 640) {
                    // Show all sections on desktop
                    document.querySelectorAll('.mobile-section').forEach(el => {
                        el.style.display = 'flex';
                    });
                } else {
                    switchSection(currentSection);
                }
            }, 100);
        });
    </script>

</div>
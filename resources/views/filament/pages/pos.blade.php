{{-- <div class="h-full flex flex-col md:flex-row bg-gray-50 overflow-hidden"> --}}
    <div class="h-full flex flex-col lg:flex-row bg-gray-50 overflow-hidden">


    {{-- ========================= --}}
    {{-- 💰 KIRI: DAFTAR PRODUK --}}
    <div class="lg:w-2/3 w-full h-full flex flex-col border-r border-gray-200 bg-white shadow-sm">
        {{-- Header Produk - Update untuk tablet --}}
        <div class="px-4 sm:px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-blue-50 to-indigo-50">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex-1">
                    <h1 class="text-lg sm:text-xl font-bold text-gray-900">Menu Produk</h1>
                    <p class="text-xs sm:text-sm text-gray-600 mt-1">Pilih produk untuk ditambahkan ke keranjang</p>
                </div>
                <div class="text-left sm:text-right">
                    <p class="text-xs font-medium text-gray-500">Kategori Terpilih</p>
                    <p class="text-sm font-semibold text-blue-600">{{ $selectedCategory }}</p>
                </div>
            </div>
        </div>

        {{-- Filter Kategori - Scroll horizontal untuk tablet --}}
        <div class="px-4 sm:px-6 py-3 bg-white border-b border-gray-100">
            <div class="flex space-x-2 overflow-x-auto pb-1 scrollbar-thin scrollbar-thumb-gray-300">
                @foreach ($categories as $category)
                    <button wire:click="setCategory('{{ $category }}')"
                        class="cursor-pointer flex-shrink-0 px-3 sm:px-4 py-2.5 rounded-lg text-xs sm:text-sm font-medium transition-all duration-200 border
                            {{ $selectedCategory === $category 
                                ? 'bg-blue-600 text-white border-blue-600 shadow-sm' 
                                : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50 hover:border-gray-400' }}">
                        {{ $category }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Grid Produk - Responsive untuk berbagai ukuran --}}
        <div class="flex-1 overflow-y-auto p-4 sm:p-6">
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3 sm:gap-4 auto-rows-[11rem] sm:auto-rows-[13rem]">
                @forelse ($products as $product)
                    <div wire:click="addProduct({{ $product->id }})"
                        class="cursor-pointer group bg-white rounded-lg sm:rounded-xl border border-gray-200 hover:border-blue-300 hover:shadow-lg transition-all duration-200 p-2 sm:p-3 flex flex-col items-center relative overflow-hidden">
                        {{-- Stock Badge --}}
                        @if($product->type !== 'produced' && $product->type !== 'bar')
                            <div class="absolute top-1 right-1 sm:top-2 sm:right-2 z-10">
                                <span class="inline-flex items-center px-1.5 py-0.5 sm:px-2 sm:py-1 rounded-full text-xs font-medium 
                                    {{ $product->stock > 10 ? 'bg-green-100 text-green-800' : 
                                        ($product->stock > 0 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                    {{ intval($product->stock) }}
                                </span>
                            </div>
                        @endif
                        
                        {{-- Product Image --}}
                        <div class="w-12 h-12 sm:w-16 sm:h-16 md:w-20 md:h-20 mb-2 sm:mb-3 rounded-lg bg-gray-100 overflow-hidden flex items-center justify-center">
                            <img src="{{ $product->image_url }}"
                                alt="{{ $product->name }}"
                                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-200">
                        </div>

                        {{-- Product Info --}}
                        <div class="text-center flex-1 flex flex-col justify-between w-full">
                            <div>
                                <h3 class="text-xs sm:text-sm font-semibold text-gray-900 line-clamp-2 leading-tight mb-1">
                                    {{ $product->name }}
                                </h3>
                            </div>
                            <div class="mt-auto">
                                <p class="text-sm sm:text-lg font-bold text-blue-600">
                                    Rp{{ number_format($product->sell_price, 0, ',', '.') }}
                                </p>
                                <div class="mt-1 sm:mt-2 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                    <span class="inline-flex items-center text-xs text-blue-600 font-medium">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                        </svg>
                                        Tambah
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center py-8 sm:py-12">
                        <div class="w-16 h-16 sm:w-24 sm:h-24 mx-auto mb-3 sm:mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                            <svg class="w-8 h-8 sm:w-12 sm:h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                            </svg>
                        </div>
                        <h3 class="text-base sm:text-lg font-medium text-gray-900 mb-2">Tidak ada produk</h3>
                        <p class="text-gray-500 text-xs sm:text-sm">Tidak ada produk ditemukan untuk kategori ini.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- 🧺 KANAN: KERANJANG --}}
    <div class="lg:w-1/3 w-full h-full flex flex-col bg-white shadow-lg">

        {{-- Header Keranjang --}}
        <div class="p-4 sm:p-6 bg-gradient-to-r from-green-50 to-emerald-50 border-b border-gray-100">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 gap-3">
                <div class="flex-1">
                    <h1 class="text-lg sm:text-xl font-bold text-gray-900">Keranjang Pesanan</h1>
                    <p class="text-xs sm:text-sm text-gray-600 mt-1">Kelola pesanan customer</p>
                </div>
                <div class="text-left sm:text-right">
                    <p class="text-xs font-medium text-gray-500">No. Pesanan</p>
                    <p class="text-sm font-bold text-gray-900">{{ $orderNumber }}</p>
                </div>
            </div>

            {{-- Customer & Order Info --}}
            <div class="bg-white rounded-lg sm:rounded-xl border border-gray-200 p-3 sm:p-4 space-y-3 sm:space-y-4">
                {{-- Tipe Order --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wide mb-2">Tipe Order</label>
                    <div class="grid grid-cols-2 gap-2">
                        <button 
                            wire:click="setOrderType('Dine In')"
                            class="w-full cursor-pointer px-2 py-2 sm:px-3 sm:py-2.5 rounded-lg text-xs sm:text-sm font-semibold transition-all duration-200 border
                                {{ $orderType === 'Dine In'
                                    ? 'bg-green-600 text-white border-green-600 shadow-sm'
                                    : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                            🍽️ Dine In
                        </button>
                        <button 
                            wire:click="setOrderType('Take Away')"
                            class="w-full cursor-pointer px-2 py-2 sm:px-3 sm:py-2.5 rounded-lg text-xs sm:text-sm font-semibold transition-all duration-200 border
                                {{ $orderType === 'Take Away'
                                    ? 'bg-green-600 text-white border-green-600 shadow-sm'
                                    : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                            🥡 Take Away
                        </button>
                    </div>
                </div>

                {{-- Nama Customer --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wide mb-2">
                        Nama Customer
                    </label>
                    <input 
                        type="text" 
                        wire:model="customerName"
                        class="w-full bg-gray-50 border border-gray-300 rounded-lg py-2 sm:py-2.5 px-3 text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 focus:outline-none transition"
                        placeholder="Masukkan nama pelanggan...">
                </div>

                {{-- Info Kasir --}}
                <div class="flex items-center justify-between text-xs sm:text-sm">
                    <span class="text-gray-600">Ditangani oleh:</span>
                    <span class="font-semibold text-gray-900">{{ $this->getNameUserLogin() }}</span>
                </div>
            </div>
        </div>

        {{-- List Item --}}
        <div class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-3">
            @forelse ($items as $index => $item)
                <div class="bg-white border border-gray-200 rounded-lg sm:rounded-xl p-3 sm:p-4 hover:shadow-md transition-all duration-200">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-start justify-between mb-2">
                                <h3 class="font-semibold text-gray-900 text-sm">{{ $item['name'] }}</h3>
                                <p class="text-sm font-bold text-gray-900">
                                    Rp{{ number_format($item['subtotal'], 0, ',', '.') }}
                                </p>
                            </div>
                            <p class="text-xs text-gray-500 mb-3">
                                Rp{{ number_format($item['price'], 0, ',', '.') }} per item
                            </p>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <button wire:click="updateQuantity({{ $index }}, {{ $item['quantity'] - 1 }})"
                                        class="cursor-pointer w-6 h-6 sm:w-8 sm:h-8 flex items-center justify-center bg-gray-100 hover:bg-gray-200 rounded-lg text-gray-600 font-semibold transition text-xs sm:text-base">
                                        −
                                    </button>
                                    <span class="w-6 sm:w-8 text-center font-semibold text-gray-900 text-sm">{{ $item['quantity'] }}</span>
                                    <button wire:click="updateQuantity({{ $index }}, {{ $item['quantity'] + 1 }})"
                                        class="cursor-pointer w-6 h-6 sm:w-8 sm:h-8 flex items-center justify-center bg-gray-100 hover:bg-gray-200 rounded-lg text-gray-600 font-semibold transition text-xs sm:text-base">
                                        +
                                    </button>
                                </div>
                                <button wire:click="removeItem({{ $index }})"
                                    class="cursor-pointer text-xs text-red-600 hover:text-red-700 font-medium flex items-center transition">
                                    <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                    Hapus
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-8 sm:py-12">
                    <div class="w-16 h-16 sm:w-20 sm:h-20 mx-auto mb-3 sm:mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 sm:w-10 sm:h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-base sm:text-lg font-medium text-gray-900 mb-2">Keranjang Kosong</h3>
                    <p class="text-gray-500 text-xs sm:text-sm">Pilih produk dari menu untuk memulai pesanan</p>
                </div>
            @endforelse
        </div>

        {{-- Ringkasan & Aksi --}}
        <div class="border-t border-gray-200 bg-white">
            {{-- Input Diskon --}}
            <div class="p-4 sm:p-6 border-b border-gray-100">
                <label class="block text-sm font-semibold text-gray-900 mb-3">Kode Diskon</label>
                <div class="flex flex-col sm:flex-row sm:space-x-3 space-y-2 sm:space-y-0">
                    <input 
                        type="text" 
                        wire:model.defer="discountCodeInput"
                        class="flex-1 bg-gray-50 border border-gray-300 rounded-xl py-3 px-4 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:outline-none transition"
                        placeholder="Masukkan kode promo...">
                    <button 
                        wire:click="applyDiscountCode"
                        class="cursor-pointer bg-blue-600 hover:bg-blue-700 text-white px-4 sm:px-6 py-3 rounded-xl text-sm font-semibold shadow-sm hover:shadow-md transition whitespace-nowrap">
                        Terapkan
                    </button>
                </div>
                @if ($discountMessage)
                    <p class="text-xs sm:text-sm mt-2 font-medium {{ $discountApplied ? 'text-green-600' : 'text-red-600' }}">
                        {{ $discountMessage }}
                    </p>
                @endif
            </div>

            {{-- Ringkasan Harga --}}
            <div class="p-4 sm:p-6 space-y-3">
                <div class="flex justify-between text-sm text-gray-600">
                    <span>Subtotal</span>
                    <span>Rp{{ number_format($total, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between text-sm text-gray-600">
                    <span>Pajak (10%)</span>
                    <span>Rp{{ number_format($tax, 0, ',', '.') }}</span>
                </div>
                @if($discount > 0)
                    <div class="flex justify-between text-sm text-green-600">
                        <span>Diskon</span>
                        <span>- Rp{{ number_format($discount, 0, ',', '.') }}</span>
                    </div>
                @endif
                <div class="border-t border-gray-200 pt-3">
                    <div class="flex justify-between text-base sm:text-lg font-bold text-gray-900">
                        <span>Total</span>
                        <span class="text-lg sm:text-xl">Rp{{ number_format($finalTotal, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            {{-- Tombol Aksi --}}
            <div class="p-4 sm:p-6 border-t border-gray-100 bg-gray-50 space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <button wire:click="saveSale"
                        class="cursor-pointer w-full bg-blue-600 hover:bg-blue-700 text-white py-3 sm:py-4 rounded-xl font-bold text-sm shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-[1.02]">
                        💾 SIMPAN TRANSAKSI
                    </button>
                    <button wire:click="openPaymentModal({{ $saleId }})" 
                            class="cursor-pointer w-full bg-green-600 hover:bg-green-700 text-white py-3 sm:py-4 rounded-xl font-bold text-sm shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-[1.02]"
                            {{ !$saleId ? 'disabled' : '' }}>
                        💵 PROSES PEMBAYARAN
                    </button>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <button wire:click="openLoadModal"
                        class="cursor-pointer bg-yellow-500 hover:bg-yellow-600 text-white py-2.5 sm:py-3 rounded-xl font-semibold text-xs sm:text-sm shadow-sm hover:shadow-md transition flex items-center justify-center space-x-2">
                        <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        <span>Transaksi</span>
                    </button>
                    <button wire:click="cancelSale"
                        class="cursor-pointer bg-gray-200 hover:bg-gray-300 text-gray-800 py-2.5 sm:py-3 rounded-xl font-semibold text-xs sm:text-sm transition flex items-center justify-center space-x-2">
                        <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        <span>Batal</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Include Modal Components --}}
    <livewire:pos-cash-in-modal />
    <livewire:pos-load-modal />
    <livewire:pos-payment-modal />
    @livewire('pos-notification')

    <style>
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .scrollbar-thin::-webkit-scrollbar {
            height: 6px;
        }
        
        .scrollbar-thumb-gray-300::-webkit-scrollbar-thumb {
            background-color: #d1d5db;
            border-radius: 3px;
        }
        
        .scrollbar-thumb-gray-300::-webkit-scrollbar-track {
            background-color: #f3f4f6;
        }
        
        /* Tablet-specific adjustments */
        @media (max-width: 1024px) and (min-width: 768px) {
            .grid-cols-tablet {
                grid-template-columns: repeat(4, 1fr);
            }
        }
        
        /* Mobile landscape and small tablets */
        @media (max-width: 767px) {
            .flex-col-mobile {
                flex-direction: column;
            }
        }
        
        /* Very small screens */
        @media (max-width: 480px) {
            .text-responsive {
                font-size: 0.75rem;
            }
            
            .p-responsive {
                padding: 0.75rem;
            }
        }
    </style>
</div>

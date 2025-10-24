<div class="h-full flex flex-col lg:flex-row tablet-layout bg-gray-50 overflow-hidden min-h-0">

    {{-- 💰 KIRI: DAFTAR PRODUK --}}
    <div class="lg:flex-1 lg:min-w-[60%] xl:min-w-[65%] w-full h-full flex flex-col border-r border-gray-200 bg-white shadow-sm min-h-0 overflow-hidden">
        {{-- Header Produk --}}
        <div class="px-4 py-3 border-b border-gray-100 bg-gradient-to-r from-blue-50 to-indigo-50 flex-shrink-0">
            <div class="flex items-center justify-between">
                <div class="flex-1 min-w-0">
                    <h1 class="text-lg font-bold text-gray-900 truncate">Menu Produk</h1>
                    <p class="text-xs text-gray-600 mt-1 truncate">Pilih produk untuk ditambahkan ke keranjang</p>
                </div>
                <div class="text-right ml-3 flex-shrink-0">
                    <p class="text-xs font-medium text-gray-500">Kategori</p>
                    <p class="text-sm font-semibold text-blue-600">{{ $selectedCategory }}</p>
                </div>
            </div>
        </div>

        {{-- Filter Kategori --}}
        <div class="px-4 py-2 bg-white border-b border-gray-100 flex-shrink-0">
            <div class="flex space-x-2 overflow-x-auto scrollbar-thin">
                @foreach ($categories as $category)
                    <button wire:click="setCategory('{{ $category }}')"
                        class="cursor-pointer flex-shrink-0 px-3 py-2 rounded-lg text-xs font-medium transition-all duration-200 border whitespace-nowrap
                            {{ $selectedCategory === $category 
                                ? 'bg-blue-600 text-white border-blue-600' 
                                : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                        {{ $category }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Grid Produk - Compact untuk tablet --}}
        <div class="flex-1 overflow-auto p-3">
            <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-2 auto-rows-[8rem]">
                @forelse ($products as $product)
                    <div wire:click="addProduct({{ $product->id }})"
                        class="cursor-pointer group bg-white rounded-lg border border-gray-200 hover:border-blue-300 hover:shadow-sm transition-all duration-200 p-2 flex flex-col items-center relative overflow-hidden h-full">
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
                        <div class="w-10 h-10 mb-1 rounded bg-gray-100 overflow-hidden flex items-center justify-center flex-shrink-0">
                            <img src="{{ $product->image_url }}"
                                alt="{{ $product->name }}"
                                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-200">
                        </div>

                        {{-- Product Info --}}
                        <div class="text-center flex-1 flex flex-col justify-between w-full min-h-0">
                            <div class="flex-1">
                                <h3 class="text-xs font-semibold text-gray-900 line-clamp-2 leading-tight break-words">
                                    {{ $product->name }}
                                </h3>
                            </div>
                            <div class="mt-1">
                                <p class="text-xs font-bold text-blue-600 leading-none">
                                    Rp{{ number_format($product->sell_price, 0, ',', '.') }}
                                </p>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center py-8">
                        <div class="w-12 h-12 mx-auto mb-2 bg-gray-100 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                            </svg>
                        </div>
                        <h3 class="text-sm font-medium text-gray-900 mb-1">Tidak ada produk</h3>
                        <p class="text-gray-500 text-xs">Tidak ada produk untuk kategori ini.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- 🧺 KANAN: KERANJANG --}}
    <div class="lg:w-[400px] xl:w-[450px] w-full h-full flex flex-col bg-white shadow-lg border-l border-gray-200 flex-shrink-0 min-h-0 overflow-hidden">
        {{-- Fixed width untuk mencegah wrapping --}}

        {{-- Header Keranjang --}}
        <div class="p-4 bg-gradient-to-r from-green-50 to-emerald-50 border-b border-gray-100 flex-shrink-0">
            <div class="flex items-center justify-between mb-3">
                <div class="flex-1 min-w-0">
                    <h1 class="text-lg font-bold text-gray-900 truncate">Keranjang</h1>
                    <p class="text-xs text-gray-600 mt-1">Kelola pesanan customer</p>
                </div>
                <div class="text-right ml-3 flex-shrink-0">
                    <p class="text-xs font-medium text-gray-500">No. Pesanan</p>
                    <p class="text-sm font-bold text-gray-900">{{ $orderNumber }}</p>
                </div>
            </div>

            {{-- Customer & Order Info --}}
            <div class="bg-white rounded border border-gray-200 p-3 space-y-3">
                {{-- Tipe Order --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-2">Tipe Order</label>
                    <div class="grid grid-cols-2 gap-2">
                        <button 
                            wire:click="setOrderType('Dine In')"
                            class="w-full cursor-pointer px-2 py-2 rounded text-xs font-semibold transition-all duration-200 border
                                {{ $orderType === 'Dine In'
                                    ? 'bg-green-600 text-white border-green-600'
                                    : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                            🍽️ Dine In
                        </button>
                        <button 
                            wire:click="setOrderType('Take Away')"
                            class="w-full cursor-pointer px-2 py-2 rounded text-xs font-semibold transition-all duration-200 border
                                {{ $orderType === 'Take Away'
                                    ? 'bg-green-600 text-white border-green-600'
                                    : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                            🥡 Take Away
                        </button>
                    </div>
                </div>

                {{-- Nama Customer --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-2">
                        Nama Customer
                    </label>
                    <input 
                        type="text" 
                        wire:model="customerName"
                        class="w-full bg-gray-50 border border-gray-300 rounded py-2 px-3 text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 focus:outline-none transition"
                        placeholder="Nama pelanggan...">
                </div>

                {{-- Info Kasir --}}
                <div class="flex items-center justify-between text-xs">
                    <span class="text-gray-600">Kasir:</span>
                    <span class="font-semibold text-gray-900 truncate ml-2">{{ $this->getNameUserLogin() }}</span>
                </div>
            </div>
        </div>

        {{-- List Item --}}
        <div class="flex-1 overflow-auto p-3 space-y-2">
            @forelse ($items as $index => $item)
                <div class="bg-white border border-gray-200 rounded p-2 hover:shadow-sm transition-all duration-200">
                    <div class="flex items-start justify-between">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between mb-1">
                                <h3 class="font-semibold text-gray-900 text-sm truncate flex-1 mr-2">{{ $item['name'] }}</h3>
                                <p class="text-sm font-bold text-gray-900 whitespace-nowrap">
                                    Rp{{ number_format($item['subtotal'], 0, ',', '.') }}
                                </p>
                            </div>
                            <p class="text-xs text-gray-500 mb-2">
                                Rp{{ number_format($item['price'], 0, ',', '.') }} × {{ $item['quantity'] }}
                            </p>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <button wire:click="updateQuantity({{ $index }}, {{ $item['quantity'] - 1 }})"
                                        class="cursor-pointer w-6 h-6 flex items-center justify-center bg-gray-100 hover:bg-gray-200 rounded text-gray-600 font-semibold transition text-xs">
                                        −
                                    </button>
                                    <span class="w-6 text-center font-semibold text-gray-900 text-xs">{{ $item['quantity'] }}</span>
                                    <button wire:click="updateQuantity({{ $index }}, {{ $item['quantity'] + 1 }})"
                                        class="cursor-pointer w-6 h-6 flex items-center justify-center bg-gray-100 hover:bg-gray-200 rounded text-gray-600 font-semibold transition text-xs">
                                        +
                                    </button>
                                </div>
                                <button wire:click="removeItem({{ $index }})"
                                    class="cursor-pointer text-xs text-red-600 hover:text-red-700 font-medium flex items-center transition ml-2">
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
                <div class="text-center py-6">
                    <div class="w-12 h-12 mx-auto mb-2 bg-gray-100 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-sm font-medium text-gray-900 mb-1">Keranjang Kosong</h3>
                    <p class="text-gray-500 text-xs">Pilih produk dari menu</p>
                </div>
            @endforelse
        </div>

        {{-- Ringkasan & Aksi --}}
        <div class="border-t border-gray-200 bg-white flex-shrink-0">
            {{-- Input Diskon --}}
            <div class="p-3 border-b border-gray-100">
                <label class="block text-sm font-semibold text-gray-900 mb-2">Kode Diskon</label>
                <div class="flex space-x-2">
                    <input 
                        type="text" 
                        wire:model.defer="discountCodeInput"
                        class="flex-1 bg-gray-50 border border-gray-300 rounded py-2 px-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:outline-none transition min-w-0"
                        placeholder="Kode promo...">
                    <button 
                        wire:click="applyDiscountCode"
                        class="cursor-pointer bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded text-sm font-semibold shadow-sm hover:shadow-md transition whitespace-nowrap flex-shrink-0">
                        Pakai
                    </button>
                </div>
                @if ($discountMessage)
                    <p class="text-xs mt-2 font-medium {{ $discountApplied ? 'text-green-600' : 'text-red-600' }}">
                        {{ $discountMessage }}
                    </p>
                @endif
            </div>

            {{-- Ringkasan Harga --}}
            <div class="p-3 space-y-2">
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
                <div class="border-t border-gray-200 pt-2">
                    <div class="flex justify-between text-base font-bold text-gray-900">
                        <span>Total</span>
                        <span>Rp{{ number_format($finalTotal, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            {{-- Tombol Aksi --}}
            <div class="p-3 border-t border-gray-100 bg-gray-50 space-y-2">
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
                <div class="grid grid-cols-2 gap-2">
                    <button wire:click="openLoadModal"
                        class="cursor-pointer bg-yellow-500 hover:bg-yellow-600 text-white py-2 rounded font-semibold text-xs shadow-sm hover:shadow-md transition flex items-center justify-center">
                        Transaksi
                    </button>
                    <button wire:click="cancelSale"
                        class="cursor-pointer bg-gray-200 hover:bg-gray-300 text-gray-800 py-2 rounded font-semibold text-xs transition flex items-center justify-center">
                        Batal
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

        /* Tablet 1280x800 specific adjustments */
        @media (min-width: 1024px) and (max-width: 1366px) {
            .grid-cols-tablet-1280 {
                grid-template-columns: repeat(6, 1fr);
            }
            
            /* Optimize font sizes for tablet */
            .text-tablet {
                font-size: 0.875rem;
            }
            
            /* Make touch targets larger */
            .touch-target {
                min-height: 44px;
                min-width: 44px;
            }
        }
        
        /* Prevent text overflow */
        .break-words {
            word-break: break-word;
        }
        
        /* Ensure minimum widths for readability */
        .min-w-0 {
            min-width: 0;
        }

        /* Fix untuk tablet dengan Chrome */
        @media (max-width: 1366px) and (min-width: 768px) {
            .tablet-layout {
                flex-direction: row !important;
                min-width: 0;
            }
            
            /* Pastikan container tidak melebihi viewport */
            .tablet-layout > div {
                min-width: 0;
                flex-shrink: 1;
            }
            
            /* Fixed width untuk keranjang di tablet */
            .tablet-layout > div:last-child {
                flex-shrink: 0;
                width: 380px;
            }
        }

        /* Untuk layar sangat sempit */
        @media (max-width: 1023px) {
            .tablet-layout {
                flex-direction: column !important;
            }
            
            .tablet-layout > div:last-child {
                width: 100%;
            }
        }

        /* Pastikan tidak ada horizontal scroll */
        html, body {
            overflow-x: hidden;
        }
        
        /* Fix untuk Chrome mobile */
        @media (hover: none) and (pointer: coarse) {
            button, [role="button"] {
                min-height: 44px;
            }
            
            input, select, textarea {
                font-size: 16px; /* Prevent zoom on iOS */
            }
        }
    </style>
</div>

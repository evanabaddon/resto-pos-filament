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
                @forelse ($products as $product)
                    <div wire:click="quickAddProduct({{ $product->id }})"
                        class="cursor-pointer group bg-white rounded-lg border border-gray-200 hover:border-blue-300 hover:shadow-sm transition-all duration-200 p-2 flex flex-col items-center relative overflow-hidden h-full touch-target">
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
                                loading="lazy">
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
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <button wire:click="mobileUpdateQuantity({{ $index }}, {{ $item['quantity'] - 1 }})"
                                        class="cursor-pointer w-6 h-6 flex items-center justify-center bg-gray-100 hover:bg-gray-200 rounded text-gray-600 font-semibold transition text-xs touch-target">
                                        −
                                    </button>
                                    <span class="w-6 text-center font-semibold text-gray-900 text-xs mobile-text-xs">{{ $item['quantity'] }}</span>
                                    <button wire:click="mobileUpdateQuantity({{ $index }}, {{ $item['quantity'] + 1 }})"
                                        class="cursor-pointer w-6 h-6 flex items-center justify-center bg-gray-100 hover:bg-gray-200 rounded text-gray-600 font-semibold transition text-xs touch-target">
                                        +
                                    </button>
                                </div>
                                <button wire:click="mobileRemoveItem({{ $index }})"
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
                <div class="text-center py-6">
                    <div class="w-10 h-10 mx-auto mb-2 bg-gray-100 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-sm font-medium text-gray-900 mb-1 mobile-text-sm">Keranjang Kosong</h3>
                    <p class="text-gray-500 text-xs mobile-text-xs">Pilih produk dari menu</p>
                </div>
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
                padding-bottom: 80px; /* Space untuk bottom nav */
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

        .safe-area-bottom {
            padding-bottom: max(12px, env(safe-area-inset-bottom));
        }

        /* Touch-friendly buttons */
        .touch-target {
            min-height: 20px;
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

    <script>
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
    </script>

</div>
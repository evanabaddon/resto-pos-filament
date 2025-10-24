<div class="h-full flex flex-col lg:flex-row bg-gray-50 overflow-hidden" id="pos-container">
    {{-- ========================= --}}
    {{-- 💰 KIRI: DAFTAR PRODUK --}}
    {{-- ========================= --}}
    <div class="lg:flex-1 w-full h-full flex flex-col border-r border-gray-200 bg-white min-h-0">
        {{-- Header Produk Compact --}}
        <div class="px-4 py-2 border-b border-gray-100 bg-blue-50 flex-shrink-0">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <h1 class="text-lg font-bold text-gray-900">Produk</h1>
                    <span class="text-sm text-blue-600 font-medium bg-blue-100 px-2 py-1 rounded">
                        {{ $selectedCategory }}
                    </span>
                </div>
                <div class="text-right text-sm text-gray-600">
                    {{ count($products) }} items
                </div>
            </div>
        </div>

        {{-- Filter Kategori Horizontal Scroll --}}
        <div class="px-4 py-2 bg-white border-b border-gray-100 flex-shrink-0">
            <div class="flex space-x-1 overflow-x-auto scrollbar-thin">
                @foreach ($categories as $category)
                    <button wire:click="setCategory('{{ $category }}')"
                        class="cursor-pointer flex-shrink-0 px-3 py-1.5 rounded text-xs font-medium transition-all duration-200 border
                            {{ $selectedCategory === $category 
                                ? 'bg-blue-600 text-white border-blue-600' 
                                : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                        {{ $category }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Grid Produk Ultra Compact --}}
        <div class="flex-1 overflow-auto p-2">
            <div class="grid grid-cols-4 md:grid-cols-5 lg:grid-cols-6 xl:grid-cols-7 2xl:grid-cols-8 gap-1 auto-rows-[6.5rem]">
                @forelse ($products as $product)
                    <div wire:click="addProduct({{ $product->id }})"
                        class="cursor-pointer group bg-white rounded border border-gray-200 hover:border-blue-300 hover:shadow-sm transition-all duration-150 p-1 flex flex-col items-center relative overflow-hidden h-full">
                        {{-- Stock Badge Mini --}}
                        @if($product->type !== 'produced' && $product->type !== 'bar')
                            <div class="absolute top-0 right-0 z-10">
                                <span class="inline-flex items-center px-1 rounded text-[8px] font-medium 
                                    {{ $product->stock > 10 ? 'bg-green-100 text-green-800' : 
                                        ($product->stock > 0 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                    {{ intval($product->stock) }}
                                </span>
                            </div>
                        @endif
                        
                        {{-- Product Image Mini --}}
                        <div class="w-8 h-8 mb-1 rounded bg-gray-100 overflow-hidden flex items-center justify-center flex-shrink-0">
                            <img src="{{ $product->image_url }}"
                                alt="{{ $product->name }}"
                                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-200">
                        </div>

                        {{-- Product Info Ultra Compact --}}
                        <div class="text-center flex-1 flex flex-col justify-between w-full min-h-0">
                            <div class="flex-1">
                                <h3 class="text-[10px] font-semibold text-gray-900 line-clamp-2 leading-tight break-words">
                                    {{ $product->name }}
                                </h3>
                            </div>
                            <div class="mt-0.5">
                                <p class="text-[10px] font-bold text-blue-600 leading-none">
                                    Rp{{ number_format($product->sell_price, 0, ',', '.') }}
                                </p>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center py-4">
                        <div class="w-8 h-8 mx-auto mb-1 bg-gray-100 rounded-full flex items-center justify-center">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                            </svg>
                        </div>
                        <h3 class="text-xs font-medium text-gray-900">Tidak ada produk</h3>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ========================= --}}
    {{-- 🧺 KANAN: KERANJANG --}}
    {{-- ========================= --}}
    <div class="lg:w-[350px] xl:w-[380px] w-full h-full flex flex-col bg-white border-l border-gray-200 flex-shrink-0 min-h-0">
        {{-- Header Keranjang Compact --}}
        <div class="p-3 bg-green-50 border-b border-gray-100 flex-shrink-0">
            <div class="flex items-center justify-between mb-2">
                <h1 class="text-lg font-bold text-gray-900">Keranjang</h1>
                <div class="text-right">
                    <p class="text-xs text-gray-500">Order #{{ $orderNumber }}</p>
                </div>
            </div>

            {{-- Customer & Order Info Compact --}}
            <div class="space-y-2">
                {{-- Tipe Order --}}
                <div class="grid grid-cols-2 gap-1">
                    <button 
                        wire:click="setOrderType('Dine In')"
                        class="w-full cursor-pointer px-2 py-1.5 rounded text-xs font-semibold transition-all duration-200 border
                            {{ $orderType === 'Dine In'
                                ? 'bg-green-600 text-white border-green-600'
                                : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                        Dine In
                    </button>
                    <button 
                        wire:click="setOrderType('Take Away')"
                        class="w-full cursor-pointer px-2 py-1.5 rounded text-xs font-semibold transition-all duration-200 border
                            {{ $orderType === 'Take Away'
                                ? 'bg-green-600 text-white border-green-600'
                                : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                        Take Away
                    </button>
                </div>

                {{-- Nama Customer --}}
                <div>
                    <input 
                        type="text" 
                        wire:model="customerName"
                        class="w-full bg-gray-50 border border-gray-300 rounded py-1.5 px-2 text-xs focus:ring-1 focus:ring-green-500 focus:border-green-500 focus:outline-none transition"
                        placeholder="Nama customer...">
                </div>
            </div>
        </div>

        {{-- List Item Compact --}}
        <div class="flex-1 overflow-auto p-2 space-y-1">
            @forelse ($items as $index => $item)
                <div class="bg-white border border-gray-200 rounded p-1.5 hover:shadow-sm transition-all duration-150">
                    <div class="flex items-center justify-between mb-1">
                        <h3 class="font-semibold text-gray-900 text-xs truncate flex-1 mr-2">{{ $item['name'] }}</h3>
                        <p class="text-xs font-bold text-gray-900 whitespace-nowrap">
                            Rp{{ number_format($item['subtotal'], 0, ',', '.') }}
                        </p>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-1">
                            <button wire:click="updateQuantity({{ $index }}, {{ $item['quantity'] - 1 }})"
                                class="cursor-pointer w-5 h-5 flex items-center justify-center bg-gray-100 hover:bg-gray-200 rounded text-gray-600 font-semibold transition text-[10px]">
                                −
                            </button>
                            <span class="w-5 text-center font-semibold text-gray-900 text-xs">{{ $item['quantity'] }}</span>
                            <button wire:click="updateQuantity({{ $index }}, {{ $item['quantity'] + 1 }})"
                                class="cursor-pointer w-5 h-5 flex items-center justify-center bg-gray-100 hover:bg-gray-200 rounded text-gray-600 font-semibold transition text-[10px]">
                                +
                            </button>
                        </div>
                        <button wire:click="removeItem({{ $index }})"
                            class="cursor-pointer text-[10px] text-red-600 hover:text-red-700 font-medium flex items-center transition">
                            Hapus
                        </button>
                    </div>
                </div>
            @empty
                <div class="text-center py-4">
                    <div class="w-8 h-8 mx-auto mb-1 bg-gray-100 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xs font-medium text-gray-900">Keranjang Kosong</h3>
                </div>
            @endforelse
        </div>

        {{-- Ringkasan & Aksi Compact --}}
        <div class="border-t border-gray-200 bg-white flex-shrink-0">
            {{-- Input Diskon --}}
            <div class="p-2 border-b border-gray-100">
                <div class="flex space-x-1">
                    <input 
                        type="text" 
                        wire:model.defer="discountCodeInput"
                        class="flex-1 bg-gray-50 border border-gray-300 rounded py-1.5 px-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 focus:outline-none transition"
                        placeholder="Kode diskon...">
                    <button 
                        wire:click="applyDiscountCode"
                        class="cursor-pointer bg-blue-600 hover:bg-blue-700 text-white px-2 py-1.5 rounded text-xs font-semibold transition whitespace-nowrap">
                        Apply
                    </button>
                </div>
                @if ($discountMessage)
                    <p class="text-[10px] mt-1 font-medium {{ $discountApplied ? 'text-green-600' : 'text-red-600' }}">
                        {{ $discountMessage }}
                    </p>
                @endif
            </div>

            {{-- Ringkasan Harga --}}
            <div class="p-2 space-y-1">
                <div class="flex justify-between text-xs text-gray-600">
                    <span>Subtotal</span>
                    <span>Rp{{ number_format($total, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between text-xs text-gray-600">
                    <span>Pajak (10%)</span>
                    <span>Rp{{ number_format($tax, 0, ',', '.') }}</span>
                </div>
                @if($discount > 0)
                    <div class="flex justify-between text-xs text-green-600">
                        <span>Diskon</span>
                        <span>- Rp{{ number_format($discount, 0, ',', '.') }}</span>
                    </div>
                @endif
                <div class="border-t border-gray-200 pt-1">
                    <div class="flex justify-between text-sm font-bold text-gray-900">
                        <span>Total</span>
                        <span>Rp{{ number_format($finalTotal, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            {{-- Tombol Aksi Compact --}}
            <div class="p-2 border-t border-gray-100 bg-gray-50 space-y-1">
                <div class="grid grid-cols-2 gap-1">
                    <button wire:click="saveSale"
                        class="cursor-pointer w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded text-xs font-bold transition">
                        SIMPAN
                    </button>
                    <button wire:click="openPaymentModal({{ $saleId }})" 
                            class="cursor-pointer w-full bg-green-600 hover:bg-green-700 text-white py-2 rounded text-xs font-bold transition"
                            {{ !$saleId ? 'disabled' : '' }}>
                        BAYAR
                    </button>
                </div>
                <div class="grid grid-cols-2 gap-1">
                    <button wire:click="openLoadModal"
                        class="cursor-pointer bg-yellow-500 hover:bg-yellow-600 text-white py-1.5 rounded text-xs font-semibold transition">
                        Transaksi
                    </button>
                    <button wire:click="cancelSale"
                        class="cursor-pointer bg-gray-200 hover:bg-gray-300 text-gray-800 py-1.5 rounded text-xs font-semibold transition">
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
</div>
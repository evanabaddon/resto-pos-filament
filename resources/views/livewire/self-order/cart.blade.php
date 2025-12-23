<div class="bg-gray-50 min-h-screen pb-48 font-sans">
    <!-- Header -->
    <div class="sticky top-0 z-30 bg-white/80 backdrop-blur-md border-b border-gray-200/50 px-5 py-4">
        <h1 class="text-lg font-bold text-gray-900 flex items-center gap-2">
            <x-heroicon-o-shopping-bag class="w-6 h-6 text-primary-600" />
            Keranjang Saya
        </h1>
    </div>

    <div class="px-5 py-6">
        @if(count($cartItems) > 0)

            <!-- Cart Items List -->
            <div class="space-y-4">
                @foreach($cartItems as $key => $item)
                    <div
                        class="flex gap-4 bg-white p-4 rounded-3xl shadow-[0_4px_20px_rgb(0,0,0,0.03)] border border-gray-100/50 relative overflow-hidden group">

                        <!-- Image -->
                        <div class="w-24 h-24 bg-gray-100 rounded-2xl flex-shrink-0 overflow-hidden relative">
                            @if(isset($item['image']) && $item['image'])
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($item['image']) }}"
                                    class="w-full h-full object-cover">
                            @else
                                <div class="flex items-center justify-center w-full h-full text-gray-300 bg-gray-50">
                                    <x-heroicon-o-photo class="w-8 h-8 opacity-50" />
                                </div>
                            @endif
                        </div>

                        <!-- Info -->
                        <div class="flex-1 flex flex-col justify-between py-1">
                            <div>
                                <h3 class="font-bold text-gray-900 text-sm leading-snug line-clamp-1 mb-1">{{ $item['name'] }}
                                </h3>
                                <p class="text-primary-600 font-bold text-xs">
                                    Rp {{ number_format($item['price'], 0, ',', '.') }}
                                </p>
                            </div>

                            <!-- Qty Control -->
                            <div class="flex items-center justify-between mt-3">
                                <div class="flex items-center bg-gray-50 rounded-xl p-1 shadow-inner border border-gray-100">
                                    <button wire:click="updateQty({{ $key }}, -1)"
                                        class="w-8 h-8 flex items-center justify-center bg-white rounded-lg shadow-sm text-gray-600 hover:text-primary-600 active:scale-95 transition-all">
                                        <span class="text-base font-bold leading-none mb-0.5">-</span>
                                    </button>
                                    <span class="text-sm font-bold w-8 text-center text-gray-900">{{ $item['qty'] }}</span>
                                    <button wire:click="updateQty({{ $key }}, 1)"
                                        class="w-8 h-8 flex items-center justify-center bg-white rounded-lg shadow-sm text-gray-600 hover:text-primary-600 active:scale-95 transition-all">
                                        <span class="text-base font-bold leading-none mb-0.5">+</span>
                                    </button>
                                </div>

                                <button wire:click="removeItem({{ $key }})"
                                    class="w-8 h-8 flex items-center justify-center bg-red-50 text-red-500 rounded-xl hover:bg-red-100 active:scale-95 transition-all">
                                    <x-heroicon-o-trash class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Sticky Bottom Summary -->
            <div
                class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-100 p-5 pb-20 rounded-t-3xl shadow-[0_-10px_40px_rgba(0,0,0,0.05)] z-40 backdrop-blur-xl bg-white/90">
                <div class="space-y-1 mb-8">
                    <div class="flex justify-between items-center text-xs text-gray-500">
                        <span>Subtotal</span>
                        <span>Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between items-center text-xs text-gray-500">
                        <span>Pajak (11%)</span>
                        <span>Rp {{ number_format($tax, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between items-center pt-2 mt-1">
                        <span class="text-gray-900 font-bold text-base">Total</span>
                        <span class="text-xl font-extrabold text-primary-600">Rp
                            {{ number_format($total, 0, ',', '.') }}</span>
                    </div>
                    <a href="{{ route('order.checkout') }}" wire:navigate
                        class="block w-full bg-primary-600 text-white text-center py-3.5 rounded-2xl font-bold text-base shadow-lg shadow-primary-500/30 hover:bg-primary-700 hover:shadow-xl active:scale-95 transition-all duration-200">
                        Lanjut ke Pemesanan
                    </a>

                    <a href="{{ route('order.menu') }}" wire:navigate
                        class="block text-center text-gray-400 font-medium text-xs mt-4 hover:text-gray-600 transition">
                        Tambah Menu Lain
                    </a>
                </div>
            </div>

        @else
            <!-- Empty State -->
            <div class="flex flex-col items-center justify-center py-20 min-h-[60vh]">
                <div class="bg-gray-100 rounded-full w-32 h-32 flex items-center justify-center mb-6 animate-pulse">
                    <x-heroicon-o-shopping-bag class="w-16 h-16 text-gray-300" />
                </div>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Keranjang Kosong</h2>
                <p class="text-gray-500 mb-8 text-center max-w-[200px]">Perut lapar? Yuk pilih menu favoritmu sekarang!</p>
                <a href="{{ route('order.menu') }}" wire:navigate
                    class="inline-flex items-center gap-2 bg-primary-600 text-white px-8 py-3.5 rounded-full font-bold text-sm shadow-lg shadow-primary-500/30 hover:bg-primary-700 hover:scale-105 transition-all duration-300">
                    <x-heroicon-o-book-open class="w-4 h-4" />
                    Lihat Menu
                </a>
            </div>
        @endif
    </div>
</div>
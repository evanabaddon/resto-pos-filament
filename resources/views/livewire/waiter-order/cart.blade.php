<div class="pb-28 bg-gray-50 min-h-screen font-sans">
    <!-- Header -->
    <div class="sticky top-0 z-30 bg-white/80 backdrop-blur-md border-b border-gray-200/50 px-4 py-3">
        <div class="flex items-center gap-3">
            <a href="{{ route('waiter.order') }}" class="p-1.5 hover:bg-gray-100 rounded-lg transition-colors">
                <x-heroicon-m-arrow-left class="w-5 h-5 text-gray-700" />
            </a>
            <h1 class="text-base font-bold text-gray-900 tracking-tight">Pesanan Waiter</h1>
        </div>
    </div>

    @if(empty($cartItems))
        <div class="flex flex-col items-center justify-center min-h-[60vh] px-5 text-center">
            <div class="bg-white p-6 rounded-full shadow-sm mb-6">
                <x-heroicon-o-shopping-bag class="w-16 h-16 text-gray-300" />
            </div>
            <h2 class="text-xl font-bold text-gray-900 mb-2">Keranjang Masih Kosong</h2>
            <p class="text-gray-500 mb-8 max-w-[250px]">Yuk bantu pelanggan memilih menu favorit mereka!</p>
            <a href="{{ route('waiter.order') }}" wire:navigate
                class="px-8 py-3 bg-primary-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-primary-500/30 hover:bg-primary-700 transition w-full max-w-xs">
                Buka Menu
            </a>
        </div>
    @else
        <div class="px-5 py-6 space-y-4">
            @foreach($cartItems as $productId => $item)
                <div class="bg-white p-4 rounded-3xl shadow-[0_4px_20px_rgb(0,0,0,0.03)] border border-gray-100 flex gap-4">
                    <!-- Image -->
                    <div class="w-20 h-20 rounded-2xl bg-gray-100 overflow-hidden flex-shrink-0">
                        <img src="{{ asset('storage/' . $item['image']) }}" class="w-full h-full object-cover">
                    </div>

                    <!-- Details -->
                    <div class="flex-1 min-w-0">
                        <div class="flex justify-between items-start gap-2">
                            <h3 class="font-bold text-gray-800 text-sm leading-tight line-clamp-2 italic">
                                {{ $item['name'] }}
                            </h3>
                            <button wire:click="removeItem({{ $productId }})"
                                class="text-gray-300 hover:text-red-500 transition-colors">
                                <x-heroicon-m-x-mark class="w-4 h-4" />
                            </button>
                        </div>
                        <p class="text-xs font-semibold text-primary-600 mt-0.5">
                            Rp {{ number_format($item['price'], 0, ',', '.') }}
                        </p>

                        <!-- Note Input (Compact & Elegant) -->
                        <div class="ml-24 -mt-2 mb-2">
                            <div class="flex items-center gap-2 border-b border-gray-100 pb-1">
                                <x-heroicon-m-pencil-square class="w-3 h-3 text-gray-400" />
                                <input type="text" placeholder="Tambah catatan (pedas, dll)..."
                                    wire:blur="updateNote({{ $productId }}, $event.target.value)" value="{{ $item['note'] }}"
                                    class="w-full text-[10px] border-0 bg-transparent focus:ring-0 p-0 text-gray-600 placeholder-gray-300 italic">
                            </div>
                        </div>

                        <!-- Quantity Control (Compact) -->
                        <div class="flex items-center justify-between mt-3">
                            <div
                                class="flex items-center border border-gray-200 rounded-lg overflow-hidden bg-gray-50 shadow-sm">
                                <button wire:click="decrement({{ $productId }})"
                                    class="p-1.5 hover:bg-white text-gray-500 transition-colors">
                                    <x-heroicon-m-minus class="w-3.5 h-3.5" />
                                </button>
                                <span class="w-8 text-center text-xs font-bold text-gray-800">{{ $item['qty'] }}</span>
                                <button wire:click="increment({{ $productId }})"
                                    class="p-1.5 hover:bg-white text-gray-500 transition-colors">
                                    <x-heroicon-m-plus class="w-3.5 h-3.5" />
                                </button>
                            </div>
                            <span class="text-xs font-bold text-gray-900">
                                Rp {{ number_format($item['price'] * $item['qty'], 0, ',', '.') }}
                            </span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Summary Footer (Shifted up to avoid overlap) -->
        <div
            class="fixed bottom-16 left-0 right-0 bg-white border-t border-gray-100 p-4 rounded-t-3xl shadow-[0_-8px_30px_rgb(0,0,0,0.08)] z-40">
            <div class="flex justify-between items-center mb-3">
                <span class="text-xs text-gray-500 font-bold uppercase tracking-wider">Total</span>
                <span class="font-extrabold text-lg text-primary-600">Rp {{ number_format($total, 0, ',', '.') }}</span>
            </div>

            <a href="{{ route('waiter.checkout') }}" wire:navigate
                class="w-full bg-primary-600 text-white text-center py-3 rounded-xl font-bold text-sm shadow-lg shadow-primary-500/20 active:scale-95 transition-all block uppercase tracking-wide">
                Lanjut Pesan
            </a>
        </div>
    @endif
</div>
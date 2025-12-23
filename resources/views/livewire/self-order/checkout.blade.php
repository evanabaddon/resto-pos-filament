<div class="pb-32 bg-gray-50 min-h-screen font-sans">
    <!-- Header -->
    <div class="sticky top-0 z-30 bg-white/80 backdrop-blur-md border-b border-gray-200/50 px-5 py-4">
        <h1 class="text-lg font-bold text-gray-900 flex items-center gap-2">
            <x-heroicon-o-credit-card class="w-6 h-6 text-primary-600" />
            Konfirmasi Pesanan
        </h1>
    </div>

    <div class="px-5 py-6">
        <!-- Form Section -->
        <div class="bg-white p-6 rounded-3xl shadow-[0_4px_20px_rgb(0,0,0,0.03)] border border-gray-100/50 mb-6">
            <h2 class="font-bold mb-5 text-gray-800 flex items-center gap-2 text-base">
                Data Pemesan
            </h2>

            <div class="space-y-5">
                <div class="group">
                    <label
                        class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2 ml-1 group-focus-within:text-primary-600 transition-colors">Nama
                        Lengkap</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <x-heroicon-m-user
                                class="h-5 w-5 text-gray-300 group-focus-within:text-primary-500 transition-colors" />
                        </div>
                        <input type="text" wire:model="name"
                            class="block w-full pl-11 pr-4 py-3 border-0 bg-gray-50 text-gray-900 placeholder-gray-400 rounded-2xl ring-1 ring-gray-200 focus:ring-2 focus:ring-primary-500 focus:bg-white transition-all shadow-sm text-sm"
                            placeholder="Contoh: Budi Santoso">
                    </div>
                    @error('name') <span
                        class="text-red-500 text-xs mt-1.5 font-bold ml-1 flex items-center gap-1"><x-heroicon-s-exclamation-circle
                    class="w-3 h-3" /> Nama wajib diisi</span> @enderror
                </div>

                <div class="group">
                    <label
                        class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2 ml-1 group-focus-within:text-primary-600 transition-colors">WhatsApp
                        <span class="text-gray-300 font-normal normal-case">(Opsional)</span></label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <x-heroicon-m-phone
                                class="h-5 w-5 text-gray-300 group-focus-within:text-primary-500 transition-colors" />
                        </div>
                        <input type="tel" wire:model="phone"
                            class="block w-full pl-11 pr-4 py-3 border-0 bg-gray-50 text-gray-900 placeholder-gray-400 rounded-2xl ring-1 ring-gray-200 focus:ring-2 focus:ring-primary-500 focus:bg-white transition-all shadow-sm text-sm"
                            placeholder="Contoh: 081234567890">
                    </div>
                    @error('phone') <span
                        class="text-red-500 text-xs mt-1.5 font-bold ml-1 flex items-center gap-1"><x-heroicon-s-exclamation-circle
                    class="w-3 h-3" /> {{ $message }}</span> @enderror

                    <div class="flex items-start gap-2 mt-3 p-3 bg-blue-50 rounded-xl">
                        <x-heroicon-m-information-circle class="w-5 h-5 text-blue-500 flex-shrink-0" />
                        <p class="text-xs text-blue-700 leading-relaxed font-medium">Nomor ini akan menerima notifikasi
                            status pesanan secara otomatis via WhatsApp.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Section -->
        <div class="bg-white p-6 rounded-3xl shadow-[0_4px_20px_rgb(0,0,0,0.03)] border border-gray-100/50 mb-6">
            <h2 class="font-bold mb-5 text-gray-800 flex items-center gap-2 text-base">
                <x-heroicon-o-receipt-percent class="w-6 h-6 text-primary-600" />
                Ringkasan
            </h2>

            <div class="space-y-4 mb-6">
                @foreach($cartItems as $item)
                    <div class="flex justify-between items-start text-xs group">
                        <div class="flex items-start gap-3">
                            <span
                                class="font-bold text-gray-500 bg-gray-100 px-1.5 py-0.5 rounded text-[10px] min-w-[24px] text-center">{{ $item['qty'] }}x</span>
                            <span class="text-gray-700 font-medium leading-relaxed">{{ $item['name'] }}</span>
                        </div>
                        <span class="font-bold text-gray-900">Rp
                            {{ number_format($item['price'] * $item['qty'], 0, ',', '.') }}</span>
                    </div>
                @endforeach
            </div>

            <div class="border-t border-dashed border-gray-200 pt-5 space-y-3 bg-gray-50/50 -mx-6 px-6 pb-2">
                <div class="flex justify-between items-center text-xs text-gray-500">
                    <span>Subtotal</span>
                    <span class="font-medium">Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between items-center text-xs text-gray-500">
                    <span>Pajak (pb1 11%)</span>
                    <span class="font-medium">Rp {{ number_format($tax, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between items-center pt-3 border-t border-gray-200">
                    <span class="font-bold text-gray-900 text-base">Total Bayar</span>
                    <span class="font-extrabold text-xl text-primary-600">Rp
                        {{ number_format($total, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <!-- Payment Info -->
        <div
            class="bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-100 p-5 rounded-3xl flex items-start gap-4 mb-8">
            <div class="bg-white p-2.5 rounded-2xl shadow-sm text-blue-600 ring-4 ring-blue-50/50">
                <x-heroicon-m-banknotes class="w-6 h-6" />
            </div>
            <div>
                <h3 class="font-bold text-blue-900 text-xs mb-1">Pembayaran di Kasir</h3>
                <p class="text-[10px] text-blue-700/80 leading-relaxed font-medium">Silakan lakukan pembayaran di kasir
                    setelah Anda selesai menikmati hidangan.</p>
            </div>
        </div>

        <!-- CTA -->
        <button wire:click="placeOrder" wire:loading.attr="disabled"
            class="w-full bg-primary-600 text-white text-center py-3.5 rounded-2xl font-bold text-base shadow-lg shadow-primary-500/30 hover:bg-primary-700 hover:shadow-xl hover:shadow-primary-500/40 active:scale-95 transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed group relative overflow-hidden ring-4 ring-primary-500/10">

            <div
                class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700 ease-in-out">
            </div>

            <div class="flex items-center justify-center min-h-[28px] relative z-10">
                <span wire:loading.remove wire:target="placeOrder">Konfirmasi Pesanan</span>
                <div wire:loading.flex wire:target="placeOrder" class="items-center justify-center gap-2">
                    <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                        </circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                    <span>Memproses...</span>
                </div>
            </div>
        </button>

        <a href="{{ route('order.cart') }}" wire:navigate
            class="block text-center text-gray-400 font-bold text-sm mt-6 hover:text-gray-600 transition">
            Kembali ke Keranjang
        </a>
    </div>
</div>
<div class="pb-32 bg-gray-50 min-h-screen font-sans">
    <!-- Header -->
    <div class="sticky top-0 z-30 bg-white/80 backdrop-blur-md border-b border-gray-200/50 px-5 py-4">
        <h1 class="text-lg font-bold text-gray-900 flex items-center gap-2">
            <x-heroicon-o-clipboard-document-check class="w-6 h-6 text-primary-600" />
            Konfirmasi (Waiter)
        </h1>
    </div>

    <div class="px-5 py-6">
        <!-- Form Section -->
        <div class="bg-white p-6 rounded-3xl shadow-[0_4px_20px_rgb(0,0,0,0.03)] border border-gray-100/50 mb-6">
            <h2 class="font-bold mb-5 text-gray-800 flex items-center gap-2 text-base">
                Info Pelanggan
            </h2>

            <div class="space-y-4">
                <!-- Name -->
                <div class="group">
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2 ml-1">Nama
                        Pelanggan / Meja</label>
                    <input type="text" wire:model="name"
                        class="block w-full px-4 py-3 border-0 bg-gray-50 text-gray-900 placeholder-gray-400 rounded-2xl ring-1 ring-gray-200 focus:ring-2 focus:ring-primary-500 focus:bg-white text-sm"
                        placeholder="Nama Pelanggan">
                    @error('name') <span class="text-red-500 text-xs mt-1 block font-bold">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Table Number (Optional) -->
                <div class="group">
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2 ml-1">Nomor Meja
                        <span class="text-gray-300 font-normal">(Opsional)</span></label>
                    <input type="text" wire:model="tableNumber"
                        class="block w-full px-4 py-3 border-0 bg-gray-50 text-gray-900 placeholder-gray-400 rounded-2xl ring-1 ring-gray-200 focus:ring-2 focus:ring-primary-500 focus:bg-white text-sm"
                        placeholder="Contoh: 12, A1, VIP (Kosongkan jika belum ada)">
                </div>

                <!-- Phone (Optional) -->
                <div class="group">
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2 ml-1">WhatsApp
                        <span class="text-gray-300 font-normal">(Opsional)</span></label>
                    <input type="tel" wire:model="phone"
                        class="block w-full px-4 py-3 border-0 bg-gray-50 text-gray-900 placeholder-gray-400 rounded-2xl ring-1 ring-gray-200 focus:ring-2 focus:ring-primary-500 focus:bg-white text-sm"
                        placeholder="08xxxxxxxxxx">
                    <p class="text-[10px] text-gray-400 mt-1 ml-1">Isi jika pelanggan ingin notifikasi pesanan.</p>
                </div>
            </div>
        </div>

        <!-- Summary Section -->
        <div class="bg-white p-6 rounded-3xl shadow-[0_4px_20px_rgb(0,0,0,0.03)] border border-gray-100/50 mb-6">
            <h2 class="font-bold mb-5 text-gray-800 flex items-center gap-2 text-base">
                Ringkasan
            </h2>

            <div class="space-y-3 mb-6">
                @foreach($cartItems as $item)
                    <div class="flex justify-between items-start text-xs text-gray-600">
                        <span>{{ $item['qty'] }}x {{ $item['name'] }}</span>
                        <span class="font-bold text-gray-900">Rp
                            {{ number_format($item['price'] * $item['qty'], 0, ',', '.') }}</span>
                    </div>
                    @if(!empty($item['note']))
                        <div class="text-[10px] text-gray-400 italic -mt-2 ml-4">Catatan: {{ $item['note'] }}</div>
                    @endif
                @endforeach
            </div>

            <div class="border-t border-dashed border-gray-200 pt-4 space-y-2">
                <div class="flex justify-between items-center text-xs text-gray-500">
                    <span>Subtotal</span>
                    <span>Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between items-center text-xs text-gray-500">
                    <span>Pajak
                        ({{ $tax > 0 ? app(\App\Settings\GeneralSettings::class)->tax_percentage . '%' : '0%' }})</span>
                    <span>Rp {{ number_format($tax, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between items-center pt-3 border-t border-gray-200">
                    <span class="font-bold text-gray-900 text-base">Total</span>
                    <span class="font-extrabold text-xl text-primary-600">Rp
                        {{ number_format($total, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <!-- CTA -->
        <button wire:click="placeOrder" wire:loading.attr="disabled"
            class="w-full bg-black text-white text-center py-4 rounded-2xl font-bold text-base shadow-xl transform active:scale-95 transition-all">
            <span wire:loading.remove>Kirim Pesanan (Ke Dapur)</span>
            <span wire:loading>Memproses...</span>
        </button>

        <a href="{{ route('waiter.cart') }}" wire:navigate
            class="block text-center text-gray-400 font-bold text-sm mt-6 hover:text-gray-600 transition">
            Kembali
        </a>
    </div>
</div>
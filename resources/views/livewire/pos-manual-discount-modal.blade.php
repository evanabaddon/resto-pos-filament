<div>
    @if ($show)
        <div class="fixed inset-0 z-[100] flex items-center justify-center p-4" x-data
            @keydown.window.escape="$wire.close()">

            {{-- Backdrop --}}
            <div class="absolute inset-0 bg-slate-900/80 transition-opacity" wire:click="close"></div>

            {{-- Modal Content --}}
            <div
                class="relative w-full max-w-sm bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col items-center animate-modal-pop">

                {{-- Header --}}
                <div
                    class="w-full bg-gradient-to-r from-violet-600 to-indigo-600 p-4 text-white text-center rounded-t-2xl relative overflow-hidden">
                    <div
                        class="absolute inset-0 opacity-20 bg-[radial-gradient(ellipse_at_top_right,_var(--tw-gradient-stops))] from-white to-transparent">
                    </div>
                    <h2 class="text-lg font-bold relative z-10">Manual Discount</h2>
                    <p class="text-xs text-indigo-100 relative z-10">Berikan potongan harga khusus</p>
                </div>

                <div class="p-6 w-full space-y-5">

                    {{-- Access Toggle Type --}}
                    <div class="flex bg-slate-100 p-1 rounded-xl">
                        <button wire:click="$set('discountType', 'fixed')"
                            class="flex-1 py-2 rounded-lg text-xs font-bold transition-all duration-200 
                                        {{ $discountType === 'fixed' ? 'bg-white text-violet-600 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
                            Nominal (Rp)
                        </button>
                        <button wire:click="$set('discountType', 'percentage')"
                            class="flex-1 py-2 rounded-lg text-xs font-bold transition-all duration-200 
                                        {{ $discountType === 'percentage' ? 'bg-white text-violet-600 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
                            Persentase (%)
                        </button>
                    </div>

                    {{-- Input Value --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">
                            {{ $discountType === 'fixed' ? 'Masukan Nominal' : 'Masukan Persen' }}
                        </label>
                        <div class="relative">
                            @if($discountType === 'fixed')
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <span class="text-slate-400 font-bold">Rp</span>
                                </div>
                            @endif

                            <input type="number" wire:model="value"
                                class="block w-full {{ $discountType === 'fixed' ? 'pl-11' : 'pl-4' }} pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-lg font-bold text-slate-800 placeholder-slate-300 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition focus:outline-none focus:bg-white"
                                placeholder="0" autofocus>

                            @if($discountType === 'percentage')
                                <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                    <span class="text-slate-400 font-bold">%</span>
                                </div>
                            @endif
                        </div>
                        @error('value') <span class="text-xs text-rose-500 mt-1 font-bold">{{ $message }}</span> @enderror
                    </div>

                    {{-- Reason Input (Optional) --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Keterangan
                            (Opsional)</label>
                        <input type="text" wire:model="reason"
                            class="block w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition focus:bg-white"
                            placeholder="Cth: Promo Teman, VIP, dll">
                    </div>

                    {{-- Actions --}}
                    <div class="flex gap-3 pt-2">
                        <button wire:click="close"
                            class="flex-1 py-3 bg-white border-2 border-slate-200 text-slate-600 font-bold text-sm rounded-xl hover:bg-slate-50 transition active:scale-95">
                            Batal
                        </button>
                        <button wire:click="apply"
                            class="flex-[2] py-3 bg-violet-600 text-white font-bold text-sm rounded-xl shadow-lg shadow-violet-200 hover:bg-violet-700 transition active:scale-95 flex items-center justify-center gap-2">
                            Terapkan
                        </button>
                    </div>
                </div>

            </div>
        </div>
    @endif
</div>
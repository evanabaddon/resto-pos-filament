<div>
    @if ($show)
        <div class="fixed inset-0 z-[100] flex items-center justify-center p-4" x-data
            @keydown.window.escape="$wire.close()">

            {{-- Backdrop --}}
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" wire:click="close"></div>

            {{-- Modal Content --}}
            <div
                class="relative w-full max-w-sm bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col items-center animate-modal-pop">

                {{-- Header --}}
                <div
                    class="w-full bg-gradient-to-r from-violet-600 to-indigo-600 p-4 text-white text-center rounded-t-2xl relative overflow-hidden">
                    <div
                        class="absolute inset-0 opacity-20 bg-[radial-gradient(ellipse_at_top_right,_var(--tw-gradient-stops))] from-white to-transparent">
                    </div>
                    <h2 class="text-lg font-bold relative z-10">Member Baru</h2>
                    <p class="text-xs text-indigo-100 relative z-10">Tambah pelanggan baru</p>
                </div>

                <div class="p-6 w-full space-y-4">

                    {{-- Name Input --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Nama
                            Lengkap</label>
                        <input type="text" wire:model="name"
                            class="block w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-800 placeholder-slate-300 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition focus:outline-none focus:bg-white"
                            placeholder="Nama Pelanggan" autofocus>
                        @error('name') <span class="text-xs text-rose-500 mt-1 font-bold">{{ $message }}</span> @enderror
                    </div>

                    {{-- Phone Input --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">No. HP /
                            WhatsApp</label>
                        <input type="tel" wire:model="phone"
                            class="block w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-800 placeholder-slate-300 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition focus:outline-none focus:bg-white"
                            placeholder="08xxxxxxxxxx">
                        @error('phone') <span class="text-xs text-rose-500 mt-1 font-bold">{{ $message }}</span> @enderror
                    </div>

                    {{-- Email Input --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Email
                            (Opsional)</label>
                        <input type="email" wire:model="email"
                            class="block w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition focus:bg-white"
                            placeholder="email@example.com">
                        @error('email') <span class="text-xs text-rose-500 mt-1 font-bold">{{ $message }}</span> @enderror
                    </div>

                    {{-- Actions --}}
                    <div class="flex gap-3 pt-2">
                        <button wire:click="close"
                            class="flex-1 py-3 bg-white border-2 border-slate-200 text-slate-600 font-bold text-sm rounded-xl hover:bg-slate-50 transition active:scale-95">
                            Batal
                        </button>
                        <button wire:click="save" wire:loading.attr="disabled"
                            class="flex-[2] py-3 bg-violet-600 text-white font-bold text-sm rounded-xl shadow-lg shadow-violet-200 hover:bg-violet-700 transition active:scale-95 flex items-center justify-center gap-2">
                            <span wire:loading.remove wire:target="save">Simpan Member</span>
                            <span wire:loading wire:target="save">Menyimpan...</span>
                        </button>
                    </div>
                </div>

            </div>
        </div>
    @endif
</div>
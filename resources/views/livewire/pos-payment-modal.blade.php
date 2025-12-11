<div>
    @if ($show)
        <div class="fixed inset-0 z-50 flex items-end justify-center sm:items-center p-4">
            {{-- Background Backdrop (Blur) --}}
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" 
                 wire:click="closeModal"></div>

            {{-- Main Modal Container --}}
            <div class="relative w-full max-w-lg bg-white rounded-t-2xl sm:rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[95vh] animate-modal-pop">
                
                {{-- Header --}}
                <div class="bg-gradient-to-r from-violet-600 to-indigo-600 px-6 py-4 flex items-center justify-between flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-bold text-white tracking-wide">Selesaikan Pembayaran</h2>
                        <p class="text-violet-100 text-xs mt-0.5 opacity-90">Transaction #{{ $invoiceNumber }}</p>
                    </div>
                    <button wire:click="closeModal" class="text-white/80 hover:text-white transition p-2 rounded-full hover:bg-white/10 touch-target">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                {{-- Scrollable Content --}}
                <div class="flex-1 overflow-y-auto bg-slate-50 p-6 space-y-6">
                    
                    {{-- 1. Total Tagihan (Hero Card) --}}
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 text-center relative overflow-hidden">
                        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-violet-500 to-fuchsia-500"></div>
                        <p class="text-slate-500 text-sm font-medium uppercase tracking-wider mb-1">Total Tagihan</p>
                        <h3 class="text-4xl font-black text-slate-800 tracking-tight">
                            <span class="text-2xl text-slate-400 font-bold mr-1 align-top relative top-1">Rp</span>{{ number_format($finalTotal, 0, ',', '.') }}
                        </h3>
                        @if($customerName)
                            <div class="mt-3 inline-flex items-center px-3 py-1 bg-violet-50 text-violet-700 rounded-full text-xs font-bold">
                                <span class="mr-1">👤</span> {{ $customerName }}
                            </div>
                        @endif
                    </div>

                    {{-- 2. Metode Pembayaran (Grid) --}}
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-3 ml-1">Pilih Metode Pembayaran</label>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            @foreach($paymentMethods as $method)
                                <button 
                                    wire:click="$set('payment_method', '{{ $method['id'] }}')"
                                    class="group relative flex flex-col items-center justify-center p-4 rounded-xl border-2 transition-all duration-200 touch-target
                                    {{ $payment_method == $method['id'] 
                                        ? 'bg-violet-50 border-violet-600 shadow-md transform -translate-y-0.5' 
                                        : 'bg-white border-slate-200 hover:border-violet-300 hover:shadow-sm' }}">
                                    
                                    {{-- Radio Indicator --}}
                                    <div class="absolute top-3 right-3 w-4 h-4 rounded-full border-2 flex items-center justify-center
                                        {{ $payment_method == $method['id'] ? 'border-violet-600' : 'border-slate-300' }}">
                                        @if($payment_method == $method['id'])
                                            <div class="w-2 h-2 rounded-full bg-violet-600"></div>
                                        @endif
                                    </div>

                                    <span class="text-2xl mb-2 grayscale group-hover:grayscale-0 transition-all duration-300">
                                        @if(stripos($method['name'], 'cash') !== false) 💵
                                        @elseif(stripos($method['name'], 'qris') !== false) 📱
                                        @elseif(stripos($method['name'], 'card') !== false) 💳
                                        @else 🪙
                                        @endif
                                    </span>
                                    <span class="text-xs font-bold {{ $payment_method == $method['id'] ? 'text-violet-700' : 'text-slate-600' }} text-center leading-tight">
                                        {{ $method['name'] }}
                                    </span>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- 3. Input Pembayaran (Contextual) --}}
                    @if($isCashPayment)
                        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm animate-fade-in-up">
                            <label class="block text-sm font-bold text-slate-700 mb-2">Nominal Diterima</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <span class="text-slate-400 text-xl font-bold">Rp</span>
                                </div>
                                <input type="number" 
                                       wire:model.live="amount_paid"
                                       wire:keydown.enter="processPayment"
                                       class="block w-full pl-12 pr-4 py-4 text-3xl font-bold text-slate-800 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-violet-100 focus:border-violet-500 transition-all placeholder-slate-300"
                                       placeholder="0"
                                       autofocus>
                            </div>

                            {{-- Kembalian Display --}}
                            <div class="mt-4 flex items-center justify-between p-4 bg-slate-50 rounded-xl border {{ $amount_paid >= $finalTotal ? 'border-emerald-200 bg-emerald-50/50' : 'border-slate-100' }}">
                                <span class="text-sm font-semibold text-slate-600">Kembalian</span>
                                <span class="text-xl font-black {{ $amount_paid >= $finalTotal ? 'text-emerald-600' : 'text-slate-400' }}">
                                    Rp{{ number_format(max(0, $this->change), 0, ',', '.') }}
                                </span>
                            </div>
                            
                            @if($amount_paid > 0 && $amount_paid < $finalTotal)
                                <p class="text-rose-500 text-xs font-bold mt-2 text-right">⚠️ Kurang Rp{{ number_format($finalTotal - $amount_paid, 0, ',', '.') }}</p>
                            @endif
                        </div>
                    @else
                         {{-- Non-Cash Feedback --}}
                         @if($selectedPaymentMethod)
                            <div class="bg-sky-50 rounded-2xl p-6 border border-sky-100 text-center animate-fade-in-up">
                                <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center mx-auto mb-3 shadow-sm text-3xl">
                                    ✓
                                </div>
                                <h3 class="text-sky-800 font-bold text-lg mb-1">{{ $selectedPaymentMethod['name'] }} Selected</h3>
                                <p class="text-sky-600 text-sm">Proceed to process exact amount: <span class="font-bold">Rp{{ number_format($finalTotal, 0, ',', '.') }}</span></p>
                            </div>
                        @else
                            <div class="border-2 border-dashed border-slate-200 rounded-2xl p-8 text-center bg-slate-50/50">
                                <p class="text-slate-400 font-medium text-sm">Silakan pilih metode pembayaran di atas</p>
                            </div>
                        @endif
                    @endif

                </div>

                {{-- Footer Actions --}}
                <div class="p-6 bg-white border-t border-slate-100 flex gap-3 pb-safe z-20 shadow-[0_-4px_20px_-5px_rgba(0,0,0,0.05)]">
                    <button wire:click="closeModal"
                        class="flex-1 py-3.5 px-6 rounded-xl border-2 border-slate-200 text-slate-600 font-bold text-sm hover:bg-slate-50 hover:border-slate-300 transition focus:scale-[0.98]">
                        BATAL
                    </button>
                    
                    <button wire:click="processPayment"
                        wire:loading.attr="disabled"
                        class="flex-[2] py-3.5 px-6 rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 text-white font-bold text-sm shadow-lg shadow-violet-200 hover:shadow-xl hover:-translate-y-0.5 transition-all focus:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed disabled:shadow-none flex items-center justify-center gap-2 group"
                        {{ (!$payment_method || ($isCashPayment && $amount_paid < $finalTotal)) ? 'disabled' : '' }}>
                        
                        <span wire:loading.remove class="flex items-center gap-2">
                            <span>PROSES BAYAR</span>
                            <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </span>
                        
                        <span wire:loading class="flex items-center gap-2">
                            <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            MEMPROSES...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    @endif
    
    {{-- Modal Preview Struk (Redesigned) --}}
    @if ($showReceiptPreview)
        <div class="fixed inset-0 z-[60] flex items-center justify-center p-4">
             <div class="absolute inset-0 bg-slate-900/70 backdrop-blur-sm" wire:click="closeReceiptPreview"></div>
            
             <div class="relative w-full max-w-sm bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh] animate-modal-pop">
                {{-- Receipt Header --}}
                <div class="bg-slate-800 text-white px-5 py-3 flex justify-between items-center">
                    <h3 class="font-bold text-sm uppercase tracking-wider">Preview Struk</h3>
                    <button wire:click="closeReceiptPreview" class="text-slate-400 hover:text-white transition">✕</button>
                </div>
                
                {{-- Paper Roll Effect Container --}}
                <div class="flex-1 overflow-y-auto bg-slate-100 p-4">
                    <div class="receipt-preview bg-white p-4 shadow-sm text-xs sm:text-sm font-mono border-t-8 border-slate-800/10 relative">
                        {{-- Jagged Edge Top --}}
                         <div class="absolute top-0 left-0 w-full h-2 bg-[linear-gradient(135deg,transparent_5px,#fff_5px),linear-gradient(225deg,transparent_5px,#fff_5px)] bg-[length:10px_10px] bg-repeat-x mt-[-10px]"></div>
                        
                        {!! $receiptContent !!}
                        
                         {{-- Jagged Edge Bottom --}}
                         <div class="absolute bottom-0 left-0 w-full h-2 bg-[linear-gradient(45deg,transparent_5px,#fff_5px),linear-gradient(-45deg,transparent_5px,#fff_5px)] bg-[length:10px_10px] bg-repeat-x mb-[-10px]"></div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="p-4 bg-white border-t border-slate-200 pb-safe flex gap-2">
                    <button wire:click="manualPrintReceipt" 
                            wire:loading.attr="disabled"
                            class="flex-1 bg-slate-800 hover:bg-slate-900 text-white py-3 rounded-xl font-bold text-sm shadow-lg flex items-center justify-center gap-2 transition active:scale-[0.98]">
                        <span wire:loading.remove>🖨️ CETAK SEKARANG</span>
                         <span wire:loading class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        </span>
                    </button>
                </div>
                 @if ($isPrinting)
                    <div class="absolute bottom-20 left-4 right-4 bg-emerald-600 text-white px-4 py-3 rounded-xl shadow-lg flex items-center justify-center gap-3 animate-slide-up">
                        <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span class="font-bold text-sm">Sedang mencetak...</span>
                    </div>
                 @endif
             </div>
        </div>
    @endif
    
    <style>
        /* Custom Animations */
        @keyframes modal-pop {
            0% { opacity: 0; transform: scale(0.95) translateY(10px); }
            100% { opacity: 1; transform: scale(1) translateY(0); }
        }
        .animate-modal-pop { animation: modal-pop 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
        
        @keyframes fade-in-up {
            0% { opacity: 0; transform: translateY(10px); }
            100% { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in-up { animation: fade-in-up 0.4s ease-out forwards; }
        
        @keyframes slide-up {
            0% { transform: translateY(100%); opacity: 0; }
            100% { transform: translateY(0); opacity: 1; }
        }
        .animate-slide-up { animation: slide-up 0.3s ease-out forwards; }

        /* Safe Area for iOS */
        .pb-safe {
            padding-bottom: max(1.5rem, env(safe-area-inset-bottom));
        }
    </style>

    <script>
    // Fungsi untuk mengontrol bottom nav bar
    function toggleBottomNav(show) {
        const bottomNav = document.querySelector('.mobile-bottom-nav');
        if (bottomNav) {
            bottomNav.style.display = show ? 'block' : 'none';
            // Add padding to body if hidden to prevent layout jump
            document.body.style.paddingBottom = show ? '80px' : '0'; 
        }
    }

    function printReceipt() {
        const receiptContent = document.querySelector('.receipt-preview').innerHTML;
        
        const printWindow = window.open('', '_blank', 'width=350,height=600');
        
        const printStyle = `
            <style>
                @media print {
                    body { 
                        margin: 0; 
                        padding: 10px; 
                        font-family: 'Courier New', monospace;
                        font-size: 12px;
                    }
                    .text-center { text-align: center; }
                    .font-bold { font-weight: bold; }
                    .text-lg { font-size: 14px; }
                    .text-sm { font-size: 11px; }
                    .text-xs { font-size: 10px; }
                    .uppercase { text-transform: uppercase; }
                    .flex { display: flex; }
                    .justify-between { justify-content: space-between; }
                    .items-start { align-items: flex-start; }
                    .flex-1 { flex: 1; }
                    .border-t { border-top: 1px solid #000; }
                    .border-dashed { border-style: dashed; }
                    .my-2 { margin-top: 0.5rem; margin-bottom: 0.5rem; }
                    .font-semibold { font-weight: 600; }
                }
            </style>
        `;

        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Struk Pembayaran</title>
                ${printStyle}
            </head>
            <body>
                ${receiptContent}
                <script>
                    window.onload = function() {
                        window.print();
                        setTimeout(() => window.close(), 1000);
                    };
                <\/script>
            </body>
            </html>
        `);
        
        printWindow.document.close();
    }

    // Event listener untuk Livewire
    document.addEventListener('livewire:initialized', () => {
        // Sembunyikan bottom nav ketika modal payment terbuka
        Livewire.on('showModal', () => {
            document.body.style.overflow = 'hidden';
            toggleBottomNav(false);
        });
        
        // Tampilkan kembali bottom nav ketika modal ditutup
        Livewire.on('closeModal', () => {
            document.body.style.overflow = '';
            toggleBottomNav(true);
        });

        // Handle untuk receipt preview
        Livewire.on('showReceiptPreview', () => {
            toggleBottomNav(false);
        });
        
        Livewire.on('closeReceiptPreview', () => {
            toggleBottomNav(true);
        });

        Livewire.on('printReceiptDirect', (event) => {
            printReceiptDirect(event.content);
        });
        
        // Listen untuk webhook print response
        Livewire.on('webhookPrintResponse', (event) => {
            if (event.success) {
                // Optional: Toast message
                console.log('✅ Print success');
            } else {
                console.error('❌ Print failed', event.error);
                alert('Gagal mencetak: ' + event.error);
            }
        });
    });

    // Fungsi untuk print langsung (opsional)
    function printReceiptDirect(content) {
        const printWindow = window.open('', '_blank', 'width=1,height=1');
        const printStyle = `<style>@media print { body { margin: 0; padding: 10px; font-family: 'Courier New', monospace; font-size: 12px; } .text-center { text-align: center; } .font-bold { font-weight: bold; } .text-sm { font-size: 11px; } .flex { display: flex; } .justify-between { justify-content: space-between; } }</style>`;
        printWindow.document.write(`<html><head><title>Struk</title>${printStyle}</head><body onload="window.print(); setTimeout(() => window.close(), 500);">${content}</body></html>`);
        printWindow.document.close();
    }
    
    // Handle mobile viewport height dan bottom navigation
    function setupModalForMobile() {
        if (window.innerWidth <= 640) {
            // Set viewport height yang aman
            const vh = window.innerHeight * 0.01;
            document.documentElement.style.setProperty('--vh', `${vh}px`);
        }
    }
    
    setupModalForMobile();
    window.addEventListener('resize', setupModalForMobile);
    </script>
</div>
<div>
    @if ($show)
        <div class="fixed inset-0 z-50 flex items-end justify-center sm:items-center p-4">
            {{-- Background Backdrop (Blur) --}}
            <div class="absolute inset-0 bg-slate-900/80 transition-opacity" 
                 wire:click="closeModal"></div>

            {{-- Main Modal Container --}}
            <div class="relative w-full max-w-lg bg-white rounded-t-2xl sm:rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[95vh] animate-modal-pop">
                
                {{-- Header --}}
                <div class="bg-white px-6 py-4 flex items-center justify-between flex-shrink-0 border-b border-slate-100/50">
                    <div>
                        <h2 class="text-lg font-bold text-slate-800 tracking-tight">{{ __('messages.complete_payment') }}</h2>
                        <p class="text-slate-400 text-xs mt-0.5 font-medium">Transaction <span class="font-mono text-violet-500">#{{ $invoiceNumber }}</span></p>
                    </div>
                    <button wire:click="closeModal" class="text-slate-400 hover:text-slate-600 transition p-2 rounded-full hover:bg-slate-100 touch-target">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                {{-- Scrollable Content --}}
                <div class="flex-1 overflow-y-auto bg-slate-50/50 p-4 space-y-4 sm:p-6 sm:space-y-6">
                    
                    {{-- 1. Total Tagihan (Hero Card) --}}
                    <div class="bg-white rounded-2xl shadow-[0_4px_20px_-10px_rgba(0,0,0,0.08)] border border-slate-100 p-5 text-center relative overflow-hidden group">
                        <div class="absolute inset-0 bg-gradient-to-br from-violet-500/5 to-fuchsia-500/5 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                        <p class="text-slate-500 text-xs font-bold uppercase tracking-widest mb-2">{{ __('messages.total_bill') }}</p>
                        <h3 class="text-4xl sm:text-5xl font-black text-slate-800 tracking-tight flex justify-center items-start gap-1">
                            <span class="text-xl sm:text-2xl text-slate-400 font-bold mt-1">Rp</span>{{ number_format($finalTotal, 0, ',', '.') }}
                        </h3>
                        @if($customerName)
                            <div class="mt-4 inline-flex items-center px-3 py-1 bg-violet-50 border border-violet-100 text-violet-700 rounded-full text-xs font-bold shadow-sm">
                                <span class="mr-1.5 opacity-70">👤</span> {{ $customerName }}
                            </div>
                        @endif
                    </div>

                    {{-- 2. Metode Pembayaran (Grid) --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-3 ml-1">{{ __('messages.payment_method_label') }}</label>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            @foreach($paymentMethods as $method)
                                <button 
                                    wire:click="$set('payment_method', '{{ $method['id'] }}')"
                                    class="group relative flex flex-col items-center justify-center p-4 rounded-2xl border transition-all duration-200 touch-target
                                    {{ $payment_method == $method['id'] 
                                        ? 'bg-violet-600 border-violet-600 shadow-lg shadow-violet-200 scale-[1.02]' 
                                        : 'bg-white border-slate-200 hover:border-violet-300 hover:shadow-md' }}">
                                    
                                    {{-- Radio Check --}}
                                    @if($payment_method == $method['id'])
                                        <div class="absolute top-2 right-2 bg-white/20 rounded-full p-1">
                                            <svg class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                        </div>
                                    @endif

                                    <span class="text-2xl mb-2 transition-transform duration-300 group-hover:scale-110 {{ $payment_method == $method['id'] ? '' : 'grayscale group-hover:grayscale-0' }}">
                                        @if(stripos($method['name'], 'cash') !== false) 💵
                                        @elseif(stripos($method['name'], 'qris') !== false) 📱
                                        @elseif(stripos($method['name'], 'card') !== false) 💳
                                        @else 🪙
                                        @endif
                                    </span>
                                    <span class="text-xs font-bold {{ $payment_method == $method['id'] ? 'text-white' : 'text-slate-600' }} text-center leading-tight">
                                        {{ $method['name'] }}
                                    </span>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- 3. Input Pembayaran (Contextual) --}}
                    @if($isCashPayment)
                        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm animate-fade-in-up">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">{{ __('messages.amount_received') }}</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
                                    <span class="text-slate-400 text-xl font-bold">Rp</span>
                                </div>
                                <input type="number" 
                                       wire:model.live="amount_paid"
                                       wire:keydown.enter="processPayment"
                                       class="block w-full pl-14 pr-5 py-4 text-3xl font-bold text-slate-800 bg-slate-50 border-2 border-slate-100 rounded-xl focus:ring-0 focus:border-violet-500 focus:bg-white transition-all placeholder-slate-300"
                                       placeholder="0"
                                       inputmode="numeric"
                                       autofocus>
                            </div>

                            {{-- Kembalian Display (Refined) --}}
                            <div class="mt-4 overflow-hidden rounded-xl border {{ $amount_paid >= $finalTotal ? 'border-emerald-200 bg-emerald-50' : 'border-slate-100 bg-slate-50' }} transition-colors duration-300">
                                <div class="flex items-center justify-between p-4">
                                    <div class="flex flex-col">
                                        <span class="text-xs font-bold uppercase tracking-wider {{ $amount_paid >= $finalTotal ? 'text-emerald-600' : 'text-slate-500' }}">{{ __('messages.change') }}</span>
                                        @if($amount_paid > 0 && $amount_paid < $finalTotal)
                                            <span class="text-[10px] font-bold text-rose-500 mt-0.5">⚠️ {{ __('messages.shortage') }} {{ number_format($finalTotal - $amount_paid, 0, ',', '.') }}</span>
                                        @endif
                                    </div>
                                    <span class="text-2xl font-black {{ $amount_paid >= $finalTotal ? 'text-emerald-600' : 'text-slate-300' }}">
                                        Rp{{ number_format(max(0, $this->change), 0, ',', '.') }}
                                    </span>
                                </div>
                                {{-- Suggested Amounts Pills (Optional - Just Visual for now or logic can be added later) --}}
                                @if($finalTotal > 0 && $amount_paid == 0)
                                    <div class="px-4 pb-3 flex gap-2 overflow-x-auto no-scrollbar">
                                        <button wire:click="$set('amount_paid', {{ $finalTotal }})" class="flex-shrink-0 px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-bold text-slate-600 hover:border-violet-300 hover:text-violet-600 transition">
                                            {{ __('messages.exact_amount') }}
                                        </button>
                                        <button wire:click="$set('amount_paid', {{ ceil($finalTotal / 10000) * 10000 }})" class="flex-shrink-0 px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-bold text-slate-600 hover:border-violet-300 hover:text-violet-600 transition">
                                            {{ number_format(ceil($finalTotal / 10000) * 10000, 0, ',', '.') }}
                                        </button>
                                        <button wire:click="$set('amount_paid', {{ ceil($finalTotal / 50000) * 50000 }})" class="flex-shrink-0 px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-bold text-slate-600 hover:border-violet-300 hover:text-violet-600 transition">
                                            {{ number_format(ceil($finalTotal / 50000) * 50000, 0, ',', '.') }}
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @else
                         {{-- Non-Cash Feedback --}}
                         @if($selectedPaymentMethod)
                            <div class="bg-violet-50 rounded-2xl p-8 border border-violet-100 text-center animate-fade-in-up">
                                <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center mx-auto mb-4 shadow-sm text-4xl ring-4 ring-violet-100">
                                    @if(stripos($selectedPaymentMethod['name'], 'qris') !== false) 📱
                                    @elseif(stripos($selectedPaymentMethod['name'], 'card') !== false) 💳
                                    @else ✓
                                    @endif
                                </div>
                                <h3 class="text-violet-900 font-bold text-lg mb-1">{{ $selectedPaymentMethod['name'] }} {{ __('messages.selected') }}</h3>
                                <p class="text-violet-600 text-sm">{{ __('messages.process_payment_amount') }} <span class="font-bold">Rp{{ number_format($finalTotal, 0, ',', '.') }}</span></p>
                            </div>
                        @else
                            <div class="border-2 border-dashed border-slate-200 rounded-2xl p-10 text-center bg-slate-50/50">
                                <p class="text-slate-400 font-bold text-sm">{{ __('messages.select_payment_method') }}</p>
                            </div>
                        @endif
                    @endif

                </div>

                {{-- Footer Actions --}}
                <div class="p-4 sm:p-6 bg-white border-t border-slate-100 flex gap-3 z-20 shadow-[0_-4px_20px_-5px_rgba(0,0,0,0.05)]"
                     style="padding-bottom: calc(1.5rem + env(safe-area-inset-bottom));">
                    <button wire:click="closeModal"
                        class="flex-1 py-3.5 px-6 rounded-xl bg-white border-2 border-slate-200 text-slate-600 font-bold text-sm hover:bg-slate-50 hover:border-slate-300 hover:text-slate-800 transition focus:scale-[0.98] shadow-sm">
                        {{ __('messages.cancel') }}
                    </button>
                    
                    <button wire:click="processPayment"
                        wire:loading.attr="disabled"
                        wire:target="processPayment"
                        class="flex-[2] py-3.5 px-6 rounded-xl bg-slate-900 text-white font-bold text-sm shadow-lg shadow-slate-200 hover:bg-black hover:shadow-xl hover:-translate-y-0.5 transition-all focus:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed disabled:shadow-none flex items-center justify-center gap-2 group"
                        {{ (!$payment_method || ($isCashPayment && $amount_paid < $finalTotal)) ? 'disabled' : '' }}>
                        
                        <span wire:loading.remove wire:target="processPayment" class="flex items-center gap-2">
                            <span>{{ __('messages.process_pay') }}</span>
                            <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </span>
                        
                        <span wire:loading wire:target="processPayment" class="flex items-center gap-2">
                            <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ __('messages.processing') }}
                        </span>
                    </button>
                </div>
            </div>
        </div>
    @endif
    
    {{-- Modal Preview Struk (Redesigned & Fixed Formatting) --}}
    @if ($showReceiptPreview)
        <div class="fixed inset-0 z-[60] flex items-center justify-center p-4">
             <div class="absolute inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity" wire:click="closeReceiptPreview"></div>
            
             <div class="relative w-full max-w-sm bg-transparent flex flex-col items-center animate-modal-pop">
                {{-- Paper Receipt --}}
                <div class="w-full bg-white shadow-2xl overflow-hidden relative" style="filter: drop-shadow(0 20px 13px rgba(0, 0, 0, 0.1));">
                    {{-- Jagged Top --}}
                    <div class="w-full h-4 bg-slate-900 relative z-10"></div>
                     <div class="w-full h-2 bg-[linear-gradient(135deg,transparent_5px,#fff_5px),linear-gradient(225deg,transparent_5px,#fff_5px)] bg-[length:10px_10px] bg-repeat-x mt-[-5px] relative z-20"></div>

                    {{-- Receipt Content --}}
                    <div class="bg-white px-0 py-6 pb-8 flex justify-center w-full">
                         {{-- Container for Receipt - Let the Component handle styles --}}
                         <div class="receipt-preview w-full flex justify-center">
                            {!! $receiptContent !!}
                         </div>
                    </div>

                    {{-- Jagged Bottom --}}
                     <div class="w-full h-3 bg-[linear-gradient(45deg,transparent_6px,#fff_6px),linear-gradient(-45deg,transparent_6px,#fff_6px)] bg-[length:12px_12px] bg-repeat-x mb-[-6px]"></div>
                </div>

                {{-- Action Buttons --}}
                <div class="mt-6 flex flex-col gap-3 w-full max-w-xs">
                    <div class="flex gap-3 w-full">
                         <button wire:click="closeReceiptPreview" 
                                class="flex-1 bg-white/10 hover:bg-white/20 text-white border border-white/20 py-3 rounded-xl font-bold text-sm transition">
                            {{ __('messages.close') }}
                        </button>
                        <button wire:click="manualPrintReceipt" 
                                wire:loading.attr="disabled"
                                wire:target="manualPrintReceipt"
                                class="flex-1 bg-emerald-500 hover:bg-emerald-600 text-white py-3 rounded-xl font-bold text-sm shadow-lg flex items-center justify-center gap-2 transition active:scale-[0.98] group">
                             <span wire:loading.remove wire:target="manualPrintReceipt" class="flex items-center gap-2">
                                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                 {{ __('messages.print') }}
                             </span>
                              <span wire:loading wire:target="manualPrintReceipt" class="animate-spin">
                                 <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                             </span>
                        </button>
                    </div>
                    
                    {{-- Secondary Option: Browser Print --}}
                    <button onclick="printReceipt()" 
                            class="w-full bg-white/5 hover:bg-white/10 text-slate-300 hover:text-white py-2 rounded-xl font-bold text-xs transition border border-white/10">
                        🖨️ {{ __('messages.print_browser') }}
                    </button>
                </div>
                
                 @if ($isPrinting)
                    <div class="fixed bottom-10 left-1/2 -translate-x-1/2 bg-slate-900 text-white px-6 py-3 rounded-full shadow-xl flex items-center gap-3 animate-slide-up z-[70]">
                        <svg class="animate-spin h-5 w-5 text-emerald-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span class="font-bold text-sm">{{ __('messages.sending_to_printer') }}</span>
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
            0% { transform: translate(-50%, 100%); opacity: 0; }
            100% { transform: translate(-50%, 0); opacity: 1; }
        }
        .animate-slide-up { animation: slide-up 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards; }

    </style>

    @inject('settings', 'App\Settings\GeneralSettings')
    @php
        $printerWidth = $settings->printer_width ?? '58mm';
    @endphp

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
        const receiptContainer = document.querySelector('.receipt-preview');
        if (!receiptContainer) {
            alert('Receipt content not found!');
            return;
        }
        const receiptContent = receiptContainer.innerHTML;
        const width = '{{ $printerWidth }}';
        
        const printWindow = window.open('', '_blank', 'width=400,height=600');
        
        const printStyle = `
            <style>
                @media print {
                    body { 
                        margin: 0; 
                        padding: 0; 
                        font-family: 'Courier New', monospace;
                        width: ${width}; 
                    }
                    @page {
                        margin: 0;
                        size: ${width} auto; 
                    }
                }
                /* Screen styles if viewed in browser */
                body {
                    font-family: 'Courier New', monospace;
                    padding: 20px;
                    background: #f3f4f6;
                    display: flex;
                    justify-content: center;
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
            <body onload="window.print(); setTimeout(() => window.close(), 1000);">
                ${receiptContent}
            </body>
            </html>
        `);
        
        printWindow.document.close();
    }

    // Event listener untuk Livewire
    document.addEventListener('livewire:initialized', () => {
        // ... (rest of the listeners)
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
        // ... (keep this as backup or remove if handled by new logic)
        // For now, keeping as is but we are using the new printReceipt() above for the main button
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
</div>
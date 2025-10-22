<div>
    @if ($show)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            {{-- Background overlay --}}
            <div class="absolute inset-0 bg-gray-900 bg-opacity-75" wire:click="closeModal"></div>

            {{-- Struk Container --}}
            <div class="relative w-full max-w-sm mx-auto">
                {{-- Struk Content --}}
                <div class="bg-white border border-gray-300 shadow-2xl">
                    {{-- Header Struk --}}
                    <div class="text-center border-b border-gray-300 py-4 px-4 bg-gray-50">
                        <h1 class="text-lg font-bold uppercase tracking-tight text-gray-900">STRUK PEMBAYARAN</h1>
                        <p class="text-xs text-gray-600 mt-1 font-medium">{{ now()->format('d/m/Y H:i') }}</p>
                    </div>

                    {{-- Info Transaksi --}}
                    <div class="py-3 px-4 border-b border-gray-200">
                        <div class="space-y-1.5 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600 font-medium">No. Transaksi:</span>
                                <span class="font-semibold text-gray-900">{{ $invoiceNumber }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 font-medium">Kasir:</span>
                                <span class="font-semibold text-gray-900">{{ auth()->user()->name }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 font-medium">Customer:</span>
                                <span class="font-semibold text-gray-900 text-right max-w-[120px] truncate">{{ $customerName ?? 'Umum' }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Daftar Produk --}}
                    <div class="py-3 px-4 border-b border-gray-200">
                        <div class="text-center font-bold text-sm mb-3 text-gray-900">DAFTAR PESANAN</div>
                        <div class="space-y-2.5 text-sm">
                            @if($saleItems && count($saleItems) > 0)
                                @foreach($saleItems as $item)
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1 pr-2">
                                            <div class="font-semibold text-gray-900 text-[13px] leading-tight">
                                                {{ $item['name'] }}
                                            </div>
                                            <div class="text-xs text-gray-500 mt-0.5">
                                                {{ $item['quantity'] }} × Rp{{ number_format($item['price'], 0, ',', '.') }}
                                            </div>
                                        </div>
                                        <div class="text-right font-semibold text-gray-900 whitespace-nowrap">
                                            Rp{{ number_format($item['subtotal'], 0, ',', '.') }}
                                        </div>
                                    </div>
                                    @if(!$loop->last)
                                        <div class="border-t border-dashed border-gray-100"></div>
                                    @endif
                                @endforeach
                            @else
                                <div class="text-center text-gray-500 text-sm py-3">
                                    Tidak ada item dalam pesanan
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Ringkasan Pembayaran --}}
                    <div class="py-3 px-4 border-b border-gray-200">
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Subtotal:</span>
                                <span class="font-medium text-gray-900">Rp{{ number_format($subtotal ?? 0, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Pajak (10%):</span>
                                <span class="font-medium text-gray-900">Rp{{ number_format($tax ?? 0, 0, ',', '.') }}</span>
                            </div>
                            @if(($discount ?? 0) > 0)
                                <div class="flex justify-between text-green-600">
                                    <span>Diskon:</span>
                                    <span class="font-medium">- Rp{{ number_format($discount ?? 0, 0, ',', '.') }}</span>
                                </div>
                            @endif
                            <div class="border-t border-gray-300 pt-2 mt-1">
                                <div class="flex justify-between text-base font-bold text-gray-900">
                                    <span>TOTAL:</span>
                                    <span>Rp{{ number_format($finalTotal, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Metode Pembayaran --}}
                    <div class="py-3 px-4 border-b border-gray-200">
                        <div class="text-center font-bold text-sm mb-3 text-gray-900">PEMBAYARAN</div>
                        
                        {{-- Pilihan Metode --}}
                        <div class="flex justify-between items-center text-sm mb-3">
                            <span class="text-gray-600 font-medium">Metode:</span>
                            <select wire:model.live="payment_method" 
                                    class="border border-gray-300 rounded px-2 py-1 text-sm font-medium focus:ring-1 focus:ring-green-500 focus:border-green-500 cursor-pointer bg-white">
                                <option value="">Pilih Metode</option>
                                @foreach($paymentMethods as $method)
                                    <option value="{{ $method['id'] }}">{{ $method['name'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Input Cash --}}
                        @if($isCashPayment)
                            <div class="space-y-2.5 bg-gray-50 p-3 rounded border border-gray-200">
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-gray-600 font-medium">Bayar:</span>
                                    <div class="relative">
                                        <span class="absolute left-2 top-1/2 transform -translate-y-1/2 text-gray-500 text-sm">Rp</span>
                                        <input type="number" 
                                               wire:model.live="amount_paid"
                                               wire:keydown.enter="processPayment"
                                               class="pl-7 pr-2 py-1.5 border border-gray-300 rounded text-right font-semibold w-28 focus:ring-1 focus:ring-green-500 focus:border-green-500 bg-white"
                                               placeholder="0"
                                               min="{{ $finalTotal }}"
                                               autofocus>
                                    </div>
                                </div>
                                
                                <div class="flex justify-between items-center text-sm pt-2 border-t border-gray-200">
                                    <span class="text-gray-600 font-medium">Kembali:</span>
                                    <span class="font-bold text-lg {{ $amount_paid < $finalTotal ? 'text-red-600' : 'text-green-600' }}">
                                        Rp{{ number_format($this->change, 0, ',', '.') }}
                                    </span>
                                </div>

                                @if($amount_paid < $finalTotal)
                                    <div class="text-center text-xs text-red-600 font-medium bg-red-50 py-1.5 rounded border border-red-200 mt-2">
                                        ⚠️ Jumlah bayar kurang!
                                    </div>
                                @endif
                            </div>
                        @else
                            {{-- Non-cash payment --}}
                            @if($selectedPaymentMethod)
                                <div class="text-center bg-blue-50 border border-blue-200 rounded py-3">
                                    <div class="font-semibold text-blue-800 text-sm mb-1">
                                        {{ strtoupper($selectedPaymentMethod['name']) }}
                                    </div>
                                    <div class="text-xs text-blue-600 font-medium">
                                        Rp{{ number_format($finalTotal, 0, ',', '.') }}
                                    </div>
                                    <div class="text-xs text-blue-500 mt-1">
                                        Jumlah bayar otomatis disesuaikan
                                    </div>
                                </div>
                            @else
                                <div class="text-center bg-yellow-50 border border-yellow-200 rounded py-3">
                                    <div class="text-xs text-yellow-700">
                                        Pilih metode pembayaran terlebih dahulu
                                    </div>
                                </div>
                            @endif
                        @endif
                    </div>

                    {{-- Footer Struk --}}
                    <div class="py-4 px-4 text-center bg-gray-50">
                        <p class="text-xs text-gray-600 mb-1">Terima kasih atas kunjungan Anda</p>
                        <p class="text-xs font-semibold text-gray-700">*** SELAMAT MENIKMATI ***</p>
                    </div>
                </div>

                {{-- Tombol Action --}}
                <div class="flex space-x-3 mt-4">
                    <button wire:click="closeModal"
                            class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-3 font-semibold text-sm cursor-pointer transition-colors rounded shadow-sm">
                        BATAL
                    </button>
                    <button wire:click="processPayment"
                            wire:loading.attr="disabled"
                            class="flex-1 bg-green-600 hover:bg-green-700 text-white py-3 font-semibold text-sm cursor-pointer transition-colors rounded shadow-sm disabled:opacity-50 disabled:cursor-not-allowed"
                            {{ (!$payment_method || ($isCashPayment && $amount_paid < $finalTotal)) ? 'disabled' : '' }}>
                        <span wire:loading.remove>BAYAR</span>
                        <span wire:loading>MEMPROSES...</span>
                    </button>
                </div>
            </div>
        </div>

        <style>
            @keyframes struk-appear {
                from { 
                    opacity: 0; 
                    transform: scale(0.95) translateY(-10px); 
                }
                to { 
                    opacity: 1; 
                    transform: scale(1) translateY(0); 
                }
            }
            
            .fixed.inset-0 {
                animation: struk-appear 0.2s ease-out;
            }
        </style>
    @endif
    
    {{-- Modal Preview Struk --}}
    @if ($showReceiptPreview)
        <div class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black bg-opacity-50">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-sm mx-auto">
                {{-- Header --}}
                <div class="flex justify-between items-center p-4 border-b">
                    <h3 class="text-lg font-bold">Preview Struk</h3>
                    <button wire:click="closeReceiptPreview" class="text-gray-500 hover:text-gray-700">
                        ✕
                    </button>
                </div>

                {{-- Content Struk --}}
                <div class="p-4 max-h-96 overflow-y-auto">
                    <div class="receipt-preview text-sm">
                        {!! $receiptContent !!}
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex space-x-2 p-4 border-t">
                    <button wire:click="closeReceiptPreview" 
                            class="flex-1 bg-gray-500 text-white py-2 rounded font-medium">
                        TUTUP
                    </button>
                    <button onclick="printReceipt()" 
                            class="flex-1 bg-green-600 text-white py-2 rounded font-medium">
                        🖨️ CETAK
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

<script>
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
                .space-y-1 > * + * { margin-top: 0.25rem; }
                .space-y-2 > * + * { margin-top: 0.5rem; }
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
    Livewire.on('printReceiptDirect', (event) => {
        printReceiptDirect(event.content);
    });
});

// Fungsi untuk print langsung (opsional)
function printReceiptDirect(content) {
    const printWindow = window.open('', '_blank', 'width=1,height=1');
    
    const printStyle = `
        <style>
            @media print {
                body { margin: 0; padding: 10px; font-family: 'Courier New', monospace; font-size: 12px; }
                .text-center { text-align: center; }
                .font-bold { font-weight: bold; }
                .text-sm { font-size: 11px; }
                .flex { display: flex; }
                .justify-between { justify-content: space-between; }
            }
        </style>
    `;

    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head><title>Struk</title>${printStyle}</head>
        <body onload="window.print(); setTimeout(() => window.close(), 500);">
            ${content}
        </body>
        </html>
    `);
    
    printWindow.document.close();
}
</script>
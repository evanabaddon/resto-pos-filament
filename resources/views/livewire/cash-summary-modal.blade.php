<!-- File: resources/views/livewire/cash-summary-modal.blade.php -->
<div>
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center">
            {{-- Background blur --}}
            <div class="absolute inset-0 bg-gray-900/80" wire:click="closeModal"></div>

            {{-- Modal box --}}
            <div
                class="relative bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto animate-fade-in">

                {{-- Header --}}
                <div class="sticky top-0 bg-white border-b border-gray-200 p-6 rounded-t-xl">
                    <div class="flex justify-between items-center">
                        <div>
                            <h2 class="text-xl font-bold text-gray-800">Rekap Sesi Kas</h2>
                            <p class="text-sm text-gray-600 mt-1">
                                Sesi dibuka: {{ $session->opened_at->format('d/m/Y H:i') }}
                                ({{ $summary['session_duration'] ?? '0 jam' }})
                            </p>
                        </div>
                        <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600 cursor-pointer">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Content --}}
                <div class="p-6 space-y-6">

                    {{-- Section 1: Cash In Hand & Expected Cash --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-blue-50 p-4 rounded-lg border border-blue-100">
                            <h3 class="text-sm font-medium text-blue-800 mb-1">Kas Awal</h3>
                            <p class="text-2xl font-bold text-blue-900">
                                Rp {{ number_format($summary['cash_in_hand'] ?? 0, 0, ',', '.') }}
                            </p>
                        </div>

                        <div class="bg-green-50 p-4 rounded-lg border border-green-100">
                            <h3 class="text-sm font-medium text-green-800 mb-1">Uang Seharusnya</h3>
                            <p class="text-2xl font-bold text-green-900">
                                Rp {{ number_format($summary['expected_cash'] ?? 0, 0, ',', '.') }}
                            </p>
                            <p class="text-[10px] text-green-600 mt-1">
                                = Kas Awal + Penjualan Cash - Pengeluaran Cash - Pembelian Cash
                            </p>
                        </div>
                    </div>

                    {{-- Section 2: Sales by Payment Method --}}
                    <div class="border border-gray-200 rounded-lg p-4">
                        <h3 class="text-sm font-semibold text-gray-700 mb-4">Penjualan per Metode Pembayaran</h3>

                        @if(!empty($summary['payment_method_sales']))
                            <div class="space-y-2">
                                @foreach($summary['payment_method_sales'] as $code => $amount)
                                    @php
                                        $method = $paymentMethods->get($code);
                                        $methodName = $method ? $method->name : ucfirst(str_replace('_', ' ', $code));
                                        $color = $method->color ?? 'gray';
                                        $colorHex = $this->getPaymentMethodColorClass($color);
                                    @endphp
                                    <div class="flex justify-between items-center p-2 rounded"
                                        style="background-color: {{ $colorHex }}10;">
                                        <div class="flex items-center space-x-2">
                                            <div class="w-3 h-3 rounded-full" style="background-color: {{ $colorHex }};"></div>
                                            <span class="text-sm font-medium text-gray-700">
                                                {{ $methodName }}
                                            </span>
                                        </div>
                                        <span class="text-sm font-bold text-gray-900">
                                            Rp {{ number_format($amount, 0, ',', '.') }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Total Sales --}}
                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-semibold text-gray-700">Total Penjualan</span>
                                    <span class="text-lg font-bold text-gray-900">
                                        Rp {{ number_format($summary['total_sales'] ?? 0, 0, ',', '.') }}
                                    </span>
                                </div>
                            </div>
                        @else
                            <p class="text-xs text-gray-500 text-center py-4">Belum ada penjualan</p>
                        @endif
                    </div>

                    {{-- Section 2.5: Unpaid Sales Warning --}}
                    @if(($summary['unpaid_count'] ?? 0) > 0)
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <div class="flex items-start space-x-3">
                                <svg class="w-5 h-5 text-yellow-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <div class="flex-1">
                                    <h3 class="text-sm font-semibold text-yellow-800 mb-1">Penjualan Belum Dibayar</h3>
                                    <div class="flex justify-between items-center">
                                        <span class="text-xs text-yellow-700">
                                            {{ $summary['unpaid_count'] }} transaksi pending
                                        </span>
                                        <span class="text-lg font-bold text-yellow-900">
                                            Rp {{ number_format($summary['unpaid_sales'] ?? 0, 0, ',', '.') }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-yellow-600 mt-2">
                                        ⚠️ Transaksi ini belum masuk perhitungan kas
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Section 3: Cash Expenses & Purchases --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex justify-between items-center mb-3">
                                <h3 class="text-xs font-semibold text-gray-700">Pengeluaran (Cash)</h3>
                                <span class="text-[10px] text-gray-500">
                                    {{ $summary['expense_count'] ?? 0 }} transaksi
                                </span>
                            </div>

                            @if(($summary['expense_count'] ?? 0) > 0)
                                <div class="bg-red-50 p-2 rounded">
                                    <div class="flex justify-between items-center">
                                        <span class="text-xs font-medium text-red-700">Total</span>
                                        <span class="text-base font-bold text-red-900">
                                            Rp {{ number_format($summary['total_cash_expenses'] ?? 0, 0, ',', '.') }}
                                        </span>
                                    </div>
                                </div>
                            @else
                                <p class="text-[10px] text-gray-500 text-center py-2">Tidak ada</p>
                            @endif
                        </div>

                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex justify-between items-center mb-3">
                                <h3 class="text-xs font-semibold text-gray-700">Pembelian Stok (Cash)</h3>
                                <span class="text-[10px] text-gray-500">
                                    {{ $summary['purchase_count'] ?? 0 }} transaksi
                                </span>
                            </div>

                            @if(($summary['purchase_count'] ?? 0) > 0)
                                <div class="bg-orange-50 p-2 rounded">
                                    <div class="flex justify-between items-center">
                                        <span class="text-xs font-medium text-orange-700">Total</span>
                                        <span class="text-base font-bold text-orange-900">
                                            Rp {{ number_format($summary['total_cash_purchases'] ?? 0, 0, ',', '.') }}
                                        </span>
                                    </div>
                                </div>
                            @else
                                <p class="text-[10px] text-gray-500 text-center py-2">Tidak ada</p>
                            @endif
                        </div>
                    </div>

                    {{-- Section 4: Cash Out & Difference --}}
                    <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                        <h3 class="text-sm font-semibold text-gray-700 mb-4">Kas Akhir & Selisih</h3>

                        {{-- Input Cash Out --}}
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Jumlah Kas Akhir (Fisik di Laci)
                            </label>
                            <div class="flex items-center space-x-3">
                                <div class="relative flex-1">
                                    <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">Rp</span>
                                    <input type="number" wire:model="manualCashOut" wire:change="updateCashOut"
                                        class="w-full border border-gray-300 rounded-lg pl-10 pr-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                        placeholder="Masukkan jumlah kas akhir" min="0" step="1000" />
                                </div>
                                <button wire:click="updateCashOut"
                                    class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition cursor-pointer">
                                    Update
                                </button>
                            </div>
                            @if($actualCashOut > 0)
                                <p class="text-xs text-gray-500 mt-1">
                                    Terakhir diisi: Rp {{ number_format($actualCashOut, 0, ',', '.') }}
                                </p>
                            @endif
                        </div>

                        {{-- Cash Difference --}}
                        @if($actualCashOut > 0)
                            <div class="border-t border-gray-200 pt-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-medium text-gray-700">Selisih Kas</span>
                                    <span
                                        class="text-lg font-bold {{ $cashDifference >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $cashDifference >= 0 ? '+' : '' }}Rp
                                        {{ number_format($cashDifference, 0, ',', '.') }}
                                    </span>
                                </div>
                                <p class="text-xs {{ $cashDifference >= 0 ? 'text-green-600' : 'text-red-600' }} mt-1">
                                    @if($cashDifference > 0)
                                        💰 Lebih Rp {{ number_format($cashDifference, 0, ',', '.') }}
                                    @elseif($cashDifference < 0)
                                        ⚠️ Kurang Rp {{ number_format(abs($cashDifference), 0, ',', '.') }}
                                    @else
                                        ✅ Tepat
                                    @endif
                                </p>
                            </div>
                        @endif
                    </div>

                    {{-- Statistics --}}
                    <div class="grid grid-cols-2 gap-4 text-center">
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <p class="text-xs text-gray-500">Total Transaksi</p>
                            <p class="text-lg font-bold text-gray-900">{{ $summary['transaction_count'] ?? 0 }}</p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <p class="text-xs text-gray-500">Rata-rata Transaksi</p>
                            <p class="text-lg font-bold text-gray-900">
                                Rp {{ number_format($summary['average_transaction'] ?? 0, 0, ',', '.') }}
                            </p>
                        </div>
                    </div>

                </div>

                {{-- Footer with Close Session Button --}}
                <div class="sticky bottom-0 bg-white border-t border-gray-200 p-6 rounded-b-xl">
                    <div class="flex justify-between items-center">
                        <button wire:click="closeModal"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition cursor-pointer">
                            Tutup Preview
                        </button>

                        <button wire:click="closeCashSession" @if(empty($manualCashOut)) disabled @endif
                            class="px-6 py-2 text-sm font-medium text-white rounded-lg shadow-sm transition flex items-center cursor-pointer 
                                                   {{ empty($manualCashOut) ? 'bg-gray-400 cursor-not-allowed opacity-70' : 'bg-red-600 hover:bg-red-700' }}">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            Tutup Sesi Kas
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <style>
            @keyframes fade-in {
                0% {
                    opacity: 0;
                    transform: translateY(-10px);
                }

                100% {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .animate-fade-in {
                animation: fade-in 0.25s ease-out forwards;
            }
        </style>
    @endif
</div>
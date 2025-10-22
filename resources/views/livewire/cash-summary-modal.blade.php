<div>
    <!-- Modal Backdrop -->
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <!-- Backdrop -->
            <div class="fixed inset-0 backdrop-blur-sm bg-opacity-50 transition-opacity animate-fade-in-backdrop"
                 wire:click="closeModal"></div>

            <!-- Modal -->
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl animate-fade-in">
                    
                    <!-- Header -->
                    <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0">
                                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                              d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01m12-.01a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-white">Cash Summary</h3>
                                    <p class="text-blue-100 text-sm">Ringkasan sesi kas saat ini</p>
                                </div>
                            </div>
                            <button wire:click="closeModal" 
                                    class="cursor-pointer rounded-md p-1 text-blue-100 hover:bg-blue-500 hover:text-white transition-colors">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="bg-white px-6 py-4">
                        @if($session)
                            <!-- Session Info -->
                            <div class="grid grid-cols-2 gap-4 mb-6 p-4 bg-gray-50 rounded-lg">
                                <div>
                                    <p class="text-sm text-gray-500">Kasir</p>
                                    <p class="font-semibold text-gray-900">{{ $session->user->name }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Dibuka</p>
                                    <p class="font-semibold text-gray-900">{{ $session->opened_at->format('d/m/Y H:i') }}</p>
                                </div>
                            </div>

                            <!-- Financial Summary -->
                            <div class="space-y-4">
                                <!-- Cash In Hand -->
                                <div class="flex justify-between items-center p-3 bg-blue-50 rounded-lg border border-blue-200">
                                    <span class="text-sm font-medium text-blue-900">Kas Awal</span>
                                    <span class="text-lg font-bold text-blue-900">{{ $this->formatCurrency($summary['cash_in_hand']) }}</span>
                                </div>

                                <!-- Sales Breakdown by Payment Method -->
                                <div class="space-y-2">
                                    <h4 class="font-semibold text-gray-900 text-sm uppercase tracking-wide">Penjualan per Metode</h4>
                                    
                                    <div class="grid grid-cols-2 gap-2">
                                        @foreach($summary['payment_method_sales'] as $methodCode => $amount)
                                            @if($amount > 0)
                                                @php
                                                    $color = $this->getPaymentMethodColor($methodCode);
                                                    $methodName = $this->getPaymentMethodName($methodCode);
                                                @endphp
                                                <div class="flex justify-between items-center p-2 bg-{{ $color }}-50 rounded border border-{{ $color }}-200">
                                                    <span class="text-sm text-{{ $color }}-800">{{ $methodName }}</span>
                                                    <span class="font-semibold text-{{ $color }}-800">{{ $this->formatCurrency($amount) }}</span>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>

                                <!-- Totals -->
                                <div class="border-t pt-4 space-y-2">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm font-medium text-gray-600">Total Penjualan</span>
                                        <span class="text-lg font-bold text-gray-900">{{ $this->formatCurrency($summary['total_sales']) }}</span>
                                    </div>
                                    
                                    <!-- Expected Cash (hanya untuk metode cash) -->
                                    @if(isset($summary['payment_method_sales']['cash']) && $summary['payment_method_sales']['cash'] > 0)
                                        <div class="flex justify-between items-center p-3 bg-gradient-to-r from-green-500 to-green-600 rounded-lg text-white">
                                            <span class="font-semibold">Uang di Laci (Expected)</span>
                                            <span class="text-xl font-bold">{{ $this->formatCurrency($summary['expected_cash']) }}</span>
                                        </div>
                                    @endif
                                </div>

                                <!-- Statistics -->
                                <div class="grid grid-cols-2 gap-4 pt-4 border-t">
                                    <div class="text-center p-3 bg-gray-50 rounded-lg">
                                        <p class="text-2xl font-bold text-blue-600">{{ $summary['transaction_count'] }}</p>
                                        <p class="text-xs text-gray-500">Total Transaksi</p>
                                    </div>
                                    <div class="text-center p-3 bg-gray-50 rounded-lg">
                                        <p class="text-2xl font-bold text-green-600">{{ $this->formatCurrency($summary['average_transaction']) }}</p>
                                        <p class="text-xs text-gray-500">Rata-rata/Transaksi</p>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="text-center py-8">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01m12-.01a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada sesi aktif</h3>
                                <p class="mt-1 text-sm text-gray-500">Mulai sesi kas terlebih dahulu untuk melihat summary.</p>
                            </div>
                        @endif
                    </div>

                    <!-- Footer -->
                    <div class="bg-gray-50 px-6 py-3 flex justify-end space-x-3">
                        <button wire:click="closeModal" 
                                type="button"
                                class="cursor-pointer px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                            Tutup
                        </button>
                        @if($session)
                            <button onclick="window.print()"
                                    class="cursor-pointer px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors flex items-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                </svg>
                                <span>Print</span>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
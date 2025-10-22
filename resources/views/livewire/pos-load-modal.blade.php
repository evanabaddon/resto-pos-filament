<div>
    @if ($show)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            {{-- Background overlay --}}
            <div class="absolute inset-0 backdrop-blur-md bg-opacity-75 transition-opacity" wire:click="closeModal"></div>

            {{-- Modal Box --}}
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-2xl mx-auto transform transition-all">
                {{-- Header --}}
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-xl font-bold text-gray-900">Transaksi Tersimpan</h2>
                            <p class="text-sm text-gray-500 mt-1">Pilih transaksi untuk dilanjutkan atau diproses pembayaran</p>
                        </div>
                        <button wire:click="closeModal" 
                                class="text-gray-400 hover:text-gray-600 transition-colors cursor-pointer">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Content --}}
                <div class="max-h-[60vh] overflow-y-auto">
                    <div class="p-6">
                        @forelse ($savedSales as $sale)
                            <div class="bg-white border border-gray-200 rounded-lg p-4 mb-3 hover:shadow-md transition-shadow duration-200">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-3 mb-2">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                {{ $sale->invoice_number }}
                                            </span>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                @if($sale->order_type === 'Dine In') bg-green-100 text-green-800
                                                @elseif($sale->order_type === 'take_away') bg-orange-100 text-orange-800
                                                @else bg-gray-100 text-gray-800 @endif">
                                                {{ $sale->order_type === 'Dine In' ? 'Makan di Tempat' : 
                                                   ($sale->order_type === 'take_away' ? 'Bawa Pulang' : 'Delivery') }}
                                            </span>
                                            {{-- Status Badge --}}
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                @if($sale->status === 'draft') bg-yellow-100 text-yellow-800
                                                @elseif($sale->status === 'paid') bg-green-100 text-green-800
                                                @elseif($sale->status === 'completed') bg-blue-100 text-blue-800
                                                @else bg-gray-100 text-gray-800 @endif">
                                                {{ match($sale->status) {
                                                    'draft' => 'Draft',
                                                    'paid' => 'Lunas',
                                                    'completed' => 'Selesai',
                                                    default => $sale->status
                                                } }}
                                            </span>
                                        </div>
                                        
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                                            <div>
                                                <p class="font-semibold text-gray-900">{{ $sale->customer_name ?? 'Pelanggan Umum' }}</p>
                                                <p class="text-gray-500 text-xs">{{ $sale->created_at->translatedFormat('d F Y H:i') }}</p>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-lg font-bold text-gray-900">
                                                    Rp{{ number_format($sale->final_total, 0, ',', '.') }}
                                                </p>
                                                <p class="text-xs text-gray-500">
                                                    {{ $sale->items_count }} item
                                                @if($sale->paymentMethod)
                                                    • {{ $sale->paymentMethod->name }}
                                                @endif
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="flex flex-col space-y-2 ml-4">
                                        {{-- Tombol Edit hanya untuk draft --}}
                                        @if($sale->status === 'draft')
                                            <button wire:click="loadSale({{ $sale->id }})"
                                                class="cursor-pointer inline-flex items-center px-3 py-2 border border-blue-600 text-sm font-medium rounded-md text-blue-600 bg-white hover:bg-blue-50 hover:text-blue-700 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                                ✏️ Edit
                                            </button>
                                        @else
                                            <button disabled
                                                class="cursor-not-allowed inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-400 bg-gray-100">
                                                ✏️ Edit
                                            </button>
                                        @endif

                                        {{-- Tombol Bayar hanya untuk draft --}}
                                        @if($sale->status === 'draft')
                                            <button wire:click="openPayment({{ $sale->id }})"
                                                class="cursor-pointer inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                                💵 Bayar
                                            </button>
                                        @elseif($sale->status === 'completed')
                                            {{-- Tombol Struk untuk transaksi completed --}}
                                            <button wire:click="printReceipt({{ $sale->id }})"
                                                class="cursor-pointer inline-flex items-center px-3 py-2 border border-purple-600 text-sm font-medium rounded-md text-purple-600 bg-white hover:bg-purple-50 hover:text-purple-700 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                                                🖨️ Struk
                                            </button>
                                        @else
                                            <button disabled
                                                class="cursor-not-allowed inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-gray-400">
                                                {{ $sale->status === 'paid' ? 'Sudah Bayar' : 'Selesai' }}
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-8">
                                <svg class="w-16 h-16 mx-auto text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                                <h3 class="mt-4 text-lg font-medium text-gray-900">Tidak ada transaksi tersimpan</h3>
                                <p class="mt-1 text-sm text-gray-500">Semua transaksi dalam sesi kas ini akan muncul di sini.</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Footer --}}
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 rounded-b-xl">
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-gray-500">
                            Menampilkan <span class="font-medium">{{ count($savedSales) }}</span> transaksi
                            @if(count($savedSales) > 0)
                                - <span class="text-yellow-600">{{ $savedSales->where('status', 'draft')->count() }} draft</span>
                                - <span class="text-green-600">{{ $savedSales->where('status', 'paid')->count() }} lunas</span>
                                - <span class="text-blue-600">{{ $savedSales->where('status', 'completed')->count() }} selesai</span>
                            @endif
                        </p>
                        <button wire:click="closeModal"
                                class="cursor-pointer px-4 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <style>
            @keyframes fade-in {
                from { opacity: 0; transform: scale(0.95); }
                to { opacity: 1; transform: scale(1); }
            }
            
            .fixed.inset-0 {
                animation: fade-in 0.2s ease-out;
            }
        </style>
    @endif
</div>
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
                                                @elseif($sale->status === 'split') bg-purple-100 text-purple-800
                                                @else bg-gray-100 text-gray-800 @endif">
                                                {{ match($sale->status) {
                                                    'draft' => 'Draft',
                                                    'paid' => 'Lunas',
                                                    'completed' => 'Selesai',
                                                    'split' => 'Split Bill',
                                                    default => $sale->status
                                                } }}
                                            </span>
                                            @if($sale->split_from)
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                                    Split #{{ $sale->split_number }}
                                                </span>
                                            @endif
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
                                                    @if($sale->split_into)
                                                        • Split {{ $sale->split_into }} bill
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
                                                {{ $sale->status === 'paid' ? 'Sudah Bayar' : ($sale->status === 'split' ? 'Split Bill' : 'Selesai') }}
                                            </button>
                                        @endif

                                        {{-- Tombol Split Bill hanya untuk draft --}}
                                        @if($sale->status === 'draft')
                                            <button wire:click="openSplitBill({{ $sale->id }})"
                                                class="cursor-pointer inline-flex items-center px-3 py-2 border border-orange-600 text-sm font-medium rounded-md text-orange-600 bg-white hover:bg-orange-50 hover:text-orange-700 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500">
                                                🍴 Split Bill
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
                                - <span class="text-purple-600">{{ $savedSales->where('status', 'split')->count() }} split</span>
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

    {{-- Split Bill Modal - Item Based --}}
    @if ($showSplitBillModal && $selectedSaleForSplit)
        <div class="fixed inset-0 z-[60] flex items-center justify-center p-4 backdrop-blur-md bg-opacity-75">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl mx-auto max-h-[90vh] overflow-hidden">
                {{-- Header --}}
                <div class="px-6 py-4 border-b border-gray-200 bg-white sticky top-0 z-10">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-xl font-bold text-gray-900">Split Bill - Pembagian per Item</h2>
                            <p class="text-sm text-gray-500 mt-1">Assign item ke masing-masing customer</p>
                        </div>
                        <button wire:click="closeSplitBillModal" 
                                class="text-gray-400 hover:text-gray-600 transition-colors cursor-pointer">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Content --}}
                <div class="overflow-y-auto max-h-[70vh] p-6">
                    {{-- Info Transaksi --}}
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <p class="font-semibold text-blue-900">{{ $selectedSaleForSplit->invoice_number }}</p>
                                <p class="text-blue-700">{{ $selectedSaleForSplit->customer_name ?? 'Pelanggan Umum' }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-blue-800 font-bold text-lg">
                                    Total: Rp{{ number_format($selectedSaleForSplit->final_total, 0, ',', '.') }}
                                </p>
                                <p class="text-blue-600">{{ $selectedSaleForSplit->items->count() }} items</p>
                            </div>
                        </div>
                    </div>

                    {{-- Configuration --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        {{-- Jumlah Pembagian --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Jumlah Pembagian</label>
                            <div class="flex items-center space-x-2">
                                <button wire:click="decrementSplitCount" 
                                        class="cursor-pointer w-8 h-8 flex items-center justify-center bg-gray-200 rounded-lg text-gray-600 hover:bg-gray-300 transition-colors"
                                        {{ $splitCount <= 2 ? 'disabled' : '' }}>
                                    −
                                </button>
                                <input type="number" 
                                       wire:model="splitCount"
                                       min="2" 
                                       max="8"
                                       class="w-20 text-center border border-gray-300 rounded-lg py-2 px-3 text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                                <button wire:click="incrementSplitCount"
                                        class="cursor-pointer w-8 h-8 flex items-center justify-center bg-gray-200 rounded-lg text-gray-600 hover:bg-gray-300 transition-colors"
                                        {{ $splitCount >= 8 ? 'disabled' : '' }}>
                                    +
                                </button>
                            </div>
                        </div>

                        {{-- Auto Assign --}}
                        <div class="flex items-end">
                            <button wire:click="autoAssignEqual"
                                    class="cursor-pointer px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700 transition-colors">
                                🎯 Auto Assign Equal
                            </button>
                        </div>

                        {{-- Total Verification --}}
                        <div class="bg-green-50 border border-green-200 rounded-lg p-3">
                            <div class="text-sm text-green-700">
                                <div class="font-semibold">Total setelah split:</div>
                                <div class="font-bold text-lg">
                                    Rp{{ number_format(collect($splitAssignments)->sum('total'), 0, ',', '.') }}
                                </div>
                                @if(collect($splitAssignments)->sum('total') != $selectedSaleForSplit->final_total)
                                    <div class="text-xs text-red-600 mt-1">
                                        ⚠️ Total belum sesuai
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Customer Names --}}
                    <div class="grid grid-cols-2 md:grid-cols-{{ $splitCount }} gap-4 mb-6">
                        @for($i = 0; $i < $splitCount; $i++)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Nama Customer {{ $i + 1 }}
                                </label>
                                <input type="text" 
                                       wire:model="customerNames.{{ $i }}"
                                       class="w-full border border-gray-300 rounded-lg py-2 px-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="Nama customer...">
                            </div>
                        @endfor
                    </div>

                    {{-- Items Assignment --}}
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Assign Items</h3>
                        @foreach($selectedSaleForSplit->items as $item)
                            <div class="border border-gray-200 rounded-lg p-4 bg-white">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex-1">
                                        <h4 class="font-semibold text-gray-900">{{ $item->product->name }}</h4>
                                        <p class="text-sm text-gray-500">
                                            Rp{{ number_format($item->unit_price, 0, ',', '.') }} × {{ $item->quantity }} 
                                            = Rp{{ number_format($item->subtotal, 0, ',', '.') }}
                                        </p>
                                    </div>
                                    <button wire:click="clearItemAssignment({{ $item->id }})"
                                            class="cursor-pointer text-xs text-red-600 hover:text-red-700 font-medium">
                                        Clear
                                    </button>
                                </div>

                                <div class="grid grid-cols-2 md:grid-cols-{{ $splitCount }} gap-3">
                                    @for($i = 0; $i < $splitCount; $i++)
                                        <div class="text-center">
                                            <label class="block text-xs font-medium text-gray-600 mb-1">
                                                {{ $customerNames[$i] ?? 'Customer '.($i+1) }}
                                            </label>
                                            <div class="flex items-center space-x-1">
                                                <button wire:click="assignPartialItem({{ $item->id }}, {{ $i }}, {{ max(0, ($itemAssignments[$item->id][$i] ?? 0) - 1) }})"
                                                        class="cursor-pointer w-6 h-6 flex items-center justify-center bg-gray-200 rounded text-gray-600 hover:bg-gray-300 text-xs">
                                                    −
                                                </button>
                                                <input type="number" 
                                                       value="{{ $itemAssignments[$item->id][$i] ?? 0 }}"
                                                       min="0" 
                                                       max="{{ $item->quantity }}"
                                                       wire:change="assignPartialItem({{ $item->id }}, {{ $i }}, $event.target.value)"
                                                       class="w-12 text-center border border-gray-300 rounded py-1 px-1 text-sm">
                                                <button wire:click="assignPartialItem({{ $item->id }}, {{ $i }}, {{ min($item->quantity, ($itemAssignments[$item->id][$i] ?? 0) + 1) }})"
                                                        class="cursor-pointer w-6 h-6 flex items-center justify-center bg-gray-200 rounded text-gray-600 hover:bg-gray-300 text-xs">
                                                    +
                                                </button>
                                            </div>
                                            @if(($itemAssignments[$item->id][$i] ?? 0) > 0)
                                                <p class="text-xs text-green-600 mt-1">
                                                    Rp{{ number_format((($item->subtotal / $item->quantity) * ($itemAssignments[$item->id][$i] ?? 0)), 0, ',', '.') }}
                                                </p>
                                            @endif
                                        </div>
                                    @endfor
                                </div>

                                {{-- Progress Bar --}}
                                @php
                                    $assigned = array_sum($itemAssignments[$item->id] ?? []);
                                    $percentage = $item->quantity > 0 ? ($assigned / $item->quantity) * 100 : 0;
                                @endphp
                                <div class="mt-2">
                                    <div class="flex justify-between text-xs text-gray-500 mb-1">
                                        <span>Assigned: {{ $assigned }}/{{ $item->quantity }}</span>
                                        <span>{{ round($percentage) }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-{{ $percentage == 100 ? 'green' : 'orange' }}-500 h-2 rounded-full" 
                                             style="width: {{ $percentage }}%"></div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Split Summary --}}
                    <div class="mt-8">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Ringkasan Split</h3>
                        <div class="grid grid-cols-1 md:grid-cols-{{ $splitCount }} gap-4">
                            @foreach($splitAssignments as $index => $split)
                                @if($split['total'] > 0)
                                    <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                                        {{-- Header Customer --}}
                                        <h4 class="font-semibold text-gray-900 mb-3 flex items-center justify-between">
                                            <span>{{ $customerNames[$index] ?? 'Customer '.($index+1) }}</span>
                                            <span class="text-orange-600 text-lg">Rp{{ number_format($split['total'], 0, ',', '.') }}</span>
                                        </h4>
                                        
                                        {{-- Detail Items --}}
                                        <div class="mb-3">
                                            <h5 class="text-sm font-medium text-gray-700 mb-2">Items:</h5>
                                            <div class="space-y-2 max-h-40 overflow-y-auto">
                                                @forelse($split['items'] ?? [] as $item)
                                                    <div class="bg-white rounded border border-gray-100 p-2 text-xs">
                                                        <div class="flex justify-between items-start">
                                                            <div class="flex-1">
                                                                <p class="font-medium text-gray-800">{{ $item['name'] }}</p>
                                                                <p class="text-gray-600">
                                                                    {{ $item['quantity'] }} × Rp{{ number_format($item['price'], 0, ',', '.') }}
                                                                </p>
                                                            </div>
                                                            <p class="font-semibold text-green-600 ml-2">
                                                                Rp{{ number_format($item['subtotal'], 0, ',', '.') }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                @empty
                                                    <p class="text-gray-500 text-xs text-center py-2">Tidak ada items</p>
                                                @endforelse
                                            </div>
                                        </div>

                                        {{-- Summary --}}
                                        <div class="border-t border-gray-300 pt-2 space-y-1 text-sm">
                                            <div class="flex justify-between">
                                                <span class="text-gray-600">Subtotal:</span>
                                                <span>Rp{{ number_format($split['subtotal'], 0, ',', '.') }}</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-gray-600">Pajak (10%):</span>
                                                <span>Rp{{ number_format($split['tax'], 0, ',', '.') }}</span>
                                            </div>
                                            <div class="border-t border-gray-300 pt-1 mt-1">
                                                <div class="flex justify-between font-bold text-base">
                                                    <span>Total:</span>
                                                    <span class="text-orange-600">Rp{{ number_format($split['total'], 0, ',', '.') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        {{-- Item Count --}}
                                        <div class="mt-2 text-xs text-gray-500 text-center">
                                            {{ count($split['items'] ?? []) }} items
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 sticky bottom-0">
                    <div class="flex space-x-3">
                        <button wire:click="closeSplitBillModal"
                                class="cursor-pointer flex-1 px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                            Batal
                        </button>
                        <button wire:click="confirmSplitBill"
                                class="cursor-pointer flex-1 px-4 py-2 bg-orange-600 border border-transparent rounded-lg text-sm font-medium text-white hover:bg-orange-700 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500"
                                {{ collect($splitAssignments)->sum('total') != $selectedSaleForSplit->final_total ? 'disabled' : '' }}>
                            ✅ Konfirmasi Split Bill
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('incrementSplitCount', () => {
            @this.set('splitCount', Math.min(8, @this.get('splitCount') + 1));
        });
        
        Livewire.on('decrementSplitCount', () => {
            @this.set('splitCount', Math.max(2, @this.get('splitCount') - 1));
        });
    });
</script>
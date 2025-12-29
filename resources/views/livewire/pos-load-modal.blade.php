<div>
    @if ($show)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            {{-- Background overlay --}}
            <div class="absolute inset-0 bg-gray-900/80 transition-opacity" wire:click="closeModal"></div>

            {{-- Modal Box --}}
            <div
                class="relative bg-white rounded-xl shadow-2xl w-full max-w-7xl mx-auto transform transition-all flex flex-col max-h-[90vh]">
                {{-- Header --}}
                <div class="px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-xl font-bold text-gray-900">Transaksi Tersimpan</h2>
                            <p class="text-sm text-gray-500 mt-1">Kelola transaksi draft, pembayaran, dan riwayat.</p>
                        </div>
                        <button wire:click="closeModal"
                            class="text-gray-400 hover:text-gray-600 transition-colors cursor-pointer">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Search & Filters --}}
                <div class="px-6 py-3 bg-white border-b border-gray-200 flex-shrink-0 z-10">
                    <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                        {{-- Search --}}
                        <div class="relative w-full md:w-1/2">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input wire:model.live.debounce.300ms="search" type="text"
                                class="pl-10 block w-full border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm py-2"
                                placeholder="Cari Invoice (#...) atau Nama Pelanggan...">
                        </div>

                        {{-- Tabs --}}
                        <div
                            class="flex space-x-1 w-full md:w-auto overflow-x-auto pb-1 hide-scrollbar bg-gray-100 p-1 rounded-lg">
                            @foreach(['draft' => 'Draft', 'completed' => 'Selesai', 'split' => 'Split', 'all' => 'Semua'] as $key => $label)
                                            <button wire:click="setTab('{{ $key }}')" class="px-4 py-1.5 text-xs font-semibold rounded-md transition-all whitespace-nowrap
                                                                            {{ $activeTab === $key
                                ? 'bg-white text-blue-600 shadow-sm ring-1 ring-gray-200'
                                : 'text-gray-500 hover:text-gray-700 hover:bg-gray-200' }}">
                                                {{ $label }}
                                            </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Content --}}
                <div class="flex-1 overflow-y-auto bg-gray-50 p-4 relative min-h-[300px]">
                    {{-- Loading State --}}
                    <div wire:loading
                        wire:target="search, setTab, previousPage, nextPage, gotoPage, executeDelete, loadSale, openPayment, openSplitBill"
                        class="absolute inset-0 bg-white/60 backdrop-blur-[1px] z-20 flex items-center justify-center rounded-lg transition-opacity">
                        <div class="bg-white p-3 rounded-full shadow-lg">
                            <svg class="animate-spin h-8 w-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                                </circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @forelse ($sales as $sale)
                            <div
                                class="bg-white border border-gray-200 rounded-xl p-4 hover:shadow-lg hover:border-blue-300 transition-all duration-200 flex flex-col h-full relative group">
                                {{-- Card Header --}}
                                <div class="flex justify-between items-start mb-3">
                                    <div class="flex flex-col">
                                        <div class="flex items-center space-x-2">
                                            <span
                                                class="text-xs font-mono font-bold bg-gray-100 text-gray-600 px-2 py-0.5 rounded">
                                                {{ $sale->invoice_number }}
                                            </span>
                                            @if($sale->status === 'split')
                                                <span
                                                    class="text-[10px] font-bold bg-purple-100 text-purple-700 px-1.5 py-0.5 rounded uppercase">SPLIT</span>
                                            @endif
                                        </div>
                                        <h3 class="font-bold text-gray-900 mt-1 truncate w-40"
                                            title="{{ $sale->customer_name }}">
                                            {{ $sale->customer_name ?? 'Umum' }}
                                        </h3>
                                        <span class="text-xs text-gray-500 flex items-center mt-0.5">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            {{ $sale->created_at->format('H:i') }}
                                        </span>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-lg font-bold text-blue-600">
                                            Rp{{ number_format($sale->final_total, 0, ',', '.') }}
                                        </div>
                                        <div
                                            class="text-xs text-gray-500 bg-gray-50 border border-gray-100 rounded px-1.5 py-0.5 inline-block mt-1">
                                            {{ $sale->items_count }} items
                                        </div>
                                    </div>
                                </div>

                                {{-- Badges --}}
                                <div class="flex flex-wrap gap-1 mb-4">
                                    <span
                                        class="px-2 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wide
                                                        {{ $sale->order_type == 'Dine In' ? 'bg-green-50 text-green-700 border border-green-100' : 'bg-orange-50 text-orange-700 border border-orange-100' }}">
                                        {{ $sale->order_type }}
                                    </span>
                                    @if($sale->paymentMethod)
                                        <span
                                            class="px-2 py-0.5 rounded text-[10px] font-semibold bg-gray-50 text-gray-600 border border-gray-100">
                                            {{ $sale->paymentMethod->name }}
                                        </span>
                                    @endif
                                </div>

                                {{-- Action Buttons --}}
                                <div class="grid grid-cols-2 gap-2 mt-auto pt-3 border-t border-gray-50">
                                    @if($sale->status === 'draft')
                                        <button wire:click="loadSale({{ $sale->id }})"
                                            class="cursor-pointer flex items-center justify-center px-3 py-2 text-xs font-bold text-blue-700 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors">
                                            ✏️ EDIT
                                        </button>
                                        <button wire:click="reprintOrder({{ $sale->id }})"
                                            class="cursor-pointer flex items-center justify-center px-3 py-2 text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors"
                                            title="Cetak ulang order ke dapur">
                                            🖨️ CETAK
                                        </button>
                                        <button wire:click="openSplitBill({{ $sale->id }})"
                                            class="cursor-pointer flex items-center justify-center px-3 py-2 text-xs font-bold text-orange-700 bg-orange-50 hover:bg-orange-100 rounded-lg transition-colors">
                                            ✂️ SPLIT
                                        </button>
                                        <button wire:click="confirmDelete({{ $sale->id }})"
                                            class="cursor-pointer flex items-center justify-center px-3 py-2 text-xs font-bold text-red-700 bg-red-50 hover:bg-red-100 rounded-lg transition-colors">
                                            🗑️ HAPUS
                                        </button>
                                        <button wire:click="openPayment({{ $sale->id }})"
                                            class="col-span-2 cursor-pointer flex items-center justify-center px-3 py-3 text-sm font-bold text-white bg-green-600 hover:bg-green-700 rounded-xl transition-all shadow-md hover:shadow-lg transform active:scale-95">
                                            💵 BAYAR SEKARANG
                                        </button>
                                    @elseif($sale->status === 'completed')
                                        <button wire:click="printReceipt({{ $sale->id }})"
                                            class="col-span-2 flex items-center justify-center px-3 py-2 text-xs font-bold text-purple-700 bg-purple-50 hover:bg-purple-100 rounded-lg transition-colors">
                                            🖨️ CETAK STRUK
                                        </button>
                                    @else
                                        <button disabled
                                            class="col-span-2 py-2 text-xs font-medium text-gray-400 bg-gray-100 rounded cursor-not-allowed">
                                            {{ strtoupper($sale->status) }}
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div
                                class="col-span-1 md:col-span-2 lg:col-span-3 text-center py-12 flex flex-col items-center justify-center text-gray-400">
                                <svg class="w-16 h-16 mb-4 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                                    </path>
                                </svg>
                                <p class="text-lg font-medium text-gray-500">Tidak ada transaksi ditemukan.</p>
                                <p class="text-sm mt-1">Coba ubah filter atau kata kunci pencarian.</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Pagination Footer --}}
                <div class="px-6 py-3 bg-white border-t border-gray-200 rounded-b-xl flex-shrink-0">
                    {{ $sales->links() }}
                </div>
            </div>
        </div>

        <style>
            @keyframes fade-in {
                from {
                    opacity: 0;
                    transform: scale(0.95);
                }

                to {
                    opacity: 1;
                    transform: scale(1);
                }
            }

            .fixed.inset-0 {
                animation: fade-in 0.2s ease-out;
            }

            .hide-scrollbar::-webkit-scrollbar {
                display: none;
            }

            .hide-scrollbar {
                -ms-overflow-style: none;
                scrollbar-width: none;
            }
        </style>
    @endif

    {{-- Split Bill Modal - Item Based --}}
    @if ($showSplitBillModal && $selectedSaleForSplit)
        <div class="fixed inset-0 z-[60] flex items-center justify-center p-4 backdrop-blur-md bg-opacity-75">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-7xl mx-auto max-h-[90vh] overflow-hidden">
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
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
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
                                <input type="number" wire:model="splitCount" min="2" max="8"
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
                                <input type="text" wire:model="customerNames.{{ $i }}"
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
                                                {{ $customerNames[$i] ?? 'Customer ' . ($i + 1) }}
                                            </label>
                                            <div class="flex items-center space-x-1">
                                                <button
                                                    wire:click="assignPartialItem({{ $item->id }}, {{ $i }}, {{ max(0, ($itemAssignments[$item->id][$i] ?? 0) - 1) }})"
                                                    class="cursor-pointer w-6 h-6 flex items-center justify-center bg-gray-200 rounded text-gray-600 hover:bg-gray-300 text-xs">
                                                    −
                                                </button>
                                                <input type="number" value="{{ $itemAssignments[$item->id][$i] ?? 0 }}" min="0"
                                                    max="{{ $item->quantity }}"
                                                    wire:change="assignPartialItem({{ $item->id }}, {{ $i }}, $event.target.value)"
                                                    class="w-12 text-center border border-gray-300 rounded py-1 px-1 text-sm">
                                                <button
                                                    wire:click="assignPartialItem({{ $item->id }}, {{ $i }}, {{ min($item->quantity, ($itemAssignments[$item->id][$i] ?? 0) + 1) }})"
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
                                            <span>{{ $customerNames[$index] ?? 'Customer ' . ($index + 1) }}</span>
                                            <span
                                                class="text-orange-600 text-lg">Rp{{ number_format($split['total'], 0, ',', '.') }}</span>
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
                                                                    {{ $item['quantity'] }} ×
                                                                    Rp{{ number_format($item['price'], 0, ',', '.') }}
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
                                                    <span
                                                        class="text-orange-600">Rp{{ number_format($split['total'], 0, ',', '.') }}</span>
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


    {{-- Delete Confirmation Modal --}}
    @if ($showDeleteModal)
        <div class="fixed inset-0 z-[70] flex items-center justify-center p-4 bg-gray-900/80">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-auto overflow-hidden transform transition-all">
                <div class="p-6">
                    <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full mb-4">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                            </path>
                        </svg>
                    </div>

                    <h3 class="text-lg font-bold text-center text-gray-900 mb-2">Hapus Transaksi?</h3>
                    <p class="text-sm text-center text-gray-500 mb-6">
                        Apakah Anda yakin ingin menghapus transaksi ini? <br>
                        <span class="font-medium text-red-600">Stok item akan dikembalikan ke inventory.</span>
                    </p>

                    <div class="flex space-x-3">
                        <button wire:click="cancelDelete"
                            class="cursor-pointer flex-1 px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                            Batal
                        </button>
                        <button wire:click="executeDelete"
                            class="cursor-pointer flex-1 px-4 py-2 bg-red-600 border border-transparent rounded-lg text-sm font-medium text-white hover:bg-red-700 transition-colors shadow-sm">
                            Ya, Hapus
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
            $wire.set('splitCount', Math.min(8, $wire.get('splitCount') + 1));
        });

        Livewire.on('decrementSplitCount', () => {
            $wire.set('splitCount', Math.max(2, $wire.get('splitCount') - 1));
        });
    });
</script>
<div class="animate-fade-in">
    <!-- Modern Segmented Control -->
    <div class="flex justify-center mb-8">
        <div class="inline-flex p-1 bg-gray-100 rounded-xl dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700">
            @foreach(['kitchen' => '👨‍🍳 Dapur', 'bar' => '🍸 Bar', 'retail' => '🛍️ Ritel', 'ready' => '✅ Siap'] as $key => $label)
            <button wire:click="switchTab('{{ $key }}')"
                class="px-6 py-2.5 text-sm font-bold rounded-lg transition-all duration-200 ease-out
                    {{ $activeTab === $key 
                        ? 'bg-white text-primary-600 shadow-md transform scale-[1.02] ring-1 ring-black/5 dark:bg-gray-700 dark:text-primary-400' 
                        : 'text-gray-500 hover:text-gray-700 hover:bg-gray-200/50 dark:text-gray-400 dark:hover:text-gray-200' }}">
                {{ $label }}
            </button>
            @endforeach
        </div>
    </div>

    <!-- Live Polling Area -->
    <div wire:poll.5s class="min-h-[70vh]">
        @if($batches->isEmpty())
        <div class="flex flex-col items-center justify-center h-96 text-center animate-fade-in">
            <div class="relative mb-6 group">
                <div class="absolute inset-0 bg-green-100 rounded-full blur-xl opacity-50 blur-anim dark:bg-green-900/30"></div>
                <x-heroicon-o-check-circle class="relative w-24 h-24 text-green-500 transition-transform duration-500 transform group-hover:scale-110" />
            </div>
            <h3 class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ $activeTab === 'ready' ? 'Antrian Siap Kosong' : 'Semua Selesai!' }}</h3>
            <p class="mt-2 text-gray-500 dark:text-gray-400">Kerja bagus tim, antrian kosong.</p>
        </div>
        @else
        <!-- Masonry-like Grid -->
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
            @foreach($batches as $batch)
            @php
            $sale = $batch->sale;
            // Determine border color based on urgency or status
            $isLongWait = $batch->created_at->diffInMinutes(now()) > 15;
            $borderColor = $activeTab === 'ready' ? 'border-green-500' : ($isLongWait ? 'border-red-500' : 'border-primary-500');
            @endphp

            <div class="flex flex-col relative w-full bg-white dark:bg-gray-800 rounded-2xl shadow-lg border-l-4 {{ $borderColor }} overflow-hidden hover:shadow-xl transition-shadow duration-300">

                <!-- Header -->
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800">
                    <div class="flex justify-between items-start mb-2">
                        <span class="text-2xl font-black text-gray-800 dark:text-white tracking-tight leading-none truncate" title="{{ $sale->customer_name ?: 'TAMU' }}">
                            {{ $sale->customer_name ?: 'TAMU' }}
                        </span>
                        <span class="text-sm font-bold text-primary-600 dark:text-primary-400 uppercase tracking-wider mt-1">
                            {{ $sale->table_number ? 'Meja #' . $sale->table_number : ($sale->order_type ?? 'Order') }}
                        </span>
                        <div class="flex flex-col items-end text-right">
                            <span class="text-lg font-bold font-mono {{ $isLongWait && $activeTab !== 'ready' ? 'text-red-500 animate-pulse' : 'text-gray-600 dark:text-gray-300' }}">
                                {{ $batch->created_at->format('H:i') }}
                            </span>
                            <span class="text-xs font-medium text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded-full mt-1">
                                {{ $batch->created_at->locale('id')->diffForHumans(null, true, true) }} lalu
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Items List -->
                <div class="flex-1 p-4 space-y-3">
                    @foreach($batch->items as $item)
                    @php
                    $statusColors = match($item->status) {
                    'pending' => 'bg-gray-50 border-gray-200 text-gray-600 dark:bg-gray-700/50 dark:border-gray-600 dark:text-gray-300',
                    'cooking' => 'bg-orange-50 border-orange-200 text-orange-700 dark:bg-orange-900/20 dark:border-orange-900/50 dark:text-orange-300',
                    'ready' => 'bg-green-50 border-green-200 text-green-700 dark:bg-green-900/20 dark:border-green-900/50 dark:text-green-300',
                    default => 'bg-gray-100 text-gray-400'
                    };
                    @endphp
                    <div class="group relative flex flex-col p-3 rounded-xl border {{ $statusColors }} transition-all duration-200">
                        <div class="flex justify-between items-start w-full">
                            <div class="flex-1 pr-2">
                                <div class="flex items-baseline gap-2">
                                    <span class="font-black text-xl">{{ (float)$item->quantity }}x</span>
                                    <span class="font-bold text-base leading-tight">{{ $item->product_name ?? $item->product->name ?? '(Tanpa Nama)' }}</span>
                                </div>
                                @if($item->notes)
                                <div class="mt-1 flex items-start gap-1 text-red-500 text-xs font-semibold bg-red-50 dark:bg-red-900/20 p-1.5 rounded-lg inline-block">
                                    <x-heroicon-m-exclamation-circle class="w-3.5 h-3.5 mt-0.5 shrink-0" />
                                    <span>{{ $item->notes }}</span>
                                </div>
                                @endif
                                @if($activeTab === 'ready')
                                <div class="mt-1 text-[10px] opacity-70 font-bold uppercase tracking-tighter">
                                    {{ $item->product->type }}
                                </div>
                                @endif
                            </div>

                            <!-- Action Button -->
                            <div class="flex shrink-0">
                                @if($activeTab === 'ready')
                                <button wire:click="markItemStatus({{ $item->id }}, 'served')"
                                    class="p-2 bg-white dark:bg-gray-800 text-blue-500 shadow-sm border border-blue-200 rounded-lg hover:bg-blue-500 hover:text-white transition-colors"
                                    title="Sajikan">
                                    <x-heroicon-m-truck class="w-6 h-6" />
                                </button>
                                @else
                                @if($item->status === 'pending')
                                <button wire:click="markItemStatus({{ $item->id }}, 'cooking')"
                                    class="p-2 bg-white dark:bg-gray-800 text-orange-500 shadow-sm border border-orange-200 rounded-lg hover:bg-orange-500 hover:text-white transition-colors"
                                    title="Mulai Masak">
                                    <x-heroicon-m-fire class="w-6 h-6" />
                                </button>
                                @elseif($item->status === 'cooking')
                                <button wire:click="markItemStatus({{ $item->id }}, 'ready')"
                                    class="p-2 bg-white dark:bg-gray-800 text-green-500 shadow-sm border border-green-200 rounded-lg hover:bg-green-500 hover:text-white transition-colors"
                                    title="Tandai Siap">
                                    <x-heroicon-m-check class="w-6 h-6" />
                                </button>
                                @endif
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <!-- Card Footer -->
                <div class="p-4 bg-gray-50/50 dark:bg-gray-800/50 border-t border-gray-100 dark:border-gray-700">
                    @if($activeTab === 'ready')
                    <button wire:click="markBatchServed({{ $sale->id }}, '{{ $batch->created_at }}')"
                        class="flex items-center justify-center w-full gap-2 px-4 py-3 text-sm font-bold text-white uppercase tracking-wider bg-gradient-to-r from-green-500 to-green-600 rounded-xl shadow-lg hover:from-green-600 hover:to-green-700 hover:shadow-green-500/30 active:scale-[0.98] transition-all">
                        <x-heroicon-m-check-badge class="w-5 h-5" />
                        Sajikan Semua
                    </button>
                    @else
                    <button wire:click="markBatchReady({{ $sale->id }}, '{{ $batch->created_at }}')"
                        class="flex items-center justify-center w-full gap-2 px-4 py-3 text-sm font-bold text-white uppercase tracking-wider bg-gradient-to-r from-indigo-500 to-indigo-600 rounded-xl shadow-lg hover:from-indigo-600 hover:to-indigo-700 hover:shadow-indigo-500/30 active:scale-[0.98] transition-all">
                        <x-heroicon-m-check-badge class="w-5 h-5" />
                        Tandai Semua Siap
                    </button>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>
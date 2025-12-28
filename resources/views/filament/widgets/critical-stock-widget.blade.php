<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <span class="text-lg font-semibold">⚠️ Stock Alert - Critical Items</span>
                @if($this->getCriticalItems()->count() > 0)
                    <x-filament::badge color="danger">
                        {{ $this->getCriticalItems()->count() }} items
                    </x-filament::badge>
                @endif
            </div>
        </x-slot>

        @php
            $criticalItems = $this->getCriticalItems();
        @endphp

        @if($criticalItems->isEmpty())
            <div class="text-center py-8">
                <div class="text-gray-400 dark:text-gray-600">
                    <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="text-lg font-medium">✅ Semua stok aman!</p>
                    <p class="text-sm mt-1">Tidak ada item yang memerlukan perhatian</p>
                </div>
            </div>
        @else
            <div class="space-y-3">
                @foreach($criticalItems as $item)
                    @php
                        $status = $this->getStockStatus($item);
                        $recommended = $this->getRecommendedRestock($item);
                        $unit = $item->unit->name ?? 'unit';
                    @endphp

                    <div
                        class="border rounded-lg p-4 {{ $status['status'] === 'critical' ? 'border-red-300 bg-red-50 dark:bg-red-950/20' : 'border-yellow-300 bg-yellow-50 dark:bg-yellow-950/20' }}">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-2">
                                    <h4 class="font-semibold text-gray-900 dark:text-white">
                                        {{ $item->name }}
                                    </h4>
                                    <x-filament::badge :color="$status['color']">
                                        {{ $status['status'] }}
                                    </x-filament::badge>
                                </div>

                                <div class="grid grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <span class="text-gray-600 dark:text-gray-400">
                                            {{ $status['stock_type'] === 'prepared' ? 'Ready Stock:' : 'Stok Saat Ini:' }}
                                        </span>
                                        <span class="font-semibold ml-1 {{ $status['status'] === 'critical' ? 'text-red-600' : 'text-yellow-600' }}">
                                            {{ number_format($status['stock_type'] === 'prepared' ? $item->prepared_stock : $item->stock, 2) }} {{ $unit }}
                                        </span>
                                        @if($status['stock_type'] === 'prepared')
                                            <span class="text-xs text-gray-500">(sudah dimasak)</span>
                                        @endif
                                    </div>
                                    <div>
                                        <span class="text-gray-600 dark:text-gray-400">Minimum:</span>
                                        <span class="font-semibold ml-1">
                                            {{ number_format($status['stock_type'] === 'prepared' ? $item->minimum_prepared_stock : $item->minimum_stock, 2) }} {{ $unit }}
                                        </span>
                                    </div>
                                </div>
                                
                                @if($status['stock_type'] === 'prepared')
                                    <div class="mt-2 p-2 bg-blue-50 dark:bg-blue-950/20 rounded border border-blue-200">
                                        <span class="text-xs text-blue-600 dark:text-blue-400">🍳 Produk Masakan</span>
                                        <span class="text-xs text-gray-600 dark:text-gray-400 ml-2">
                                            Segera masak lebih banyak!
                                        </span>
                                    </div>
                                @else
                                    <div class="mt-2 p-2 bg-white/50 dark:bg-gray-900/50 rounded">
                                        <span class="text-xs text-gray-600 dark:text-gray-400">💡 Rekomendasi Restock:</span>
                                        <span class="font-bold text-green-600 dark:text-green-400 ml-1">
                                            {{ number_format($recommended, 2) }} {{ $unit }}
                                        </span>
                                        <span class="text-xs text-gray-500">(untuk 3 hari)</span>
                                    </div>
                                @endif
                            </div>

                            <div class="ml-4">
                                <x-filament::button
                                    x-data=""
                                    x-on:click="$dispatch('open-modal', { id: 'record-production-{{ $item->id }}' })"
                                    size="sm"
                                    color="success"
                                >
                                    🍳 Masak Lagi
                                </x-filament::button>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Production Modal --}}
                    <x-filament::modal id="record-production-{{ $item->id }}" width="md">
                        <x-slot name="heading">
                            Record Production: {{ $item->name }}
                        </x-slot>
                        
                        <div class="space-y-4">
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    Current Ready Stock: <strong>{{ number_format($item->prepared_stock, 2) }} {{ $unit }}</strong>
                                </p>
                            </div>
                            
                            <form wire:submit.prevent="recordProduction({{ $item->id }}, $event.target.quantity.value)">
                                <x-filament::input.wrapper>
                                    <x-filament::input
                                        type="number"
                                        name="quantity"
                                        placeholder="Berapa porsi yang dimasak?"
                                        min="1"
                                        step="0.01"
                                        required
                                    />
                                </x-filament::input.wrapper>
                                
                                <div class="mt-4 flex flex-col gap-2">
                                    <div class="flex justify-between items-center bg-gray-50 dark:bg-gray-800 p-2 rounded text-xs text-gray-500">
                                        <span>ℹ️ Stok bahan baku akan otomatis terpotong.</span>
                                    </div>
                                    
                                    <div class="flex justify-end gap-2 mt-2">
                                        <x-filament::button
                                            type="button"
                                            color="danger"
                                            size="sm"
                                            class="mr-auto"
                                            wire:click="resetStock({{ $item->id }})"
                                            wire:confirm="Yakin ingin membuang sisa stok (RESET ke 0)? Stok bahan baku TIDAK akan dikembalikan."
                                        >
                                            🗑️ Reset / Buang Sisa
                                        </x-filament::button>

                                        <x-filament::button
                                            type="button"
                                            color="gray"
                                            x-on:click="$dispatch('close-modal', { id: 'record-production-{{ $item->id }}' })"
                                        >
                                            Batal
                                        </x-filament::button>
                                        
                                        <x-filament::button type="submit" color="success">
                                            Simpan Masakan
                                        </x-filament::button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </x-filament::modal>
                @endforeach
            </div>

            <div class="mt-4 text-xs text-gray-500 dark:text-gray-400 text-center">
                Auto-refresh setiap 5 menit • Last updated: {{ now()->format('H:i') }}
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
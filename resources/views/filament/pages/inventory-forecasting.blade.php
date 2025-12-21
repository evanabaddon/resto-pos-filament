<x-filament-panels::page>
    <div class="space-y-6">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h2 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">Smart Inventory Forecasting</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Analisis Prediksi Stok & Kebutuhan Restock (Metode AI)</p>
            </div>

            @if($aiResults)
            <div class="flex items-center gap-4">
                <x-filament::button
                    wire:click="exportToPdf"
                    icon="heroicon-m-document-arrow-down"
                    color="gray"
                    size="sm"
                    wire:loading.attr="disabled">
                    Export PDF
                </x-filament::button>

                <div class="flex items-center gap-2 text-xs text-gray-400 bg-gray-100 dark:bg-gray-800 px-3 py-1.5 rounded-full border border-gray-200 dark:border-gray-700 shadow-sm">
                    <x-heroicon-m-clock class="w-4 h-4" />
                    Dianalisa: {{ $lastGeneratedAt }}
                    <button wire:click="generateAiForecast"
                        wire:loading.attr="disabled"
                        class="ml-2 hover:text-primary-500 transition-colors disabled:opacity-50 group">
                        <x-heroicon-m-arrow-path class="w-4 h-4 group-hover:rotate-180 transition-transform duration-500"
                            wire:loading.class="animate-spin"
                            wire:target="generateAiForecast" />
                    </button>
                </div>
            </div>
            @endif
        </div>

        @if(!$aiResults)
        <div class="flex flex-col items-center justify-center py-12 bg-white dark:bg-gray-900 rounded-xl border border-dashed border-gray-300 dark:border-gray-700 shadow-sm">
            <x-heroicon-o-chart-bar class="w-16 h-16 text-gray-200 dark:text-gray-800 mb-4" />
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Belum Ada Analisis</h3>
            <p class="text-sm text-gray-500 mb-6 text-center max-w-xs px-4">AI akan memprediksi kebutuhan stok Anda berdasarkan data konsumsi 7 hari terakhir.</p>
            <x-filament::button wire:click="generateAiForecast" size="lg" icon="heroicon-m-sparkles" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="generateAiForecast">Generate Analisis Sekarang</span>
                <span wire:loading wire:target="generateAiForecast">Sedang Menganalisa...</span>
            </x-filament::button>
        </div>
        @else
        <!-- AI Analysis Section -->
        <div class="bg-gradient-to-br from-indigo-50 to-white dark:from-indigo-900/10 dark:to-gray-900 p-6 rounded-2xl border border-indigo-100 dark:border-indigo-500/20 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-indigo-500 rounded-lg text-white">
                        <x-heroicon-m-sparkles class="w-5 h-5" />
                    </div>
                    <div>
                        <h3 class="font-bold text-indigo-900 dark:text-indigo-400">AI Inventory Insights</h3>
                        <p class="text-xs text-indigo-700/60 dark:text-indigo-400/60">{{ app(\App\Settings\GeneralSettings::class)->ai_assistant_name }} Analysis</p>
                    </div>
                </div>

                @if($lastGeneratedAt)
                <div class="flex items-center gap-1.5 px-3 py-1.5 bg-white/50 dark:bg-gray-800/50 rounded-full text-[10px] font-medium text-gray-500 dark:text-gray-400 border border-gray-100 dark:border-gray-700">
                    <x-heroicon-m-clock class="w-3.5 h-3.5" />
                    Terakhir update: {{ $lastGeneratedAt }}
                </div>
                @endif
            </div>

            <div class="prose prose-sm dark:prose-invert max-w-none">
                <p class="text-gray-700 dark:text-gray-300 italic">
                    "{{ $aiResults['analysis'] ?? 'Analisis tidak tersedia.' }}"
                </p>
            </div>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($aiResults['recommendations'] as $rec)
                @if(($rec['suggested_restock'] ?? 0) > 0)
                <div @class([ 'p-4 rounded-xl border-l-4 shadow-sm' , 'bg-red-50 border-red-500 dark:bg-red-950/20'=> ($rec['urgency'] ?? '') === 'high',
                    'bg-orange-50 border-orange-500 dark:bg-orange-950/20' => ($rec['urgency'] ?? '') === 'medium',
                    'bg-blue-50 border-blue-500 dark:bg-blue-950/20' => ($rec['urgency'] ?? '') === 'low',
                    ])>
                    <div class="flex justify-between items-start mb-2">
                        <h4 class="font-bold text-gray-900 dark:text-white">{{ $rec['product_name'] }}</h4>
                        <x-filament::badge :color="match($rec['urgency'] ?? '') {
                                        'high' => 'danger',
                                        'medium' => 'warning',
                                        'low' => 'info',
                                        default => 'gray'
                                    }">
                            {{ strtoupper($rec['urgency'] ?? 'NORMAL') }}
                        </x-filament::badge>
                    </div>
                    <div class="space-y-1 text-sm">
                        <p class="text-gray-600 dark:text-gray-400">
                            Prediksi Kebutuhan: <span class="font-semibold">{{ $rec['predicted_need'] }}</span>
                        </p>
                        <p class="text-gray-900 dark:text-white font-bold">
                            Saran Restock: +{{ $rec['suggested_restock'] }}
                        </p>
                        <p class="text-xs mt-2 text-gray-500 italic">
                            "{{ $rec['reason'] }}"
                        </p>
                    </div>
                </div>
                @endif
                @endforeach
            </div>
        </div>
        @endif

        <!-- Raw Consumption Data -->
        <x-filament::section>
            <x-slot name="heading">
                Riwayat Konsumsi Produk (7 Hari Terakhir)
            </x-slot>

            <div class="overflow-x-auto">
                <table class="w-full text-left divide-y divide-gray-200 dark:divide-white/5">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-white/5">
                            <th class="px-4 py-3 text-sm font-semibold text-gray-950 dark:text-white">Produk / Bahan</th>
                            <th class="px-4 py-3 text-sm font-semibold text-gray-950 dark:text-white">Stok Saat Ini</th>
                            <th class="px-4 py-3 text-sm font-semibold text-gray-950 dark:text-white">Total Terpakai (7d)</th>
                            <th class="px-4 py-3 text-sm font-semibold text-gray-950 dark:text-white">Rata-rata/Hari</th>
                            <th class="px-4 py-3 text-sm font-semibold text-gray-950 dark:text-white">Satuan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                        @foreach($historyData as $data)
                        <tr>
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $data['name'] }}</td>
                            <td @class([ 'px-4 py-3 text-sm font-medium' , 'text-red-600'=> $data['current_stock'] <= 0, 'text-gray-900 dark:text-white'=> $data['current_stock'] > 0
                                    ])>
                                    {{ $data['current_stock'] }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ number_format($data['total_consumed'], 2) }}</td>
                            <td class="px-4 py-3 text-sm font-semibold text-indigo-600 dark:text-indigo-400">
                                {{ $data['average_daily'] }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $data['unit'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
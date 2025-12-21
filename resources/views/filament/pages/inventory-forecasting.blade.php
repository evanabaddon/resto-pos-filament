<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-6">
        <!-- AI Analysis Section -->
        @if($aiResults)
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <x-filament::icon
                            icon="heroicon-m-sparkles"
                            class="h-5 w-5 text-indigo-500" />
                        <span>AI Insight & Analisis</span>
                    </div>
                    @if($lastGeneratedAt)
                    <div class="flex items-center gap-1 text-xs font-normal text-gray-500">
                        <x-filament::icon icon="heroicon-m-clock" class="h-4 w-4" />
                        Terakhir update: {{ $lastGeneratedAt }}
                    </div>
                    @endif
                </div>
            </x-slot>

            <div class="prose dark:prose-invert max-w-none">
                <p class="text-lg text-gray-700 dark:text-gray-300 italic">
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
        </x-filament::section>
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
                            <th class="px-4 py-3 text-sm font-semibold">Produk / Bahan</th>
                            <th class="px-4 py-3 text-sm font-semibold">Stok Saat Ini</th>
                            <th class="px-4 py-3 text-sm font-semibold">Total Terpakai (7d)</th>
                            <th class="px-4 py-3 text-sm font-semibold">Rata-rata/Hari</th>
                            <th class="px-4 py-3 text-sm font-semibold">Satuan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                        @foreach($historyData as $data)
                        <tr>
                            <td class="px-4 py-3 text-sm">{{ $data['name'] }}</td>
                            <td @class([ 'px-4 py-3 text-sm font-medium' , 'text-red-600'=> $data['current_stock'] <= 0,
                                    ])>
                                    {{ $data['current_stock'] }}
                            </td>
                            <td class="px-4 py-3 text-sm">{{ number_format($data['total_consumed'], 2) }}</td>
                            <td class="px-4 py-3 text-sm font-semibold text-indigo-600 dark:text-indigo-400">
                                {{ $data['average_daily'] }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $data['unit'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
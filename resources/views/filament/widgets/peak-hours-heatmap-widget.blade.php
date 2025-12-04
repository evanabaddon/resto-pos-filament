{{-- resources/views/filament/widgets/peak-hours-heatmap.blade.php --}}
<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                        Jam Sibuk Restoran
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        30 Hari Terakhir
                    </p>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="flex items-center">
                        <div class="w-3 h-3 rounded-sm bg-green-100 mr-1"></div>
                        <span class="text-xs">Sepi</span>
                    </div>
                    <div class="flex items-center">
                        <div class="w-3 h-3 rounded-sm bg-green-300 mr-1"></div>
                        <span class="text-xs">Normal</span>
                    </div>
                    <div class="flex items-center">
                        <div class="w-3 h-3 rounded-sm bg-yellow-300 mr-1"></div>
                        <span class="text-xs">Rama</span>
                    </div>
                    <div class="flex items-center">
                        <div class="w-3 h-3 rounded-sm bg-red-400 mr-1"></div>
                        <span class="text-xs">Sibuk</span>
                    </div>
                </div>
            </div>

            <!-- Heatmap Container -->
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="w-full text-sm">
                    <!-- Header Jam -->
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="p-3 text-left font-medium text-gray-500 dark:text-gray-400 w-16">
                                Hari / Jam
                            </th>
                            @foreach($this->getHoursRange() as $hour)
                                <th class="p-2 text-center font-medium text-gray-500 dark:text-gray-400 text-xs min-w-[40px]">
                                    {{ sprintf('%02d:00', $hour) }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    
                    <!-- Body - Data per hari -->
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day)
                            @if(isset($this->heatmapData[$day]))
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <!-- Nama Hari -->
                                    <td class="p-3 font-medium text-gray-900 dark:text-white whitespace-nowrap sticky left-0 bg-white dark:bg-gray-900 z-10">
                                        {{ $this->getDayName($day) }}
                                    </td>
                                    
                                    <!-- Data per jam -->
                                    @foreach($this->getHoursRange() as $hour)
                                        @php
                                            $cell = $this->heatmapData[$day][$hour] ?? null;
                                            $count = $cell ? $cell['count'] : 0;
                                            $intensity = $this->getIntensity($count);
                                            $colorClass = $this->getColorClass($intensity);
                                            $colorStyle = $this->getColorStyle($intensity);
                                        @endphp
                                        
                                        <td class="p-1 text-center relative group">
                                            @if($cell)
                                                <div 
                                                    class="w-10 h-10 mx-auto flex items-center justify-center rounded-md transition-all hover:scale-105 hover:shadow-md {{ $colorClass }}"
                                                    style="{{ $colorStyle }}"
                                                    x-tooltip="{
                                                        content: `
                                                            <div class='text-xs p-2'>
                                                                <div class='font-semibold'>{{ sprintf('%02d:00', $hour) }}</div>
                                                                <div>{{ $this->getDayName($day) }}</div>
                                                                <div class='mt-1'>Transaksi: <span class='font-bold'>{{ $count }}</span></div>
                                                                <div>Rata-rata: <span class='font-bold'>Rp {{ number_format($cell['avg_value'], 0, ',', '.') }}</span></div>
                                                            </div>
                                                        `,
                                                        theme: $store.theme,
                                                    }"
                                                >
                                                    <span class="text-xs font-semibold {{ $intensity > 50 ? 'text-white' : 'text-gray-900 dark:text-white' }}">
                                                        {{ $count }}
                                                    </span>
                                                </div>
                                            @else
                                                <div class="w-10 h-10 mx-auto flex items-center justify-center rounded-md bg-gray-50 dark:bg-gray-800">
                                                    <span class="text-xs text-gray-400">0</span>
                                                </div>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                    
                    <!-- Footer - Total per jam -->
                    <tfoot class="bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
                        <tr>
                            <td class="p-3 font-medium text-gray-500 dark:text-gray-400">
                                Total
                            </td>
                            @foreach($this->getHoursRange() as $hour)
                                @php
                                    $totalPerHour = 0;
                                    foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day) {
                                        if(isset($this->heatmapData[$day][$hour])) {
                                            $totalPerHour += $this->heatmapData[$day][$hour]['count'];
                                        }
                                    }
                                @endphp
                                <td class="p-2 text-center">
                                    <div class="text-xs font-semibold {{ $totalPerHour > 0 ? 'text-primary-600 dark:text-primary-400' : 'text-gray-400' }}">
                                        {{ $totalPerHour }}
                                    </div>
                                </td>
                            @endforeach
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Legend dan Summary -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                    <h3 class="font-medium text-gray-900 dark:text-white mb-2">Ringkasan</h3>
                    @php
                        $peakHour = 0;
                        $peakCount = 0;
                        $totalTransactions = 0;
                        
                        foreach($this->getHoursRange() as $hour) {
                            $hourTotal = 0;
                            foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day) {
                                if(isset($this->heatmapData[$day][$hour])) {
                                    $hourTotal += $this->heatmapData[$day][$hour]['count'];
                                }
                            }
                            $totalTransactions += $hourTotal;
                            if($hourTotal > $peakCount) {
                                $peakCount = $hourTotal;
                                $peakHour = $hour;
                            }
                        }
                    @endphp
                    <ul class="space-y-1 text-gray-600 dark:text-gray-400">
                        <li>⏰ <strong>Jam Paling Sibuk:</strong> {{ sprintf('%02d:00', $peakHour) }} ({{ $peakCount }} transaksi)</li>
                        <li>📊 <strong>Total Transaksi:</strong> {{ $totalTransactions }} (30 hari)</li>
                        <li>💰 <strong>Rata-rata Transaksi:</strong> Rp {{ number_format($this->heatmapData['Monday'][$peakHour]['avg_value'] ?? 0, 0, ',', '.') }}</li>
                    </ul>
                </div>
                <div>
                    <h3 class="font-medium text-gray-900 dark:text-white mb-2">Keterangan Warna</h3>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div class="flex items-center">
                            <div class="w-4 h-4 rounded-sm bg-green-100 mr-2"></div>
                            <span>Sepi (0-25%)</span>
                        </div>
                        <div class="flex items-center">
                            <div class="w-4 h-4 rounded-sm bg-green-300 mr-2"></div>
                            <span>Normal (26-50%)</span>
                        </div>
                        <div class="flex items-center">
                            <div class="w-4 h-4 rounded-sm bg-yellow-300 mr-2"></div>
                            <span>Rama (51-75%)</span>
                        </div>
                        <div class="flex items-center">
                            <div class="w-4 h-4 rounded-sm bg-red-400 mr-2"></div>
                            <span>Sibuk (76-100%)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
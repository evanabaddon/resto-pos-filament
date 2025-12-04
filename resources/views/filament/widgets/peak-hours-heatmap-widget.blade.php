{{-- resources/views/filament/widgets/peak-hours-heatmap-widget.blade.php --}}
<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-5">

            {{-- HEADER --}}
            <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px">

                <div>
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                        Jam Sibuk Restoran
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        30 Hari Terakhir
                    </p>
                </div>

                {{-- LEGEND --}}
                <div style="display:flex;align-items:center;gap:8px" class="text-xs">

                    <div style="display:flex;align-items:center;margin-right:12px">
                        <span class="w-3 h-3 rounded bg-green-100 mr-1"></span> Sepi
                    </div>

                    <div style="display:flex;align-items:center;margin-right:12px">
                        <span class="w-3 h-3 rounded bg-green-300 mr-1"></span> Normal
                    </div>

                    <div style="display:flex;align-items:center;margin-right:12px">
                        <span class="w-3 h-3 rounded bg-yellow-300 mr-1"></span> Rama
                    </div>

                    <div style="display:flex;align-items:center">
                        <span class="w-3 h-3 rounded bg-red-400 mr-1"></span> Sibuk
                    </div>

                </div>

            </div>

            {{-- HEATMAP --}}
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="table-auto w-full text-sm">

                    {{-- HEADER JAM --}}
                    <thead class="bg-gray-50 dark:bg-gray-900/50 sticky top-0 z-20">
                        <tr>
                            <th class="p-3 text-left font-medium text-gray-600 dark:text-gray-400 min-w-[70px]">
                                Hari / Jam
                            </th>
                            @foreach($this->getHoursRange() as $hour)
                                <th class="p-2 text-center font-medium text-gray-500 dark:text-gray-400 text-xs min-w-[45px]">
                                    {{ sprintf('%02d:00', $hour) }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>

                    {{-- BODY --}}
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $day)
                            @if(isset($this->heatmapData[$day]))
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40">

                                    {{-- NAMA HARI --}}
                                    <td class="p-3 font-semibold text-gray-900 dark:text-white sticky left-0 bg-white dark:bg-gray-900 z-10 border-r border-gray-200 dark:border-gray-700">
                                        {{ $this->getDayName($day) }}
                                    </td>

                                    {{-- CELL PER JAM --}}
                                    @foreach($this->getHoursRange() as $hour)
                                        @php
                                            $cell = $this->heatmapData[$day][$hour] ?? null;
                                            $count = $cell['count'] ?? 0;
                                            $intensity = $this->getIntensity($count);
                                            $colorClass = $this->getColorClass($intensity);
                                            $colorStyle = $this->getColorStyle($intensity);
                                        @endphp
                                        <td class="p-1 text-center">
                                            <div
                                                class="mx-auto flex items-center justify-center rounded-md transition-all 
                                                hover:scale-105 shadow-sm 
                                                {{ $colorClass }}"
                                                style="{{ $colorStyle }};
                                                    width: clamp(1.5rem, 4vw, 2.5rem);
                                                    height: clamp(1.5rem, 4vw, 2.5rem);
                                                "
                                                x-tooltip="
                                                    `<div class='text-xs p-1.5'>
                                                        <div class='font-semibold'>{{ sprintf('%02d:00', $hour) }}</div>
                                                        <div>{{ $this->getDayName($day) }}</div>
                                                        <div class='mt-1'>Transaksi: <b>{{ $count }}</b></div>
                                                        <div>Rata-rata: <b>Rp {{ number_format($cell['avg_value'] ?? 0, 0, ',', '.') }}</b></div>
                                                    </div>`
                                                "
                                            >
                                                <span class="text-xs font-semibold 
                                                    {{ $intensity > 50 ? 'text-white' : 'text-gray-900 dark:text-white' }}">
                                                    {{ $count }}
                                                </span>
                                            </div>
                                        </td>
                                    @endforeach
                                </tr>
                            @endif
                        @endforeach
                    </tbody>

                    {{-- FOOTER TOTAL --}}
                    <tfoot class="bg-gray-50 dark:bg-gray-900/40 border-t border-gray-200 dark:border-gray-700">
                        <tr>
                            <td class="p-3 text-left font-medium text-gray-700 dark:text-gray-300">
                                Total
                            </td>
                            @foreach($this->getHoursRange() as $hour)
                                @php
                                    $total = collect(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'])
                                        ->sum(fn($d) => $this->heatmapData[$d][$hour]['count'] ?? 0);
                                @endphp
                                <td class="p-2 text-center">
                                    <span class="text-xs font-semibold {{ $total > 0 ? 'text-primary-600 dark:text-primary-400' : 'text-gray-400' }}">
                                        {{ $total }}
                                    </span>
                                </td>
                            @endforeach
                        </tr>
                    </tfoot>

                </table>
            </div>

            {{-- SUMMARY + LEGEND BARU --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 text-sm">
                <div>
                    <h3 class="font-medium text-gray-900 dark:text-white mb-2">Ringkasan</h3>

                    @php
                        $summary = collect($this->getHoursRange())->mapWithKeys(function ($hour) {
                            $sum = collect(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'])
                                ->sum(fn($d) => $this->heatmapData[$d][$hour]['count'] ?? 0);
                            return [$hour => $sum];
                        });

                        $peakHour = $summary->keys()->first();
                        $peakCount = $summary->max();
                        $peakHour = $summary->search($peakCount);
                        $totalTrans = $summary->sum();
                    @endphp

                    <ul class="space-y-1 text-gray-600 dark:text-gray-400">
                        <li>⏰ <b>Jam Paling Sibuk:</b> {{ sprintf('%02d:00', $peakHour) }} ({{ $peakCount }} transaksi)</li>
                        <li>📊 <b>Total Transaksi:</b> {{ $totalTrans }} (30 hari)</li>
                        <li>💰 <b>Rata-rata Transaksi:</b>
                            Rp {{ number_format($this->heatmapData['Monday'][$peakHour]['avg_value'] ?? 0, 0, ',', '.') }}
                        </li>
                    </ul>
                </div>

                <div>
                    <h3 class="font-medium text-gray-900 dark:text-white mb-2">Keterangan Warna</h3>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div class="flex items-center"><span class="w-4 h-4 bg-green-100 rounded mr-2"></span> Sepi (0–25%)</div>
                        <div class="flex items-center"><span class="w-4 h-4 bg-green-300 rounded mr-2"></span> Normal (26–50%)</div>
                        <div class="flex items-center"><span class="w-4 h-4 bg-yellow-300 rounded mr-2"></span> Rama (51–75%)</div>
                        <div class="flex items-center"><span class="w-4 h-4 bg-red-400 rounded mr-2"></span> Sibuk (76–100%)</div>
                    </div>
                </div>
            </div>

        </div>
    </x-filament::section>
</x-filament-widgets::widget>

<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;
use Illuminate\Support\Facades\DB;

class PeakHoursHeatmapWidget extends ApexChartWidget
{
    protected static ?int $sort = 6;
    protected static ?string $chartId = 'peakHoursHeatmap';
    protected static ?string $heading = 'Heatmap Jam Sibuk Restoran';
    protected static ?string $description = 'Distribusi transaksi berdasarkan hari dan jam dalam 30 hari terakhir';
    protected int|string|array $columnSpan = 'full';
    protected static ?string $maxHeight = '400px';
    
    // Cache untuk data
    private ?array $cachedHeatmapData = null;
    private ?array $cachedAverageValues = null;
    private ?int $cachedMaxTransaction = null;
    
    /**
     * Get heatmap data with caching
     */
    protected function getHeatmapData(): array
    {
        if ($this->cachedHeatmapData !== null) {
            return $this->cachedHeatmapData;
        }
        
        $data = Sale::select(
                DB::raw('HOUR(created_at) as hour'),
                DB::raw('DAYNAME(created_at) as day'),
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('AVG(final_total) as avg_value')
            )
            ->where('status', 'completed')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('hour', 'day')
            ->orderByRaw("FIELD(day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')")
            ->orderBy('hour')
            ->get();

        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $hours = range(10, 22);
        
        $heatmap = [];
        
        // Initialize with zeros
        foreach ($days as $day) {
            foreach ($hours as $hour) {
                $heatmap[$day][$hour] = [
                    'count' => 0,
                    'avg_value' => 0,
                ];
            }
        }
        
        // Fill with actual data
        foreach ($data as $record) {
            if ($record->hour >= 10 && $record->hour <= 22) {
                if (isset($heatmap[$record->day][$record->hour])) {
                    $heatmap[$record->day][$record->hour] = [
                        'count' => (int) $record->transaction_count,
                        'avg_value' => round((float) $record->avg_value),
                    ];
                }
            }
        }
        
        $this->cachedHeatmapData = $heatmap;
        return $heatmap;
    }
    
    /**
     * Get maximum transaction count with caching
     */
    protected function getMaxTransaction(): int
    {
        if ($this->cachedMaxTransaction !== null) {
            return $this->cachedMaxTransaction;
        }
        
        $heatmapData = $this->getHeatmapData();
        $max = 0;
        
        foreach ($heatmapData as $day => $hours) {
            foreach ($hours as $hour => $data) {
                if ($data['count'] > $max) {
                    $max = $data['count'];
                }
            }
        }
        
        $this->cachedMaxTransaction = $max ?: 1;
        return $this->cachedMaxTransaction;
    }
    
    /**
     * Get average values with caching
     */
    protected function getAverageValues(): array
    {
        if ($this->cachedAverageValues !== null) {
            return $this->cachedAverageValues;
        }
        
        $heatmapData = $this->getHeatmapData();
        $averages = [];
        
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        
        foreach ($days as $day) {
            $dayAverages = [];
            for ($hour = 10; $hour <= 22; $hour++) {
                $dayAverages[] = $heatmapData[$day][$hour]['avg_value'] ?? 0;
            }
            $averages[] = $dayAverages;
        }
        
        $this->cachedAverageValues = $averages;
        return $averages;
    }
    
    /**
     * Chart options (heatmap)
     */
    protected function getOptions(): array
    {
        $heatmapData = $this->getHeatmapData();
        $maxTransaction = $this->getMaxTransaction();
        
        // Hitung total transaksi untuk subtitle
        $totalTransactions = 0;
        foreach ($heatmapData as $day => $hours) {
            foreach ($hours as $hour => $data) {
                $totalTransactions += $data['count'];
            }
        }
        
        $series = [];
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $dayLabels = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
        
        foreach ($days as $index => $day) {
            $dayData = [];
            
            // Data per jam dari jam 10-22
            for ($hour = 10; $hour <= 22; $hour++) {
                $count = $heatmapData[$day][$hour]['count'] ?? 0;
                $dayData[] = [
                    'x' => sprintf('%02d:00', $hour),
                    'y' => $count
                ];
            }
            
            $series[] = [
                'name' => $dayLabels[$index],
                'data' => $dayData
            ];
        }
        
        // Gunakan color ranges yang dinamis
        $colorRanges = $this->getColorRanges($maxTransaction);
        
        $options = [
            'chart' => [
                'type' => 'heatmap',
                'height' => 350,
                'width' => '100%',
                'toolbar' => [
                    'show' => true,
                    'tools' => [
                        'download' => true,
                        'selection' => false,
                        'zoom' => false,
                        'zoomin' => false,
                        'zoomout' => false,
                        'pan' => false,
                        'reset' => true,
                    ]
                ]
            ],
            'plotOptions' => [
                'heatmap' => [
                    'shadeIntensity' => 0.6,
                    'radius' => 3,
                    'useFillColorAsStroke' => false,
                    'colorScale' => [
                        'ranges' => $colorRanges,
                    ]
                ]
            ],
            'dataLabels' => [
                'enabled' => true,
                'style' => [
                    'fontSize' => '11px',
                    'fontWeight' => 'bold',
                    'colors' => ['#000000']
                ],
                'formatter' => 'function(val) { 
                    return val > 0 ? val : ""; 
                }'
            ],
            'stroke' => [
                'width' => 1,
                'colors' => ['#ffffff']
            ],
            'xaxis' => [
                'type' => 'category',
                'categories' => array_map(function($h) {
                    return sprintf('%02d:00', $h);
                }, range(10, 22)),
                'labels' => [
                    'style' => [
                        'fontSize' => '12px',
                        'fontWeight' => 600,
                    ]
                ],
                'title' => [
                    'text' => 'Jam Operasional',
                    'style' => [
                        'fontSize' => '14px',
                        'fontWeight' => 'bold',
                        'color' => '#374151'
                    ]
                ]
            ],
            'yaxis' => [
                'labels' => [
                    'style' => [
                        'fontSize' => '13px',
                        'fontWeight' => 600,
                    ]
                ],
                'title' => [
                    'text' => 'Hari',
                    'style' => [
                        'fontSize' => '14px',
                        'fontWeight' => 'bold',
                        'color' => '#374151'
                    ]
                ]
            ],
            'tooltip' => [
                'custom' => 'function({ series, seriesIndex, dataPointIndex, w }) {
                    const dayNames = ["Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu", "Minggu"];
                    const hour = w.globals.labels[dataPointIndex];
                    const day = dayNames[seriesIndex];
                    const value = series[seriesIndex][dataPointIndex];
                    
                    const avgValue = ' . json_encode($this->getAverageValues()) . ';
                    const avg = avgValue[seriesIndex] ? avgValue[seriesIndex][dataPointIndex] : 0;
                    
                    let intensityClass = "text-green-600";
                    if (value > 5) intensityClass = "text-yellow-600";
                    if (value > 10) intensityClass = "text-red-600";
                    
                    return \'<div class="apexcharts-tooltip-title p-2 bg-gray-100 border-b">\' + 
                           \'<div class="font-bold text-lg">\' + day + \' \' + hour + \'</div>\' +
                           \'</div>\' +
                           \'<div class="p-2">\' +
                           \'<div class="flex justify-between items-center mb-1">\' +
                           \'<span>Transaksi:</span>\' +
                           \'<span class="font-bold \' + intensityClass + \'">\' + value + \'</span>\' +
                           \'</div>\' +
                           \'<div class="flex justify-between items-center">\' +
                           \'<span>Rata-rata Nilai:</span>\' +
                           \'<span class="font-bold text-blue-600">Rp \' + avg.toLocaleString(\'id-ID\') + \'</span>\' +
                           \'</div>\' +
                           \'</div>\';
                }'
            ],
            'legend' => [
                'position' => 'bottom',
                'horizontalAlign' => 'center',
                'fontSize' => '14px',
                'itemMargin' => [
                    'horizontal' => 20,
                    'vertical' => 10
                ]
            ],
            'grid' => [
                'padding' => [
                    'top' => 20,
                    'right' => 20,
                    'bottom' => 30,
                    'left' => 20
                ]
            ],
            'subtitle' => [
                'text' => "Total {$totalTransactions} transaksi dalam 30 hari terakhir",
                'align' => 'center',
                'style' => [
                    'fontSize' => '12px',
                    'color' => '#6B7280'
                ]
            ],
            'responsive' => [
                [
                    'breakpoint' => 768,
                    'options' => [
                        'chart' => [
                            'height' => 300
                        ],
                        'dataLabels' => [
                            'style' => [
                                'fontSize' => '10px'
                            ]
                        ],
                        'xaxis' => [
                            'labels' => [
                                'style' => [
                                    'fontSize' => '10px'
                                ]
                            ]
                        ],
                        'yaxis' => [
                            'labels' => [
                                'style' => [
                                    'fontSize' => '11px'
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    'breakpoint' => 640,
                    'options' => [
                        'chart' => [
                            'height' => 250
                        ],
                        'dataLabels' => [
                            'enabled' => false
                        ],
                        'legend' => [
                            'position' => 'bottom',
                            'horizontalAlign' => 'center',
                            'fontSize' => '12px'
                        ]
                    ]
                ]
            ],
            'series' => $series
        ];
        
        // Jika data kosong, tambahkan pesan
        if ($totalTransactions === 0) {
            $options['annotations'] = [
                'texts' => [
                    [
                        'x' => '50%',
                        'y' => '50%',
                        'text' => 'Tidak ada data transaksi',
                        'fontSize' => '16px',
                        'fontWeight' => 'bold',
                        'foreColor' => '#9CA3AF'
                    ]
                ]
            ];
        }
        
        return $options;
    }

    // Dummy data
    // protected function getOptions(): array
    // {
    //     // TEST SIMPLE - Data dummy pasti muncul
    //     return [
    //         'chart' => [
    //             'type' => 'heatmap',
    //             'height' => 350,
    //         ],
    //         'plotOptions' => [
    //             'heatmap' => [
    //                 'colorScale' => [
    //                     'ranges' => [
    //                         ['from' => 0, 'to' => 0, 'color' => '#F3F4F6', 'name' => 'Empty'],
    //                         ['from' => 1, 'to' => 5, 'color' => '#4ADE80', 'name' => 'Low'],
    //                         ['from' => 6, 'to' => 10, 'color' => '#FBBF24', 'name' => 'Medium'],
    //                         ['from' => 11, 'to' => 20, 'color' => '#EF4444', 'name' => 'High'],
    //                     ]
    //                 ]
    //             ]
    //         ],
    //         'dataLabels' => [
    //             'enabled' => true,
    //         ],
    //         'xaxis' => [
    //             'categories' => ['10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00', '20:00', '21:00', '22:00'],
    //         ],
    //         'series' => [
    //             [
    //                 'name' => 'Senin',
    //                 'data' => [
    //                     ['x' => '10:00', 'y' => 2],
    //                     ['x' => '11:00', 'y' => 5],
    //                     ['x' => '12:00', 'y' => 12],
    //                     ['x' => '13:00', 'y' => 8],
    //                     ['x' => '14:00', 'y' => 3],
    //                     ['x' => '15:00', 'y' => 4],
    //                     ['x' => '16:00', 'y' => 6],
    //                     ['x' => '17:00', 'y' => 9],
    //                     ['x' => '18:00', 'y' => 15],
    //                     ['x' => '19:00', 'y' => 18],
    //                     ['x' => '20:00', 'y' => 10],
    //                     ['x' => '21:00', 'y' => 7],
    //                     ['x' => '22:00', 'y' => 4],
    //                 ]
    //             ],
    //             [
    //                 'name' => 'Selasa',
    //                 'data' => [
    //                     ['x' => '10:00', 'y' => 3],
    //                     ['x' => '11:00', 'y' => 4],
    //                     ['x' => '12:00', 'y' => 14],
    //                     ['x' => '13:00', 'y' => 10],
    //                     ['x' => '14:00', 'y' => 5],
    //                     ['x' => '15:00', 'y' => 6],
    //                     ['x' => '16:00', 'y' => 8],
    //                     ['x' => '17:00', 'y' => 12],
    //                     ['x' => '18:00', 'y' => 16],
    //                     ['x' => '19:00', 'y' => 20],
    //                     ['x' => '20:00', 'y' => 12],
    //                     ['x' => '21:00', 'y' => 8],
    //                     ['x' => '22:00', 'y' => 5],
    //                 ]
    //             ]
    //         ]
    //     ];
    // }
    
    /**
     * Get color ranges based on max transaction
     */
    protected function getColorRanges(int $maxTransaction): array
    {
        // Untuk data sangat sedikit
        if ($maxTransaction <= 1) {
            return [
                [
                    'from' => 0,
                    'to' => 0,
                    'name' => 'Tidak Ada',
                    'color' => '#F3F4F6' // gray-100
                ],
                [
                    'from' => 1,
                    'to' => 1,
                    'name' => 'Ada Transaksi',
                    'color' => '#10B981' // green-500
                ]
            ];
        }
        
        // Untuk data sedikit (2-5 transaksi)
        if ($maxTransaction <= 5) {
            return [
                [
                    'from' => 0,
                    'to' => 0,
                    'name' => 'Sepi',
                    'color' => '#F3F4F6' // gray-100
                ],
                [
                    'from' => 1,
                    'to' => ceil($maxTransaction / 2),
                    'name' => 'Normal',
                    'color' => '#86EFAC' // green-300
                ],
                [
                    'from' => ceil($maxTransaction / 2) + 1,
                    'to' => $maxTransaction,
                    'name' => 'Rama',
                    'color' => '#22C55E' // green-500
                ]
            ];
        }
        
        // Untuk data lebih banyak
        $ranges = [
            [
                'from' => 0,
                'to' => 0,
                'name' => 'Sepi',
                'color' => '#F3F4F6' // gray-100
            ],
            [
                'from' => 1,
                'to' => ceil($maxTransaction * 0.25),
                'name' => 'Sepi',
                'color' => '#DCFCE7' // green-100
            ]
        ];
        
        if ($maxTransaction > ceil($maxTransaction * 0.25)) {
            $ranges[] = [
                'from' => ceil($maxTransaction * 0.25) + 1,
                'to' => ceil($maxTransaction * 0.5),
                'name' => 'Normal',
                'color' => '#4ADE80' // green-400
            ];
        }
        
        if ($maxTransaction > ceil($maxTransaction * 0.5)) {
            $ranges[] = [
                'from' => ceil($maxTransaction * 0.5) + 1,
                'to' => ceil($maxTransaction * 0.75),
                'name' => 'Rama',
                'color' => '#FBBF24' // yellow-400
            ];
        }
        
        if ($maxTransaction > ceil($maxTransaction * 0.75)) {
            $ranges[] = [
                'from' => ceil($maxTransaction * 0.75) + 1,
                'to' => $maxTransaction,
                'name' => 'Sibuk',
                'color' => '#EF4444' // red-500
            ];
        }
        
        return $ranges;
    }
}
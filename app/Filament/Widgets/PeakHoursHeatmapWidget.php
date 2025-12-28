<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;
use Illuminate\Support\Facades\DB;

class PeakHoursHeatmapWidget extends ApexChartWidget
{
    protected static ?int $sort = 6;

    /**
     * Chart Id
     */
    protected static ?string $chartId = 'peakHoursHeatmap';

    /**
     * Widget Title
     */
    protected static ?string $heading = 'Heatmap Jam Sibuk Restoran';

    /**
     * Widget Description
     */
    protected static ?string $description = 'Distribusi transaksi berdasarkan hari dan jam dalam 30 hari terakhir';

    /**
     * Make widget full width
     */
    // protected int|string|array $columnSpan = 'full';

    /**
     * Chart options (heatmap)
     */
    /**
     * Get operational hours range
     */
    protected function getOperationalHours(): array
    {
        $settings = app(\App\Settings\GeneralSettings::class);
        $start = $settings->operational_start_hour ?? 10;
        $end = $settings->operational_end_hour ?? 22;

        if ($start <= $end) {
            return range($start, $end);
        } else {
            // Overnight (e.g. 18 to 02) -> 18...23, 0...2
            return array_merge(range($start, 23), range(0, $end));
        }
    }

    /**
     * Chart options (heatmap)
     */
    protected function getOptions(): array
    {
        $heatmapData = $this->getHeatmapData();
        $maxTransaction = $this->getMaxTransaction($heatmapData);
        $hours = $this->getOperationalHours();

        $series = [];
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $dayLabels = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

        foreach ($days as $index => $day) {
            $dayData = [];

            // Loop through configured operational hours
            foreach ($hours as $hour) {
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

        return [
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
                        'ranges' => $this->getColorRanges($maxTransaction),
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
            ],
            'stroke' => [
                'width' => 1,
                'colors' => ['#ffffff']
            ],
            'xaxis' => [
                'type' => 'category',
                'categories' => array_map(function ($h) {
                    return sprintf('%02d:00', $h);
                }, $hours),
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
                        'color' => '#6B7280'
                    ]
                ]
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
    }

    /**
     * Get heatmap data from database
     */
    protected function getHeatmapData(): array
    {
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
        $hours = $this->getOperationalHours();

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
            // Only include data if it falls within operational hours
            if (isset($heatmap[$record->day][$record->hour])) {
                $heatmap[$record->day][$record->hour] = [
                    'count' => $record->transaction_count,
                    'avg_value' => round($record->avg_value),
                ];
            }
        }

        return $heatmap;
    }

    /**
     * Get maximum transaction count
     */
    protected function getMaxTransaction(array $heatmapData): int
    {
        $max = 0;
        foreach ($heatmapData as $day => $hours) {
            foreach ($hours as $hour => $data) {
                if ($data['count'] > $max) {
                    $max = $data['count'];
                }
            }
        }
        return $max ?: 1;
    }

    /**
     * Get color ranges based on max transaction
     */
    protected function getColorRanges(int $maxTransaction): array
    {
        $ranges = [
            [
                'from' => 0,
                'to' => ceil($maxTransaction * 0.25),
                'name' => 'Sepi',
                'color' => '#DCFCE7' // green-100
            ],
            [
                'from' => ceil($maxTransaction * 0.25) + 1,
                'to' => ceil($maxTransaction * 0.5),
                'name' => 'Normal',
                'color' => '#4ADE80' // green-400
            ],
            [
                'from' => ceil($maxTransaction * 0.5) + 1,
                'to' => ceil($maxTransaction * 0.75),
                'name' => 'Ramai',
                'color' => '#FBBF24' // yellow-400
            ]
        ];

        // Jika ada nilai di atas 75%
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

    /**
     * Get average values for tooltip
     */
    protected function getAverageValues(): array
    {
        $heatmapData = $this->getHeatmapData();
        $averages = [];

        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $hours = $this->getOperationalHours();

        foreach ($days as $day) {
            $dayAverages = [];
            foreach ($hours as $hour) {
                $dayAverages[] = $heatmapData[$day][$hour]['avg_value'] ?? 0;
            }
            $averages[] = $dayAverages;
        }

        return $averages;
    }

    /**
     * Widget max height
     */
    protected static ?string $maxHeight = '400px';
}
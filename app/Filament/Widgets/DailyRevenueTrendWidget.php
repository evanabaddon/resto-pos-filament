<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class DailyRevenueTrendWidget extends ApexChartWidget
{
    protected static ?string $chartId = 'dailyRevenueTrendChart';
    protected static ?string $heading = 'Trend Pendapatan 30 Hari Terakhir';
    protected static ?string $description = 'Pendapatan dan jumlah transaksi harian';
    protected static ?int $sort = 4;
    
    protected function getOptions(): array
    {
        $revenueData = Sale::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(final_total) as total_revenue'),
                DB::raw('COUNT(*) as transaction_count')
            )
            ->where('status', 'completed')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        if ($revenueData->isEmpty()) {
            return [
                'chart' => [
                    'type' => 'line',
                    'height' => 350,
                    'toolbar' => ['show' => false]
                ],
                'series' => [
                    ['name' => 'Pendapatan', 'data' => []],
                    ['name' => 'Transaksi', 'data' => []]
                ],
                'xaxis' => ['categories' => []],
                'annotations' => [
                    'texts' => [[
                        'x' => '50%',
                        'y' => '50%',
                        'text' => 'Tidak ada data pendapatan 30 hari terakhir',
                        'foreColor' => '#9CA3AF'
                    ]]
                ],
            ];
        }
        
        $dates = $revenueData->pluck('date')->map(function($date) {
            return Carbon::parse($date)->format('d M');
        })->toArray();
        
        $revenues = $revenueData->pluck('total_revenue')->map(fn($r) => (int) $r)->toArray();
        $transactions = $revenueData->pluck('transaction_count')->map(fn($t) => (int) $t)->toArray();
        
        return [
            'chart' => [
                'type' => 'line',
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
            'series' => [
                [
                    'name' => 'Pendapatan',
                    'type' => 'area',
                    'data' => $revenues,
                ],
                [
                    'name' => 'Jumlah Transaksi',
                    'type' => 'area',
                    'data' => $transactions,
                ]
            ],
            'xaxis' => [
                'categories' => $dates,
                'labels' => [
                    'style' => [
                        'fontSize' => '11px',
                    ]
                ],
                'title' => [
                    'text' => 'Tanggal',
                    'style' => [
                        'fontSize' => '14px',
                        'fontWeight' => 'bold',
                        'color' => '#6B7280'
                    ]
                ]
            ],
            'yaxis' => [
                [
                    'seriesName' => 'Pendapatan',
                    'axisTicks' => ['show' => true],
                    'axisBorder' => [
                        'show' => true,
                        'color' => '#4F46E5',
                    ],
                    'labels' => [
                        'style' => ['colors' => '#4F46E5'],
                    ],
                    'title' => [
                        'text' => 'Pendapatan (Rp)',
                        'style' => [
                            'color' => '#4F46E5',
                            'fontSize' => '12px',
                            'fontWeight' => 600,
                        ]
                    ],
                ],
                [
                    'seriesName' => 'Jumlah Transaksi',
                    'opposite' => true,
                    'axisTicks' => ['show' => true],
                    'axisBorder' => [
                        'show' => true,
                        'color' => '#10B981',
                    ],
                    'labels' => [
                        'style' => ['colors' => '#10B981']
                    ],
                    'title' => [
                        'text' => 'Jumlah Transaksi',
                        'style' => [
                            'color' => '#10B981',
                            'fontSize' => '12px',
                            'fontWeight' => 600,
                        ]
                    ],
                ],
            ],
            'stroke' => [
                'width' => [3, 3],
                'curve' => 'smooth',
                'colors' => ['#4F46E5', '#10B981'],
            ],
            // HANYA untuk series pertama (Pendapatan)
            'fill' => [
                'type' => 'solid',
                'colors' => ['#4F46E5'], // Hanya warna untuk series pertama
                'opacity' => 0.1,
            ],
            'markers' => [
                'size' => 4,
                'colors' => ['#4F46E5', '#10B981'],
                'strokeColors' => '#fff',
                'strokeWidth' => 2,
                'hover' => ['size' => 6]
            ],
            'colors' => ['#4F46E5', '#10B981'],
            'dataLabels' => [
                'enabled' => false
            ],
            'legend' => [
                'position' => 'top',
                'horizontalAlign' => 'center',
                'fontSize' => '14px',
                'itemMargin' => [
                    'horizontal' => 20,
                    'vertical' => 10
                ]
            ],
            'grid' => [
                'borderColor' => '#E5E7EB',
                'strokeDashArray' => 4,
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
                        'chart' => ['height' => 300],
                        'xaxis' => [
                            'labels' => [
                                'style' => ['fontSize' => '10px']
                            ]
                        ]
                    ]
                ],
                [
                    'breakpoint' => 640,
                    'options' => [
                        'chart' => ['height' => 250],
                        'legend' => [
                            'position' => 'bottom',
                            'horizontalAlign' => 'center',
                            'fontSize' => '12px'
                        ]
                    ]
                ]
            ],
        ];
    }
    
    protected static ?string $maxHeight = '400px';
}
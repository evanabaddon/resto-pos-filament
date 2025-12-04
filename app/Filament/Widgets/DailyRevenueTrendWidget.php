<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class DailyRevenueTrendWidget extends ChartWidget
{
    protected ?string $heading = 'Trend Pendapatan Harian (30 Hari)';
    // protected static ?int $sort = 2;
    
    protected function getData(): array
    {
        $revenueData = Sale::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(final_total) as total_revenue'),
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('AVG(final_total) as avg_transaction')
            )
            ->where('status', 'completed')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Pendapatan (Rp)',
                    'data' => $revenueData->pluck('total_revenue')->toArray(),
                    'borderColor' => '#4F46E5',
                    'backgroundColor' => 'rgba(79, 70, 229, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Jumlah Transaksi',
                    'data' => $revenueData->pluck('transaction_count')->toArray(),
                    'borderColor' => '#10B981',
                    'backgroundColor' => 'transparent',
                    'borderDash' => [5, 5],
                    'yAxisID' => 'y1',
                ],
            ],
            'labels' => $revenueData->pluck('date')->map(function($date) {
                return \Carbon\Carbon::parse($date)->format('d M');
            })->toArray(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => true,
            'interaction' => [
                'mode' => 'index',
                'intersect' => false,
            ],
            'plugins' => [
                'legend' => [
                    'position' => 'top',
                    'align' => 'center',
                    'labels' => [
                        'boxWidth' => 12,
                        'usePointStyle' => true,
                    ]
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => function($context) {
                            $label = $context->dataset->label;
                            $value = $context->raw;
                            
                            if ($context->datasetIndex === 0) { // Pendapatan
                                return $label . ': Rp ' . number_format($value, 0, ',', '.');
                            } else { // Jumlah Transaksi
                                return $label . ': ' . $value . ' transaksi';
                            }
                        }
                    ]
                ]
            ],
            'scales' => [
                'y' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'left',
                    'title' => [
                        'display' => true,
                        'text' => 'Pendapatan (Rp)',
                        'color' => '#4F46E5',
                        'font' => [
                            'weight' => 'bold'
                        ]
                    ],
                    'ticks' => [
                        'callback' => 'function(value) {
                            if (value >= 1000000) {
                                return "Rp " + (value/1000000).toFixed(1) + " jt";
                            } else if (value >= 1000) {
                                return "Rp " + (value/1000).toFixed(0) + " rb";
                            }
                            return "Rp " + value;
                        }'
                    ],
                    'grid' => [
                        'drawBorder' => false,
                    ],
                ],
                'y1' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'right',
                    'title' => [
                        'display' => true,
                        'text' => 'Jumlah Transaksi',
                        'color' => '#10B981',
                        'font' => [
                            'weight' => 'bold'
                        ]
                    ],
                    'grid' => [
                        'drawOnChartArea' => false,
                        'drawBorder' => false,
                    ],
                    'ticks' => [
                        'stepSize' => 1,
                        'precision' => 0
                    ],
                ],
                'x' => [
                    'grid' => [
                        'display' => false
                    ],
                    'ticks' => [
                        'maxRotation' => 45,
                        'minRotation' => 45
                    ]
                ],
            ],
        ];
    }
    
    protected function getType(): string
    {
        return 'line';
    }

}
<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class DailyRevenueTrendWidget extends ChartWidget
{
    protected ?string $heading = 'Trend Pendapatan 30 Hari Terakhir';

    protected function getData(): array
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

        return [
            'datasets' => [
                [
                    'label' => 'Pendapatan (Rp)',
                    'data' => $revenueData->pluck('total_revenue')->map(function($value) {
                        return $value ;
                    })->toArray(),
                    'borderColor' => '#4F46E5',
                    'backgroundColor' => 'rgba(79, 70, 229, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
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

    protected function getOptions(): ?array
    {
        return [
            'responsive' => true,
            'interaction' => [
                'mode' => 'index',
                'intersect' => false,
            ],
            'scales' => [
                'y' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'left',
                    'title' => [
                        'display' => true,
                        'text' => 'Pendapatan (Rp)'
                    ],
                ],
                'y1' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'right',
                    'title' => [
                        'display' => true,
                        'text' => 'Jumlah Transaksi'
                    ],
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                ],
            ],
        ];
    }
    
    protected function getType(): string
    {
        return 'line';
    }
}

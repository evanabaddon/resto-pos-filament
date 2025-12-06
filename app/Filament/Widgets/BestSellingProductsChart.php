<?php

namespace App\Filament\Widgets;

use App\Models\SaleItem;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;
use Illuminate\Support\Facades\DB;

class BestSellingProductsChart extends ApexChartWidget
{
    protected static ?string $chartId = 'bestSellingProductsChart';
    protected static ?string $heading = 'Produk Terlaris (7 Hari Terakhir)';
    protected static ?string $description = 'Top 10 produk dengan penjualan tertinggi';
    protected static ?int $sort = 3;
    
    protected function getOptions(): array
    {
        $products = SaleItem::select(
                'products.name as product_name',
                DB::raw('SUM(sale_items.quantity) as total_quantity')
            )
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->whereHas('sale', function($query) {
                $query->where('status', 'completed')
                    ->where('created_at', '>=', now()->subDays(7));
            })
            ->groupBy('products.name', 'products.id')
            ->orderByDesc('total_quantity')
            ->limit(10)
            ->get();
        
        // Jika data kosong
        if ($products->isEmpty()) {
            return [
                'chart' => [
                    'type' => 'bar',
                    'height' => 350,
                    'toolbar' => ['show' => false]
                ],
                'series' => [['data' => []]],
                'xaxis' => ['categories' => []],
                'annotations' => [
                    'texts' => [[
                        'x' => '50%',
                        'y' => '50%',
                        'text' => 'Tidak ada data',
                        'foreColor' => '#9CA3AF'
                    ]]
                ],
            ];
        }
        
        $productNames = $products->pluck('product_name')->toArray();
        $quantities = $products->pluck('total_quantity')->toArray();

        $colors = [
            '#4F46E5', // indigo-600
            '#7C3AED', // violet-600  
            '#EC4899', // pink-500
            '#10B981', // emerald-500
            '#F59E0B', // amber-500
            '#EF4444', // red-500
            '#8B5CF6', // purple-500
            '#06B6D4', // cyan-500
            '#84CC16', // lime-500
            '#F97316', // orange-500
        ];
        
        return [
            'chart' => [
                'type' => 'bar',
                'height' => 350,
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
                    'name' => 'Jumlah Terjual',
                    'data' => $quantities
                ]
            ],
            'xaxis' => [
                'categories' => $productNames,
                'labels' => [
                    'rotate' => -45,
                    'style' => [
                        'fontSize' => '11px',
                    ]
                ],
            ],
            'yaxis' => [
                'title' => [
                    'text' => 'Jumlah Terjual',
                ]
            ],
            'colors' => $colors,
            'plotOptions' => [
                'bar' => [
                    'columnWidth' => '60%',
                    'borderRadius' => 4,
                    'distributed' => true,
                ]
            ],
            'dataLabels' => [
                'enabled' => true 
            ],
            'tooltip' => [
                'y' => [
                    'formatter' => 'function(value) { return value + " unit"; }'
                ]
            ],
        ];
    }
}
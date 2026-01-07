<?php

namespace App\Filament\Widgets;

use App\Models\SaleItem;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class BestSellingFoodChart extends ApexChartWidget
{
    protected static ?string $chartId = 'bestSellingFoodChart';
    protected static ?string $heading = 'Makanan Terlaris (7 Hari Terakhir)';
    protected static ?string $description = 'Top 10 makanan dengan penjualan tertinggi';
    protected static ?int $sort = 1;

    // Enable lazy loading for better performance
    protected static bool $isLazy = true;

    // Property untuk filter
    public ?string $filter = '7days';

    // Override method getHeading() untuk mendukung filter
    public function getHeading(): string
    {
        return $this->getHeadingByFilter();
    }

    protected function getOptions(): array
    {
        // Cache for 15 minutes, separate cache per filter
        $cacheKey = 'best_selling_food_' . $this->filter;
        $products = Cache::remember($cacheKey, 900, function () {
            $query = SaleItem::select(
                'products.name as product_name',
                DB::raw('SUM(sale_items.quantity) as total_quantity')
            )
                ->join('products', 'sale_items.product_id', '=', 'products.id')
                ->whereHas('sale', function ($query) {
                    $query->where('status', 'completed');

                    // Filter berdasarkan periode
                    if ($this->filter === 'today') {
                        $query->whereDate('created_at', today());
                    } elseif ($this->filter === 'yesterday') {
                        $query->whereDate('created_at', today()->subDay());
                    } elseif ($this->filter === '7days') {
                        $query->where('created_at', '>=', now()->subDays(7));
                    } elseif ($this->filter === '30days') {
                        $query->where('created_at', '>=', now()->subDays(30));
                    }
                })
                ->where('products.type', 'produced')
                ->groupBy('products.name', 'products.id')
                ->orderByDesc('total_quantity')
                ->limit(10);

            return $query->get();
        });

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

    protected function getHeadingByFilter(): string
    {
        return match ($this->filter) {
            'today' => 'Makanan Terlaris (Hari Ini)',
            'yesterday' => 'Makanan Terlaris (Kemarin)',
            '7days' => 'Makanan Terlaris (7 Hari Terakhir)',
            '30days' => 'Makanan Terlaris (30 Hari Terakhir)',
            default => 'Makanan Terlaris (7 Hari Terakhir)',
        };
    }

    // Method untuk menampilkan filter dropdown di widget
    protected function getFilters(): ?array
    {
        return [
            'today' => 'Hari Ini',
            'yesterday' => 'Kemarin',
            '7days' => '7 Hari Terakhir',
            '30days' => '30 Hari Terakhir',
        ];
    }
}

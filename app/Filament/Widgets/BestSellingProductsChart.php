<?php

namespace App\Filament\Widgets;

use App\Models\SaleItem;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class BestSellingProductsChart extends ChartWidget
{
    protected ?string $heading = 'Produk Terlaris (7 Hari Terakhir)';

    protected function getData(): array
    {
        // Menggunakan join dengan tabel products
        $products = SaleItem::select(
                'products.name as product_name',
                DB::raw('SUM(sale_items.quantity) as total_quantity'),
                DB::raw('SUM(sale_items.subtotal) as total_revenue')
            )
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->whereHas('sale', function($query) {
                $query->where('status', 'completed')
                    ->where('created_at', '>=', now()->subDays(7));
            })
            ->groupBy('products.name', 'products.id') // tambah products.id untuk konsistensi
            ->orderByDesc('total_quantity')
            ->limit(10)
            ->get();
            
        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Terjual',
                    'data' => $products->pluck('total_quantity')->toArray(),
                    'backgroundColor' => [
                        '#4F46E5', '#7C3AED', '#EC4899', '#10B981', 
                        '#F59E0B', '#EF4444', '#8B5CF6', '#06B6D4',
                        '#84CC16', '#F97316'
                    ],
                ],
            ],
            'labels' => $products->pluck('product_name')->toArray(),
        ];
    }

    protected function getOptions(): ?array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => function($context) {
                            $productIndex = $context->dataIndex;
                            $products = $this->getCachedData()['labels'];
                            $quantities = $this->getCachedData()['datasets'][0]['data'];
                            
                            if (isset($products[$productIndex]) && isset($quantities[$productIndex])) {
                                $revenue = DB::table('sale_items')
                                    ->join('products', 'sale_items.product_id', '=', 'products.id')
                                    ->where('products.name', $products[$productIndex])
                                    ->whereHas('sale', function($query) {
                                        $query->where('status', 'completed')
                                            ->where('created_at', '>=', now()->subDays(7));
                                    })
                                    ->sum('sale_items.subtotal');
                                    
                                return [
                                    'Jumlah: ' . $quantities[$productIndex],
                                    'Pendapatan: Rp ' . number_format($revenue, 0, ',', '.')
                                ];
                            }
                            return $context->label . ': ' . $context->raw;
                        }
                    ]
                ]
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Jumlah Terjual'
                    ]
                ],
                'x' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Produk'
                    ]
                ]
            ]
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}

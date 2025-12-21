<?php

namespace App\Services;

use App\Models\Product;
use App\Models\SaleItem;
use Illuminate\Support\Facades\DB;

class MenuEngineeringService
{
    public function getMatrix(int $days = 30): array
    {
        $startDate = now()->subDays($days);

        // 1. Get Sales Volume (Popularity)
        $salesData = SaleItem::query()
            ->where('created_at', '>=', $startDate)
            ->select('product_id', DB::raw('SUM(quantity) as total_qty'))
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id');

        // 2. Get All Sellable Products
        $products = Product::query()
            ->where('is_sellable', true)
            ->where(function ($query) {
                $query->where('name', 'not like', '%Down Payment%')
                    ->where('name', 'not like', '%DP%');
            })
            ->with(['recipes.ingredient', 'unit'])
            ->get();

        $matrix = [];
        $totalMargin = 0;
        $totalQty = 0;
        $countItems = 0;

        foreach ($products as $product) {
            $qty = $salesData->get($product->id)->total_qty ?? 0;

            // Calculate COGS
            $cogs = 0;
            if ($product->recipes->isNotEmpty()) {
                foreach ($product->recipes as $recipe) {
                    $ingredientPrice = $recipe->ingredient->base_price ?? 0;
                    $cogs += ($ingredientPrice * $recipe->quantity);
                }
            } else {
                // Retail/Service uses base_price
                $cogs = $product->base_price ?? 0;
            }

            $contributionMargin = $product->sell_price - $cogs;

            $matrix[] = [
                'id' => $product->id,
                'name' => $product->name,
                'type' => $product->type,
                'sell_price' => $product->sell_price,
                'cogs' => $cogs,
                'margin' => $contributionMargin,
                'popularity' => $qty,
                'unit' => $product->unit->name ?? 'pcs',
            ];

            if ($qty > 0) {
                $totalMargin += $contributionMargin;
                $totalQty += $qty;
                $countItems++;
            }
        }

        if ($countItems === 0) {
            return [
                'items' => $matrix,
                'averages' => ['margin' => 0, 'popularity' => 0]
            ];
        }

        // Calculate Averages for Thresholds
        $avgMargin = $totalMargin / $countItems;
        $avgPopularity = $totalQty / $countItems;

        // 3. Classify Items
        foreach ($matrix as &$item) {
            $isHighProfit = $item['margin'] >= $avgMargin;
            $isHighPopularity = $item['popularity'] >= $avgPopularity;

            if ($isHighProfit && $isHighPopularity) {
                $item['category'] = 'UNIT UNGGULAN';
                $item['description'] = 'Kinerja tinggi & potensi tinggi';
            } elseif (!$isHighProfit && $isHighPopularity) {
                $item['category'] = 'UNIT ANDALAN';
                $item['description'] = 'Kinerja tinggi tapi pertumbuhan rendah';
            } elseif ($isHighProfit && !$isHighPopularity) {
                $item['category'] = 'UNIT POTENSIAL';
                $item['description'] = 'Potensi tinggi tapi kinerja belum optimal';
            } else {
                $item['category'] = 'UNIT KURANG BERKEMBANG';
                $item['description'] = 'Kinerja & potensi rendah';
            }
        }

        return [
            'items' => $matrix,
            'averages' => [
                'margin' => round($avgMargin, 2),
                'popularity' => round($avgPopularity, 2),
            ]
        ];
    }
}

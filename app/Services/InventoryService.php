<?php

namespace App\Services;

use App\Models\Product;
use App\Models\SaleItem;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InventoryService
{
    /**
     * Get consumption history of raw materials (ingredients) for the past X days.
     */
    public function getConsumptionHistory(int $days = 7): array
    {
        $startDate = Carbon::now()->subDays($days)->startOfDay();

        // Get all sale items from the period, excluding Down Payment (DP)
        $saleItems = SaleItem::where('created_at', '>=', $startDate)
            ->whereHas('product', function ($query) {
                $query->where('name', '!=', 'Down Payment (DP)');
            })
            ->with(['product.recipes.ingredient.unit', 'product.recipes.unit'])
            ->get();

        $consumption = [];

        foreach ($saleItems as $item) {
            $product = $item->product;
            if (!$product)
                continue;

            $date = $item->created_at->format('Y-m-d');
            $qtySold = $item->quantity;

            if ($product->recipes->isNotEmpty()) {
                // It's a menu item with ingredients
                foreach ($product->recipes as $recipe) {
                    $ingredient = $recipe->ingredient;
                    if (!$ingredient)
                        continue;

                    $totalUsed = $this->calculateRealQuantity($recipe, $qtySold);

                    $this->addtoConsumption($consumption, $ingredient, $totalUsed, $date);
                }

                // ALSO track the menu item itself if it is 'produced' type (for Daily Prep)
                if ($product->type === 'produced') {
                    $this->addtoConsumption($consumption, $product, $qtySold, $date);
                }

            } else {
                // It's a direct product (e.g. bottled water or raw material sold directly)
                $this->addtoConsumption($consumption, $product, $qtySold, $date);
            }
        }

        return $consumption;
    }

    /**
     * Calculate real quantity based on recipe conversion.
     */
    protected function calculateRealQuantity($recipe, $qtySold): float
    {
        $recipeRate = max($recipe->unit->conversion_rate ?? 1, 0.0001);
        $ingredientRate = max($recipe->ingredient->unit->conversion_rate ?? 1, 0.0001);
        $conversion = $ingredientRate / $recipeRate;

        return $recipe->quantity * $qtySold * $conversion;
    }

    /**
     * Add or update consumption data for a product/ingredient.
     */
    /**
     * Get full data for AI forecasting.
     */
    public function getForecastingData(int $historyDays = 30): array
    {
        $history = $this->getConsumptionHistory($historyDays);

        // Count occurrences of each day in the past X days
        $dayCounts = []; // e.g., ['Monday' => 4, 'Tuesday' => 4]
        for ($i = 0; $i < $historyDays; $i++) {
            $dayName = Carbon::now()->subDays($i)->format('l');
            if (!isset($dayCounts[$dayName])) {
                $dayCounts[$dayName] = 0;
            }
            $dayCounts[$dayName]++;
        }

        foreach ($history as $id => &$data) {
            $data['average_daily'] = round($data['total_consumed'] / $historyDays, 2);

            // Calculate specific average for each day name (e.g. Average Monday)
            $data['daily_averages'] = [];
            if (isset($data['daily_usage'])) {
                foreach ($data['daily_usage'] as $day => $total) {
                    $count = $dayCounts[$day] ?? 1;
                    $data['daily_averages'][$day] = round($total / $count, 2);
                }
            }
        }

        return array_values($history);
    }

    /**
     * Add or update consumption data for a product/ingredient.
     */
    protected function addtoConsumption(array &$consumption, Product $product, float $quantity, string $date = ''): void
    {
        // Only forecast for 'raw', 'retail', and 'produced' (daily stock items)
        if (!$this->isForecastingRelevant($product)) {
            return;
        }

        $id = $product->id;
        if (!isset($consumption[$id])) {
            $consumption[$id] = [
                'id' => $id,
                'name' => $product->name,
                'current_stock' => $product->stock,
                'prepared_stock' => $product->prepared_stock ?? 0,
                'unit' => $product->unit->name ?? 'pcs',
                'type' => $product->type,
                'total_consumed' => 0,
                'average_daily' => 0,
                'daily_usage' => [], // Format: ['Monday' => 10, 'Tuesday' => 5...] (Accumulated)
                'daily_averages' => [], // Format: ['Monday' => 2.5] (Weighted Average)
                'daily_history' => [], // Raw history: ['2023-10-01' => 5]
            ];
        }

        $consumption[$id]['total_consumed'] += $quantity;

        if ($date) {
            $dayName = date('l', strtotime($date));
            if (!isset($consumption[$id]['daily_usage'][$dayName])) {
                $consumption[$id]['daily_usage'][$dayName] = 0;
            }
            $consumption[$id]['daily_usage'][$dayName] += $quantity;

            // Raw History for granular AI analysis
            if (!isset($consumption[$id]['daily_history'][$date])) {
                $consumption[$id]['daily_history'][$date] = 0;
            }
            $consumption[$id]['daily_history'][$date] += $quantity;
        }
    }

    /**
     * Check if a product is relevant for restocking forecast.
     */
    protected function isForecastingRelevant(Product $product): bool
    {
        // Include 'produced' because users might track daily prep (Nasi, Ayam)
        return in_array($product->type, ['raw', 'retail', 'produced']);
    }
}

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
            if (!$product) continue;

            $qtySold = $item->quantity;

            if ($product->recipes->isNotEmpty()) {
                // It's a menu item with ingredients
                foreach ($product->recipes as $recipe) {
                    $ingredient = $recipe->ingredient;
                    if (!$ingredient) continue;

                    $totalUsed = $this->calculateRealQuantity($recipe, $qtySold);

                    $this->addtoConsumption($consumption, $ingredient, $totalUsed);
                }
            } else {
                // It's a direct product (e.g. bottled water)
                $this->addtoConsumption($consumption, $product, $qtySold);
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
    protected function addtoConsumption(array &$consumption, Product $product, float $quantity): void
    {
        // Only forecast for 'raw' (ingredients) and 'retail' products
        if (!$this->isForecastingRelevant($product)) {
            return;
        }

        $id = $product->id;
        if (!isset($consumption[$id])) {
            $consumption[$id] = [
                'id' => $id,
                'name' => $product->name,
                'current_stock' => $product->stock,
                'unit' => $product->unit->name ?? 'pcs',
                'total_consumed' => 0,
                'average_daily' => 0,
            ];
        }
        $consumption[$id]['total_consumed'] += $quantity;
    }

    /**
     * Check if a product is relevant for restocking forecast.
     */
    protected function isForecastingRelevant(Product $product): bool
    {
        return in_array($product->type, ['raw', 'retail']);
    }

    /**
     * Get full data for AI forecasting.
     */
    public function getForecastingData(int $historyDays = 7): array
    {
        $history = $this->getConsumptionHistory($historyDays);

        foreach ($history as $id => &$data) {
            $data['average_daily'] = round($data['total_consumed'] / $historyDays, 2);
        }

        return array_values($history);
    }
}

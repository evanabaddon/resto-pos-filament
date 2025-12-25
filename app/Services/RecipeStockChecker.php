<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class RecipeStockChecker
{
    /**
     * Check if product has sufficient ingredients for requested quantity
     * 
     * @param Product $product
     * @param int $requestedQty
     * @return array ['available' => bool, 'max_portions' => int, 'limiting_ingredient' => string|null]
     */
    public function checkAvailability(Product $product, int $requestedQty): array
    {
        // If product has no recipes, it's a direct product - always available based on its own stock
        if (!$product->recipes()->exists()) {
            return [
                'available' => $product->stock >= $requestedQty,
                'max_portions' => (int) floor($product->stock),
                'limiting_ingredient' => null,
            ];
        }

        $maxPortions = $this->getMaxPortions($product);

        return [
            'available' => $maxPortions >= $requestedQty,
            'max_portions' => $maxPortions,
            'limiting_ingredient' => $this->getLimitingIngredient($product),
        ];
    }

    /**
     * Get maximum portions that can be made based on ingredient availability
     * 
     * @param Product $product
     * @return int
     */
    public function getMaxPortions(Product $product): int
    {
        // NO CACHE - Always calculate real-time for accurate badge updates

        // If no recipes, return product's own stock
        if (!$product->recipes()->exists()) {
            return (int) floor($product->stock);
        }

        $recipes = $product->recipes()->with(['ingredient.unit', 'unit'])->get();

        if ($recipes->isEmpty()) {
            return 0;
        }

        $minPortions = PHP_INT_MAX;
        $conversionService = app(\App\Services\UnitConversionService::class);

        foreach ($recipes as $recipe) {
            if (!$recipe->ingredient) {
                // If ingredient is missing, can't make any portions
                return 0;
            }

            // Use UnitConversionService for accurate conversion
            // Convert recipe quantity from recipe unit to ingredient unit
            $requiredPerPortion = $conversionService->convert(
                $recipe->quantity,
                $recipe->unit_id,
                $recipe->ingredient->unit_id
            );

            // Calculate max portions based on this ingredient
            $availableStock = $recipe->ingredient->stock;

            if ($requiredPerPortion > 0) {
                $portionsFromThisIngredient = floor($availableStock / $requiredPerPortion);
            } else {
                $portionsFromThisIngredient = 0;
            }

            // Track minimum (limiting ingredient)
            $minPortions = min($minPortions, $portionsFromThisIngredient);
        }

        $maxPortions = $minPortions === PHP_INT_MAX ? 0 : (int) $minPortions;

        // Subtract quantities from draft sales (only today's drafts)
        // This prevents old draft sales from blocking stock availability
        $draftQty = \App\Models\SaleItem::whereHas('sale', function ($query) {
            $query->where('status', 'draft')
                ->orWhere('status', 'pending')
                ->orWhereNull('payment_method');
        })
            ->whereHas('sale', function ($query) {
                // Only consider drafts created today
                $query->whereDate('created_at', today());
            })
            ->where('product_id', $product->id)
            ->sum('quantity');

        return max(0, $maxPortions - (int) $draftQty);
    }

    /**
     * Get the ingredient that limits production
     * 
     * @param Product $product
     * @return string|null
     */
    public function getLimitingIngredient(Product $product): ?string
    {
        if (!$product->recipes()->exists()) {
            return null;
        }

        $recipes = $product->recipes()->with(['ingredient.unit', 'unit'])->get();

        if ($recipes->isEmpty()) {
            return null;
        }

        $minPortions = PHP_INT_MAX;
        $limitingIngredientName = null;
        $conversionService = app(\App\Services\UnitConversionService::class);

        foreach ($recipes as $recipe) {
            if (!$recipe->ingredient) {
                continue;
            }

            // Use UnitConversionService for accurate conversion
            $requiredPerPortion = $conversionService->convert(
                $recipe->quantity,
                $recipe->unit_id,
                $recipe->ingredient->unit_id
            );

            $availableStock = $recipe->ingredient->stock;

            if ($requiredPerPortion > 0) {
                $portionsFromThisIngredient = floor($availableStock / $requiredPerPortion);
            } else {
                $portionsFromThisIngredient = 0;
            }

            if ($portionsFromThisIngredient < $minPortions) {
                $minPortions = $portionsFromThisIngredient;
                $limitingIngredientName = $recipe->ingredient->name;
            }
        }

        return $limitingIngredientName;
    }

    /**
     * Get ingredient requirements for a given quantity
     * 
     * @param Product $product
     * @param int $qty
     * @return array
     */
    public function getIngredientRequirements(Product $product, int $qty): array
    {
        if (!$product->recipes()->exists()) {
            return [];
        }

        $recipes = $product->recipes()->with(['ingredient.unit', 'unit'])->get();
        $requirements = [];
        $conversionService = app(\App\Services\UnitConversionService::class);

        foreach ($recipes as $recipe) {
            if (!$recipe->ingredient) {
                continue;
            }

            // Use UnitConversionService for accurate conversion
            $requiredPerPortion = $conversionService->convert(
                $recipe->quantity,
                $recipe->unit_id,
                $recipe->ingredient->unit_id
            );

            $totalRequired = $requiredPerPortion * $qty;

            $requirements[] = [
                'ingredient' => $recipe->ingredient->name,
                'required' => $totalRequired,
                'available' => $recipe->ingredient->stock,
                'sufficient' => $recipe->ingredient->stock >= $totalRequired,
                'unit' => $recipe->ingredient->unit->symbol ?? '',
            ];
        }

        return $requirements;
    }

    /**
     * Invalidate cache for a product
     * 
     * @param int $productId
     * @return void
     */
    public function invalidateCache(int $productId): void
    {
        Cache::forget("max_portions_{$productId}");
    }

    /**
     * Invalidate cache for all products that use a specific ingredient
     * 
     * @param int $ingredientId
     * @return void
     */
    public function invalidateCacheForIngredient(int $ingredientId): void
    {
        // Find all products that use this ingredient
        $products = Product::whereHas('recipes', function ($query) use ($ingredientId) {
            $query->where('ingredient_id', $ingredientId);
        })->get();

        foreach ($products as $product) {
            $this->invalidateCache($product->id);
        }
    }
}

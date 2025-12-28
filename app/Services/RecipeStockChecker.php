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
     * @param array $cartItems Optional cart items to include in reservation calculation
     * @return array ['available' => bool, 'max_portions' => int, 'limiting_ingredient' => string|null]
     */
    public function checkAvailability(Product $product, int $requestedQty, array $cartItems = []): array
    {
        // For produced/bar items with prepared stock management enabled
        if (in_array($product->type, ['produced', 'bar']) && $product->enable_stock_alert) {
            $preparedStock = $product->prepared_stock ?? 0;

            // Subtract cart usage if this product is in cart directly
            // Note: For prepared items without recipes (or purely prepared), we check direct quantity
            if (!empty($cartItems) && isset($cartItems[$product->id])) {
                // Handle different cart structures (simple array or assoc)
                $cartQty = is_array($cartItems[$product->id]) ? ($cartItems[$product->id]['qty'] ?? 0) : $cartItems[$product->id];
                $preparedStock = max(0, $preparedStock - $cartQty);
            }

            return [
                'available' => $preparedStock >= $requestedQty,
                'max_portions' => (int) floor($preparedStock),
                'limiting_ingredient' => $preparedStock < $requestedQty ? 'Ready stock habis - perlu dimasak lagi' : null,
            ];
        }

        // If product has no recipes, it's a direct product - always available based on its own stock
        if (!$product->recipes()->exists()) {
            $stock = $product->stock;
            // Subtract cart usage
            if (!empty($cartItems) && isset($cartItems[$product->id])) {
                $cartQty = is_array($cartItems[$product->id]) ? ($cartItems[$product->id]['qty'] ?? 0) : $cartItems[$product->id];
                $stock = max(0, $stock - $cartQty);
            }

            return [
                'available' => $stock >= $requestedQty,
                'max_portions' => (int) floor($stock),
                'limiting_ingredient' => null,
            ];
        }

        $maxPortions = $this->getMaxPortions($product, $cartItems);

        return [
            'available' => $maxPortions >= $requestedQty,
            'max_portions' => $maxPortions,
            'limiting_ingredient' => $this->getLimitingIngredient($product, $cartItems),
        ]; // Cart usage is already subtracted inside getMaxPortions (via ingredients)
    }

    /**
     * Get maximum portions that can be made based on ingredient availability
     * 
     * @param Product $product
     * @param array $cartItems
     * @return int
     */
    public function getMaxPortions(Product $product, array $cartItems = []): int
    {
        // NO CACHE - Always calculate real-time for accurate badge updates

        // If no recipes, return product's own stock
        if (!$product->recipes()->exists()) {
            return (int) floor($product->stock);
        }

        // Use already loaded recipes if available, otherwise load them
        if ($product->relationLoaded('recipes')) {
            $recipes = $product->recipes;
        } else {
            $recipes = $product->recipes()->with(['ingredient.unit', 'unit'])->get();
        }

        if ($recipes->isEmpty()) {
            return 0;
        }

        $minPortions = PHP_INT_MAX;
        $conversionService = app(\App\Services\UnitConversionService::class);

        // Get RESERVED ingredients from ALL drafts with TIMESTAMP
        $reservedDrafts = $this->getAllReservedDrafts();

        // Calculate RESERVED ingredients from CURRENT CART
        $cartReserved = $this->calculateCartReservedIngredients($cartItems);

        foreach ($recipes as $recipe) {
            if (!$recipe->ingredient) {
                return 0;
            }

            // Convert recipe quantity from recipe unit to ingredient unit
            $requiredPerPortion = $conversionService->convert(
                $recipe->quantity,
                $recipe->unit_id,
                $recipe->ingredient->unit_id
            );

            // Get total available stock for this ingredient
            $ingredient = $recipe->ingredient;
            if (in_array($ingredient->type, ['produced', 'bar']) && $ingredient->enable_stock_alert) {
                $totalStock = $ingredient->prepared_stock ?? 0;
            } else {
                $totalStock = $ingredient->stock ?? 0;
            }

            // FILTER RESERVATIONS: Only count drafts created AFTER the ingredient's last stock update
            $reservedQty = 0;
            if (isset($reservedDrafts[$ingredient->id])) {
                foreach ($reservedDrafts[$ingredient->id] as $draft) {
                    $draftTime = $draft['created_at'];
                    $stockTime = $ingredient->updated_at;

                    if ($draftTime && $stockTime && $draftTime->gt($stockTime)) {
                        $reservedQty += $draft['quantity'];
                    }
                }
            }

            // ADD CART RESERVATION
            $cartQty = $cartReserved[$ingredient->id] ?? 0;
            $reservedQty += $cartQty;

            $availableStock = max(0, $totalStock - $reservedQty);

            if ($requiredPerPortion > 0) {
                $portionsFromThisIngredient = floor($availableStock / $requiredPerPortion);
            } else {
                $portionsFromThisIngredient = 0;
            }

            $minPortions = min($minPortions, $portionsFromThisIngredient);
        }

        return $minPortions === PHP_INT_MAX ? 0 : (int) $minPortions;
    }

    /**
     * Get LIMITING ingredient accounting for shared usage and timestamps
     */
    public function getLimitingIngredient(Product $product, array $cartItems = []): ?string
    {
        if (!$product->recipes()->exists()) {
            return null;
        }

        $recipes = $product->recipes()->with(['ingredient.unit', 'unit'])->get();
        if ($recipes->isEmpty())
            return null;

        $minPortions = PHP_INT_MAX;
        $limitingIngredientName = null;
        $conversionService = app(\App\Services\UnitConversionService::class);
        $reservedDrafts = $this->getAllReservedDrafts();
        $cartReserved = $this->calculateCartReservedIngredients($cartItems);

        foreach ($recipes as $recipe) {
            if (!$recipe->ingredient)
                continue;

            $requiredPerPortion = $conversionService->convert(
                $recipe->quantity,
                $recipe->unit_id,
                $recipe->ingredient->unit_id
            );

            // Handle prepared ingredients
            $ingredient = $recipe->ingredient;
            if (in_array($ingredient->type, ['produced', 'bar']) && $ingredient->enable_stock_alert) {
                $totalStock = $ingredient->prepared_stock ?? 0;
            } else {
                $totalStock = $ingredient->stock ?? 0;
            }

            // FILTER RESERVATIONS - Stock Opname Aware
            $reservedQty = 0;
            if (isset($reservedDrafts[$ingredient->id])) {
                foreach ($reservedDrafts[$ingredient->id] as $draft) {
                    if ($draft['created_at']->gt($ingredient->updated_at)) {
                        $reservedQty += $draft['quantity'];
                    }
                }
            }

            // ADD CART RESERVATION
            $cartQty = $cartReserved[$ingredient->id] ?? 0;
            $reservedQty += $cartQty;

            $availableStock = max(0, $totalStock - $reservedQty);

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
     * Calculate Reserved Ingredients from Cart Items
     * @param array $cartItems
     * @return array [ingredient_id => total_qty]
     */
    private function calculateCartReservedIngredients(array $cartItems): array
    {
        if (empty($cartItems))
            return [];

        $reserved = [];
        $conversionService = app(\App\Services\UnitConversionService::class);

        // Optimize: Load all products in cart once with recipes
        // Handle $cartItems structure: it could be [productId => ['qty' => 1]] (WaiterMenu) or other structures.
        // Assuming WaiterMenu structure: [id => [qty, ...]]

        $productIds = array_keys($cartItems);
        $products = Product::whereIn('id', $productIds)->with(['recipes.ingredient.unit', 'recipes.unit'])->get()->keyBy('id');

        foreach ($cartItems as $productId => $itemData) {
            $qty = is_array($itemData) ? ($itemData['qty'] ?? 0) : $itemData;
            if ($qty <= 0)
                continue;

            $product = $products->find($productId);
            if (!$product || $product->recipes->isEmpty())
                continue;

            foreach ($product->recipes as $recipe) {
                if (!$recipe->ingredient)
                    continue;

                $perUnitUsage = $conversionService->convert(
                    $recipe->quantity,
                    $recipe->unit_id,
                    $recipe->ingredient->unit_id
                );

                $totalUsage = $perUnitUsage * $qty;

                if (!isset($reserved[$recipe->ingredient_id])) {
                    $reserved[$recipe->ingredient_id] = 0;
                }
                $reserved[$recipe->ingredient_id] += $totalUsage;
            }
        }
        return $reserved;
    }

    public function getIngredientRequirements(Product $product, int $qty): array
    {
        if (!$product->recipes()->exists()) {
            return [];
        }

        $recipes = $product->recipes()->with(['ingredient.unit', 'unit'])->get();
        $requirements = [];
        $conversionService = app(\App\Services\UnitConversionService::class);
        $reservedDrafts = $this->getAllReservedDrafts();

        foreach ($recipes as $recipe) {
            if (!$recipe->ingredient)
                continue;

            $requiredPerPortion = $conversionService->convert(
                $recipe->quantity,
                $recipe->unit_id,
                $recipe->ingredient->unit_id
            );

            $totalRequired = $requiredPerPortion * $qty;

            // Handle prepared ingredients
            $ingredient = $recipe->ingredient;
            if (in_array($ingredient->type, ['produced', 'bar']) && $ingredient->enable_stock_alert) {
                $totalStock = $ingredient->prepared_stock ?? 0;
            } else {
                $totalStock = $ingredient->stock ?? 0;
            }

            // FILTER RESERVATIONS - Stock Opname Aware
            $reservedQty = 0;
            if (isset($reservedDrafts[$ingredient->id])) {
                foreach ($reservedDrafts[$ingredient->id] as $draft) {
                    if ($draft['created_at']->gt($ingredient->updated_at)) {
                        $reservedQty += $draft['quantity'];
                    }
                }
            }

            $availableStock = max(0, $totalStock - $reservedQty);

            $requirements[] = [
                'ingredient' => $recipe->ingredient->name,
                'required' => $totalRequired,
                'available' => $availableStock,
                'sufficient' => $availableStock >= $totalRequired,
                'unit' => $recipe->ingredient->unit->symbol ?? '',
            ];
        }

        return $requirements;
    }

    /**
     * Get detailed list of draft usages per ingredient with timestamps
     * Returns [ingredient_id => [['quantity' => float, 'created_at' => Carbon], ...]]
     */
    private function getAllReservedDrafts(?int $excludeSaleId = null): array
    {
        // 1. Get all draft items created TODAY and YESTERDAY (to be safe if midnight crossover)
        // Actually, just get pending/drafts. If they are very old, they might be irrelevant provided updated_at is newer.
        // But for performance, limit to last 24h.

        $query = \App\Models\SaleItem::whereHas('sale', function ($q) use ($excludeSaleId) {
            $q->whereIn('status', ['draft', 'pending'])
                ->whereNotIn('status', ['split', 'merge']);
            // ->whereDate('created_at', '>=', today()->subDay()); // Optional perf optimization

            if ($excludeSaleId) {
                $q->where('id', '!=', $excludeSaleId);
            }
        })
            ->with(['product.recipes', 'sale:id,created_at']); // Eager load sale created_at

        $items = $query->get();
        $reserved = []; // [ingredient_id => [ {qty, created_at}, ... ]]
        $conversionService = app(\App\Services\UnitConversionService::class);

        foreach ($items as $item) {
            if (!$item->product || $item->product->recipes->isEmpty())
                continue;

            // Use item created_at or sale created_at? Sale created_at is safer for the "order time".
            // SaleItem created_at should be same as sale usually, or when item added.
            $draftTime = $item->created_at;

            foreach ($item->product->recipes as $recipe) {
                if (!$recipe->ingredient)
                    continue;

                // Calculate total ingredient usage: Item Qty * Recipe Qty (converted)
                $perUnitUsage = $conversionService->convert(
                    $recipe->quantity,
                    $recipe->unit_id,
                    $recipe->ingredient->unit_id
                );

                $totalUsage = $perUnitUsage * $item->quantity;

                if (!isset($reserved[$recipe->ingredient_id])) {
                    $reserved[$recipe->ingredient_id] = [];
                }

                $reserved[$recipe->ingredient_id][] = [
                    'quantity' => $totalUsage,
                    'created_at' => $draftTime
                ];
            }
        }

        return $reserved;
    }

    public function batchCheckAvailability(array $productIds, array $cartItems, ?int $excludeSaleId = null): array
    {
        if (empty($productIds))
            return [];

        $products = Product::whereIn('id', $productIds)
            ->with(['recipes.ingredient.unit', 'recipes.unit'])
            ->get()
            ->keyBy('id');

        // Calculate reserved ingredients from ALL drafts (shared pool)
        // Also include current cart interactions if needed? 
        // Ideally cart items should be added to reserved pool too?
        // For simplicity: The passed $cartItems are "pending" items not yet in DB drafts?
        // Or checks for current cart?

        $reservedDrafts = $this->getAllReservedDrafts($excludeSaleId);

        // Add current cart items to reserved pool if they consume ingredients
        // (Assuming cartItems are just structure ['product_id', 'quantity'])
        $conversionService = app(\App\Services\UnitConversionService::class);

        // Note: CART ITEMS are usually temporary, but if we strictly check stock, we should count them.
        // However, batchCheck is mainly used for rendering menu. 
        // We can skip adding cartItems to reserved pool here if we assume `getAllReservedIngredients` covers DB drafts.
        // But to be consistent with 'checkAvailability' which blindly checks DB...
        // Let's stick to DB drafts for availability. cartItems validation usually happens at checkout.

        $result = [];

        foreach ($products as $id => $product) {
            // Check availability using SHARED reserved ingredients with TIMESTAMP check
            $maxPortions = $this->calculateMaxPortionsWithSharedReserve($product, $reservedDrafts, $conversionService);

            // We do NOT subtract specific product draft logic anymore because
            // we already subtracted the INGREDIENTS used by those drafts.
            // So $maxPortions is already the "TRUE REMAINING" portions we can make.

            // However, we still need to subtract CURRENT CART quantity of THIS product
            // because `getAllReservedIngredients` only checks DB.
            // If user has 5 items in cart, we should deduct 5?
            // Actually, usually `batchCheckAvailability` is for display "Available/Habis".

            $result[$id] = [
                'available' => $maxPortions > 0,
                'max_portions' => $maxPortions,
                'remaining' => $maxPortions, // Already net
                'limiting_ingredient' => $maxPortions === 0 ? 'Stok Bahan Habis' : null,
            ];
        }

        return $result;
    }

    private function calculateMaxPortionsWithSharedReserve(Product $product, array $reservedDrafts, $conversionService): int
    {
        if (!$product->relationLoaded('recipes') || $product->recipes->isEmpty()) {
            return (int) floor($product->stock ?? 0);
        }

        $minPortions = PHP_INT_MAX;

        foreach ($product->recipes as $recipe) {
            if (!$recipe->ingredient)
                return 0;

            $requiredPerPortion = $conversionService->convert(
                $recipe->quantity,
                $recipe->unit_id,
                $recipe->ingredient->unit_id
            );

            // Handle prepared ingredients
            $ingredient = $recipe->ingredient;
            if (in_array($ingredient->type, ['produced', 'bar']) && $ingredient->enable_stock_alert) {
                $totalStock = $ingredient->prepared_stock ?? 0;
            } else {
                $totalStock = $ingredient->stock ?? 0;
            }

            // FILTER RESERVATIONS - Stock Opname Aware
            $reservedQty = 0;
            if (isset($reservedDrafts[$ingredient->id])) {
                foreach ($reservedDrafts[$ingredient->id] as $draft) {
                    if ($draft['created_at']->gt($ingredient->updated_at)) {
                        $reservedQty += $draft['quantity'];
                    }
                }
            }

            $availableStock = max(0, $totalStock - $reservedQty);

            $portionsFromThisIngredient = $requiredPerPortion > 0
                ? floor($availableStock / $requiredPerPortion)
                : 0;
            $minPortions = min($minPortions, $portionsFromThisIngredient);
        }

        return $minPortions === PHP_INT_MAX ? 0 : (int) $minPortions;
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

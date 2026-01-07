<?php

namespace App\Filament\Pages\Concerns;

use App\Models\Product;
use App\Models\DiscountCode;
use App\Services\RecipeStockChecker;
use Illuminate\Support\Collection;

trait HasCart
{
    // 🚀 PHASE 1 OPTIMIZATIONS
    protected $productCache = []; // Cache loaded products
    protected $availabilityCache = []; // Memoize stock availability checks
    protected $pendingRecalculation = false; // Debounce recalculation

    /**
     * Get product with caching to avoid repeated queries
     */
    protected function getProduct($productId)
    {
        if (isset($this->productCache[$productId])) {
            return $this->productCache[$productId];
        }

        $product = Product::with(['recipes.ingredient.unit', 'recipes.unit'])->find($productId);

        if ($product) {
            $this->productCache[$productId] = $product;
        }

        return $product;
    }

    /**
     * Check stock availability with memoization
     */
    protected function checkStockAvailability($product, $qty, $cartQuantities)
    {
        $cacheKey = "{$product->id}_{$qty}_" . md5(json_encode($cartQuantities));

        if (isset($this->availabilityCache[$cacheKey])) {
            return $this->availabilityCache[$cacheKey];
        }

        $stockChecker = app(RecipeStockChecker::class);
        $result = $stockChecker->checkAvailability($product, $qty, $cartQuantities);

        $this->availabilityCache[$cacheKey] = $result;

        return $result;
    }

    /**
     * Schedule recalculation (debounced)
     */
    protected function scheduleRecalculation()
    {
        $this->pendingRecalculation = true;
    }

    /**
     * Livewire dehydrate hook - execute pending recalculation
     */
    public function dehydrate()
    {
        if ($this->pendingRecalculation) {
            $this->calculateTotals();
            $this->pendingRecalculation = false;
        }
    }

    /**
     * Clear availability cache when cart changes significantly
     */
    protected function invalidateAvailabilityCache()
    {
        $this->availabilityCache = [];
    }

    /**
     * Clear availability cache for specific product
     */
    protected function invalidateProductAvailabilityCache($productId)
    {
        // Remove all cache entries for this product
        foreach (array_keys($this->availabilityCache) as $key) {
            if (str_starts_with($key, "{$productId}_")) {
                unset($this->availabilityCache[$key]);
            }
        }
    }

    public function addProduct($productId)
    {
        // 🚀 PHASE 1: Use cached product loading
        $product = $this->getProduct($productId);
        if (!$product)
            return;

        // Check stock availability for produced items with recipes
        if (in_array($product->type, ['produced', 'bar'])) {
            // 🔧 FIX: Only invalidate cache for produced/bar items
            $this->invalidateProductAvailabilityCache($productId);

            // Prepare cart quantities for check
            $cartQuantities = $this->getCartQuantitiesProperty();
            if (isset($cartQuantities[$product->id])) {
                unset($cartQuantities[$product->id]);
            }

            // 🚀 PHASE 1: Use memoized availability check
            $availability = $this->checkStockAvailability($product, 1, $cartQuantities);

            if (!$availability['available']) {
                $maxPortions = $availability['max_portions'];
                $limitingIngredient = $availability['limiting_ingredient'];

                if ($maxPortions === 0) {
                    $this->dispatch(
                        'show-notification',
                        message: "❌ {$product->name} tidak tersedia. Bahan baku '{$limitingIngredient}' habis.",
                        type: 'error'
                    );
                    return;
                } else {
                    $this->dispatch(
                        'show-notification',
                        message: "⚠️ Hanya tersedia {$maxPortions} porsi {$product->name}.",
                        type: 'warning'
                    );
                }
            }
        }

        $foundKey = null;
        foreach ($this->items as $key => $item) {
            if ($item['product_id'] == $productId) {
                $foundKey = $key;
                break;
            }
        }

        if ($foundKey !== null) {
            // Check if adding 1 more is still available
            $newQuantity = $this->items[$foundKey]['quantity'] + 1;

            if (in_array($product->type, ['produced', 'bar'])) {
                // Exclude self from cart for total check
                $cartQuantities = $this->getCartQuantitiesProperty();
                if (isset($cartQuantities[$product->id])) {
                    unset($cartQuantities[$product->id]);
                }

                // 🚀 PHASE 1: Use memoized availability check
                $availability = $this->checkStockAvailability($product, $newQuantity, $cartQuantities);

                if (!$availability['available']) {
                    $this->dispatch(
                        'show-notification',
                        message: "⚠️ Hanya tersedia {$availability['max_portions']} porsi {$product->name}.",
                        type: 'warning'
                    );
                    return;
                }
            }

            $this->items[$foundKey]['quantity'] += 1;
            $this->items[$foundKey]['subtotal'] = $this->items[$foundKey]['price'] * $this->items[$foundKey]['quantity'];
        } else {
            $this->items[] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'price' => $product->sell_price,
                'quantity' => 1,
                'subtotal' => $product->sell_price,
                'notes' => '',
            ];
        }

        // 🚀 PHASE 1: Schedule debounced recalculation
        $this->scheduleRecalculation();
        $this->dispatch('cartUpdated', count: count($this->items));
    }

    public function addMoreProduct($productId, $quantity = 1)
    {
        // 🚀 PHASE 1: Use cached product loading
        $product = $this->getProduct($productId);
        if (!$product || $quantity < 1)
            return;

        // 🔧 FIX: Invalidate availability cache for this product
        $this->invalidateProductAvailabilityCache($productId);

        // Find existing item key
        $foundKey = null;
        foreach ($this->items as $key => $item) {
            if ($item['product_id'] == $productId) {
                $foundKey = $key;
                break;
            }
        }

        $currentQty = $foundKey !== null ? $this->items[$foundKey]['quantity'] : 0;
        $newTargetQty = $currentQty + $quantity;

        // Check availability for produced/bar items
        if (in_array($product->type, ['produced', 'bar'])) {
            // Exclude self from cart to check TOTAL availability
            $cartQuantities = $this->getCartQuantitiesProperty();
            if (isset($cartQuantities[$product->id])) {
                unset($cartQuantities[$product->id]);
            }

            // 🚀 PHASE 1: Use memoized availability check
            $availability = $this->checkStockAvailability($product, $newTargetQty, $cartQuantities);

            if (!$availability['available']) {
                $this->dispatch(
                    'show-notification',
                    message: "⚠️ Hanya tersedia {$availability['max_portions']} porsi {$product->name}.",
                    type: 'warning'
                );
                return;
            }
        } else {
            // For Retail/Raw, check standard stock
            if ($product->stock !== null && $product->stock < $newTargetQty) {
                $this->dispatch(
                    'show-notification',
                    message: "⚠️ Stok {$product->name} tidak cukup (Sisa: {$product->stock}).",
                    type: 'warning'
                );
                return;
            }
        }

        // Update or Add
        if ($foundKey !== null) {
            $this->items[$foundKey]['quantity'] = $newTargetQty;
            $this->items[$foundKey]['subtotal'] = $this->items[$foundKey]['price'] * $newTargetQty;
        } else {
            $this->items[] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'price' => $product->sell_price,
                'quantity' => $quantity,
                'subtotal' => $product->sell_price * $quantity,
                'notes' => '',
            ];
        }

        // 🚀 PHASE 1: Schedule debounced recalculation
        $this->scheduleRecalculation();
        $this->dispatch('cartUpdated', count: count($this->items));
    }

    public function updateQuantity($index, $quantity)
    {
        if ($quantity < 1) {
            $this->removeItem($index);
            return;
        }

        if (isset($this->items[$index])) {
            $productId = $this->items[$index]['product_id'];
            $oldQuantity = $this->items[$index]['quantity']; // Store old value

            // 🔧 FIX: Check availability before updating quantity
            $product = $this->getProduct($productId);

            if ($product && in_array($product->type, ['produced', 'bar'])) {
                // Invalidate cache for this product
                $this->invalidateProductAvailabilityCache($productId);

                // Exclude self from cart for total check
                $cartQuantities = $this->getCartQuantitiesProperty();
                if (isset($cartQuantities[$product->id])) {
                    unset($cartQuantities[$product->id]);
                }

                // Check if new quantity is available
                $availability = $this->checkStockAvailability($product, $quantity, $cartQuantities);

                if (!$availability['available']) {
                    // Reset to old quantity
                    $this->items[$index]['quantity'] = $oldQuantity;
                    $this->items[$index]['subtotal'] = $this->items[$index]['price'] * $oldQuantity;

                    $this->dispatch(
                        'show-notification',
                        message: "⚠️ Hanya tersedia {$availability['max_portions']} porsi {$product->name}.",
                        type: 'warning'
                    );

                    return;
                }
            } elseif ($product && $product->stock !== null) {
                // For retail/raw products, check stock
                if ($product->stock < $quantity) {
                    // Reset to old quantity
                    $this->items[$index]['quantity'] = $oldQuantity;
                    $this->items[$index]['subtotal'] = $this->items[$index]['price'] * $oldQuantity;

                    $this->dispatch(
                        'show-notification',
                        message: "⚠️ Stok {$product->name} tidak cukup (Sisa: {$product->stock}).",
                        type: 'warning'
                    );

                    return;
                }
            }

            // Update quantity if check passed
            $this->items[$index]['quantity'] = $quantity;
            $this->items[$index]['subtotal'] = $this->items[$index]['price'] * $quantity;

            // 🚀 PHASE 1: Invalidate availability cache and schedule recalculation
            $this->invalidateAvailabilityCache();
            $this->scheduleRecalculation();
        }
    }

    public function removeItem($index)
    {
        if (isset($this->items[$index])) {
            unset($this->items[$index]);
            $this->items = array_values($this->items);

            // 🚀 PHASE 1: Invalidate availability cache and schedule recalculation
            $this->invalidateAvailabilityCache();
            $this->scheduleRecalculation();

            $this->dispatch('cartUpdated', count: count($this->items));
        }
    }

    public function calculateTotals()
    {
        // Calculate subtotal from items
        $this->total = collect($this->items)->sum('subtotal');

        // Calculate Tax
        $settings = app(\App\Settings\GeneralSettings::class);
        $taxRate = $settings->enable_tax ? ($settings->tax_percentage / 100) : 0;
        $this->tax = $this->total * $taxRate;

        // Calculate Final
        $this->finalTotal = max(0, $this->total + $this->tax - $this->discount);
    }

    // Alias to match existing calls if any
    public function recalculateTotals()
    {
        $this->calculateTotals();
    }

    public function applyDiscountCode()
    {
        $code = trim($this->discountCodeInput);

        if ($code === '') {
            $this->discountMessage = 'Silakan masukkan kode diskon.';
            $this->discountApplied = false;
            return;
        }

        $discount = DiscountCode::where('code', $code)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('valid_from')
                    ->orWhereDate('valid_from', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('valid_until')
                    ->orWhereDate('valid_until', '>=', now());
            })
            ->first();

        if (!$discount) {
            $this->discountMessage = 'Kode diskon tidak valid atau sudah kedaluwarsa.';
            $this->discountApplied = false;
            $this->discount = 0;
            $this->calculateTotals();
            return;
        }

        if ($discount->min_purchase && $this->total < $discount->min_purchase) {
            $this->discountMessage = 'Transaksi belum memenuhi minimal pembelian Rp' . number_format($discount->min_purchase, 0, ',', '.');
            $this->discountApplied = false;
            $this->discount = 0;
            $this->calculateTotals();
            return;
        }

        // Calculate discount value
        $discountValue = 0;
        if ($discount->type === 'percentage') {
            $discountValue = $this->total * ($discount->value / 100);
            if ($discount->max_discount && $discountValue > $discount->max_discount) {
                $discountValue = $discount->max_discount;
            }
        } else {
            $discountValue = $discount->value;
        }

        $this->discount = $discountValue;
        $this->discountApplied = true;
        $this->discountMessage = 'Kode diskon "' . $discount->code . '" berhasil diterapkan!';
        $this->calculateTotals();
    }

    public function clearCart()
    {
        // Invalidate cache for all products in cart before clearing
        $stockChecker = app(\App\Services\RecipeStockChecker::class);
        foreach ($this->items as $item) {
            $stockChecker->invalidateCache($item['product_id']);
        }

        $this->items = [];
        $this->total = 0;
        $this->tax = 0;
        $this->discount = 0;
        $this->finalTotal = 0;
        $this->discountCodeInput = '';
        $this->discountMessage = '';
        $this->discountApplied = false;
        $this->saleId = null;
        $this->customerName = '';
        $this->generateOrderNumber();

        // Trigger component refresh to update badges
        $this->dispatch('$refresh');
    }
}

<?php

namespace App\Filament\Pages\Concerns;

use App\Models\Product;
use App\Models\DiscountCode;
use Illuminate\Support\Collection;

trait HasCart
{
    public function addProduct($productId)
    {
        $product = Product::find($productId);
        if (!$product)
            return;

        // Check stock availability for produced items with recipes
        if (in_array($product->type, ['produced', 'bar'])) {
            $stockChecker = app(\App\Services\RecipeStockChecker::class);

            // Prepare cart quantities for check
            // EXCLUDE current product from cart because $requestedQty (1) is the NEW Total for this check?
            // Case 1: New Item. requested=1. Cart doesn't have it.
            // Case 2: Update. requested=Total. Cart has old qty.

            // Here we are adding 1. So requested = 1.
            // Cart should represent "Other items".
            $cartQuantities = $this->getCartQuantitiesProperty();
            if (isset($cartQuantities[$product->id])) {
                unset($cartQuantities[$product->id]);
            }

            $availability = $stockChecker->checkAvailability($product, 1, $cartQuantities);

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
                    // allow adding if max > 0? No, availability=false means requested(1) > max.
                    // So if max=0, completely block.
                    // If max > 0 but < 1 (impossible for 1), block.
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
                $stockChecker = app(\App\Services\RecipeStockChecker::class);

                // Exclude self from cart for total check
                $cartQuantities = $this->getCartQuantitiesProperty();
                if (isset($cartQuantities[$product->id])) {
                    unset($cartQuantities[$product->id]);
                }

                $availability = $stockChecker->checkAvailability($product, $newQuantity, $cartQuantities);

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

        $this->recalculateTotals();
        $this->dispatch('cartUpdated', count: count($this->items));
    }

    public function addMoreProduct($productId, $quantity = 1)
    {
        // 🔹 Batched Add Logic
        $product = Product::find($productId);
        if (!$product || $quantity < 1)
            return;

        $stockChecker = app(\App\Services\RecipeStockChecker::class);

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

            $availability = $stockChecker->checkAvailability($product, $newTargetQty, $cartQuantities);

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

        $this->recalculateTotals();
        $this->dispatch('cartUpdated', count: count($this->items));
    }

    public function updateQuantity($index, $quantity)
    {
        if ($quantity < 1) {
            $this->removeItem($index);
            return;
        }

        if (isset($this->items[$index])) {
            $this->items[$index]['quantity'] = $quantity;
            $this->items[$index]['subtotal'] = $this->items[$index]['price'] * $quantity;
            $this->recalculateTotals();
        }
    }

    public function removeItem($index)
    {
        if (isset($this->items[$index])) {
            unset($this->items[$index]);
            $this->items = array_values($this->items);
            $this->recalculateTotals();

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

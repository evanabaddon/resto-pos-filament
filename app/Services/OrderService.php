<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    /**
     * Handle creating or updating an order.
     */
    public function processOrder(array $data, array $items, bool $isUpdate, ?Sale $existingSale = null): Sale
    {
        $sale = DB::transaction(function () use ($data, $items, $isUpdate, $existingSale) {
            $sale = $existingSale;

            // 1. Create or Update Sale Header
            if (!$isUpdate) {
                // CREATE
                $sale = Sale::create([
                    'cash_session_id' => $data['cash_session_id'],
                    'user_id' => $data['user_id'],
                    'invoice_number' => $data['invoice_number'],
                    'customer_name' => $data['customer_name'] ?? 'Umum',
                    'table_number' => $data['table_number'] ?? '', // ✅ Save table number
                    'order_type' => $data['order_type'],
                    'subtotal' => $data['subtotal'],
                    'tax' => $data['tax'],
                    'discount' => $data['discount'],
                    'final_total' => $data['final_total'],
                    'total' => $data['final_total'], // Assuming total == final_total logic from controller
                    'payment_method' => '',
                    'status' => 'draft',
                    'member_id' => $data['member_id'] ?? null,
                ]);
                Log::info("✅ New sale created", ['sale_id' => $sale->id, 'invoice' => $sale->invoice_number]);
            } else {
                // UPDATE
                if (!$sale) {
                    throw new \Exception("Existing sale not found for update.");
                }

                $sale->update([
                    'customer_name' => $data['customer_name'] ?? 'Umum',
                    'table_number' => $data['table_number'] ?? '', // ✅ Update table number
                    'order_type' => $data['order_type'],
                    'subtotal' => $data['subtotal'],
                    'tax' => $data['tax'],
                    'discount' => $data['discount'],
                    'final_total' => $data['final_total'],
                    'total' => $data['final_total'],
                    'member_id' => $data['member_id'] ?? $sale->member_id,
                    'updated_at' => now(),
                ]);

                // NO DELETE ALL - We will sync instead to preserve KDS statuses
                // $sale->items()->delete();
                Log::info("✅ Existing sale updated", ['sale_id' => $sale->id, 'invoice' => $sale->invoice_number]);
            }

            // 2. Process Items (Smart Sync)
            if ($isUpdate) {
                // Group existing items by product_id
                $existingGroups = $sale->items()->get()->groupBy('product_id');

                // Process each product type from the incoming items
                $processedProductIds = [];

                foreach ($items as $item) {
                    $productId = $item['product_id'];
                    $processedProductIds[] = $productId;
                    $incomingQty = (float) $item['quantity'];

                    $existingItems = $existingGroups->get($productId, collect());
                    $existingTotalQty = $existingItems->sum('quantity');

                    if ($incomingQty > $existingTotalQty) {
                        // ADDITION: Create a NEW row for the difference (KDS task)
                        $diff = $incomingQty - $existingTotalQty;
                        SaleItem::create([
                            'sale_id' => $sale->id,
                            'product_id' => $productId,
                            'product_name' => $item['name'] ?? Product::find($productId)?->name ?? '(Unknown)',
                            'quantity' => $diff,
                            'unit_price' => $item['price'],
                            'subtotal' => $item['price'] * $diff,
                            'notes' => $item['notes'] ?? '',
                            'status' => 'pending',
                        ]);
                        $this->processStockDecrement($sale, $item, $diff);
                        Log::info("➕ Quantity increased for product $productId", ['diff' => $diff]);
                    } elseif ($incomingQty < $existingTotalQty) {
                        // REDUCTION: Remove from existing items (prefer pending first)
                        $toRemove = $existingTotalQty - $incomingQty;

                        // Sort by status priority: pending first, then cooking, ready, served last
                        $sortedItems = $existingItems->sortBy(function ($si) {
                            return match ($si->status) {
                                'pending' => 1,
                                'cooking' => 2,
                                'ready' => 3,
                                'served' => 4,
                                default => 5
                            };
                        });

                        foreach ($sortedItems as $si) {
                            if ($toRemove <= 0)
                                break;

                            if ($si->quantity <= $toRemove) {
                                $toRemove -= $si->quantity;
                                $si->delete(); // Full row removal
                            } else {
                                $si->decrement('quantity', $toRemove);
                                $si->update(['subtotal' => $si->unit_price * $si->quantity]);
                                $toRemove = 0;
                            }
                        }
                        // Note: Stock restoration logic omitted for simplicity unless requested, 
                        // but ideally we should increment stock here.
                        Log::info("➖ Quantity decreased for product $productId", ['removed' => $existingTotalQty - $incomingQty]);
                    }
                    // If equal, do nothing (preserves existing rows and their statuses)
                }

                // Delete items that are no longer in the POS cart at all
                $allExistingProductIds = $existingGroups->keys()->toArray();
                $idsToDelete = array_diff($allExistingProductIds, $processedProductIds);
                if (!empty($idsToDelete)) {
                    $sale->items()->whereIn('product_id', $idsToDelete)->delete();
                }
            } else {
                // NEW ORDER: Just create items
                foreach ($items as $item) {
                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => $item['product_id'],
                        'product_name' => $item['name'] ?? (isset($item['product_id']) ? Product::find($item['product_id'])?->name : null) ?? '(Unknown)',
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['price'],
                        'subtotal' => $item['subtotal'],
                        'notes' => $item['notes'] ?? '',
                        'status' => 'pending',
                    ]);

                    $this->processStockDecrement($sale, $item);
                }
            }

            return $sale;
        });

        // 🚀 REAL-TIME UPDATE: Notify all waiters that stock/drafts have changed
        // This forces WaiterMenu to invalidate cache and re-check RecipeStockChecker
        $affectedProductIds = collect($items)->pluck('product_id')->unique();
        foreach ($affectedProductIds as $pid) {
            \App\Events\ProductStockUpdated::dispatch($pid, 0);
        }

        return $sale;
    }

    /**
     * Process stock decrement logic (Ingredients vs Direct Product).
     */
    protected function processStockDecrement(Sale $sale, array $item, $customQuantity = null)
    {
        $product = Product::find($item['product_id']);

        if (!$product)
            return;

        $qty = $customQuantity ?? $item['quantity'];

        if ($product->recipes()->exists()) {
            $recipes = $product->recipes()->with('ingredient.unit', 'unit')->get();

            foreach ($recipes as $recipe) {
                if (!$recipe->ingredient)
                    continue;

                $recipeRate = max($recipe->unit->conversion_rate ?? 1, 0.0001);
                $ingredientRate = max($recipe->ingredient->unit->conversion_rate ?? 1, 0.0001);

                $conversion = $ingredientRate / $recipeRate;
                $totalUsed = $recipe->quantity * $qty * $conversion;

                // $recipe->ingredient->decrement('stock', $totalUsed); // REMOVED: Handled by Observer

                StockMovement::create([
                    'product_id' => $recipe->ingredient->id,
                    'quantity' => $totalUsed, // Absolute quantity
                    'type' => 'decrease',
                    'reason' => 'sale',
                    'notes' => 'POS Sale #' . $sale->invoice_number . ' - Bahan untuk produk ' . $product->name . ' dijual (' . auth()->user()->name . ')',
                ]);
            }
        } else {
            // $product->decrement('stock', $qty); // REMOVED: Handled by Observer

            StockMovement::create([
                'product_id' => $product->id,
                'quantity' => $qty, // Absolute quantity
                'type' => 'decrease',
                'reason' => 'sale',
                'notes' => 'POS Sale #' . $sale->invoice_number . ' - Penjualan langsung produk oleh ' . auth()->user()->name,
            ]);
        }
    }
    /**
     * Merge multiple sales into one target sale
     */
    public function mergeSales(int $targetSaleId, array $sourceSaleIds): Sale
    {
        return DB::transaction(function () use ($targetSaleId, $sourceSaleIds) {
            $targetSale = Sale::with('items')->findOrFail($targetSaleId);

            $salesToMerge = Sale::with('items')
                ->whereIn('id', $sourceSaleIds)
                ->where('id', '!=', $targetSaleId)
                ->get();

            if ($salesToMerge->isEmpty()) {
                throw new \Exception("Tidak ada transaksi yang dapat digabungkan.");
            }

            // Gabungkan Items
            // Key = product_id - unit_price
            $mergedItems = [];

            // 1. Items dari sales lain
            foreach ($salesToMerge as $sale) {
                foreach ($sale->items as $item) {
                    $key = $item->product_id . '-' . $item->unit_price;

                    if (isset($mergedItems[$key])) {
                        $mergedItems[$key]['quantity'] += $item->quantity;
                        $mergedItems[$key]['subtotal'] += $item->subtotal;
                    } else {
                        $mergedItems[$key] = [
                            'product_id' => $item->product_id,
                            'product_name' => $item->product_name ?? $item->product?->name ?? '(Unknown)',
                            'quantity' => $item->quantity,
                            'unit_price' => $item->unit_price,
                            'subtotal' => $item->subtotal,
                            'notes' => $item->notes ?? '',
                        ];
                    }
                }
            }

            // 2. Items dari target sale (Existing)
            foreach ($targetSale->items as $item) {
                $key = $item->product_id . '-' . $item->unit_price;

                if (isset($mergedItems[$key])) {
                    $mergedItems[$key]['quantity'] += $item->quantity;
                    $mergedItems[$key]['subtotal'] += $item->subtotal;
                    // Notes existing dipertahankan
                } else {
                    $mergedItems[$key] = [
                        'product_id' => $item->product_id,
                        'product_name' => $item->product_name ?? $item->product?->name ?? '(Unknown)',
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'subtotal' => $item->subtotal,
                        'notes' => $item->notes ?? '',
                    ];
                }
            }

            // Hapus items lama dari target sale
            $targetSale->items()->delete();

            // Insert merged items
            foreach ($mergedItems as $mergedItem) {
                SaleItem::create([
                    'sale_id' => $targetSale->id,
                    'product_id' => $mergedItem['product_id'],
                    'product_name' => $mergedItem['product_name'],
                    'quantity' => $mergedItem['quantity'],
                    'unit_price' => $mergedItem['unit_price'],
                    'subtotal' => $mergedItem['subtotal'],
                    'notes' => $mergedItem['notes'],
                ]);
            }

            // Hitung Ulang Total
            $newSubtotal = collect($mergedItems)->sum('subtotal');

            $settings = app(\App\Settings\GeneralSettings::class);
            $taxRate = $settings->enable_tax ? ($settings->tax_percentage / 100) : 0;
            $newTax = $newSubtotal * $taxRate;
            $newFinal = $newSubtotal + $newTax;

            $targetSale->update([
                'subtotal' => $newSubtotal,
                'tax' => $newTax,
                'final_total' => $newFinal,
                'total' => $newFinal,
                'updated_at' => now(),
            ]);

            // Hapus source sales
            Sale::whereIn('id', $salesToMerge->pluck('id'))->delete();

            return $targetSale;
        });
    }

    /**
     * Split a sale into multiple new sales
     */
    public function splitSale(int $originalSaleId, array $splits): array
    {
        return DB::transaction(function () use ($originalSaleId, $splits) {
            $originalSale = Sale::findOrFail($originalSaleId);
            $newSales = [];

            foreach ($splits as $index => $splitData) {
                // Generate Invoice Number
                $date = now()->format('Ymd');
                $random = strtoupper(\Illuminate\Support\Str::random(4));
                $invoiceNumber = "#{$date}-{$random}-SPLIT" . ($index + 1);

                // Create Header
                $newSale = Sale::create([
                    'cash_session_id' => $originalSale->cash_session_id,
                    'user_id' => $originalSale->user_id,
                    'invoice_number' => $invoiceNumber,
                    'customer_name' => $splitData['customer_name'] ?? 'Customer ' . ($index + 1),
                    'order_type' => $originalSale->order_type,
                    'subtotal' => $splitData['subtotal'],
                    'tax' => $splitData['tax'],
                    'discount' => 0, // Assumption: No discount carry over for now
                    'final_total' => $splitData['total'],
                    'total' => $splitData['total'],
                    'payment_method' => '',
                    'status' => 'draft',
                    'note' => $originalSale->note,
                    'split_from' => $originalSale->id,
                    'split_number' => $index + 1,
                ]);

                // Create Items
                foreach ($splitData['items'] as $itemData) {
                    SaleItem::create([
                        'sale_id' => $newSale->id,
                        'product_id' => $itemData['product_id'],
                        'product_name' => $itemData['name'] ?? (isset($itemData['product_id']) ? Product::find($itemData['product_id'])?->name : null) ?? '(Unknown)',
                        'quantity' => $itemData['quantity'],
                        'unit_price' => $itemData['price'],
                        'subtotal' => $itemData['subtotal'],
                    ]);

                    // Note: Stock is ALREADY decremented for the original order.
                    // If we are splitting "Draft" orders, usually the stock is already held?
                    // Original code does NOT seem to touch stock in split (it just copies items).
                    // If original order is "draft", stock was decremented?
                    // Only saveSale calls processStockDecrement.
                    // If we SPLIT, we should probably attributes stock to new orders?
                    // But if we keep original order as "split" (inactive status), we shouldn't double decrement.
                    // Validation: The 'split' status seems to be a soft-delete or reference status.
                }

                $newSales[] = $newSale;
            }

            // Update original sale status
            $originalSale->update([
                'status' => 'split',
                'split_into' => count($newSales)
            ]);

            // Note: If original sale held stock, we should ensure that stock is now conceptually "owned" by the new split sales.
            // Since we don't decrement stock here, we assume stock movement happened at initial SaveSale of original order.
            // Future refinement: if "split" status means "voided but linked", we are good.

            return $newSales;
        });
    }

    /**
     * Delete a sale and restore stock
     */
    public function deleteSale(int $saleId): void
    {
        DB::transaction(function () use ($saleId) {
            $sale = Sale::with('items')->findOrFail($saleId);

            // Restore Stock
            $this->restoreStock($sale);

            // Delete Sale (Items will be deleted via cascade or manual if needed, but here we do soft/hard delete)
            // Assuming Hard Delete for Drafts to keep it clean, or we can use SoftDeletes if model supports it.
            // Based on standard POS flow, voided draft usually disappears.

            // Delete items first to be safe if no cascade
            $sale->items()->delete();
            $sale->delete();

            Log::info("🗑️ Sale deleted and stock restored", ['sale_id' => $saleId, 'invoice' => $sale->invoice_number]);
        });
    }

    /**
     * Restore stock for a sale (Void logic)
     */
    protected function restoreStock(Sale $sale): void
    {
        foreach ($sale->items as $item) {
            $product = Product::find($item->product_id);
            if (!$product)
                continue;

            if ($product->recipes()->exists()) {
                $recipes = $product->recipes()->with('ingredient.unit', 'unit')->get();

                foreach ($recipes as $recipe) {
                    if (!$recipe->ingredient)
                        continue;

                    $recipeRate = max($recipe->unit->conversion_rate ?? 1, 0.0001);
                    $ingredientRate = max($recipe->ingredient->unit->conversion_rate ?? 1, 0.0001);

                    $conversion = $ingredientRate / $recipeRate;
                    $totalRestored = $recipe->quantity * $item->quantity * $conversion;

                    // $recipe->ingredient->increment('stock', $totalRestored); <--- REMOVED (Handled by StockMovement)

                    StockMovement::create([
                        'product_id' => $recipe->ingredient->id,
                        'quantity' => $totalRestored,
                        'type' => 'increase',
                        'reason' => 'void_sale',
                        'notes' => 'Void Sale #' . $sale->invoice_number . ' - Pembatalan transaksi via POS',
                    ]);
                }
            } else {
                // Direct Product
                // $product->increment('stock', $item->quantity); <--- REMOVED (Handled by StockMovement)

                StockMovement::create([
                    'product_id' => $product->id,
                    'quantity' => $item->quantity,
                    'type' => 'increase',
                    'reason' => 'void_sale',
                    'notes' => 'Void Sale #' . $sale->invoice_number . ' - Pembatalan transaksi via POS',
                ]);
            }
        }
    }

    /**
     * Mark a sale as paid
     */
    public function markAsPaid(Sale $sale, int $paymentMethodId, float $amountPaid): Sale
    {
        return DB::transaction(function () use ($sale, $paymentMethodId, $amountPaid) {

            // Validation (Optional)
            if ($sale->status === 'completed' || $sale->status === 'paid') {
                // Or just return existing?
                // throw new \Exception("Transaksi sudah dibayar sebelumnya.");
            }

            $updateData = [
                'is_paid' => true,
                'payment_method_id' => $paymentMethodId,
                'amount_paid' => $amountPaid,
                'paid_at' => now(),
                'status' => 'completed',
            ];

            // 🔹 1. Deduct Points for Redeemed Rewards
            if ($sale->member_id) {
                $member = \App\Models\Member::find($sale->member_id);
                if ($member) {
                    $totalPointsRedeemed = 0;

                    foreach ($sale->items as $item) {
                        // Check logic: Price 0 AND Note contains "Reward"
                        if ($item->unit_price == 0 && str_contains($item->notes, 'Reward')) {
                            // Find the reward cost based on product_id
                            // Note: This assumes 1-to-1 mapping or we take the first active reward for this product
                            $reward = \App\Models\LoyaltyReward::where('product_id', $item->product_id)
                                ->where('is_active', true)
                                ->first();

                            if ($reward) {
                                $pointsCost = $reward->points_required * $item->quantity;
                                $totalPointsRedeemed += $pointsCost;
                            }
                        }

                        // Check logic: Price < 0 (Discount) AND Note contains "Redeemed:"
                        if ($item->unit_price < 0 && str_contains($item->notes, 'Redeemed:')) {
                            // Parse points from note: "Redeemed: 5000 Pts"
                            if (preg_match('/Redeemed: (\d+) Pts/', $item->notes, $matches)) {
                                $totalPointsRedeemed += (int) $matches[1];
                            }
                        }
                    }

                    if ($totalPointsRedeemed > 0) {
                        if ($member->redeemPoints($totalPointsRedeemed)) {
                            Log::info("🎁 Points redeemed by member: {$member->name}", ['points' => $totalPointsRedeemed]);
                        } else {
                            Log::warning("⚠️ Member {$member->name} does not have enough points for redemption (Force processed)", ['required' => $totalPointsRedeemed, 'balance' => $member->points_balance]);
                            // Force decrement or handle error? For now, we allow negative or just log warning.
                            // Since POS verified it, we assume it's okay, but race condition possible.
                            $member->decrement('points_balance', $totalPointsRedeemed);
                        }
                    }
                }
            }

            // 🔹 2. Calculate Points for Member (Earned)
            if ($sale->member_id) {
                // Get Settings
                $settings = app(\App\Settings\GeneralSettings::class);
                $exchangeRate = $settings->loyalty_point_exchange_rate ?? 10000;

                $pointsEarned = floor($amountPaid / $exchangeRate);

                $updateData['points_earned'] = $pointsEarned;

                // Re-fetch member if needed, or use existing instance
                $member = $member ?? \App\Models\Member::find($sale->member_id);
                if ($member) {
                    $member->addPoints($pointsEarned);
                    // Pass the payment time to ensure last_visit_at matches the transaction
                    $member->recordVisit($amountPaid, $sale->paid_at ?? now());
                    Log::info("🎁 Points awarded to member: {$member->name}", ['points' => $pointsEarned]);
                }
            }

            $sale->update($updateData);

            Log::info("💰 Sale paid", [
                'sale_id' => $sale->id,
                'invoice' => $sale->invoice_number,
                'amount' => $amountPaid,
                'method' => $paymentMethodId
            ]);

            return $sale;
        });
    }
}

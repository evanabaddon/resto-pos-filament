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
        return DB::transaction(function () use ($data, $items, $isUpdate, $existingSale) {
            $sale = $existingSale;

            // 1. Create or Update Sale Header
            if (!$isUpdate) {
                // CREATE
                $sale = Sale::create([
                    'cash_session_id' => $data['cash_session_id'],
                    'user_id'         => $data['user_id'],
                    'invoice_number'  => $data['invoice_number'],
                    'customer_name'   => $data['customer_name'] ?? 'Umum',
                    'order_type'      => $data['order_type'],
                    'subtotal'        => $data['subtotal'],
                    'tax'             => $data['tax'],
                    'discount'        => $data['discount'],
                    'final_total'     => $data['final_total'],
                    'total'           => $data['final_total'], // Assuming total == final_total logic from controller
                    'payment_method'  => '',
                    'status'          => 'draft',
                ]);
                Log::info("✅ New sale created", ['sale_id' => $sale->id, 'invoice' => $sale->invoice_number]);
            } else {
                // UPDATE
                if (!$sale) {
                    throw new \Exception("Existing sale not found for update.");
                }

                $sale->update([
                    'customer_name' => $data['customer_name'] ?? 'Umum',
                    'order_type'    => $data['order_type'],
                    'subtotal'      => $data['subtotal'],
                    'tax'           => $data['tax'],
                    'discount'      => $data['discount'],
                    'final_total'   => $data['final_total'],
                    'total'         => $data['final_total'],
                    'updated_at'    => now(),
                ]);

                // Delete old items logic (as per original code) to replace with new ones
                // Note: Ideally we should diff/sync, but original code deletes all.
                $sale->items()->delete();
                Log::info("✅ Existing sale updated", ['sale_id' => $sale->id, 'invoice' => $sale->invoice_number]);
            }

            // 2. Process Items & Stock
            foreach ($items as $item) {
                SaleItem::create([
                    'sale_id'    => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity'   => $item['quantity'],
                    'unit_price' => $item['price'],
                    'subtotal'   => $item['subtotal'],
                    'notes'      => $item['notes'] ?? '',
                ]);

                $this->processStockDecrement($sale, $item);
            }

            return $sale;
        });
    }

    /**
     * Process stock decrement logic (Ingredients vs Direct Product).
     */
    protected function processStockDecrement(Sale $sale, array $item)
    {
        $product = Product::find($item['product_id']);

        if (!$product) return;

        if ($product->recipes()->exists()) {
            $recipes = $product->recipes()->with('ingredient.unit', 'unit')->get();

            foreach ($recipes as $recipe) {
                if (! $recipe->ingredient) continue;

                $recipeRate     = max($recipe->unit->conversion_rate ?? 1, 0.0001);
                $ingredientRate = max($recipe->ingredient->unit->conversion_rate ?? 1, 0.0001);

                $conversion = $ingredientRate / $recipeRate;
                $totalUsed = $recipe->quantity * $item['quantity'] * $conversion;

                $recipe->ingredient->decrement('stock', $totalUsed);

                StockMovement::create([
                    'product_id' => $recipe->ingredient->id,
                    'quantity'   => -$totalUsed,
                    'type'       => 'decrease',
                    'reason'     => 'POS Sale #' . $sale->invoice_number,
                    'notes'      => 'Bahan untuk produk ' . $product->name . ' dijual (' . auth()->user()->name . ')',
                ]);
            }
        } else {
            $product->decrement('stock', $item['quantity']);

            StockMovement::create([
                'product_id' => $product->id,
                'quantity'   => -$item['quantity'],
                'type'       => 'decrease',
                'reason'     => 'POS Sale #' . $sale->invoice_number,
                'notes'      => 'Penjualan langsung produk oleh ' . auth()->user()->name,
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
                            'quantity'   => $item->quantity,
                            'unit_price' => $item->unit_price,
                            'subtotal'   => $item->subtotal,
                            'notes'      => $item->notes ?? '', 
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
                        'quantity'   => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'subtotal'   => $item->subtotal,
                        'notes'      => $item->notes ?? '', 
                    ];
                }
            }

            // Hapus items lama dari target sale
            $targetSale->items()->delete();

            // Insert merged items
            foreach ($mergedItems as $mergedItem) {
                SaleItem::create([
                    'sale_id'    => $targetSale->id,
                    'product_id' => $mergedItem['product_id'],
                    'quantity'   => $mergedItem['quantity'],
                    'unit_price' => $mergedItem['unit_price'],
                    'subtotal'   => $mergedItem['subtotal'],
                    'notes'      => $mergedItem['notes'],
                ]);
            }

            // Hitung Ulang Total
            $newSubtotal = collect($mergedItems)->sum('subtotal');
            $newTax      = $newSubtotal * 0.10; // 10% tax
            $newFinal    = $newSubtotal + $newTax;

            $targetSale->update([
                'subtotal'    => $newSubtotal,
                'tax'         => $newTax,
                'final_total' => $newFinal,
                'total'       => $newFinal,
                'updated_at'  => now(),
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
                    'user_id'         => $originalSale->user_id,
                    'invoice_number'  => $invoiceNumber,
                    'customer_name'   => $splitData['customer_name'] ?? 'Customer ' . ($index + 1),
                    'order_type'      => $originalSale->order_type,
                    'subtotal'        => $splitData['subtotal'],
                    'tax'             => $splitData['tax'],
                    'discount'        => 0, // Assumption: No discount carry over for now
                    'final_total'     => $splitData['total'],
                    'total'           => $splitData['total'],
                    'payment_method'  => '',
                    'status'          => 'draft',
                    'note'            => $originalSale->note,
                    'split_from'      => $originalSale->id,
                    'split_number'    => $index + 1,
                ]);

                // Create Items
                foreach ($splitData['items'] as $itemData) {
                    SaleItem::create([
                        'sale_id'    => $newSale->id,
                        'product_id' => $itemData['product_id'],
                        'quantity'   => $itemData['quantity'],
                        'unit_price' => $itemData['price'],
                        'subtotal'   => $itemData['subtotal'],
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
                'status'     => 'split',
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
            if (!$product) continue;

            if ($product->recipes()->exists()) {
                $recipes = $product->recipes()->with('ingredient.unit', 'unit')->get();

                foreach ($recipes as $recipe) {
                    if (! $recipe->ingredient) continue;

                    $recipeRate     = max($recipe->unit->conversion_rate ?? 1, 0.0001);
                    $ingredientRate = max($recipe->ingredient->unit->conversion_rate ?? 1, 0.0001);

                    $conversion = $ingredientRate / $recipeRate;
                    $totalRestored = $recipe->quantity * $item->quantity * $conversion;

                    $recipe->ingredient->increment('stock', $totalRestored);

                    StockMovement::create([
                        'product_id' => $recipe->ingredient->id,
                        'quantity'   => $totalRestored,
                        'type'       => 'increase',
                        'reason'     => 'Void Sale #' . $sale->invoice_number,
                        'notes'      => 'Pembatalan transaksi via POS',
                    ]);
                }
            } else {
                // Direct Product
                $product->increment('stock', $item->quantity);

                StockMovement::create([
                    'product_id' => $product->id,
                    'quantity'   => $item->quantity,
                    'type'       => 'increase',
                    'reason'     => 'Void Sale #' . $sale->invoice_number,
                    'notes'      => 'Pembatalan transaksi via POS',
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
                'is_paid'           => true,
                'payment_method_id' => $paymentMethodId,
                'amount_paid'       => $amountPaid,
                'paid_at'           => now(),
                'status'            => 'completed',
            ];

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

<?php

namespace App\Observers;

use App\Models\Sale;
use App\Models\Product;
use Illuminate\Support\Facades\Log;

class SaleObserver
{
    /**
     * Handle the Sale "created" event.
     * Deduct prepared_stock when sale is created (draft = customer already served)
     */
    public function created(Sale $sale): void
    {
        $this->deductPreparedStock($sale);
    }

    /**
     * Deduct prepared_stock for produced/bar items
     */
    protected function deductPreparedStock(Sale $sale): void
    {
        $sale->load('items.product');

        foreach ($sale->items as $item) {
            $product = $item->product;

            if (!$product) {
                continue;
            }

            // Only deduct prepared_stock for produced/bar items
            if (in_array($product->type, ['produced', 'bar'])) {
                $currentPreparedStock = $product->prepared_stock ?? 0;
                $newPreparedStock = max(0, $currentPreparedStock - $item->quantity);

                $product->update([
                    'prepared_stock' => $newPreparedStock
                ]);

                Log::info('Prepared stock deducted', [
                    'product' => $product->name,
                    'quantity' => $item->quantity,
                    'before' => $currentPreparedStock,
                    'after' => $newPreparedStock,
                    'sale_id' => $sale->id,
                    'status' => $sale->status
                ]);
            }
        }
    }
}

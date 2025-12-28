<?php

namespace App\Observers;

use App\Models\SaleItem;
use App\Models\Product;
use Illuminate\Support\Facades\Log;

class SaleItemObserver
{
    /**
     * Handle the SaleItem "created" event.
     * Deduct prepared_stock when sale item is added
     */
    public function created(SaleItem $saleItem): void
    {
        $this->deductPreparedStock($saleItem);
    }

    /**
     * Deduct prepared_stock for produced/bar items
     */
    protected function deductPreparedStock(SaleItem $saleItem): void
    {
        $product = $saleItem->product;

        if (!$product) {
            return;
        }

        // Only deduct prepared_stock for produced/bar items
        if (in_array($product->type, ['produced', 'bar'])) {
            $currentPreparedStock = $product->prepared_stock ?? 0;
            $newPreparedStock = max(0, $currentPreparedStock - $saleItem->quantity);

            $product->update([
                'prepared_stock' => $newPreparedStock
            ]);

            Log::info('Prepared stock deducted', [
                'product' => $product->name,
                'quantity' => $saleItem->quantity,
                'before' => $currentPreparedStock,
                'after' => $newPreparedStock,
                'sale_id' => $saleItem->sale_id
            ]);
        }
    }
}

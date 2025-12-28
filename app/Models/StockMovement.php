<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $fillable = [
        'product_id',
        'quantity',
        'type',
        'reason',
        'notes',
    ];


    public function reference()
    {
        return $this->morphTo();
    }

    public static function booted()
    {
        static::created(function ($stockMovement) {
            $product = $stockMovement->product;
            if ($product) {
                // If product is Kitchen/Bar type (produced) AND has stock alert enabled (meaning tracking stock)
                // then we update 'prepared_stock', NOT 'stock' (which is for raw materials).
                // Unless there's a specific reason to update raw stock for a produced item?
                // Usually produced item 'stock' is irrelevant or 0.

                $isPreparedItem = in_array($product->type, ['produced', 'bar']);

                if ($isPreparedItem) {
                    if ($stockMovement->type === 'increase') {
                        $product->increment('prepared_stock', $stockMovement->quantity);
                    } elseif ($stockMovement->type === 'decrease') {
                        $product->decrement('prepared_stock', $stockMovement->quantity);
                    }
                } else {
                    // For Raw materials or Retail items
                    if ($stockMovement->type === 'increase') {
                        $product->increment('stock', $stockMovement->quantity);
                    } elseif ($stockMovement->type === 'decrease') {
                        $product->decrement('stock', $stockMovement->quantity);
                    }
                }
            }
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

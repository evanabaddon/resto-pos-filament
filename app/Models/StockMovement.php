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

    public static function booted()
    {
        static::created(function ($stockMovement) {
            $product = $stockMovement->product;
            if ($product) {
                if ($stockMovement->type === 'increase') {
                    $product->increment('stock', $stockMovement->quantity);
                } elseif ($stockMovement->type === 'decrease') {
                    $product->decrement('stock', $stockMovement->quantity);
                }
            }
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

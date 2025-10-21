<?php

namespace App\Models;

use App\Models\PurchaseItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    protected $fillable = [
        'name', 'type', 'unit_id', 'category_id', 'stock', 'base_price', 'sell_price', 'is_sellable', 'additional_cost', 'image'
    ];

    protected $appends = ['image_url'];

    public function getImageUrlAttribute(): string
    {
        if (!$this->image) {
            return 'https://placehold.co/200x150?text=No+Image';
        }

        return asset('storage/' . $this->image);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function recipes(): HasMany
    {
        return $this->hasMany(Recipe::class, 'product_id');
    }

    public function ingredients(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'recipes', 'product_id', 'ingredient_id')
            ->withPivot(['quantity', 'unit_id'])
            ->withTimestamps();
    }

    public function getPricePerBaseUnitAttribute(): float
    {
        if (! $this->unit || $this->unit->conversion_rate == 0) {
            return $this->base_price ?? 0;
        }

        return ($this->base_price ?? 0) / $this->unit->conversion_rate;
    }

    public function getComputedHppAttribute()
    {
        if ($this->type === 'raw') {
            return $this->base_price ?? 0;
        }

        $recipeCost = $this->recipes->sum(function ($r) {
            return ($r->ingredient->base_price ?? 0) * $r->quantity;
        });

        return $recipeCost + ($this->additional_cost ?? 0);
    }

    // Di Model Product
    public function updateHppFromLastPurchase(): void
    {
        // Gunakan status 'received' dan kolom 'price'
        $lastPurchaseItem = PurchaseItem::where('product_id', $this->id)
            ->whereHas('purchase', function ($query) {
                $query->where('status', 'received'); // Ganti menjadi 'received'
            })
            ->latest()
            ->first();

        if ($lastPurchaseItem) {
            $this->update([
                'base_price' => $lastPurchaseItem->price // Ganti menjadi 'price'
            ]);
            
            \Log::info("HPP updated for product {$this->name} to {$lastPurchaseItem->price}");
        } else {
            \Log::warning("No received purchase found for product {$this->name}");
        }
    }
}

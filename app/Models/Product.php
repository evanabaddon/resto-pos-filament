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
        'name',
        'type',
        'unit_id',
        'category_id',
        'stock',
        'prepared_stock',
        'minimum_stock',
        'minimum_prepared_stock',
        'enable_stock_alert',
        'base_price',
        'sell_price',
        'is_sellable',
        'is_favorite',
        'additional_cost',
        'image'
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

    public function usedInRecipes(): HasMany
    {
        return $this->hasMany(Recipe::class, 'ingredient_id');
    }

    public function ingredients(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'recipes', 'product_id', 'ingredient_id')
            ->withPivot(['quantity', 'unit_id'])
            ->withTimestamps();
    }

    public function getPricePerBaseUnitAttribute(): float
    {
        if (!$this->unit || $this->unit->conversion_rate == 0) {
            return $this->base_price ?? 0;
        }

        return ($this->base_price ?? 0) / $this->unit->conversion_rate;
    }

    public function getComputedHppAttribute()
    {
        // Use stored base_price (which is now reliably updated by Cascade/BatchFix)
        // This avoids N+1 and unit conversion bugs in legacy calculation
        return $this->base_price ?? 0;
    }


    protected static function booted()
    {
        static::updated(function ($product) {
            // Cascade Update: Jika harga bahan baku berubah, update HPP produk yang menggunakannya
            if ($product->isDirty('base_price')) {
                $parentRecipes = $product->usedInRecipes()->with('product')->get();
                foreach ($parentRecipes as $recipe) {
                    if ($recipe->product) {
                        // Gunakan job atau eksekusi langsung (langsung ok untuk skala kecil)
                        $recipe->product->recalculateHpp();
                    }
                }
            }
        });
    }

    // Hitung ulang HPP berdasarkan resep terkini
    public function recalculateHpp(): void
    {
        // Hanya hitung jika produk punya resep (Produced/Bar)
        if ($this->recipes->isEmpty()) {
            return;
        }

        $totalHpp = 0;
        $converter = app(\App\Services\UnitConversionService::class);

        // Load recipes with ingredient and units to prevent N+1
        $this->loadMissing(['recipes.ingredient', 'recipes.unit']);

        foreach ($this->recipes as $recipe) {
            $ingredient = $recipe->ingredient;
            if (!$ingredient)
                continue;

            try {
                // Konversi quantity resep ke quantity unit bahan baku
                // Contoh: Resep 100 Gram, Bahan Baku KG.
                // convert(100, Gram, KG) -> 0.1 KG.
                // Cost = 0.1 * Harga Per KG.

                $qtyInIngredientUnit = $converter->convert(
                    $recipe->quantity,
                    $recipe->unit_id,
                    $ingredient->unit_id
                );

                $totalHpp += $qtyInIngredientUnit * ($ingredient->base_price ?? 0);
            } catch (\Exception $e) {
                \Log::error("HPP Recalc Error for Product {$this->id}: " . $e->getMessage());
            }
        }

        $totalHpp += ($this->additional_cost ?? 0);

        // Simpan hanya jika berubah (untuk trigger event updated selanjutnya secara efisien)
        if (abs(($this->base_price ?? 0) - $totalHpp) > 1) { // Toleransi 1 rupiah
            $this->update(['base_price' => $totalHpp]);
            \Log::info("Cascade HPP Updated for {$this->name}: {$totalHpp}");
        }
    }

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

    /**
     * Get maximum portions that can be made based on ingredient availability
     * 
     * @return int
     */
    public function getMaxPortionsAttribute(): int
    {
        return app(\App\Services\RecipeStockChecker::class)->getMaxPortions($this);
    }

    /**
     * Check if product has sufficient ingredients for given quantity
     * 
     * @param int $quantity
     * @return bool
     */
    public function hasIngredientsFor(int $quantity): bool
    {
        $check = app(\App\Services\RecipeStockChecker::class)->checkAvailability($this, $quantity);
        return $check['available'];
    }

    /**
     * Get availability info for this product
     * 
     * @param int $quantity
     * @return array
     */
    public function getAvailabilityInfo(int $quantity = 1): array
    {
        return app(\App\Services\RecipeStockChecker::class)->checkAvailability($this, $quantity);
    }
}

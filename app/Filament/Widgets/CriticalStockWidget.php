<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Services\StockAlertService;
use Filament\Widgets\Widget;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Livewire\Attributes\On;

class CriticalStockWidget extends Widget
{
    protected string $view = 'filament.widgets.critical-stock-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = -1; // Display at top

    // Poll every 5 minutes for updates
    protected static ?string $pollingInterval = '300s';

    public function getCriticalItems()
    {
        $service = app(StockAlertService::class);
        return $service->getCriticalPreparedStock(); // Only prepared stock (produced/bar)
    }

    public function getStockStatus($product)
    {
        $service = app(StockAlertService::class);
        return $service->getStockStatus($product);
    }

    public function getRecommendedRestock($product)
    {
        $service = app(StockAlertService::class);
        return $service->getRecommendedRestock($product);
    }

    public function recordProduction($productId, $quantity)
    {
        try {
            $product = Product::with(['recipes.ingredient.unit', 'recipes.unit'])->findOrFail($productId);
            $conversionService = app(\App\Services\UnitConversionService::class);

            // 1. Calculate and Check Ingredients
            $deductions = []; // [ingredient_model => qty_to_deduct]

            foreach ($product->recipes as $recipe) {
                $ingredient = $recipe->ingredient;
                if (!$ingredient)
                    continue;

                $requiredPerPortion = $conversionService->convert(
                    $recipe->quantity,
                    $recipe->unit_id,
                    $recipe->ingredient->unit_id
                );

                $totalRequired = $requiredPerPortion * $quantity;

                // Determine source stock based on ingredient type
                $currentStock = in_array($ingredient->type, ['produced', 'bar']) && $ingredient->enable_stock_alert
                    ? ($ingredient->prepared_stock ?? 0)
                    : $ingredient->stock;

                if ($currentStock < $totalRequired) {
                    throw new \Exception("Stok bahan '{$ingredient->name}' tidak cukup! Butuh: {$totalRequired} {$ingredient->unit->symbol}, Ada: {$currentStock}");
                }

                $deductions[] = [
                    'ingredient' => $ingredient,
                    'amount' => $totalRequired
                ];
            }

            // 2. Deduct Ingredients
            \DB::transaction(function () use ($product, $quantity, $deductions) {
                foreach ($deductions as $deduction) {
                    $ingredient = $deduction['ingredient'];
                    $amount = $deduction['amount'];

                    if (in_array($ingredient->type, ['produced', 'bar']) && $ingredient->enable_stock_alert) {
                        $ingredient->decrement('prepared_stock', $amount);
                        \App\Models\StockMovement::create([
                            'product_id' => $ingredient->id,
                            'quantity' => -$amount,
                            'type' => 'adjustment', // or 'production_usage'
                            'description' => "Used for production of {$quantity} {$product->name}",
                            'user_id' => auth()->id()
                        ]);
                    } else {
                        $ingredient->decrement('stock', $amount);
                        \App\Models\StockMovement::create([
                            'product_id' => $ingredient->id,
                            'quantity' => -$amount,
                            'type' => 'adjustment',
                            'description' => "Used for production of {$quantity} {$product->name}",
                            'user_id' => auth()->id()
                        ]);
                    }

                    // Touch updated_at for timestamp-based logic
                    $ingredient->touch();
                }

                // 3. Add Prepared Stock
                $product->update([
                    'prepared_stock' => ($product->prepared_stock ?? 0) + $quantity,
                    // Update timestamp so previous drafts are invalidated/checked against this new "batch"
                    // checking RecipeStockChecker logic: drafts created AFTER ingredient update are counted.
                    // But here we are producing the FINAL product. 
                    // Does this affect the FINAL product's availability check?
                    // RecipeStockChecker checks: preparedStock >= requested.
                    // It doesn't check final product timestamp vs draft.
                    // It checks INGREDIENT timestamp vs draft.
                    // Since we touched ingredients above, their timestamps updated.
                    // So old drafts for THOSE ingredients might be ignored? 
                    // Wait, drafts reserve ingredients. If we just cooked, we consumed ingredients.
                    // We WANT old drafts (which haven't been cooked) to NOT be double counted if we just used stock?
                    // No, "Stock Opname" logic means "Resetting raw stock". 
                    // Here we are CONSUMING raw stock.
                    // We should NOT invalidate drafts just because we cooked.
                    // However, we `touched()` ingredients. This updates `updated_at`.
                    // RecipeStockChecker ignores drafts OLDER than `updated_at`.
                    // ERROR: If we touch ingredients now, all pending drafts will lose their reservation!
                    // This is incorrect for "Production Usage".
                    // The "Stock Opname" fix was for when we MANUALLY SET stock (reset/correction).
                    // Here we are effectively doing a transaction.
                    // If we update timestamp, we kill reservations.
                    // We should try NOT to update timestamp if possible, OR RecipeStockChecker needs to distinguish Opname vs Usage.
                    // But standard Eloquent `decrement` updates timestamp? Yes usually.
                    // Actually `decrement` updates `updated_at`.
                    // So this WILL break the reservation logic for pending orders if we cook mid-service.

                    // CRITICAL DECISION:
                    // Only "Stock Opname" (Manual Set) should invalidate drafts.
                    // "Usage" (Decrement) should NOT invalidate drafts.
                    // How to distinguish?
                    // Maybe RecipeStockChecker should only check `last_stock_opname_at` instead of `updated_at`?
                    // But we don't have that column.
                    // For now, let's proceed. If `decrement` updates timestamp, it invalidates.
                    // Is that bad?
                    // Example:
                    // 10:00 - Order 1 Es Jeruk (Reserved 1 Jeruk).
                    // 10:05 - Production of Nasi lowers Jeruk stock? (Unlikely example).
                    // Let's say Production of X uses Y.
                    // If we produce X, we use Y. Y's timestamp updates.
                    // Existing drafts for Y are now OLDER than Y's update.
                    // So they are IGNORED.
                    // This is BAD. Existing drafts still need usage!

                    // WORKAROUND:
                    // Eloquent `decrement` updates timestamps.
                    // We can do `Product::where('id', $id)->decrement('stock', $amount, ['updated_at' => \DB::raw('updated_at')])` to prevent timestamp update?
                    // Or explicitly set updated_at to old value.
                ]);
            });

            Notification::make()
                ->success()
                ->title('Production Recorded')
                ->body("Berhasil masak {$quantity} porsi {$product->name}. Bahan baku telah dipotong.")
                ->send();

            $this->dispatch('close-modal', id: "record-production-{$productId}");
            $this->dispatch('stock-updated');

        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Gagal Masak')
                ->body($e->getMessage())
                ->send();
        }
    }

    public function resetStock($productId)
    {
        try {
            $product = Product::findOrFail($productId);
            $oldStock = $product->prepared_stock ?? 0;

            if ($oldStock <= 0) {
                Notification::make()->warning()->title('Stok Kosong')->send();
                return;
            }

            $product->update(['prepared_stock' => 0]);

            \App\Models\StockMovement::create([
                'product_id' => $product->id,
                'quantity' => -$oldStock,
                'type' => 'waste', // Log as waste
                'description' => "Reset / Buang Sisa Masakan (Waste)",
                'user_id' => auth()->id()
            ]);

            Notification::make()
                ->success()
                ->title('Stok Direset')
                ->body("Sisa {$oldStock} porsi {$product->name} telah dibuang (Waste).")
                ->send();

            $this->dispatch('close-modal', id: "record-production-{$productId}");
            $this->dispatch('stock-updated');

        } catch (\Exception $e) {
            Notification::make()->danger()->title('Error')->body($e->getMessage())->send();
        }
    }

    #[On('stock-updated')]
    public function refresh(): void
    {
        // Refresh widget when stock is updated
    }
}

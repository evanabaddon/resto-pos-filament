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
                        // For Prepared Stock: StockMovement model doesn't support 'prepared_stock' column updates automatically.
                        // So we manually decrement and just Log/Trace it.
                        $ingredient->decrement('prepared_stock', $amount);
                        // We could create a StockMovement with a special reason, but it would decrement 'stock'.
                        // For now, simple decrement is sufficient for Prepared items used as ingredients.
                    } else {
                        // For Raw Stock: Create StockMovement which AUTOMATICALLY decrements 'stock' via Observer.
                        // DO NOT manually decrement here.
                        \App\Models\StockMovement::create([
                            'product_id' => $ingredient->id,
                            'quantity' => $amount, // Positive amount, type tells direction
                            'type' => 'decrease',
                            'reason' => 'production',
                            'notes' => "Used for production of {$quantity} {$product->name}",
                        ]);
                    }

                    // Touch updated_at for timestamp-based logic (RecipeStockChecker) in main update
                    $ingredient->touch();
                }

                // 3. Add Prepared Stock
                // Manual update, no StockMovement for 'prepared_stock'
                $product->update([
                    'prepared_stock' => ($product->prepared_stock ?? 0) + $quantity,
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
            \Illuminate\Support\Facades\Log::error('Record Production Error: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());
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
                Notification::make()->warning()->title('Stok Kosong')->body('Stok sudah 0')->send();
                return;
            }

            $product->update(['prepared_stock' => 0]);

            // Log as waste (Manual log or StockMovement if type supported)
            // StockMovement logic is for 'stock' column. 'waste' reason is supported.
            // But since this is prepared_stock, maybe we skip StockMovement to avoid affecting raw stock?
            // Actually, if we just want to LOG it, we can create a record but disable the observer? 
            // Or just rely on the fact that for type='waste' (not increase/decrease enum) it might do nothing?
            // But 'type' enum is limited to ['increase', 'decrease'].
            // So we can't use type='waste'.
            // We use type='decrease', reason='waste'.
            // BUT this will decrement 'stock'. We don't want that for prepared item.
            // So we skip StockMovement for prepared item reset.
            // Just Log.
            \Illuminate\Support\Facades\Log::info("Reset/Waste Prepared Stock for {$product->name}: -{$oldStock}");

            Notification::make()
                ->success()
                ->title('Stok Direset')
                ->body("Sisa {$oldStock} porsi {$product->name} telah dibuang (Waste).")
                ->send();

            $this->dispatch('close-modal', id: "record-production-{$productId}");
            $this->dispatch('stock-updated');

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Reset Stock Error: ' . $e->getMessage());
            Notification::make()->danger()->title('Error')->body($e->getMessage())->send();
        }
    }

    #[On('stock-updated')]
    public function refresh(): void
    {
        // Refresh widget when stock is updated
    }
}

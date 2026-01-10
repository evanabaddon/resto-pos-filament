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

    // Enable lazy loading for better performance
    protected static bool $isLazy = true;

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
            // Using DB Transaction similar to CreateProduction
            \Illuminate\Support\Facades\DB::transaction(function () use ($productId, $quantity) {
                // 1. Create Production Record
                $production = new \App\Models\Production();
                $production->fill([
                    'product_id' => $productId,
                    'user_id' => auth()->id(),
                    'quantity' => $quantity,
                    'notes' => __('messages.production_note_db'),
                ]);
                $production->save();

                $product = $production->product;
                if (!$product)
                    throw new \Exception("Product not found");

                // 2. Increase Prepared Stock (Output)
                // Polymorphic link
                $production->stockMovements()->create([
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'type' => 'increase',
                    'reason' => 'production_output',
                    'notes' => __('messages.production_movement_note_db'),
                ]);

                // 3. Deduct Ingredients (Input)
                $conversionService = app(\App\Services\UnitConversionService::class);
                $product->load(['recipes.ingredient.unit', 'recipes.unit']);

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

                    if ($totalRequired > 0) {

                        // Validation: Check stock first (Optional based on preference)
                        // If checking prepared stock
                        $currentStock = 0;
                        if (in_array($ingredient->type, ['produced', 'bar']) && $ingredient->enable_stock_alert) {
                            $currentStock = $ingredient->prepared_stock ?? 0;
                        } else {
                            $currentStock = $ingredient->stock ?? 0;
                        }

                        if ($currentStock < $totalRequired) {
                            throw new \Exception(__('messages.insufficient_stock_error', [
                                'ingredient' => $ingredient->name,
                                'required' => $totalRequired,
                                'unit' => $ingredient->unit->symbol,
                                'current' => $currentStock
                            ]));
                        }

                        // Create Stock Movement for Ingredient
                        // Polymorphic link to this Production record
                        $production->stockMovements()->create([
                            'product_id' => $ingredient->id,
                            'quantity' => $totalRequired,
                            'type' => 'decrease',
                            'reason' => 'production_ingredient',
                            'notes' => __('messages.ingredient_movement_note_db', ['quantity' => $quantity, 'product' => $product->name]),
                        ]);
                    }
                }
            });

            Notification::make()
                ->success()
                ->title(__('messages.production_recorded_title'))
                ->body(__('messages.production_success_body', ['quantity' => $quantity]))
                ->send();

            $this->dispatch('close-modal', id: "record-production-{$productId}");
            $this->dispatch('stock-updated');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Record Production Error: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());
            Notification::make()
                ->danger()
                ->title(__('messages.production_failed_title'))
                ->body("Error: " . $e->getMessage())
                ->send();
        }
    }

    public function resetStock($productId)
    {
        try {
            $product = Product::findOrFail($productId);
            $oldStock = $product->prepared_stock ?? 0;

            if ($oldStock <= 0) {
                Notification::make()->warning()->title(__('messages.stock_empty_title'))->body(__('messages.stock_empty_body'))->send();
                return;
            }

            // Create StockMovement record for waste/reset
            // We use 'decrease' type with 'waste' reason to log this action
            // But we need to manually update prepared_stock since observer targets 'stock' column
            \Illuminate\Support\Facades\DB::transaction(function () use ($product, $oldStock) {
                // Create movement record for audit trail
                \App\Models\StockMovement::create([
                    'product_id' => $product->id,
                    'quantity' => $oldStock,
                    'type' => 'decrease',
                    'reason' => 'waste',
                    'notes' => __('messages.reset_stock_note_db'),
                ]);

                // Manually update prepared_stock to 0 (observer won't handle this for prepared items)
                $product->update(['prepared_stock' => 0]);
            });

            Notification::make()
                ->success()
                ->title(__('messages.stock_reset_title'))
                ->body(__('messages.stock_reset_body', ['stock' => $oldStock, 'product' => $product->name]))
                ->send();

            $this->dispatch('close-modal', id: "record-production-{$productId}");
            $this->dispatch('close-modal', id: "confirm-reset-{$productId}");
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

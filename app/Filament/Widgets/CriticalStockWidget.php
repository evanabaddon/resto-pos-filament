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
            // Using DB Transaction similar to CreateProduction
            \Illuminate\Support\Facades\DB::transaction(function () use ($productId, $quantity) {
                // 1. Create Production Record
                $production = new \App\Models\Production();
                $production->fill([
                    'product_id' => $productId,
                    'user_id' => auth()->id(),
                    'quantity' => $quantity,
                    'notes' => 'Quick cook via Dashboard',
                ]);
                $production->save();

                $product = $production->product;
                if (!$product) throw new \Exception("Product not found");

                // 2. Increase Prepared Stock (Output)
                // Polymorphic link
                $production->stockMovements()->create([
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'type' => 'increase',
                    'reason' => 'production_output',
                    'notes' => 'Hasil produksi (Quick Cook)',
                ]);

                // 3. Deduct Ingredients (Input)
                $conversionService = app(\App\Services\UnitConversionService::class);
                $product->load(['recipes.ingredient.unit', 'recipes.unit']);

                foreach ($product->recipes as $recipe) {
                    $ingredient = $recipe->ingredient;
                    if (!$ingredient) continue;

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
                            throw new \Exception("Stok bahan '{$ingredient->name}' tidak cukup! Butuh: {$totalRequired} {$ingredient->unit->symbol}, Ada: {$currentStock}");
                        }

                        // Create Stock Movement for Ingredient
                        // Polymorphic link to this Production record
                        $production->stockMovements()->create([
                            'product_id' => $ingredient->id,
                            'quantity' => $totalRequired,
                            'type' => 'decrease',
                            'reason' => 'production_ingredient',
                            'notes' => "Bahan baku untuk produksi {$quantity} {$product->name} (Quick Cook)",
                        ]);
                    }
                }
            });

            Notification::make()
                ->success()
                ->title('Production Recorded')
                ->body("Berhasil masak {$quantity} porsi. History produksi tercatat.")
                ->send();

            $this->dispatch('close-modal', id: "record-production-{$productId}");
            $this->dispatch('stock-updated');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Record Production Error: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());
            Notification::make()
                ->danger()
                ->title('Gagal Masak')
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

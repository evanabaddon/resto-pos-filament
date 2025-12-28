<?php

namespace App\Filament\Resources\Production\Pages;

use App\Filament\Resources\Production\ProductionResource;
use App\Models\Production;
use App\Models\StockMovement;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Services\UnitConversionService;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;

class CreateProduction extends CreateRecord
{
    protected static string $resource = ProductionResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            // 1. Create Production Record
            $production = new Production(); // Use model directly to allow polymorphic linking later
            $production->fill([
                'product_id' => $data['product_id'],
                'user_id' => auth()->id(),
                'quantity' => $data['quantity'],
                'notes' => $data['notes'] ?? null,
            ]);
            $production->save(); // Save first to get ID

            $product = $production->product;
            $quantity = $production->quantity;

            // 2. Increase Prepared Stock (Output)
            // Logic handled in StockMovement observer (via 'production' reason logic if we add it, but our Observer logic depends on Product Type)
            // Observer logic: if type=produced/bar -> increment prepared_stock.

            $production->stockMovements()->create([
                'product_id' => $product->id,
                'quantity' => $quantity,
                'type' => 'increase',
                'reason' => 'production_output',
                'notes' => 'Hasil produksi internal',
            ]);

            // 3. Validate Ingredient Availability FIRST
            $conversionService = app(UnitConversionService::class);
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
                    // Check available stock
                    $currentStock = in_array($ingredient->type, ['produced', 'bar']) && $ingredient->enable_stock_alert
                        ? ($ingredient->prepared_stock ?? 0)
                        : ($ingredient->stock ?? 0);

                    if ($currentStock < $totalRequired) {
                        Notification::make()
                            ->title('Stok Bahan Tidak Cukup')
                            ->body("Stok bahan '{$ingredient->name}' tidak cukup! " .
                                "Dibutuhkan: " . number_format($totalRequired, 2) . " {$ingredient->unit->name}, " .
                                "Tersedia: " . number_format($currentStock, 2) . " {$ingredient->unit->name}")
                            ->danger()
                            ->send();

                        // Halt the form submission without showing exception page
                        throw new Halt();
                    }
                }
            }

            // 4. Deduct Ingredients (Input) - Only if validation passed
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
                    // Create Stock Movement for Ingredient
                    // Polymorphic link to this Production record
                    $production->stockMovements()->create([
                        'product_id' => $ingredient->id,
                        'quantity' => $totalRequired,
                        'type' => 'decrease',
                        'reason' => 'production_ingredient',
                        'notes' => "Bahan baku untuk produksi {$quantity} {$product->name}",
                    ]);
                }
            }

            // Check availability is not forcefully blocking here, 
            // but we could add validation in `beforeCreate` if strictly required.
            // For now, allow negative stock (debt) or let the database handle constraints if any.

            return $production;
        });
    }
}

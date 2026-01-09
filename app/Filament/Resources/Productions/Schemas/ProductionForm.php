<?php

namespace App\Filament\Resources\Productions\Schemas;

use Filament\Schemas\Schema;
use App\Models\Product;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;

class ProductionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('messages.production_input'))
                    ->schema([
                        Select::make('product_id')
                            ->label(__('messages.product_kitchen_bar'))
                            ->relationship('product', 'name', function ($query) {
                                return $query->whereIn('type', ['produced', 'bar'])
                                    ->where('enable_stock_alert', true);
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, Set $set) {
                                if (!$state) {
                                    $set('stock_info', null);
                                    return;
                                }

                                $product = Product::with(['recipes.ingredient.unit', 'recipes.unit'])->find($state);
                                if (!$product)
                                    return;

                                // 1. Current Prepared Stock
                                $info = __('messages.current_prepared_stock') . ": " . ($product->prepared_stock ?? 0) . " " . ($product->unit->name ?? __('messages.portion')) . "\n";

                                // 2. Calculate Max Production based on Ingredients
                                $maxPossible = null;
                                $conversionService = app(\App\Services\UnitConversionService::class);

                                foreach ($product->recipes as $recipe) {
                                    $ingredient = $recipe->ingredient;
                                    if (!$ingredient)
                                        continue;

                                    // Get available stock (raw or prepared)
                                    $currentStock = in_array($ingredient->type, ['produced', 'bar']) && $ingredient->enable_stock_alert
                                        ? ($ingredient->prepared_stock ?? 0)
                                        : ($ingredient->stock ?? 0);

                                    $requiredPerPortion = $conversionService->convert(
                                        $recipe->quantity,
                                        $recipe->unit_id,
                                        $recipe->ingredient->unit_id
                                    );

                                    if ($requiredPerPortion <= 0)
                                        continue;

                                    $maxForThisIngredient = floor($currentStock / $requiredPerPortion);

                                    if (is_null($maxPossible) || $maxForThisIngredient < $maxPossible) {
                                        $maxPossible = $maxForThisIngredient;
                                    }
                                }

                                if (!is_null($maxPossible)) {
                                    $info .= __('messages.max_production_estimate') . ": {$maxPossible} " . __('messages.portion') . " (" . __('messages.limited_by_ingredients') . ")";
                                } else {
                                    $info .= " " . __('messages.max_production_estimate') . ": " . __('messages.unlimited_no_recipe');
                                }

                                $set('stock_info', $info);
                            }),


                        View::make('filament.forms.components.stock-info')
                            ->statePath('stock_info')
                            ->hidden(fn($state) => blank($state))
                            ->columnSpanFull(),

                        TextInput::make('quantity')
                            ->label(__('messages.production_quantity'))
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->required(),

                        Textarea::make('notes')
                            ->label(__('messages.notes'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}

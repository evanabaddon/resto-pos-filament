<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Unit;
use App\Models\Product;
use App\Models\PurchaseItem;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater\TableColumn;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('image')
                    ->label(__('messages.image'))
                    ->directory('products')
                    ->disk('public')
                    ->image()
                    ->imageEditor()
                    ->maxSize(2048)
                    ->columnSpanFull(),

                // 🏷 Nama Produk
                TextInput::make('name')
                    ->label(__('messages.product_name'))
                    ->required(),

                // 📦 Tipe Produk
                Select::make('type')
                    ->label(__('messages.product_type'))
                    ->options([
                        'raw' => __('messages.raw_material'),
                        'produced' => __('messages.kitchen_product'),
                        'bar' => __('messages.bar_product'),
                        'retail' => __('messages.retail_product'),
                    ])
                    ->required()
                    ->reactive(),

                // Kategori
                Select::make('category_id')
                    ->label(__('messages.category'))
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),

                // ⚖ Satuan
                Select::make('unit_id')
                    ->label(__('messages.unit'))
                    ->relationship('unit', 'name')
                    ->searchable()
                    ->required()
                    ->preload(),

                // 📊 Stok
                TextInput::make('stock')
                    ->numeric()
                    ->label(__('messages.current_stock'))
                    ->hidden(fn(callable $get) => in_array($get('type'), ['produced', 'bar']))
                    // ->disabled(fn($record) => $record !== null)
                    ->default(0),

                // ⚠️ Stock Alert Settings
                Toggle::make('enable_stock_alert')
                    ->label(__('messages.min_stock_alert'))
                    ->helperText(__('messages.track_stock_helper'))
                    ->default(false)
                    ->reactive()
                    ->hidden(fn(callable $get) => in_array($get('type'), ['produced', 'bar'])),

                TextInput::make('minimum_stock')
                    ->numeric()
                    ->label(__('messages.min_stock_threshold'))
                    ->helperText(__('messages.min_stock_helper'))
                    ->suffix(fn(callable $get) => Product::find($get('id'))?->unit?->name ?? 'unit')
                    ->visible(fn(callable $get) => $get('enable_stock_alert') === true)
                    ->hidden(fn(callable $get) => in_array($get('type'), ['produced', 'bar'])),

                // 🍳 Prepared Stock (untuk produk produced/bar)
                TextInput::make('prepared_stock')
                    ->numeric()
                    ->label(__('messages.ready_stock'))
                    ->helperText(__('messages.ready_stock_helper'))
                    ->suffix(fn(callable $get) => Product::find($get('id'))?->unit?->name ?? 'porsi')
                    ->default(0)
                    ->visible(fn(callable $get) => in_array($get('type'), ['produced', 'bar'])),

                Toggle::make('enable_stock_alert')
                    ->label(__('messages.ready_stock_alert'))
                    ->helperText(__('messages.ready_stock_alert_helper'))
                    ->default(false)
                    ->reactive()
                    ->visible(fn(callable $get) => in_array($get('type'), ['produced', 'bar'])),

                TextInput::make('minimum_prepared_stock')
                    ->numeric()
                    ->label(__('messages.min_ready_stock'))
                    ->helperText(__('messages.min_ready_stock_helper'))
                    ->suffix(fn(callable $get) => Product::find($get('id'))?->unit?->name ?? 'porsi')
                    ->visible(fn(callable $get) => $get('enable_stock_alert') === true && in_array($get('type'), ['produced', 'bar'])),

                // 🍳 Komposisi bahan untuk produk produced DAN bar
                Repeater::make('recipes')
                    ->label(__('messages.recipe_composition'))
                    ->relationship()
                    ->compact()
                    ->table([
                        TableColumn::make(__('messages.ingredient')),
                        TableColumn::make(__('messages.quantity')),
                        TableColumn::make(__('messages.unit')),
                        TableColumn::make(__('messages.cost_price')),
                    ])
                    ->schema([
                        Select::make('ingredient_id')
                            ->label(__('messages.ingredient'))
                            ->relationship(
                                name: 'ingredient',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn($query) => $query->whereIn('type', ['raw', 'produced'])
                            )
                            ->searchable()
                            ->required()
                            ->reactive()
                            ->preload()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set, callable $get, $component) {
                                self::updateMaterialPrice($set, $get, $component);
                                self::updateHpp($set, $get);
                            }),

                        Select::make('unit_id')
                            ->label(__('messages.unit'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->options(function (callable $get) {
                                $ingredientId = $get('ingredient_id');
                                if (!$ingredientId)
                                    return [];

                                $ingredient = Product::with('unit')->find($ingredientId);
                                if (!$ingredient || !$ingredient->unit)
                                    return [];

                                // Find the absolute root of the unit family
                                $rootUnitId = $ingredient->unit->base_unit_id ?? $ingredient->unit->id;

                                // Fetch all units in this family (Root itself, or children of Root)
                                $units = Unit::query()
                                    ->where('id', $rootUnitId)
                                    ->orWhere('base_unit_id', $rootUnitId)
                                    ->get();

                                return $units->mapWithKeys(function ($unit) {
                                    $label = $unit->name;
                                    if ($unit->conversion_rate && $unit->base_unit_id) {
                                        $label .= " (x{$unit->conversion_rate})";
                                    }
                                    return [$unit->id => $label];
                                });
                            })
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get, $component) {
                                self::updateMaterialPrice($set, $get, $component);
                                self::updateHpp($set, $get);
                            }),

                        TextInput::make('quantity')
                            ->label(__('messages.quantity'))
                            ->numeric()
                            ->required()
                            ->default(1)
                            ->reactive()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set, callable $get, $component) {
                                self::updateMaterialPrice($set, $get, $component);
                                self::updateHpp($set, $get);
                            }),

                        TextInput::make('material_price')
                            ->label(__('messages.cost_price'))
                            ->numeric()
                            ->readOnly()
                            ->prefix('Rp')
                            ->default(0)
                            ->formatStateUsing(function ($state, callable $get, $component) {
                                return self::calculateMaterialPrice($get, $component);
                            })
                    ])
                    ->columns(4) // Diubah dari 3 menjadi 4 karena ada kolom baru
                    ->visible(fn(callable $get) => in_array($get('type'), ['produced', 'bar']))
                    ->afterStateUpdated(fn($state, callable $set, callable $get) => self::updateHpp($set, $get))
                    ->columnSpanFull(),

                // 💰 Biaya tambahan (opsional)
                TextInput::make('additional_cost')
                    ->numeric()
                    ->prefix('Rp')
                    ->label(__('messages.additional_cost'))
                    ->default(0)
                    ->visible(fn($get) => in_array($get('type'), ['produced', 'bar']))
                    ->reactive()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn($state, callable $set, callable $get) => self::updateHpp($set, $get)),

                // 📉 HPP / base price
                TextInput::make('base_price')
                    ->numeric()
                    ->prefix('Rp')
                    ->label(__('messages.base_price'))
                    ->nullable()
                    ->reactive()
                    ->readOnly(fn($get) => in_array($get('type'), ['produced', 'bar']))
                    ->dehydrated(true)
                    ->suffixActions([
                        Action::make('updateFromPurchase')
                            ->icon('heroicon-o-arrow-path')
                            ->tooltip(__('messages.update_hpp_tooltip'))
                            ->action(function ($livewire, $get, $set) {
                                $productId = $get('id');

                                if ($productId) {
                                    $product = Product::find($productId);

                                    $lastPurchaseItem = PurchaseItem::where('product_id', $productId)
                                        ->whereHas('purchase', function ($query) {
                                            $query->where('status', 'received');
                                        })
                                        ->latest()
                                        ->first();

                                    if ($lastPurchaseItem) {
                                        $product->update([
                                            'base_price' => $lastPurchaseItem->price
                                        ]);
                                        $product->refresh();

                                        $set('base_price', $product->base_price);
                                        $set('sell_price', $product->base_price);

                                        Notification::make()
                                            ->title(__('messages.hpp_updated_title'))
                                            ->body(__('messages.hpp_updated_body', ['price' => number_format($product->base_price, 0, ',', '.')]))
                                            ->success()
                                            ->send();
                                    } else {
                                        Notification::make()
                                            ->title(__('messages.data_not_found_title'))
                                            ->body(__('messages.purchase_not_found_body'))
                                            ->warning()
                                            ->send();
                                    }
                                }
                            })
                            ->visible(fn($get) => !empty($get('id')) && in_array($get('type'), ['raw', 'retail'])),

                        Action::make('recalculateHpp')
                            ->icon('heroicon-o-calculator')
                            ->tooltip(__('messages.recalc_hpp_tooltip'))
                            ->action(function ($livewire, $get, $set) {
                                self::updateHpp($set, $get);

                                Notification::make()
                                    ->title(__('messages.hpp_recalculated_title'))
                                    ->body(__('messages.hpp_recalculated_body'))
                                    ->success()
                                    ->send();
                            })
                            ->visible(fn($get) => in_array($get('type'), ['produced', 'bar']))
                    ])
                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                        if (blank($get('sell_price')) || $get('sell_price') == 0) {
                            $set('sell_price', $state);
                        }
                    }),

                // 💵 Harga jual
                TextInput::make('sell_price')
                    ->numeric()
                    ->prefix('Rp')
                    ->label(__('messages.selling_price'))
                    ->required()
                    ->reactive()
                    ->live(onBlur: true)
                    ->minValue(function ($get) {
                        $basePrice = $get('base_price') ?? 0;
                        return $basePrice;
                    })
                    ->hidden(fn() => auth()->user()->role === 'inventory') // Hide for inventory
                    ->helperText(function ($get) {
                        $basePrice = $get('base_price') ?? 0;
                        return __('messages.selling_price_must_be_higher', ['price' => number_format($basePrice, 0, ',', '.')]);
                    })
                    ->afterStateHydrated(function ($set, $get, $state) {
                        if (blank($state)) {
                            $basePrice = $get('base_price') ?? 0;
                            $defaultPrice = max($basePrice * 1.3, $basePrice + 1);
                            $set('sell_price', $defaultPrice);
                        }
                    }),

                // 💰 Keuntungan (hanya tampilan, tidak disimpan)
                TextInput::make('profit_display')
                    ->label(__('messages.profit'))
                    ->prefix('Rp')
                    ->readOnly()
                    ->reactive()
                    ->dehydrated(false) // Pastikan tidak disimpan ke database
                    ->hidden(fn() => auth()->user()->role === 'inventory') // Hide for inventory
                    ->formatStateUsing(function ($state, callable $get) {
                        $basePrice = $get('base_price') ?? 0;
                        $sellPrice = $get('sell_price') ?? 0;
                        $profit = $sellPrice - $basePrice;

                        return number_format($profit, 0, ',', '.');
                    })
                    ->afterStateHydrated(function ($set, $get) {
                        // Update initial state
                        $basePrice = $get('base_price') ?? 0;
                        $sellPrice = $get('sell_price') ?? 0;
                        $profit = $sellPrice - $basePrice;
                        $set('profit_display', number_format($profit, 0, ',', '.'));
                    })
                    ->helperText(function ($get) {
                        $basePrice = $get('base_price') ?? 0;
                        $sellPrice = $get('sell_price') ?? 0;
                        $profit = $sellPrice - $basePrice;

                        if ($basePrice > 0) {
                            $margin = ($profit / $basePrice) * 100;
                            return __('messages.margin_label', ['percent' => number_format($margin, 1)]) .
                                ($margin < 0 ? " ⚠️ " . __('messages.margin_loss') : ($margin < 10 ? " ⚠️ " . __('messages.margin_low') : " ✅"));
                        }

                        return "Margin: 0%";
                    })
                    ->disabled(),

                // 🔘 Bisa dijual di POS
                Toggle::make('is_sellable')
                    ->inline()
                    ->label(__('messages.is_sellable'))
                    ->default(fn(callable $get) => in_array($get('type'), ['produced', 'bar', 'retail'])),

                // ⭐ Menu Unggulan (Upselling)
                Toggle::make('is_favorite')
                    ->inline()
                    ->label(__('messages.is_favorite'))
                    ->helperText(__('messages.favorite_helper'))
                    ->default(false),
            ]);
    }

    /**
     * Tentukan jenis bahan yang boleh digunakan berdasarkan tipe produk
     */
    protected static function getAllowedIngredientTypes(callable $get): array
    {
        $productType = $get('type');

        return match ($productType) {
            'produced' => ['raw', 'produced'], // Kitchen bisa pakai bahan baku DAN produk produced (e.g., Nasi Putih)
            'bar' => ['raw', 'produced'], // Bar bisa pakai bahan baku dan produk kitchen
            default => ['raw']
        };
    }

    /**
     * Hitung harga bahan berdasarkan quantity dan harga beli
     */
    protected static function calculateMaterialPrice(callable $get, $component): float
    {
        $ingredientId = $get('ingredient_id');
        $quantity = $get('quantity') ?? 0;
        $unitId = $get('unit_id');

        if (!$ingredientId || $quantity <= 0 || !$unitId) {
            return 0;
        }

        $ingredient = Product::find($ingredientId);
        if (!$ingredient) {
            return 0;
        }

        // Use robust service
        try {
            $convertedQuantity = app(\App\Services\UnitConversionService::class)
                ->convert($quantity, $unitId, $ingredient->unit_id);
        } catch (\Exception $e) {
            return 0;
        }

        $materialPrice = ($ingredient->base_price ?? 0) * $convertedQuantity;

        return $materialPrice;
    }

    /**
     * Update harga bahan ketika ada perubahan
     */
    protected static function updateMaterialPrice(callable $set, callable $get, $component): void
    {
        $materialPrice = self::calculateMaterialPrice($get, $component);
        $set('material_price', $materialPrice);
    }

    /**
     * Hitung ulang HPP untuk produk produced dan bar
     */
    protected static function updateHpp(callable $set, callable $get): void
    {
        $productType = $get('type');

        if (!in_array($productType, ['produced', 'bar'])) {
            return;
        }

        $recipes = $get('recipes') ?? [];
        $totalHpp = 0;
        $converter = app(\App\Services\UnitConversionService::class);

        foreach ($recipes as $recipe) {
            $ingredientId = $recipe['ingredient_id'] ?? null;
            $quantity = $recipe['quantity'] ?? 0;
            $unitId = $recipe['unit_id'] ?? null;

            if (!$ingredientId || $quantity <= 0) {
                continue;
            }

            $ingredient = Product::find($ingredientId);
            if (!$ingredient) {
                continue;
            }

            try {
                $convertedQuantity = $converter->convert(
                    $quantity,
                    $unitId,
                    $ingredient->unit_id
                );

                $totalHpp += ($ingredient->base_price ?? 0) * $convertedQuantity;
            } catch (\Exception $e) {
            }
        }

        $additionalCost = $get('additional_cost') ?? 0;
        $finalHpp = $totalHpp + $additionalCost;

        $set('base_price', $finalHpp);

        // Auto-update sell_price jika kosong atau perlu adjustment
        $currentSellPrice = $get('sell_price') ?? 0;
        if ($currentSellPrice <= $finalHpp || $currentSellPrice == 0) {
            $recommendedPrice = $finalHpp * 1.3; // 30% markup
            $set('sell_price', $recommendedPrice);
        }
    }
}

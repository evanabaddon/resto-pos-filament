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
                    ->label('Gambar Produk')
                    ->directory('products') // folder di storage/app/public/products
                    ->disk('public')
                    ->image()
                    ->imageEditor() // aktifkan crop/resize editor bawaan Filament
                    ->maxSize(2048)
                    ->columnSpanFull(),

                // 🏷 Nama Produk
                TextInput::make('name')
                    ->label('Nama Produk')
                    ->required(),

                // 📦 Tipe Produk
                Select::make('type')
                    ->label('Tipe Produk')
                    ->options([
                        'raw' => 'Bahan Baku',
                        'produced' => 'Produk Kitchen / Produksi (dengan resep)',
                        'retail' => 'Produk Jadi Siap Jual (tanpa resep)',
                    ])
                    ->required()
                    ->reactive(),
                
                // Kategori
                Select::make('category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),

                // ⚖ Satuan
                Select::make('unit_id')
                    ->label('Satuan')
                    ->relationship('unit', 'name')
                    ->searchable()
                    ->required()
                    ->preload(),

                // 📊 Stok
                TextInput::make('stock')
                    ->numeric()
                    ->label('Stok Saat Ini')
                    ->hidden(fn(callable $get) => $get('type') === 'produced')
                    ->disabled(fn($record) => $record !== null)
                    ->default(0),

                // 🍳 Komposisi bahan hanya untuk produk produced
                Repeater::make('recipes')
                    ->label('Komposisi Bahan')
                    ->relationship()
                    ->compact()
                    ->table([
                        TableColumn::make('Bahan'),
                        TableColumn::make('Jumlah'),
                        TableColumn::make('Satuan'),
                    ])
                    ->schema([
                        Select::make('ingredient_id')
                            ->label('Bahan')
                             ->relationship(
                                name: 'ingredient',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn ($query) => $query->where('type', 'raw')
                            )
                            ->searchable()
                            ->required()
                            ->reactive()
                            ->preload()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn($state, callable $set, callable $get) => self::updateHpp($set, $get)),

                        TextInput::make('quantity')
                            ->label('Jumlah')
                            ->numeric()
                            ->required()
                            ->default(1)
                            ->reactive()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn($state, callable $set, callable $get) => self::updateHpp($set, $get)),

                        Select::make('unit_id')
                            ->label('Satuan')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->options(function (callable $get) {
                                $ingredientId = $get('ingredient_id');
                                if (! $ingredientId) return [];

                                $ingredient = Product::with('unit.baseUnit')->find($ingredientId);
                                if (! $ingredient || ! $ingredient->unit) return [];

                                // Ambil unit dasar dan unit terkait
                                $baseUnitId = $ingredient->unit->base_unit_id ?: $ingredient->unit->id;

                                $units = Unit::query()
                                    ->where('id', $baseUnitId)
                                    ->orWhere('base_unit_id', $baseUnitId)
                                    ->get();

                                return $units->mapWithKeys(function ($unit) {
                                    $label = $unit->name;
                                    if ($unit->conversion_rate && $unit->base_unit_id) {
                                        $label;
                                        // $label .= " (1 base = {$unit->conversion_rate} {$unit->symbol})";
                                    }
                                    return [$unit->id => $label];
                                });
                            })
                            ->reactive(),

                    ])
                    ->columns(3)
                    ->visible(fn(callable $get) => $get('type') === 'produced')
                    ->afterStateUpdated(fn($state, callable $set, callable $get) => self::updateHpp($set, $get))
                    ->columnSpanFull(),

                // 💰 Biaya tambahan (opsional)
                TextInput::make('additional_cost')
                    ->numeric()
                    ->prefix('Rp')
                    ->label('Biaya Tambahan Produksi (Opsional)')
                    ->default(0)
                    ->visible(fn($get) => in_array($get('type'), ['produced', 'retail']))
                    ->reactive()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn($state, callable $set, callable $get) => self::updateHpp($set, $get)),

                // 📉 HPP / base price
                TextInput::make('base_price')
                    ->numeric()
                    ->prefix('Rp')
                    ->label('Harga Pokok (HPP)')
                    ->nullable()
                    ->reactive()
                    ->readOnly(fn($get) => $get('type') === 'produced') // hanya readonly untuk produk produced
                    ->dehydrated(true)
                    ->suffixActions([
                        Action::make('updateFromPurchase')
                            ->icon('heroicon-o-arrow-path')
                            ->tooltip('Update HPP dari pembelian terakhir')
                            ->action(function ($livewire, $get, $set) {
                                $productId = $get('id');
                                
                                if ($productId) {
                                    $product = Product::find($productId);
                                    
                                    // Hapus debug dd() dan ganti dengan logic yang benar
                                    $lastPurchaseItem = PurchaseItem::where('product_id', $productId)
                                        ->whereHas('purchase', function ($query) {
                                            $query->where('status', 'received'); // Ganti menjadi 'received'
                                        })
                                        ->latest()
                                        ->first();

                                    if ($lastPurchaseItem) {
                                        $product->update([
                                            'base_price' => $lastPurchaseItem->price // Ganti menjadi 'price'
                                        ]);
                                        $product->refresh();
                                        
                                        $set('base_price', $product->base_price);
                                        $set('sell_price', $product->base_price);
                                        
                                        Notification::make()
                                            ->title('HPP Updated')
                                            ->body('HPP berhasil diupdate: Rp ' . number_format($product->base_price, 0, ',', '.'))
                                            ->success()
                                            ->send();
                                    } else {
                                        Notification::make()
                                            ->title('Data tidak ditemukan')
                                            ->body('Tidak ada pembelian dengan status received untuk produk ini.')
                                            ->warning()
                                            ->send();
                                    }
                                }
                            })
                            ->visible(fn($get) => !empty($get('id'))),

                        Action::make('recalculateHpp')
                            ->icon('heroicon-o-calculator')
                            ->tooltip('Hitung ulang HPP dari resep')
                            ->action(function ($livewire, $get, $set) {
                                self::updateHpp($set, $get);
                                
                                Notification::make()
                                    ->title('HPP Dihitung Ulang')
                                    ->body('HPP berhasil dihitung dari komposisi resep')
                                    ->success()
                                    ->send();
                            })
                            ->visible(fn($get) => $get('type') === 'produced')
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
                    ->label('Harga Jual')
                    ->required()
                    ->reactive()
                    ->live(onBlur: true)
                    ->minValue(function ($get) {
                        $basePrice = $get('base_price') ?? 0;
                        return $basePrice; // Minimal base_price
                    })
                    ->helperText(function ($get) {
                        $basePrice = $get('base_price') ?? 0;
                        return "Harga jual harus lebih besar dari HPP: Rp " . number_format($basePrice, 0, ',', '.');
                    })
                    ->afterStateHydrated(function ($set, $get, $state) {
                        if (blank($state)) {
                            $basePrice = $get('base_price') ?? 0;
                            // Auto-set ke base_price * 1.3 (30% markup) atau minimal base_price
                            $defaultPrice = max($basePrice * 1.3, $basePrice + 1);
                            $set('sell_price', $defaultPrice);
                        }
                    }),

                // 🔘 Bisa dijual di POS
                Toggle::make('is_sellable')
                    ->label('Bisa Dijual di POS?')
                    ->default(fn(callable $get) => in_array($get('type'), ['produced', 'retail'])),

                // 🔧 Manual override untuk produk produced
                Toggle::make('is_manual')
                    ->label('Manual Override HPP')
                    ->visible(fn($get) => $get('type') === 'produced')
                    ->default(false),
            ]);
    }

    /**
     * Hitung ulang HPP hanya untuk produk produced
     */
    // protected static function updateHpp(callable $set, callable $get): void
    // {
    //     if ($get('type') !== 'produced') {
    //         return; // raw dan retail HPP diisi manual
    //     }

    //     $recipes = $get('recipes') ?? [];
    //     $totalHpp = collect($recipes)->sum(function ($item) {
    //         $ingredient = Product::find($item['ingredient_id'] ?? null);
    //         $recipeUnit = Unit::find($item['unit_id'] ?? null);

    //         if (!$ingredient || !$ingredient->unit) {
    //             return 0;
    //         }

    //         $ingredientConv = $ingredient->unit->conversion_rate ?? 1;
    //         $recipeConv = $recipeUnit->conversion_rate ?? 1;

    //         $qtyInIngredientUnit = ($item['quantity'] ?? 0) * ($ingredientConv / $recipeConv);
    //         $hargaPerBaseUnit = $ingredient->base_price ?? 0;

    //         return $hargaPerBaseUnit * $qtyInIngredientUnit;
    //     });

    //     $set('base_price', $totalHpp + ($get('additional_cost') ?? 0));
    // }

    /**
     * Hitung ulang HPP untuk produk produced
     */
    protected static function updateHpp(callable $set, callable $get): void
    {
        if ($get('type') !== 'produced') {
            return;
        }

        $recipes = $get('recipes') ?? [];
        $totalHpp = 0;

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

            // Konversi quantity ke unit dasar bahan baku
            $convertedQuantity = self::convertQuantityToBaseUnit(
                $quantity, 
                $unitId, 
                $ingredient->unit_id
            );

            $totalHpp += ($ingredient->base_price ?? 0) * $convertedQuantity;
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

    /**
     * Konversi quantity ke unit dasar bahan baku - FIXED VERSION
     */
    protected static function convertQuantityToBaseUnit($quantity, $fromUnitId, $ingredientBaseUnitId): float
    {
        \Log::info("=== CONVERSION DEBUG ===");
        \Log::info("Quantity: {$quantity}, From Unit: {$fromUnitId}, To Unit: {$ingredientBaseUnitId}");

        // Jika unit sama, tidak perlu konversi
        if ($fromUnitId == $ingredientBaseUnitId) {
            \Log::info("Same unit, no conversion needed");
            return $quantity;
        }
        
        $fromUnit = Unit::find($fromUnitId);
        $ingredientBaseUnit = Unit::find($ingredientBaseUnitId);
        
        if (!$fromUnit || !$ingredientBaseUnit) {
            \Log::warning("Unit not found");
            return $quantity;
        }
        
        \Log::info("From Unit: {$fromUnit->name} (Base: {$fromUnit->base_unit_id}), To Unit: {$ingredientBaseUnit->name}");
        
        // Cari base unit dari unit resep
        $fromBaseUnitId = $fromUnit->base_unit_id ?: $fromUnit->id;
        
        // Jika base unit berbeda, tidak bisa konversi
        if ($fromBaseUnitId != $ingredientBaseUnitId) {
            \Log::warning("Different base units: {$fromBaseUnitId} vs {$ingredientBaseUnitId}");
            return $quantity;
        }
        
        // Konversi: quantity dalam unit resep → quantity dalam base unit
        if ($fromUnit->base_unit_id) {
            // Ini unit turunan: conversion_rate = "1 base = x unit ini"
            // Jadi: quantity (base) = quantity (unit ini) / conversion_rate
            $converted = $quantity / $fromUnit->conversion_rate;
            \Log::info("Conversion: {$quantity} {$fromUnit->name} / {$fromUnit->conversion_rate} = {$converted} {$ingredientBaseUnit->name}");
            return $converted;
        } else {
            // Ini sudah base unit
            \Log::info("Already base unit, no conversion");
            return $quantity;
        }
    }
}

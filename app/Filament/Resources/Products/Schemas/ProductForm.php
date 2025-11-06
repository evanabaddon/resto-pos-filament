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
                    ->directory('products')
                    ->disk('public')
                    ->image()
                    ->imageEditor()
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
                        'produced' => 'Produk Kitchen (dengan resep)',
                        'bar' => 'Produk Bar (dengan resep)',
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
                    ->hidden(fn(callable $get) => in_array($get('type'), ['produced', 'bar']))
                    // ->disabled(fn($record) => $record !== null)
                    ->default(0),

                // 🍳 Komposisi bahan untuk produk produced DAN bar
                Repeater::make('recipes')
                    ->label('Komposisi Bahan')
                    ->relationship()
                    ->compact()
                    ->table([
                        TableColumn::make('Bahan'),
                        TableColumn::make('Jumlah'),
                        TableColumn::make('Satuan'),
                        TableColumn::make('Harga Bahan'),
                    ])
                    ->schema([
                        Select::make('ingredient_id')
                            ->label('Bahan')
                            ->relationship(
                                name: 'ingredient',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn ($query, callable $get) => $query->whereIn('type', self::getAllowedIngredientTypes($get))
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

                        TextInput::make('quantity')
                            ->label('Jumlah')
                            ->numeric()
                            ->required()
                            ->default(1)
                            ->reactive()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set, callable $get, $component) {
                                self::updateMaterialPrice($set, $get, $component);
                                self::updateHpp($set, $get);
                            }),

                        Select::make('unit_id')
                            ->label('Satuan')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->options(function (callable $get) {
                                $ingredientId = $get('ingredient_id');
                                if (!$ingredientId) return [];

                                $ingredient = Product::with('unit.baseUnit')->find($ingredientId);
                                if (!$ingredient || !$ingredient->unit) return [];

                                $baseUnitId = $ingredient->unit->base_unit_id ?: $ingredient->unit->id;

                                $units = Unit::query()
                                    ->where('id', $baseUnitId)
                                    ->orWhere('base_unit_id', $baseUnitId)
                                    ->get();

                                return $units->mapWithKeys(function ($unit) {
                                    $label = $unit->name;
                                    if ($unit->conversion_rate && $unit->base_unit_id) {
                                        $label;
                                    }
                                    return [$unit->id => $label];
                                });
                            })
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get, $component) {
                                self::updateMaterialPrice($set, $get, $component);
                                self::updateHpp($set, $get);
                            }),

                        TextInput::make('material_price')
                            ->label('Harga Bahan')
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
                    ->label('Biaya Tambahan Produksi (Opsional)')
                    ->default(0)
                    ->visible(fn($get) => in_array($get('type'), ['produced', 'bar']))
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
                    ->readOnly(fn($get) => in_array($get('type'), ['produced', 'bar']))
                    ->dehydrated(true)
                    ->suffixActions([
                        Action::make('updateFromPurchase')
                            ->icon('heroicon-o-arrow-path')
                            ->tooltip('Update HPP dari pembelian terakhir')
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
                            ->visible(fn($get) => !empty($get('id')) && in_array($get('type'), ['raw', 'retail'])),

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
                    ->label('Harga Jual')
                    ->required()
                    ->reactive()
                    ->live(onBlur: true)
                    ->minValue(function ($get) {
                        $basePrice = $get('base_price') ?? 0;
                        return $basePrice;
                    })
                    ->helperText(function ($get) {
                        $basePrice = $get('base_price') ?? 0;
                        return "Harga jual harus lebih besar dari HPP: Rp " . number_format($basePrice, 0, ',', '.');
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
                    ->label('Keuntungan')
                    ->prefix('Rp')
                    ->readOnly()
                    ->reactive()
                    ->dehydrated(false) // Pastikan tidak disimpan ke database
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
                            return "Margin: " . number_format($margin, 1) . "%" . 
                                ($margin < 0 ? " ⚠️ Rugi" : ($margin < 10 ? " ⚠️ Margin rendah" : " ✅"));
                        }
                        
                        return "Margin: 0%";
                    })
                    ->disabled(),
                
                // 🔘 Bisa dijual di POS
                Toggle::make('is_sellable')
                    ->inline()
                    ->label('Bisa Dijual di POS?')
                    ->default(fn(callable $get) => in_array($get('type'), ['produced', 'bar', 'retail'])),
            ]);
    }

    /**
     * Tentukan jenis bahan yang boleh digunakan berdasarkan tipe produk
     */
    protected static function getAllowedIngredientTypes(callable $get): array
    {
        $productType = $get('type');
        
        return match($productType) {
            'produced' => ['raw'], // Kitchen hanya bisa pakai bahan baku
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

        // Konversi quantity ke unit dasar bahan baku
        $convertedQuantity = self::convertQuantityToBaseUnit(
            $quantity, 
            $unitId, 
            $ingredient->unit_id
        );

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
     * Konversi quantity ke unit dasar bahan baku
     */
    protected static function convertQuantityToBaseUnit($quantity, $fromUnitId, $ingredientBaseUnitId): float
    {
        if ($fromUnitId == $ingredientBaseUnitId) {
            return $quantity;
        }
        
        $fromUnit = Unit::find($fromUnitId);
        $ingredientBaseUnit = Unit::find($ingredientBaseUnitId);
        
        if (!$fromUnit || !$ingredientBaseUnit) {
            return $quantity;
        }
        
        $fromBaseUnitId = $fromUnit->base_unit_id ?: $fromUnit->id;
        
        if ($fromBaseUnitId != $ingredientBaseUnitId) {
            return $quantity;
        }
        
        if ($fromUnit->base_unit_id) {
            return $quantity / $fromUnit->conversion_rate;
        } else {
            return $quantity;
        }
    }
}
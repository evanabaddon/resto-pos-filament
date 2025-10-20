<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Unit;
use App\Models\Product;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
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
                    ->nullable()
                    ->reactive()
                    ->live(onBlur: true)
                    ->afterStateHydrated(function ($set, $get, $state) {
                        if (blank($state)) {
                            $set('sell_price', $get('base_price'));
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
    protected static function updateHpp(callable $set, callable $get): void
    {
        if ($get('type') !== 'produced') {
            return; // raw dan retail HPP diisi manual
        }

        $recipes = $get('recipes') ?? [];
        $totalHpp = collect($recipes)->sum(function ($item) {
            $ingredient = Product::find($item['ingredient_id'] ?? null);
            $recipeUnit = Unit::find($item['unit_id'] ?? null);

            if (!$ingredient || !$ingredient->unit) {
                return 0;
            }

            $ingredientConv = $ingredient->unit->conversion_rate ?? 1;
            $recipeConv = $recipeUnit->conversion_rate ?? 1;

            $qtyInIngredientUnit = ($item['quantity'] ?? 0) * ($ingredientConv / $recipeConv);
            $hargaPerBaseUnit = $ingredient->base_price ?? 0;

            return $hargaPerBaseUnit * $qtyInIngredientUnit;
        });

        $set('base_price', $totalHpp + ($get('additional_cost') ?? 0));
    }
}

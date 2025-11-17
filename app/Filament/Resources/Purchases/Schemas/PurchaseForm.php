<?php

namespace App\Filament\Resources\Purchases\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Repeater\TableColumn;

class PurchaseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('invoice_number')
                    ->required()
                    ->default(fn() => 'INV-' . now()->format('ymdHis'))
                    ->disabled(fn($record) => filled($record)),

                DatePicker::make('date')
                    ->required()
                    ->default(now()),

                TextInput::make('supplier_name')
                    ->label('Supplier'),

                Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'received' => 'Diterima',
                    ])
                    ->default('draft')
                    ->required(),

                Repeater::make('items')
                    ->relationship()
                    ->table([
                        TableColumn::make('Nama Produk'),
                        TableColumn::make('Unit'),
                        TableColumn::make('QTY'),
                        TableColumn::make('Harga'),
                        TableColumn::make('Subtotal'),
                    ])
                    ->schema([
                        Select::make('product_id')
                            ->relationship(
                                name: 'product',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query) => $query->whereIn('type', ['raw', 'retail'])
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live() // Tambahkan live
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                // Reset unit dan price ketika produk berubah
                                $set('unit_id', null);
                                $set('price', null);
                                
                                if ($state) {
                                    $product = \App\Models\Product::find($state);
                                    if ($product) {
                                        // Set unit_id otomatis dari produk
                                        if ($product->unit_id) {
                                            $set('unit_id', $product->unit_id);
                                        }
                                        // Set price otomatis dari base_price produk
                                        if ($product->base_price) {
                                            $set('price', $product->base_price);
                                        }
                                    }
                                }
                                
                                // Hitung ulang subtotal
                                $set('subtotal', ($get('price') ?? 0) * ($get('quantity') ?? 0));
                            }),

                        Select::make('unit_id')
                            ->label('Unit')
                            ->options(function (callable $get, $state) {
                                // Dapatkan product_id dari state repeater
                                $productId = $get('product_id');
                                
                                if (!$productId) {
                                    // Jika belum ada produk yang dipilih, tampilkan semua unit
                                    return \App\Models\Unit::all()->pluck('name', 'id');
                                }
                                
                                // Ambil produk dan unit yang terkait
                                $product = \App\Models\Product::find($productId);
                                if ($product && $product->unit_id) {
                                    // Tampilkan hanya unit yang terkait dengan produk
                                    return \App\Models\Unit::where('id', $product->unit_id)
                                        ->pluck('name', 'id');
                                }
                                
                                return \App\Models\Unit::all()->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live(),

                        TextInput::make('quantity')
                            ->numeric()
                            ->required()
                            ->default(1)
                            ->reactive()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $set('subtotal', ($get('price') ?? 0) * ($state ?? 0));
                            }),

                        TextInput::make('price')
                            ->numeric()
                            ->required()
                            ->reactive()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $set('subtotal', ($get('quantity') ?? 0) * ($state ?? 0));
                            }),

                        TextInput::make('subtotal')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(),
                    ])
                    ->columns(5)
                    ->columnSpanFull()
                    ->reactive()
                    ->afterStateUpdated(function (callable $set, $state) {
                        // hitung total setiap kali items berubah
                        $total = collect($state)
                            ->sum(fn($item) => ($item['quantity'] ?? 0) * ($item['price'] ?? 0));
                        $set('total', $total);
                    }),

                TextInput::make('total')
                    ->numeric()
                    ->disabled()
                    ->dehydrated()
                    ->default(0)
                    ->reactive()
                    ->columnSpanFull(),
            ]);
    }
}
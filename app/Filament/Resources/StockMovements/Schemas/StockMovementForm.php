<?php

namespace App\Filament\Resources\StockMovements\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

class StockMovementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')
                    ->relationship('product', 'name', fn($query) => $query->whereIn('type', ['raw', 'retail']))
                    ->label('Produk')
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state) {
                            $product = \App\Models\Product::with('unit')->find($state);
                            $set('_product_unit', $product?->unit?->symbol ?? '');
                        }
                    })
                    ->required(),

                Select::make('type')
                    ->label('Jenis')
                    ->options([
                        'increase' => 'Penambahan (+)',
                        'decrease' => 'Pengurangan (-)',
                    ])
                    ->required(),

                TextInput::make('quantity')
                    ->numeric()
                    ->minValue(1)
                    ->label('Jumlah')
                    ->suffix(fn($get) => $get('_product_unit') ?: '')
                    ->required(),

                Select::make('reason')
                    ->label('Alasan')
                    ->options([
                        'opname' => 'Stock Opname (Adjustment)',
                        'purchase' => 'Pembelian Baru',
                        'damage' => 'Barang Rusak/Expired',
                        'gift' => 'Hadiah/Bonus',
                        'other' => 'Lain-lain',
                    ])
                    ->required(),

                Textarea::make('notes')
                    ->label('Catatan')
                    ->rows(2),
            ]);
    }
}

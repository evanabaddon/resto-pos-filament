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
                    ->relationship('product', 'name')
                    ->label('Produk')
                    ->searchable()
                    ->preload()
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

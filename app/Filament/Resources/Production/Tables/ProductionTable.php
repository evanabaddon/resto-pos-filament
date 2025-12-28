<?php

namespace App\Filament\Resources\Production\Tables;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class ProductionTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                TextColumn::make('product.name')
                    ->label('Produk')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('quantity')
                    ->label('Jumlah')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label('Oleh'),

                TextColumn::make('notes')
                    ->label('Catatan')
                    ->limit(30),
            ])
            ->defaultSort('created_at', 'desc');
    }
}

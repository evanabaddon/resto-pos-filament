<?php

namespace App\Filament\Resources\Products\Tables;

use Dom\Text;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->label('Gambar')
                    ->default('https://placehold.co/50')
                    ->circular(true)
                    ->width(50),
                    
                TextColumn::make('name')
                    ->label('Nama'),

                TextColumn::make('category.name')
                    ->label('Kategori'),

                TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->colors([
                        'info' => 'raw',
                        'success' => 'finished', 
                        'warning' => 'produced',
                    ])
                    ->formatStateUsing(function ($state) {
                        return match($state) {
                            'raw' => 'Bahan Baku',
                            'retail' => 'Produk Jadi',
                            'produced' => 'Produk Kitchen',
                            default => $state,
                        };
                    }),

                TextColumn::make('stock_display')
                    ->label('Stok')
                    ->getStateUsing(function ($record) {
                        $stock = number_format($record->stock ?? 0, 2);

                        $unit = $record->unit;
                        if (! $unit) {
                            return "{$stock}";
                        }

                        $unitSymbol = $unit->symbol ?: $unit->name;

                        // Jika punya base unit dan conversion rate
                        if ($unit->baseUnit && $unit->conversion_rate > 0) {
                            $baseUnitSymbol = $unit->baseUnit->symbol ?: $unit->baseUnit->name;
                            $rate = rtrim(rtrim(number_format($unit->conversion_rate, 4, '.', ''), '0'), '.');

                            return <<<HTML
                                {$stock} {$unitSymbol}
                                <br><small class="text-gray-500">(1 {$baseUnitSymbol} = {$rate} {$unitSymbol})</small>
                            HTML;
                        }

                        // Jika tidak ada konversi
                        return "{$stock} {$unitSymbol}";
                    })
                    ->html()
                    ->sortable(),

                TextColumn::make('base_price')
                    ->label('HPP')
                    ->money('IDR'),

                TextColumn::make('sell_price')
                    ->label('Harga Jual')
                    ->money('IDR'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

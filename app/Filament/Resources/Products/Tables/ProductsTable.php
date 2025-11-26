<?php

namespace App\Filament\Resources\Products\Tables;

use Dom\Text;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Schemas\Components\Utilities\Set;
use AlperenErsoy\FilamentExport\Actions\FilamentExportBulkAction;


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
                    ->width(50)
                    ->disk('public'),
                    
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),

                TextColumn::make('category.name')
                    ->label('Kategori'),

                TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->colors([
                        'info' => 'raw',
                        'success' => 'retail', 
                        'warning' => 'produced',
                        'danger' => 'bar',
                    ])
                    ->formatStateUsing(function ($state) {
                        return match($state) {
                            'raw' => 'Bahan Baku',
                            'retail' => 'Produk Jadi',
                            'produced' => 'Produk Kitchen',
                            'bar' => 'Produk Bar',
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
                    ->sortable(query: fn ($query, $direction) => $query->orderBy('stock', $direction)),


                TextColumn::make('base_price')
                    ->label('HPP')
                    ->money('IDR'),

                TextColumn::make('sell_price')
                    ->label('Harga Jual')
                    ->money('IDR'),

                TextColumn::make('profit')
                    ->label('Keuntungan')
                    ->money('IDR')
                    ->getStateUsing(function ($record) {
                        $basePrice = $record->base_price ?? 0;
                        $sellPrice = $record->sell_price ?? 0;
                        
                        return $sellPrice - $basePrice;
                    })
                    ->color(function ($record) {
                        $basePrice = $record->base_price ?? 0;
                        $sellPrice = $record->sell_price ?? 0;
                        $profit = $sellPrice - $basePrice;
                        
                        return $profit >= 0 ? 'success' : 'danger';
                    })
                    ->description(function ($record) {
                        $basePrice = $record->base_price ?? 0;
                        $sellPrice = $record->sell_price ?? 0;
                        
                        if ($basePrice > 0) {
                            $profit = $sellPrice - $basePrice;
                            $margin = ($profit / $basePrice) * 100;
                            return number_format($margin, 1) . '%';
                        }
                        
                        return '0%';
                    })
                    ->sortable(query: fn ($query, $direction) => 
                        $query->orderByRaw('(sell_price - base_price) ' . $direction)
                    ),
            ])
            ->defaultSort('name')
            ->filters([
                // 🔹 Filter kategori (relasi)
                SelectFilter::make('category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name')
                    ->preload()
                    ->searchable(),

                // 🔹 Filter stok rendah
                TernaryFilter::make('low_stock')
                    ->label('Stok Rendah')
                    ->placeholder('Semua')
                    ->trueLabel('Stok < 10')
                    ->falseLabel('Stok ≥ 10')
                    ->queries(
                        true: fn ($query) => $query->where('stock', '<', 10),
                        false: fn ($query) => $query->where('stock', '>=', 10),
                        blank: fn ($query) => $query
                    ),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                FilamentExportBulkAction::make('export')
                    ->label('Export Selected')
                    ->fileName('Daftar Produk')
                    ->defaultFormat('xlsx'),
                ]),
            ]);
    }
}

<?php

namespace App\Filament\Resources\StockMovements\Tables;

use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;

class StockMovementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')->label('Produk'),

                TextColumn::make('type')
                    ->badge()
                    ->colors([
                        'success' => 'increase',
                        'danger' => 'decrease',
                    ])
                    ->formatStateUsing(fn($state) => $state === 'increase' ? 'Masuk' : 'Keluar'),

                TextColumn::make('quantity')->label('Qty')->numeric(2),

                TextColumn::make('reason')
                    ->label('Alasan')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('notes')->label('Catatan')->limit(30),

                TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d M Y H:i'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->recordActions([
                // EditAction::make(),
            ])
            ->toolbarActions([
                // BulkActionGroup::make([
                //     DeleteBulkAction::make(),
                // ]),
            ]);
    }
}

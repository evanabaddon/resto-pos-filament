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
                \Filament\Tables\Filters\SelectFilter::make('reason')
                    ->label('Alasan')
                    ->options([
                        'stock_opname' => 'Stock Opname',
                        'production_output' => 'Hasil Produksi',
                        'production_ingredient' => 'Bahan Produksi',
                        'waste' => 'Waste/Buang',
                        'sale' => 'Penjualan (POS)',
                        'void_sale' => 'Void Sale',
                    ])
                    ->multiple(),

                \Filament\Tables\Filters\Filter::make('other_reasons')
                    ->label('Lainnya')
                    ->query(fn($query) => $query->whereNotIn('reason', [
                        'stock_opname',
                        'production_output',
                        'production_ingredient',
                        'waste',
                        'sale',
                        'void_sale',
                    ]))
                    ->toggle(),

                \Filament\Tables\Filters\Filter::make('created_at')
                    ->schema([
                        \Filament\Forms\Components\DatePicker::make('created_from')
                            ->label('Dari Tanggal'),
                        \Filament\Forms\Components\DatePicker::make('created_until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['created_from'], fn($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['created_until'], fn($q, $date) => $q->whereDate('created_at', '<=', $date));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['created_from'] ?? null) {
                            $indicators[] = 'Dari: ' . \Carbon\Carbon::parse($data['created_from'])->format('d M Y');
                        }
                        if ($data['created_until'] ?? null) {
                            $indicators[] = 'Sampai: ' . \Carbon\Carbon::parse($data['created_until'])->format('d M Y');
                        }
                        return $indicators;
                    }),
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

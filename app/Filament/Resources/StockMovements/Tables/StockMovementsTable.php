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
                TextColumn::make('product.name')->label(__('messages.product_resource')),

                TextColumn::make('type')
                    ->badge()
                    ->colors([
                        'success' => 'increase',
                        'danger' => 'decrease',
                    ])
                    ->formatStateUsing(fn($state) => $state === 'increase' ? __('messages.stock_in') : __('messages.stock_out')),

                TextColumn::make('quantity')->label(__('messages.quantity'))->numeric(2),

                TextColumn::make('reason')
                    ->label(__('messages.reason'))
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn($state) => __('messages.' . $state) ?? $state),

                TextColumn::make('notes')->label(__('messages.notes'))->limit(30),

                TextColumn::make('created_at')
                    ->label(__('messages.date'))
                    ->dateTime('d M Y H:i'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('reason')
                    ->label(__('messages.reason'))
                    ->options([
                        'stock_opname' => __('messages.stock_opname'),
                        'production_output' => __('messages.production_output'),
                        'production_ingredient' => __('messages.production_ingredient'),
                        'waste' => __('messages.waste'),
                        'sale' => __('messages.sale'),
                        'void_sale' => __('messages.void_sale'),
                    ])
                    ->multiple(),

                \Filament\Tables\Filters\Filter::make('other_reasons')
                    ->label(__('messages.other'))
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
                            ->label(__('messages.date_from')),
                        \Filament\Forms\Components\DatePicker::make('created_until')
                            ->label(__('messages.date_until')),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['created_from'], fn($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['created_until'], fn($q, $date) => $q->whereDate('created_at', '<=', $date));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['created_from'] ?? null) {
                            $indicators[] = __('messages.date_from') . ': ' . \Carbon\Carbon::parse($data['created_from'])->format('d M Y');
                        }
                        if ($data['created_until'] ?? null) {
                            $indicators[] = __('messages.date_until') . ': ' . \Carbon\Carbon::parse($data['created_until'])->format('d M Y');
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

<?php

namespace App\Filament\Resources\DiscountCodes\Tables;

use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;

class DiscountCodesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label(__('messages.discount_code'))->searchable(),
                TextColumn::make('name')->label(__('messages.promo_name')),
                TextColumn::make('type')
                    ->label(__('messages.discount_type'))
                    ->badge()
                    ->colors([
                        'success' => 'Percentage',
                        'info' => 'Fixed',
                    ]),
                TextColumn::make('value')
                    ->label(__('messages.discount_value'))
                    ->formatStateUsing(
                        fn($record) =>
                        $record->type === 'Percentage'
                            ? "{$record->value}%"
                            : 'Rp ' . number_format($record->value, 0, ',', '.')
                    ),
                TextColumn::make('valid_until')->label(__('messages.valid_until')),
                IconColumn::make('is_active')->boolean()->label(__('messages.is_active')),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->hidden(fn() => !in_array(auth()->user()->role, ['super_admin', 'admin'])),
                ]),
            ]);
    }
}

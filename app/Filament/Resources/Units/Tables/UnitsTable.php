<?php

namespace App\Filament\Resources\Units\Tables;

use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;

class UnitsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label(__('messages.name')),
                TextColumn::make('symbol')->label(__('messages.symbol')),
                TextColumn::make('baseUnit.name')->label(__('messages.base_unit')),
                TextColumn::make('conversion_rate')->label(__('messages.conversion_rate'))->formatStateUsing(fn($state) => number_format($state, 0, '.', ',')),
            ])
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

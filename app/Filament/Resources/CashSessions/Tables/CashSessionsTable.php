<?php

namespace App\Filament\Resources\CashSessions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CashSessionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')->label('User'),
                TextColumn::make('opened_at')->label('Opened At')->dateTime(),
                TextColumn::make('closed_at')->label('Closed At')->dateTime(),
                TextColumn::make('cash_in_hand')->label('Kas Masuk')->money('idr'),
                TextColumn::make('cash_out')->label('Kas Keluar')->money('idr'),
            ])
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

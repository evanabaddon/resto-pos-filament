<?php

namespace App\Filament\Resources\CashSessions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CashSessionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')->label('User')->sortable()->searchable(),
                TextColumn::make('opened_at')->label('Dibuka')->dateTime()->sortable(),
                TextColumn::make('closed_at')->label('Ditutup')->dateTime()->sortable(),
                TextColumn::make('cash_in_hand')->label('Kasir Awal')->money('idr', true)->sortable(),
                TextColumn::make('cash_out')->label('Kasir Tutup')->money('idr', true)->sortable(),
                TextColumn::make('status')->label('Status')->sortable()->searchable(),
            ])
            ->defaultSort('opened_at', 'desc')
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                // EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

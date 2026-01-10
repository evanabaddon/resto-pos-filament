<?php

namespace App\Filament\Resources\CashSessions\Tables;

use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;

class CashSessionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')->label(__('messages.user'))->sortable()->searchable(),
                TextColumn::make('opened_at')->label(__('messages.opened_at'))->sortable()->formatStateUsing(fn($state) => Carbon::parse($state)->isoFormat('D MMMM Y | HH:mm')),
                TextColumn::make('closed_at')->label(__('messages.closed_at'))->sortable()->formatStateUsing(fn($state) => Carbon::parse($state)->isoFormat('D MMMM Y | HH:mm')),
                TextColumn::make('cash_in_hand')->label(__('messages.starting_cash'))->money('idr', true)->sortable(),
                TextColumn::make('cash_out')->label(__('messages.ending_cash'))->money('idr', true)->sortable(),
                TextColumn::make('status')->label(__('messages.status'))->sortable()->searchable()->badge()->color(fn($state) => $state === 'open' ? 'success' : 'danger'),
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

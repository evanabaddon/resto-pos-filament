<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('email'),
                TextColumn::make('role')->label('Role')
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            'admin' => 'Staff Admin',
                            'waiter' => 'Waiter',
                            'cashier' => 'Cashier',
                            'accountant' => 'Staff Keuangan',
                            'inventory' => 'Staff Gudang',
                            'kitchen' => 'Kitchen / Dapur',
                            'super_admin' => 'Super Admin',
                            default => $state,
                        };
                    }),
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
                        ->visible(function () {
                            return auth()->user()->role === 'super_admin';
                        }),
                ]),
            ]);
    }
}

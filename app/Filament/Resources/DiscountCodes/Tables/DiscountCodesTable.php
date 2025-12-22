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
                TextColumn::make('code')->label('Kode')->searchable(),
                TextColumn::make('name')->label('Nama'),
                BadgeColumn::make('type')
                    ->label('Tipe')
                    ->colors([
                        'success' => 'percentage',
                        'info' => 'fixed',
                    ]),
                TextColumn::make('value')
                    ->label('Nilai')
                    ->formatStateUsing(
                        fn($record) =>
                        $record->type === 'percentage'
                        ? "{$record->value}%"
                        : 'Rp ' . number_format($record->value, 0, ',', '.')
                    ),
                TextColumn::make('valid_until')->label('Berlaku Sampai'),
                IconColumn::make('is_active')->boolean()->label('Aktif'),
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

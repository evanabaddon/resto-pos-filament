<?php

namespace App\Filament\Clusters\Crm\Resources\Members\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class MembersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('tier.name')
                    ->label('Level')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Sedulur Tinetes' => 'warning',
                        'Sedulur' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('points_balance')
                    ->label('Poin')
                    ->sortable(),
                TextColumn::make('total_visits')
                    ->label('Kunjungan')
                    ->sortable(),
                TextColumn::make('last_visit_at')
                    ->label('Terakhir Datang')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
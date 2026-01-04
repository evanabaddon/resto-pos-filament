<?php

namespace App\Filament\Resources\TvConfigs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class TvConfigsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('is_active')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state ? 'Active' : 'Inactive')
                    ->colors([
                        'success' => fn($state) => $state === true,
                        'gray' => fn($state) => $state === false,
                    ]),

                TextColumn::make('images')
                    ->label('Images')
                    ->formatStateUsing(fn($state) => is_array($state) ? count($state) . ' images' : '0 images')
                    ->badge(),

                TextColumn::make('music_url')
                    ->label('Music URL')
                    ->limit(50)
                    ->tooltip(fn($state) => $state),

                TextColumn::make('slide_duration')
                    ->label('Duration')
                    ->formatStateUsing(fn($state) => $state . ' ms')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('activate')
                    ->label('Activate')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn($record) => !$record->is_active)
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        // Deactivate all others
                        \App\Models\TvConfig::where('id', '!=', $record->id)->update(['is_active' => false]);
                        // Activate this one
                        $record->update(['is_active' => true]);
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

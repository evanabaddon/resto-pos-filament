<?php

namespace App\Filament\Resources\TvConfigs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class TvConfigsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Configuration Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                ToggleColumn::make('is_active')
                    ->label('Active')
                    ->beforeStateUpdated(function ($record, $state) {
                        if ($state) {
                            // Deactivate all other configs before activating this one
                            \App\Models\TvConfig::where('id', '!=', $record->id)
                                ->update(['is_active' => false]);
                        }
                    })
                    ->afterStateUpdated(function ($record, $state) {
                        \Filament\Notifications\Notification::make()
                            ->title($state ? 'Configuration Activated' : 'Configuration Deactivated')
                            ->body($record->name . ' is now ' . ($state ? 'active' : 'inactive'))
                            ->success()
                            ->send();
                    }),

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
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

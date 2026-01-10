<?php

namespace App\Filament\Clusters\Hrm\Resources\Contracts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ContractsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.name')
                    ->label(__('messages.employee_resource'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('start_date')
                    ->label(__('messages.start_date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('end_date')
                    ->label(__('messages.end_date'))
                    ->date()
                    ->sortable()
                    ->placeholder(__('messages.forever')),
                ImageColumn::make('signature_path')
                    ->label(__('messages.signature'))
                    ->checkFileExistence(false)
                    ->getStateUsing(fn($record) => $record->signature_path ? asset('storage/' . $record->signature_path) : null),
                TextColumn::make('created_at')
                    ->label(__('messages.created_at'))
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
                    DeleteBulkAction::make()
                        ->hidden(fn() => !in_array(auth()->user()->role, ['super_admin', 'admin'])),
                ]),
            ]);
    }
}

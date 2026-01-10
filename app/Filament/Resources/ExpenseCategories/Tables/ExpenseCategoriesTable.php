<?php

namespace App\Filament\Resources\ExpenseCategories\Tables;

use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

class ExpenseCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('messages.category_name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('description')
                    ->label(__('messages.description'))
                    ->limit(50)
                    ->searchable(),

                IconColumn::make('is_active')
                    ->label(__('messages.status'))
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('expenses_count')
                    ->label(__('messages.expense_count'))
                    ->counts('expenses')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label(__('messages.created_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('is_active')
                    ->label(__('messages.is_active'))
                    ->options([
                        true => __('messages.active'),
                        false => __('messages.inactive'),
                    ]),
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

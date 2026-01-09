<?php

namespace App\Filament\Resources\Productions\Tables;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class ProductionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('messages.date'))
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                TextColumn::make('product.name')
                    ->label(__('messages.product_resource'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('quantity')
                    ->label(__('messages.quantity'))
                    ->numeric()
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label(__('messages.by')),

                TextColumn::make('notes')
                    ->label(__('messages.notes'))
                    ->limit(30),
            ])
            ->defaultSort('created_at', 'desc');
    }
}

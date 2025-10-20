<?php

namespace App\Filament\Resources\Sales\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SalesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')->label('Invoice Number')->searchable()->sortable(),
                TextColumn::make('customer_name')->label('Customer Name')->searchable()->sortable(),
                TextColumn::make('order_type')->label('Order Type')->searchable()->sortable(),
                TextColumn::make('final_total')->label('Total Amount')->money('idr', true)->sortable(),
                TextColumn::make('status')->label('Status')->sortable(),
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

<?php

namespace App\Filament\Resources\Sales\Tables;

use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Illuminate\Support\Facades\Date;

class SalesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('updated_at')
                    ->label('Tanggal')
                    ->formatStateUsing(fn ($state) => $state->diffForHumans()),
                TextColumn::make('invoice_number')->label('Invoice Number')->searchable()->sortable(),
                TextColumn::make('customer_name')->label('Customer Name')->searchable()->sortable(),
                TextColumn::make('order_type')->label('Order Type')->searchable()->sortable(),
                TextColumn::make('payment_method')->label('Payment Method')->searchable()->sortable(),
                TextColumn::make('status')->label('Status')->sortable(),
                TextColumn::make('final_total')->label('Total Amount')->sortable()->money('IDR')->summarize(Sum::make()->money('IDR')->label('Total Penjualan')),
            ])
            ->defaultSort('created_at', 'desc')
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

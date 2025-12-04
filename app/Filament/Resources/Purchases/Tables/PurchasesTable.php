<?php

namespace App\Filament\Resources\Purchases\Tables;

use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\Summarizers\Sum;

class PurchasesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')->label('Tanggal')->formatStateUsing(fn ($state) => Carbon::parse($state)->isoFormat('D MMMM Y')),
                TextColumn::make('invoice_number')
                    ->label('Nomor Nota')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Referensi disalin!'),
                TextColumn::make('supplier_name'),
                TextColumn::make('status')
                    ->badge()
                    ->colors(['warning' => 'draft', 'success' => 'received']),
                TextColumn::make('total')->money('IDR')->summarize(Sum::make()->money('IDR')->label('Total Pembelian')),
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
            ])->defaultSort('created_at', 'desc');
    }
}

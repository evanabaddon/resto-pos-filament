<?php

namespace App\Filament\Resources\Purchases\Tables;

use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class PurchasesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')->label('Tanggal')->formatStateUsing(fn($state) => Carbon::parse($state)->isoFormat('D MMMM Y')),
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
                TextColumn::make('fund_source')
                    ->label('Sumber Dana')
                    ->formatStateUsing(fn($state) => \App\Models\Purchase::getFundSources()[$state] ?? $state)
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        \App\Models\Purchase::FUND_SOURCE_CASHIER => 'success',
                        \App\Models\Purchase::FUND_SOURCE_PETTY_CASH => 'info',
                        \App\Models\Purchase::FUND_SOURCE_TRANSFER => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('total')->money('IDR')->summarize(Sum::make()->money('IDR')->label('Total Pembelian')),
            ])
            ->filters([
                DateRangeFilter::make('created_at')->label('Tanggal Transaksi'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->hidden(fn() => !in_array(auth()->user()->role, ['super_admin', 'admin'])),
                ]),
            ])->defaultSort('created_at', 'desc');
    }
}

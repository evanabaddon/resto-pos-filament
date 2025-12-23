<?php

namespace App\Filament\Resources\CashSessions\RelationManagers;

use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CashPurchasesRelationManager extends RelationManager
{
    protected static string $relationship = 'cashPurchases';

    protected static ?string $title = 'Pembelian (Cash)';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('invoice_number')
            ->columns([
                TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('invoice_number')
                    ->label('No. Invoice')
                    ->searchable()
                    ->fontFamily(\Filament\Support\Enums\FontFamily::Mono),
                TextColumn::make('supplier_name')
                    ->label('Supplier')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'order' => 'warning',
                        'received' => 'success',
                        'cancelled' => 'danger',
                    }),
                TextColumn::make('total')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable()
                    ->weight('bold')
                    ->color('danger'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // No create action here, purchases made elsewhere
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}

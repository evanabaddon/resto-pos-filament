<?php

namespace App\Filament\Clusters\Hrm\Resources\Loans\Tables;

use App\Models\Loan;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LoansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.name')
                    ->searchable()
                    ->sortable()
                    ->label('Pegawai'),
                TextColumn::make('amount')
                    ->money('IDR')
                    ->sortable()
                    ->label('Total Pinjaman'),
                TextColumn::make('remaining_amount')
                    ->money('IDR')
                    ->sortable()
                    ->state(fn(Loan $record) => $record->remaining_amount ?? $record->amount)
                    ->label('Sisa Tagihan'),
                TextColumn::make('installment_amount')
                    ->money('IDR')
                    ->label('Cicilan'),
                TextColumn::make('start_month_year')
                    ->label('Mulai'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'gray',
                        'approved' => 'info',
                        'rejected' => 'danger',
                        'paid' => 'success',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'paid' => 'Lunas',
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

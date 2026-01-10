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
                    ->label(__('messages.employee_resource')),
                TextColumn::make('amount')
                    ->money('IDR')
                    ->sortable()
                    ->label(__('messages.loan_amount')),
                TextColumn::make('remaining_amount')
                    ->money('IDR')
                    ->sortable()
                    ->state(fn(Loan $record) => $record->remaining_amount ?? $record->amount)
                    ->label(__('messages.remaining_amount')),
                TextColumn::make('installment_amount')
                    ->money('IDR')
                    ->label(__('messages.installment')),
                TextColumn::make('start_month_year')
                    ->label(__('messages.start_date')),
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
                        'pending' => __('messages.pending'),
                        'approved' => __('messages.approved'),
                        'rejected' => __('messages.rejected'),
                        'paid' => __('messages.paid'),
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

<?php

namespace App\Filament\Resources\Expenses\Tables;

use Dom\Text;
use App\Models\Expense;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Filament\Actions\EditAction;
use Filament\Tables\Filters\Filter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextInputColumn;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class ExpensesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')
                    ->label(__('messages.date'))
                    ->formatStateUsing(fn($state) => Carbon::parse($state)->isoFormat('D MMMM Y'))
                    ->sortable(),

                TextColumn::make('reference')
                    ->label(__('messages.reference'))
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage(__('messages.copy_reference_message')),

                TextColumn::make('category.name')
                    ->label(__('messages.expense_category'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('description')
                    ->label(__('messages.description'))
                    ->limit(50)
                    ->searchable(),

                TextColumn::make('fund_source')
                    ->label(__('messages.fund_source'))
                    ->formatStateUsing(fn($state) => Expense::getFundSources()[$state] ?? $state)
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        Expense::FUND_SOURCE_CASHIER => 'success',
                        Expense::FUND_SOURCE_PETTY_CASH => 'info',
                        Expense::FUND_SOURCE_TRANSFER => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('amount')
                    ->label(__('messages.amount'))
                    ->money('IDR')
                    ->sortable()
                    ->color('danger'),

                \Filament\Tables\Columns\IconColumn::make('is_stock_purchase')
                    ->label(__('messages.is_stock') ?? 'Stok?')
                    ->boolean()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('paymentMethod.name')
                    ->label(__('messages.payment_method'))
                    ->badge()
                    ->color('gray'),

                TextColumn::make('recipient')
                    ->label(__('messages.recipient'))
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('status')
                    ->label(__('messages.status'))
                    ->badge()
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ])
                    ->icons([
                        'heroicon-o-clock' => 'pending',
                        'heroicon-o-check-circle' => 'approved',
                        'heroicon-o-x-circle' => 'rejected',
                    ]),

                TextColumn::make('user.name')
                    ->label(__('messages.created_by'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label(__('messages.created_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('amount')
                    ->summarize(Sum::make()->hidden(fn(Builder $query): bool => !$query->exists())
                        ->money('IDR')
                        ->label(__('messages.total_expenses'))),
            ])
            ->filters([
                SelectFilter::make('fund_source')
                    ->label(__('messages.fund_source'))
                    ->options(Expense::getFundSources()),

                SelectFilter::make('expense_category_id')
                    ->label(__('messages.expense_category'))
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('payment_method_id')
                    ->label(__('messages.payment_method'))
                    ->relationship('paymentMethod', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('status')
                    ->label(__('messages.status'))
                    ->options([
                        'pending' => __('messages.status_pending'),
                        'approved' => __('messages.status_approved') ?? 'Approved',
                        'rejected' => __('messages.status_rejected') ?? 'Rejected',
                    ]),

                SelectFilter::make('is_stock_purchase')
                    ->label(__('messages.is_stock') ?? 'Stok?')
                    ->options([
                        '1' => __('messages.yes') ?? 'Ya',
                        '0' => __('messages.no') ?? 'Tidak',
                    ]),

                DateRangeFilter::make('created_at')->label(__('messages.transaction_date')),
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

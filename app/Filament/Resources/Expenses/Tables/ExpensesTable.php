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
                    ->label('Tanggal')
                    ->formatStateUsing(fn ($state) => Carbon::parse($state)->isoFormat('D MMMM Y'))
                    ->sortable(),

                TextColumn::make('reference')
                    ->label('Referensi')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Referensi disalin!'),
                
                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('description')
                    ->label('Deskripsi')
                    ->limit(50)
                    ->searchable(),

                TextColumn::make('fund_source')
                    ->label('Sumber Dana')
                    ->formatStateUsing(fn($state) => Expense::getFundSources()[$state] ?? $state)
                    ->badge()
                    ->color(fn($state) => match($state) {
                        Expense::FUND_SOURCE_CASHIER => 'success',
                        Expense::FUND_SOURCE_PETTY_CASH => 'info',
                        Expense::FUND_SOURCE_TRANSFER => 'warning',
                        default => 'gray',
                    }),
                
                TextColumn::make('amount')
                    ->label('Jumlah')
                    ->money('IDR')
                    ->sortable()
                    ->color('danger'),
                
                TextColumn::make('paymentMethod.name')
                    ->label('Metode Bayar')
                    ->badge()
                    ->color('gray'),
                
                TextColumn::make('recipient')
                    ->label('Penerima')
                    ->searchable()
                    ->toggleable(),
                
                TextColumn::make('status')
                    ->label('Status')
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
                    ->label('Dibuat Oleh')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('amount')
                    ->summarize(Sum::make()->hidden(fn (Builder $query): bool => ! $query->exists())
                    ->money('IDR')
                    ->label('Total Pengeluaran')),
            ])
            ->filters([
                SelectFilter::make('fund_source')
                    ->label('Sumber Dana')
                    ->options(Expense::getFundSources()),

                SelectFilter::make('expense_category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                
                SelectFilter::make('payment_method_id')
                    ->label('Metode Pembayaran')
                    ->relationship('paymentMethod', 'name')
                    ->searchable()
                    ->preload(),
                
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                    ]),
                
                DateRangeFilter::make('created_at')->label('Tanggal Transaksi'),
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

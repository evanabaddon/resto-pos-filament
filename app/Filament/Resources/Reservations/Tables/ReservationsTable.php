<?php

namespace App\Filament\Resources\Reservations\Tables;

use Filament\Tables\Table;
use App\Models\Reservation;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Filters\Filter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\SelectFilter;
use App\Filament\Resources\Reservations\Widgets\ReservationCalendarWidget;

class ReservationsTable
{
    // Widgets yang akan muncul di HEADER (atas tabel)
    protected function getHeaderWidgets(): array
    {
        return [
            ReservationCalendarWidget::class,
        ];
    }
    protected function getHeaderWidgetsColumns(): int|array
    {
        return 1; // Calendar full width
    }
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer_name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('customer_phone')
                    ->label('Telepon')
                    ->searchable(),
                    
                TextColumn::make('party_size')
                    ->label('Jumlah')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => $state > 10 ? 'warning' : 'success'),
                    
                TextColumn::make('reservation_date')
                    ->label('Tanggal')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                    
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'confirmed',
                        'info' => 'seated',
                        'gray' => 'completed',
                        'danger' => 'cancelled',
                    ])
                    ->icons([
                        'heroicon-o-clock' => 'pending',
                        'heroicon-o-check-circle' => 'confirmed',
                        'heroicon-o-user-group' => 'seated',
                        'heroicon-o-check-badge' => 'completed',
                        'heroicon-o-x-circle' => 'cancelled',
                    ]),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'seated' => 'Seated',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
                    
                Filter::make('reservation_date')
                    ->schema([
                        DatePicker::make('from')
                            ->label('Dari Tanggal'),
                        DatePicker::make('until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q) => $q->whereDate('reservation_date', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->whereDate('reservation_date', '<=', $data['until']));
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                Action::make('confirm')
                    ->label('Konfirmasi')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Reservation $record) => $record->status === 'pending')
                    ->action(fn (Reservation $record) => $record->update(['status' => 'confirmed'])),
                    
                Action::make('seat')
                    ->label('Dudukkan')
                    ->icon('heroicon-o-user-group')
                    ->color('info')
                    ->visible(fn (Reservation $record) => $record->status === 'confirmed')
                    ->action(fn (Reservation $record) => $record->update(['status' => 'seated'])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

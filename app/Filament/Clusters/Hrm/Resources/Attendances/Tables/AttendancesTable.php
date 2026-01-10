<?php

namespace App\Filament\Clusters\Hrm\Resources\Attendances\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class AttendancesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.name')
                    ->label(__('messages.employee_resource'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('employee.shift.name')
                    ->label(__('messages.shift_resource'))
                    ->sortable(),
                TextColumn::make('date')
                    ->label(__('messages.date'))
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('clock_in')
                    ->label(__('messages.clock_in'))
                    ->time('H:i')
                    ->sortable(),
                TextColumn::make('clock_out')
                    ->label(__('messages.clock_out'))
                    ->time('H:i')
                    ->sortable(),
                TextColumn::make('status_details')
                    ->label(__('messages.status'))
                    ->badge()
                    ->state(function ($record) {
                        $states = [];
                        if ($record->is_late) {
                            $states[] = 'Terlambat';
                        }
                        if ($record->is_early_leave) {
                            $states[] = 'Pulang Cepat';
                        }
                        if ($record->overtime_minutes > 0) {
                            $states[] = 'Overtime: ' . $record->overtime_minutes . 'm';
                        }
                        if (empty($states)) {
                            $states[] = 'Hadir';
                        }
                        return $states;
                    })
                    ->color(fn(string $state): string => match (true) {
                        $state === 'Terlambat' => 'danger',
                        $state === 'Pulang Cepat' => 'warning',
                        str_starts_with($state, 'Overtime') => 'info',
                        default => 'success',
                    }),
                TextColumn::make('snapshot_path')
                    ->label(__('messages.snapshot'))
                    ->formatStateUsing(fn() => __('messages.view_photo'))
                    ->icon('heroicon-o-camera')
                    ->url(fn($record) => Storage::url($record->snapshot_path))
                    ->openUrlInNewTab()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('employee')
                    ->label(__('messages.employee_resource'))
                    ->relationship('employee', 'name')
                    ->searchable()
                    ->preload(),
                \Filament\Tables\Filters\Filter::make('date')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('date_from')->label(__('messages.date_from')),
                        \Filament\Forms\Components\DatePicker::make('date_until')->label(__('messages.date_until')),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['date_from'],
                                fn($query, $date) => $query->whereDate('date', '>=', $date)
                            )
                            ->when(
                                $data['date_until'],
                                fn($query, $date) => $query->whereDate('date', '<=', $date)
                            );
                    }),
                \Filament\Tables\Filters\TernaryFilter::make('is_late')
                    ->label(__('messages.filter_late')),
                \Filament\Tables\Filters\TernaryFilter::make('is_early_leave')
                    ->label(__('messages.filter_early_leave')),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn() => !in_array(auth()->user()->role, ['super_admin', 'admin'])),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->hidden(fn() => !in_array(auth()->user()->role, ['super_admin', 'admin'])),
                ]),
            ]);
    }
}

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
                    ->label('Nama Pegawai')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('employee.shift.name')
                    ->label('Shift')
                    ->sortable(),
                TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('clock_in')
                    ->label('Jam Masuk')
                    ->time('H:i')
                    ->sortable(),
                TextColumn::make('clock_out')
                    ->label('Jam Pulang')
                    ->time('H:i')
                    ->sortable(),
                TextColumn::make('status_details')
                    ->label('Status')
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
                    ->label('Foto')
                    ->formatStateUsing(fn() => 'Lihat Foto')
                    ->icon('heroicon-o-camera')
                    ->url(fn($record) => Storage::url($record->snapshot_path))
                    ->openUrlInNewTab()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('employee')
                    ->label('Pegawai')
                    ->relationship('employee', 'name')
                    ->searchable()
                    ->preload(),
                \Filament\Tables\Filters\Filter::make('date')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('date_from')->label('Dari Tanggal'),
                        \Filament\Forms\Components\DatePicker::make('date_until')->label('Sampai Tanggal'),
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
                    ->label('Filter Terlambat'),
                \Filament\Tables\Filters\TernaryFilter::make('is_early_leave')
                    ->label('Filter Pulang Cepat'),
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

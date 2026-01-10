<?php

namespace App\Filament\Clusters\Hrm\Resources\LeaveRequests\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Textarea;
use Filament\Support\Enums\FontWeight;

class LeaveRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.name')
                    ->label(__('messages.employee_resource'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label(__('messages.type'))
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'sakit' => __('messages.sick_leave'),
                        'izin' => __('messages.permission'),
                        'cuti_tahunan' => __('messages.annual_leave'),
                        'cuti_khusus' => __('messages.special_leave'),
                        default => $state,
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'sakit' => 'warning',
                        'izin' => 'info',
                        'cuti_tahunan' => 'success',
                        'cuti_khusus' => 'primary',
                    }),
                TextColumn::make('start_date')
                    ->label(__('messages.start_date'))
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('end_date')
                    ->label(__('messages.end_date'))
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'gray',
                        'approved' => 'success',
                        'rejected' => 'danger',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'pending' => __('messages.pending'),
                        'approved' => __('messages.approved'),
                        'rejected' => __('messages.rejected'),
                        default => $state,
                    }),
                TextColumn::make('approver.name')
                    ->label(__('messages.approved_by'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('reason')
                    ->label(__('messages.reason'))
                    ->limit(30),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('approve')
                    ->label(__('messages.approve'))
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn($record) => $record->status === 'pending')
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'approved',
                            'approved_by' => auth()->id(),
                        ]);
                        Notification::make()->title(__('messages.approval_notification'))->success()->send();
                    }),
                Action::make('reject')
                    ->label(__('messages.reject'))
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Textarea::make('rejection_reason')->label(__('messages.rejection_reason'))->required()
                    ])
                    ->visible(fn($record) => $record->status === 'pending')
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => 'rejected',
                            'rejection_reason' => $data['rejection_reason'],
                            'approved_by' => auth()->id(),
                        ]);
                        Notification::make()->title(__('messages.rejection_notification'))->danger()->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->hidden(fn() => !in_array(auth()->user()->role, ['super_admin', 'admin'])),
                ]),
            ]);
    }
}

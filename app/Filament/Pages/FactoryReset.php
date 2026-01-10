<?php

namespace App\Filament\Pages;

use App\Services\BackupService;
use Filament\Pages\Page;
use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use BackedEnum;
use UnitEnum;

class FactoryReset extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path';

    protected string $view = 'filament.pages.factory-reset';

    public static function getNavigationGroup(): ?string
    {
        return __('messages.settings');
    }

    protected static ?string $navigationLabel = 'Factory Reset';

    public static function getNavigationLabel(): string
    {
        return __('messages.factory_reset');
    }

    protected static ?int $navigationSort = 999;

    public static function canAccess(): bool
    {
        return auth()->user()->role === \App\Enums\UserRole::SuperAdmin;
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('operational_reset')
                ->label(__('messages.operational_reset'))
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading(__('messages.operational_reset_heading'))
                ->modalDescription(__('messages.operational_reset_desc'))
                ->modalIcon('heroicon-o-exclamation-triangle')
                ->modalIconColor('warning')
                ->schema([
                    Forms\Components\TextInput::make('password')
                        ->label(__('messages.password_superadmin'))
                        ->password()
                        ->required()
                        ->rules([
                            function () {
                                return function (string $attribute, $value, \Closure $fail) {
                                    if (!Hash::check($value, auth()->user()->password)) {
                                        $fail('Password salah!');
                                    }
                                };
                            },
                        ]),
                    Forms\Components\Checkbox::make('confirm')
                        ->label(__('messages.i_understand_risks'))
                        ->accepted()
                        ->required(),
                ])
                ->action(function (array $data) {
                    $this->performOperationalReset();
                }),

            \Filament\Actions\Action::make('factory_reset')
                ->label(__('messages.factory_reset_total'))
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading(__('messages.factory_reset_heading'))
                ->modalDescription(__('messages.factory_reset_desc'))
                ->modalIcon('heroicon-o-exclamation-circle')
                ->modalIconColor('danger')
                ->schema([
                    Forms\Components\TextInput::make('password')
                        ->label(__('messages.password_superadmin'))
                        ->password()
                        ->required()
                        ->rules([
                            function () {
                                return function (string $attribute, $value, \Closure $fail) {
                                    if (!Hash::check($value, auth()->user()->password)) {
                                        $fail('Password salah!');
                                    }
                                };
                            },
                        ]),
                    Forms\Components\Checkbox::make('confirm')
                        ->label(__('messages.i_understand_factory'))
                        ->accepted()
                        ->required(),
                ])
                ->action(function (array $data) {
                    $this->performFactoryReset();
                }),
        ];
    }

    protected function performOperationalReset(): void
    {
        $backup = null;

        try {
            // 1. Create backup first
            $backupService = app(BackupService::class);
            $backup = $backupService->createBackup();

            // 2. Use raw SQL to bypass all observers
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            DB::statement('TRUNCATE TABLE sales');
            DB::statement('TRUNCATE TABLE sale_items');
            DB::statement('TRUNCATE TABLE stock_movements');
            DB::statement('TRUNCATE TABLE productions');
            DB::statement('TRUNCATE TABLE purchases');
            DB::statement('TRUNCATE TABLE purchase_items');
            DB::statement('TRUNCATE TABLE cash_sessions');
            DB::statement('TRUNCATE TABLE expenses');
            DB::statement('TRUNCATE TABLE reservations');
            DB::statement('TRUNCATE TABLE reservation_items');
            DB::statement('TRUNCATE TABLE members');
            DB::statement('TRUNCATE TABLE payrolls');
            DB::statement('TRUNCATE TABLE attendances');
            DB::statement('TRUNCATE TABLE loans');
            DB::statement('TRUNCATE TABLE loan_payments');
            DB::statement('TRUNCATE TABLE leave_requests');
            DB::statement('TRUNCATE TABLE whatsapp_messages');
            DB::statement('TRUNCATE TABLE print_jobs');
            DB::statement('UPDATE products SET stock = 0, prepared_stock = 0');

            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            Log::info('Operational Reset completed', [
                'backup' => $backup,
                'user' => auth()->user()->name,
            ]);

            Notification::make()
                ->success()
                ->title(__('messages.operational_reset_success'))
                ->body(__('messages.operational_reset_success_body', ['backup' => $backup]))
                ->send();
        } catch (\Exception $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            Log::error('Operational Reset failed', ['error' => $e->getMessage()]);

            Notification::make()
                ->danger()
                ->title('Reset Gagal')
                ->body('Terjadi kesalahan: ' . $e->getMessage())
                ->send();
        }
    }

    protected function performFactoryReset(): void
    {
        $backup = null;

        try {
            // 1. Create backup first
            $backupService = app(BackupService::class);
            $backup = $backupService->createBackup();

            // 2. Use raw SQL to bypass all observers
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            // Operational data
            DB::statement('TRUNCATE TABLE sales');
            DB::statement('TRUNCATE TABLE sale_items');
            DB::statement('TRUNCATE TABLE stock_movements');
            DB::statement('TRUNCATE TABLE productions');
            DB::statement('TRUNCATE TABLE purchases');
            DB::statement('TRUNCATE TABLE purchase_items');
            DB::statement('TRUNCATE TABLE cash_sessions');
            DB::statement('TRUNCATE TABLE expenses');
            DB::statement('TRUNCATE TABLE reservations');
            DB::statement('TRUNCATE TABLE reservation_items');
            DB::statement('TRUNCATE TABLE members');
            DB::statement('TRUNCATE TABLE payrolls');
            DB::statement('TRUNCATE TABLE attendances');
            DB::statement('TRUNCATE TABLE loans');
            DB::statement('TRUNCATE TABLE loan_payments');
            DB::statement('TRUNCATE TABLE leave_requests');
            DB::statement('TRUNCATE TABLE whatsapp_messages');
            DB::statement('TRUNCATE TABLE print_jobs');

            // Master data
            DB::statement('TRUNCATE TABLE recipes');
            DB::statement('TRUNCATE TABLE products');
            DB::statement('TRUNCATE TABLE categories');
            DB::statement('TRUNCATE TABLE employees');
            DB::statement('TRUNCATE TABLE employee_documents');
            DB::statement('TRUNCATE TABLE contracts');
            DB::statement('TRUNCATE TABLE tables');
            DB::statement('TRUNCATE TABLE discount_codes');
            DB::statement('TRUNCATE TABLE loyalty_tiers');
            DB::statement('TRUNCATE TABLE loyalty_rewards');
            DB::statement('TRUNCATE TABLE payroll_formulas');

            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            Log::info('Factory Reset completed', [
                'backup' => $backup,
                'user' => auth()->user()->name,
            ]);

            Notification::make()
                ->success()
                ->title(__('messages.factory_reset_success'))
                ->body(__('messages.factory_reset_success_body', ['backup' => $backup]))
                ->send();
        } catch (\Exception $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            Log::error('Factory Reset failed', ['error' => $e->getMessage()]);

            Notification::make()
                ->danger()
                ->title('Reset Gagal')
                ->body('Terjadi kesalahan: ' . $e->getMessage())
                ->send();
        }
    }

    public function restoreBackup(string $filename): void
    {
        try {
            $backupService = app(BackupService::class);
            $backupService->restoreBackup($filename);

            Notification::make()
                ->success()
                ->title(__('messages.restore_success'))
                ->body(__('messages.restore_success_body', ['file' => $filename]))
                ->send();

            redirect()->to(static::getUrl());
        } catch (\Exception $e) {
            Log::error('Backup restore failed', ['error' => $e->getMessage()]);

            Notification::make()
                ->danger()
                ->title(__('messages.restore_failed'))
                ->body('Terjadi kesalahan: ' . $e->getMessage())
                ->send();
        }
    }

    public function deleteBackup(string $filename): void
    {
        try {
            $backupService = app(BackupService::class);
            $backupService->deleteBackup($filename);

            Notification::make()
                ->success()
                ->title(__('messages.backup_deleted'))
                ->body(__('messages.backup_deleted_body', ['file' => $filename]))
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title(__('messages.failed_to_delete'))
                ->body('Terjadi kesalahan: ' . $e->getMessage())
                ->send();
        }
    }
}

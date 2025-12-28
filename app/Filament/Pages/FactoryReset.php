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

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'Factory Reset';

    protected static ?int $navigationSort = 999;

    public static function canAccess(): bool
    {
        return auth()->user()->role === \App\Enums\UserRole::SuperAdmin;
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('operational_reset')
                ->label('Reset Operasional')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Reset Operasional')
                ->modalDescription('Ini akan menghapus SEMUA data transaksi (penjualan, pembelian, stock movement, dll) tapi SIMPAN produk & resep. Stok akan di-reset ke 0.')
                ->modalIcon('heroicon-o-exclamation-triangle')
                ->modalIconColor('warning')
                ->schema([
                    Forms\Components\TextInput::make('password')
                        ->label('Password SuperAdmin')
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
                        ->label('Saya mengerti risiko dan ingin melanjutkan')
                        ->accepted()
                        ->required(),
                ])
                ->action(function (array $data) {
                    $this->performOperationalReset();
                }),

            \Filament\Actions\Action::make('factory_reset')
                ->label('Factory Reset Total')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('⚠️ PERINGATAN: Factory Reset Total')
                ->modalDescription('Ini akan menghapus SEMUA DATA termasuk produk, resep, kategori, karyawan, dll. Hanya user dan settings yang akan tetap ada. TIDAK BISA DI-UNDO!')
                ->modalIcon('heroicon-o-exclamation-circle')
                ->modalIconColor('danger')
                ->schema([
                    Forms\Components\TextInput::make('password')
                        ->label('Password SuperAdmin')
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
                        ->label('Saya mengerti ini akan menghapus SEMUA DATA dan TIDAK BISA DI-UNDO')
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
                ->title('Reset Operasional Berhasil')
                ->body("Semua data transaksi telah dihapus. Backup: {$backup}")
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
                ->title('Factory Reset Berhasil')
                ->body("Semua data telah dihapus. Backup: {$backup}")
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
                ->title('Restore Berhasil')
                ->body("Database telah dikembalikan dari backup: {$filename}")
                ->send();

            redirect()->to(static::getUrl());
        } catch (\Exception $e) {
            Log::error('Backup restore failed', ['error' => $e->getMessage()]);

            Notification::make()
                ->danger()
                ->title('Restore Gagal')
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
                ->title('Backup Dihapus')
                ->body("Backup {$filename} telah dihapus")
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Gagal Menghapus')
                ->body('Terjadi kesalahan: ' . $e->getMessage())
                ->send();
        }
    }
}

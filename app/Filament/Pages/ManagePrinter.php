<?php

namespace App\Filament\Pages;

use UnitEnum;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Filament\Pages\SettingsPage;
use App\Settings\PrinterSettings;
use Filament\Support\Icons\Heroicon;
use App\Services\ReceiptPrintService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;

class ManagePrinter extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-printer';

    protected static string $settings = PrinterSettings::class;

    public static function getNavigationGroup(): ?string
    {
        return __('messages.settings');
    }

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return auth()->user()->role === \App\Enums\UserRole::SuperAdmin || auth()->user()->role === \App\Enums\UserRole::Admin;
    }

    public function getTitle(): string
    {
        return __('messages.manage_printer');
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.manage_printer');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('messages.printer_configuration'))
                    ->description(__('messages.printer_config_desc'))
                    ->schema([
                        Select::make('printer_type')
                            ->label(__('messages.printer_type'))
                            ->options([
                                'network' => __('messages.network_printer'),
                                'usb' => __('messages.usb_printer'),
                            ])
                            ->default('usb')
                            ->live()
                            ->required(),
                    ]),

                Section::make(__('messages.usb_printer_settings'))
                    ->description(__('messages.usb_settings_desc'))
                    ->visible(fn($get) => $get('printer_type') === 'usb')
                    ->schema([
                        Select::make('usb_printer_mode')
                            ->label(__('messages.usb_printer_mode'))
                            ->options([
                                'single' => __('messages.single_printer_mode'),
                                'multiple' => __('messages.multiple_printer_mode'),
                            ])
                            ->default('single')
                            ->live()
                            ->required(),

                        TextInput::make('usb_printer_name')
                            ->label(__('messages.usb_printer_name'))
                            ->placeholder('POS-58')
                            ->helperText(__('messages.usb_printer_name_helper'))
                            ->default('POS-58')
                            ->required(),

                        TextInput::make('usb_kitchen_printer_name')
                            ->label(__('messages.usb_kitchen_printer_name'))
                            ->placeholder('Kitchen-POS-58')
                            ->helperText(__('messages.usb_kitchen_helper'))
                            ->visible(fn($get) => $get('usb_printer_mode') === 'multiple'),

                        TextInput::make('usb_bar_printer_name')
                            ->label(__('messages.usb_bar_printer_name'))
                            ->placeholder('Bar-POS-58')
                            ->helperText(__('messages.usb_bar_helper'))
                            ->visible(fn($get) => $get('usb_printer_mode') === 'multiple'),

                        TextInput::make('usb_general_printer_name')
                            ->label(__('messages.usb_general_printer_name'))
                            ->placeholder('General-POS-58')
                            ->helperText(__('messages.usb_general_helper'))
                            ->visible(fn($get) => $get('usb_printer_mode') === 'multiple'),
                    ]),

                Section::make(__('messages.network_printer_settings'))
                    ->description(__('messages.network_settings_desc'))
                    ->visible(fn($get) => $get('printer_type') === 'network')
                    ->schema([
                        TextInput::make('kitchen_printer_ip')
                            ->label(__('messages.kitchen_printer_ip'))
                            ->placeholder('192.168.1.100')
                            ->required(),
                        TextInput::make('kitchen_printer_port')
                            ->label(__('messages.kitchen_printer_port'))
                            ->numeric()
                            ->placeholder('9100')
                            ->default(9100)
                            ->required(),

                        TextInput::make('bar_printer_ip')
                            ->label(__('messages.bar_printer_ip'))
                            ->placeholder('192.168.1.101')
                            ->required(),
                        TextInput::make('bar_printer_port')
                            ->label(__('messages.bar_printer_port'))
                            ->numeric()
                            ->placeholder('9100')
                            ->default(9100)
                            ->required(),

                        TextInput::make('general_printer_ip')
                            ->label(__('messages.general_printer_ip'))
                            ->placeholder('192.168.1.102')
                            ->required(),
                        TextInput::make('general_printer_port')
                            ->label(__('messages.general_printer_port'))
                            ->numeric()
                            ->placeholder('9100')
                            ->default(9100)
                            ->required(),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('testPrinters')
                ->label(__('messages.test_printers'))
                ->color('success')
                ->action('testPrinters'),
        ];
    }

    public function detectUsbPrinters()
    {
        try {
            $printService = new ReceiptPrintService();
            $detectedPrinters = $printService->getAvailablePrinters();

            // ✅ FIX: Pastikan $detectedPrinters adalah array
            if (empty($detectedPrinters)) {
                Notification::make()
                    ->title(__('messages.no_usb_printers_found'))
                    ->body(__('messages.no_usb_printers_body'))
                    ->warning()
                    ->send();
                return;
            }

            // ✅ FIX: Gunakan implode hanya jika array tidak empty
            $message = __('messages.usb_printers_detected') . ":\n" . implode("\n", $detectedPrinters);

            Notification::make()
                ->title(__('messages.usb_printers_detected'))
                ->body($message)
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title(__('messages.detection_failed'))
                ->body('Gagal mendeteksi printer: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function testPrinters()
    {
        try {
            /** @var \App\Settings\PrinterSettings $settings */
            $settings = app(PrinterSettings::class);
            $results = [];

            if (($settings->printer_type ?? 'usb') === 'usb') {
                // TEST USB PRINTERS
                $printService = new ReceiptPrintService();

                if (($settings->usb_printer_mode ?? 'single') === 'single') {
                    // Test single printer
                    $printerName = $settings->usb_printer_name ?? 'BAR';
                    try {
                        $testResult = $printService->testPrinter($printerName);
                        $results[] = $testResult['success']
                            ? __('messages.test_success_msg', ['division' => 'USB Printer (All)', 'name' => $printerName])
                            : __('messages.test_failed_msg', ['division' => 'USB Printer (All)', 'error' => $testResult['error']]);
                    } catch (\Exception $e) {
                        $results[] = __('messages.test_failed_msg', ['division' => 'USB Printer (All)', 'error' => $e->getMessage()]);
                    }
                } else {
                    // Test multiple printers
                    $printers = [
                        'Main' => $settings->usb_printer_name ?? 'BAR',
                        'Kitchen' => $settings->usb_kitchen_printer_name ?? $settings->usb_printer_name ?? 'BAR',
                        'Bar' => $settings->usb_bar_printer_name ?? $settings->usb_printer_name ?? 'BAR',
                        'General' => $settings->usb_general_printer_name ?? $settings->usb_printer_name ?? 'BAR',
                    ];

                    foreach ($printers as $division => $printerName) {
                        try {
                            $testResult = $printService->testPrinter($printerName);
                            $results[] = $testResult['success']
                                ? __('messages.test_success_msg', ['division' => $division, 'name' => $printerName])
                                : __('messages.test_failed_msg', ['division' => $division, 'error' => $testResult['error']]);
                        } catch (\Exception $e) {
                            $results[] = __('messages.test_failed_msg', ['division' => $division, 'error' => $e->getMessage()]);
                        }
                    }
                }
            } else {
                // TEST NETWORK PRINTERS - Untuk hosting, skip network test
                $results[] = "ℹ️ " . __('messages.network_test_skipped');
            }

            // ✅ FIX: Pastikan $results tidak null
            $message = __('messages.printer_test_results') . ":\n" . (!empty($results) ? implode("\n", $results) : "No tests performed");

            Notification::make()
                ->title(__('messages.printer_test_results'))
                ->body($message)
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title(__('messages.test_failed'))
                ->body('Printer test failed: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function generateTestContent(string $printerName): string
    {
        return __('messages.test_printer_name', ['name' => $printerName]) . "\n" .
            __('messages.connection_successful') . "\n" .
            "Time: " . now()->format('Y-m-d H:i:s') . "\n" .
            "========================\n" .
            __('messages.printer_config_working') . "\n\n\n";
    }
}

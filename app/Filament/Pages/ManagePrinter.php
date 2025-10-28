<?php

namespace App\Filament\Pages;

use App\Settings\PrinterSettings;
use UnitEnum;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Filament\Pages\SettingsPage;
use Filament\Support\Icons\Heroicon;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;

class ManagePrinter extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-printer';

    protected static string $settings = PrinterSettings::class;

    protected static string | UnitEnum | null $navigationGroup = 'Settings';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Printer Configuration')
                    ->description('Konfigurasi tipe printer dan koneksi')
                    ->schema([
                        Select::make('printer_type')
                            ->label('Tipe Printer')
                            ->options([
                                'network' => 'Network Printer (IP)',
                                'usb' => 'USB Printer',
                            ])
                            ->default('usb')
                            ->live()
                            ->required(),
                    ]),
                
                Section::make('USB Printer Settings')
                    ->description('Konfigurasi untuk USB Printer - Bisa gunakan satu printer untuk semua atau printer terpisah')
                    ->visible(fn ($get) => $get('printer_type') === 'usb')
                    ->schema([
                        Select::make('usb_printer_mode')
                            ->label('Mode USB Printer')
                            ->options([
                                'single' => 'Satu Printer untuk Semua Divisi',
                                'multiple' => 'Printer Terpisah per Divisi',
                            ])
                            ->default('single')
                            ->live()
                            ->required(),
                        
                        TextInput::make('usb_printer_name')
                            ->label('Nama Printer USB (Utama)')
                            ->placeholder('POS-58')
                            ->helperText('Digunakan jika mode "Satu Printer" atau sebagai fallback')
                            ->default('POS-58')
                            ->required(),
                            
                        TextInput::make('usb_kitchen_printer_name')
                            ->label('Nama Printer Dapur')
                            ->placeholder('Kitchen-POS-58')
                            ->helperText('Kosongkan jika menggunakan printer utama')
                            ->visible(fn ($get) => $get('usb_printer_mode') === 'multiple'),
                            
                        TextInput::make('usb_bar_printer_name')
                            ->label('Nama Printer Bar')
                            ->placeholder('Bar-POS-58')
                            ->helperText('Kosongkan jika menggunakan printer utama')
                            ->visible(fn ($get) => $get('usb_printer_mode') === 'multiple'),
                            
                        TextInput::make('usb_general_printer_name')
                            ->label('Nama Printer Umum')
                            ->placeholder('General-POS-58')
                            ->helperText('Kosongkan jika menggunakan printer utama')
                            ->visible(fn ($get) => $get('usb_printer_mode') === 'multiple'),
                    ]),
                
                Section::make('Network Printer Settings')
                    ->description('Konfigurasi untuk Network Printer')
                    ->visible(fn ($get) => $get('printer_type') === 'network')
                    ->schema([
                        TextInput::make('kitchen_printer_ip')
                            ->label('Kitchen Printer IP')
                            ->placeholder('192.168.1.100')
                            ->required(),
                        TextInput::make('kitchen_printer_port')
                            ->label('Kitchen Printer Port')
                            ->numeric()
                            ->placeholder('9100')
                            ->default(9100)
                            ->required(),
                            
                        TextInput::make('bar_printer_ip')
                            ->label('Bar Printer IP')
                            ->placeholder('192.168.1.101')
                            ->required(),
                        TextInput::make('bar_printer_port')
                            ->label('Bar Printer Port')
                            ->numeric()
                            ->placeholder('9100')
                            ->default(9100)
                            ->required(),
                            
                        TextInput::make('general_printer_ip')
                            ->label('General Printer IP')
                            ->placeholder('192.168.1.102')
                            ->required(),
                        TextInput::make('general_printer_port')
                            ->label('General Printer Port')
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
            Action::make('detectPrinters')
                ->label('Detect USB Printers')
                ->color('info')
                ->action('detectUsbPrinters')
                ->visible(fn () => $this->data['printer_type'] === 'usb' ?? false),
                
            Action::make('testPrinters')
                ->label('Test Printers')
                ->color('success')
                ->action('testPrinters'),
        ];
    }

    public function detectUsbPrinters()
    {
        try {
            $printService = new \App\Services\ReceiptPrintService();
            $detectedPrinters = $printService->detectUsbPrinters();
            
            if (empty($detectedPrinters)) {
                Notification::make()
                    ->title('No USB Printers Found')
                    ->body('Tidak ada printer USB yang terdeteksi. Pastikan printer sudah terinstall.')
                    ->warning()
                    ->send();
                return;
            }
            
            $message = "USB Printers Detected:\n" . implode("\n", $detectedPrinters);
            
            Notification::make()
                ->title('USB Printers Detection')
                ->body($message)
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            Notification::make()
                ->title('Detection Failed')
                ->body('Gagal mendeteksi printer: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function testPrinters()
    {
        try {
            /** @var PrinterSettings $settings */
            $settings = app(PrinterSettings::class);
            $results = [];

            if (($settings->printer_type ?? 'usb') === 'usb') {
                // TEST USB PRINTERS
                $printService = new \App\Services\ReceiptPrintService();
                
                if (($settings->usb_printer_mode ?? 'single') === 'single') {
                    // Test single printer
                    $printerName = $settings->usb_printer_name ?? 'POS-58';
                    try {
                        $testResult = $printService->testUsbPrinter($printerName);
                        $results[] = $testResult['success'] 
                            ? "✅ USB Printer (All): SUCCESS - {$printerName}"
                            : "❌ USB Printer (All): FAILED - {$testResult['error']}";
                    } catch (\Exception $e) {
                        $results[] = "❌ USB Printer (All): FAILED - {$e->getMessage()}";
                    }
                } else {
                    // Test multiple printers
                    $printers = [
                        'Main' => $settings->usb_printer_name ?? 'POS-58',
                        'Kitchen' => $settings->usb_kitchen_printer_name ?? $settings->usb_printer_name ?? 'POS-58',
                        'Bar' => $settings->usb_bar_printer_name ?? $settings->usb_printer_name ?? 'POS-58',
                        'General' => $settings->usb_general_printer_name ?? $settings->usb_printer_name ?? 'POS-58',
                    ];
                    
                    foreach ($printers as $division => $printerName) {
                        try {
                            $testResult = $printService->testUsbPrinter($printerName);
                            $results[] = $testResult['success']
                                ? "✅ {$division}: SUCCESS - {$printerName}"
                                : "❌ {$division}: FAILED - {$testResult['error']}";
                        } catch (\Exception $e) {
                            $results[] = "❌ {$division}: FAILED - {$e->getMessage()}";
                        }
                    }
                }

            } else {
                // TEST NETWORK PRINTERS
                $orderPrintService = new \App\Services\OrderPrintService();

                $printers = [
                    'Kitchen' => [
                        'ip' => $settings->kitchen_printer_ip ?? '192.168.1.100',
                        'port' => $settings->kitchen_printer_port ?? '9100'
                    ],
                    'Bar' => [
                        'ip' => $settings->bar_printer_ip ?? '192.168.1.101',
                        'port' => $settings->bar_printer_port ?? '9100'
                    ],
                    'General' => [
                        'ip' => $settings->general_printer_ip ?? '192.168.1.102',
                        'port' => $settings->general_printer_port ?? '9100'
                    ],
                ];

                foreach ($printers as $name => $printer) {
                    try {
                        $testContent = $this->generateTestContent($name);
                        $orderPrintService->printToNetworkPrinter($testContent, $printer['ip'], $printer['port']);
                        $results[] = "✅ {$name}: SUCCESS - {$printer['ip']}:{$printer['port']}";
                    } catch (\Exception $e) {
                        $results[] = "❌ {$name}: FAILED - {$e->getMessage()}";
                    }
                }
            }

            $message = "Test Results:\n" . implode("\n", $results);
            
            Notification::make()
                ->title('Printer Test Results')
                ->body($message)
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Test Failed')
                ->body('Printer test failed: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function generateTestContent(string $printerName): string
    {
        return "TEST {$printerName} PRINTER\n" .
            "Connection Successful\n" .
            "Time: " . now()->format('Y-m-d H:i:s') . "\n" .
            "========================\n" .
            "Printer configuration is working correctly!\n\n\n";
    }
}

<?php

namespace App\Services;

use App\Models\Sale;
use Filament\Notifications\Notification;
use Mike42\Escpos\Printer;
use App\Settings\GeneralSettings;
use App\Settings\PrinterSettings;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;

class ReceiptPrintService
{
    protected $printer;
    protected $sale;
    protected $connector;
    protected $printerInitialized = false;
    protected $printerSettings;
    protected $useWebhook;
    protected $isHostingEnvironment;

    public function __construct(?Sale $sale = null)
    {
        $this->sale = $sale;
        $this->printerSettings = app(GeneralSettings::class); // Changed to GeneralSettings or keeping as is? 
        // Wait, line 7 imported GeneralSettings, but line 27 used PrinterSettings originally. 
        // Let's check imports. original imports had PrinterSettings.
        // I should stick to original design or fix it.
        // Let's assume PrinterSettings is still needed for printer name.
        $this->printerSettings = app(\App\Settings\PrinterSettings::class);
        $this->useWebhook = config('app.use_webhook_printing', false);
        $this->isHostingEnvironment = $this->detectHostingEnvironment();

        Log::info("🖨️ ReceiptPrintService initialized", [
            'environment' => $this->isHostingEnvironment ? 'hosting' : 'local',
            'use_webhook' => $this->useWebhook
        ]);
    }

    /**
     * Deteksi apakah running di hosting environment
     */
    protected function detectHostingEnvironment(): bool
    {
        if (config('app.env') === 'production') {
            return true;
        }

        $host = request()->getHost() ?? parse_url(config('app.url'), PHP_URL_HOST) ?? '';

        $isLocal = in_array($host, ['localhost', '127.0.0.1', '::1', '0.0.0.0'])
            || str_contains($host, '.local')
            || str_contains($host, '.test')
            || str_contains($host, '192.168.')
            || $host === 'localhost'
            || empty($host);

        return !$isLocal;
    }

    /**
     * Print receipt untuk customer - WITH WEBHOOK SUPPORT
     */
    public function printReceipt(): bool
    {
        if (!$this->sale) {
            throw new \Exception('Sale data is required for receipt printing');
        }

        // **STRATEGI PRINT BERDASARKAN ENVIRONMENT**
        if ($this->isHostingEnvironment) {
            // DI HOSTING: PAKAI WEBHOOK
            return $this->printReceiptViaWebhook();
        } else {
            // DI LOCAL: PILIH WEBHOOK ATAU DIRECT
            if ($this->useWebhook) {
                return $this->printReceiptViaWebhook();
            } else {
                return $this->printReceiptDirect();
            }
        }
    }

    protected function printReceiptViaWebhook(): bool
    {
        try {
            $sale = $this->sale->load(['items.product', 'user', 'paymentMethod']);
            $settings = app(GeneralSettings::class); // Retrieve settings

            Log::info('🌐 Sending receipt print via webhook', [
                'sale_id' => $sale->id,
                'invoice' => $sale->invoice_number
            ]);

            // Generate receipt content
            $content = $this->generateReceiptContent($sale);
            $printerName = $this->printerSettings->usb_printer_name ?? 'KASIR';

            // Serialize sale data for payload
            $payload = [
                'sale' => $sale->toArray(),
                'items' => $sale->items->map(function ($item) {
                    $i = $item->toArray();
                    $i['product_name'] = $item->product->name ?? 'Unknown';
                    $i['quantity'] = $item->quantity + 0;
                    return $i;
                })->toArray(),
                'store' => [
                    'name' => $settings->app_name ?? config('app.name', 'Resto POS'),
                    'address' => $settings->company_address ?? '-',
                    'phone' => $settings->company_phone ?? '-'
                ]
            ];

            // Kirim via webhook
            $result = $this->sendWebhookPrint($content, $printerName, 'receipt', $sale->id, $payload);

            if ($result['success']) {
                Log::info('✅ Receipt print queued via webhook', ['job_id' => $result['job_id']]);
                return true;
            } else {
                throw new \Exception($result['error'] ?? 'Webhook print failed');
            }

        } catch (\Exception $e) {
            Log::error('❌ Webhook receipt print failed: ' . $e->getMessage());

            // Fallback ke direct print jika di local environment
            if (!$this->isHostingEnvironment) {
                Log::info('🔄 Falling back to direct receipt print');
                return $this->printReceiptDirect();
            }

            throw new \Exception("Gagal mencetak struk: " . $e->getMessage());
        }
    }

    /**
     * Send print job via webhook
     */
    protected function sendWebhookPrint(string $content, string $printer, string $type, ?int $saleId = null, array $payload = []): array
    {
        try {
            $webhookUrl = config('app.webhook_print_url');
            $secretKey = config('app.print_secret');

            if (!$webhookUrl) {
                throw new \Exception('Webhook URL not configured');
            }

            Log::info("🌐 Sending webhook print for {$type}", [
                'printer' => $printer,
                'webhook_url' => $webhookUrl,
                'content_length' => strlen($content)
            ]);

            $response = Http::timeout(15)
                ->withOptions(['verify' => false])
                ->withHeaders([
                    'X-Print-Secret' => $secretKey,
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'POS-System/1.0'
                ])
                ->post($webhookUrl, [
                    'content' => $content,
                    'payload' => $payload, // Send payload
                    'printer' => $printer,
                    'division' => 'receipt',
                    'sale_id' => $saleId,
                    'type' => $type
                ]);

            if ($response->successful()) {
                $result = $response->json();

                if ($result['success'] ?? false) {
                    return [
                        'success' => true,
                        'job_id' => $result['job_id'],
                        'message' => 'Print job queued via webhook'
                    ];
                } else {
                    throw new \Exception($result['error'] ?? 'Webhook returned error');
                }
            } else {
                throw new \Exception("HTTP {$response->status()}: " . substr($response->body(), 0, 200));
            }

        } catch (\Exception $e) {
            Log::error("❌ Webhook print failed: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Test receipt printing dengan webhook support
     */
    public function testReceiptPrint(): array
    {
        try {
            if ($this->isHostingEnvironment || $this->useWebhook) {
                // Test via webhook
                $testContent = $this->generateTestReceiptContent();
                $printerName = $this->printerSettings->usb_printer_name ?? 'KASIR';

                $result = $this->sendWebhookPrint($testContent, $printerName, 'test');

                if ($result['success']) {
                    return [
                        'success' => true,
                        'message' => 'Test receipt queued via webhook',
                        'job_id' => $result['job_id'],
                        'method' => 'webhook'
                    ];
                } else {
                    throw new \Exception($result['error']);
                }
            } else {
                // Test direct print
                return $this->testPrinter();
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'method' => $this->isHostingEnvironment ? 'webhook' : 'direct'
            ];
        }
    }

    /**
     * Generate test receipt content
     */
    protected function generateTestReceiptContent(): string
    {
        $content = "TEST STRUK\n";
        $content .= "===================\n";
        $content .= config('app.name', 'Toko Saya') . "\n";
        $content .= "===================\n";
        $content .= "No. Transaksi: TEST-001\n";
        $content .= "Tanggal: " . now()->format('d/m/Y H:i') . "\n";
        $content .= "Kasir: Test User\n";
        $content .= "===================\n";
        $content .= "ITEM TEST x1 - Rp10.000\n";
        $content .= "===================\n";
        $content .= "Subtotal: Rp10.000\n";
        $content .= "Pajak (10%): Rp1.000\n";
        $content .= "TOTAL: Rp11.000\n";
        $content .= "===================\n";
        $content .= "Bayar: Rp20.000\n";
        $content .= "Kembali: Rp9.000\n";
        $content .= "===================\n";
        $content .= "*** TEST SUCCESS ***\n\n\n";

        return $content;
    }

    /**
     * Generate receipt content untuk webhook
     */
    protected function generateReceiptContent(Sale $sale): string
    {
        $content = "";

        // Header
        $content .= "STRUK PEMBAYARAN\n";
        $content .= "========================\n\n";

        // Store Info
        // Store Info
        $settings = app(GeneralSettings::class);
        $content .= ($settings->app_name ?? config('app.name', 'Resto POS')) . "\n";
        $content .= "Telp: " . ($settings->company_phone ?? '-') . "\n";
        $content .= "Alamat: " . ($settings->company_address ?? '-') . "\n";
        $content .= "========================\n\n";

        // Sale Info
        $content .= "No. Transaksi: " . $sale->invoice_number . "\n";

        // Show Split Info
        if ($sale->split_number) {
            $content .= "** SPLIT BILL #" . $sale->split_number . " **\n";
        }

        $content .= "Tanggal: " . $sale->created_at->format('d/m/Y H:i') . "\n";
        $content .= "Kasir: " . ($sale->user->name ?? 'System') . "\n";
        $content .= "Customer: " . ($sale->customer_name ?? 'Umum') . "\n";
        $content .= "Tipe Order: " . $sale->order_type . "\n";
        $content .= "========================\n\n";

        // Items
        $content .= "ITEM YANG DIBELI:\n";
        $content .= "------------------------\n";

        foreach ($sale->items as $item) {
            $productName = $item->product->name ?? 'Unknown Product';

            if (strlen($productName) > 20) {
                $productName = substr($productName, 0, 17) . '...';
            }

            $content .= $productName . "\n";

            // TAMBAHKAN NOTES JIKA ADA
            if (!empty($item->notes)) {
                $content .= "  📝 " . $item->notes . "\n";
            }

            $quantityLine = sprintf(
                "  %-2d x %-10s",
                $item->quantity,
                "Rp" . number_format($item->unit_price, 0, ',', '.')
            );

            $subtotal = "Rp" . number_format($item->subtotal, 0, ',', '.');
            $content .= $quantityLine . $subtotal . "\n";
        }

        $content .= "------------------------\n\n";

        // Summary
        $content .= "Subtotal: " . $this->formatCurrency($sale->subtotal) . "\n";
        $content .= "Pajak (10%): " . $this->formatCurrency($sale->tax) . "\n";

        if ($sale->discount > 0) {
            $content .= "Potongan: -" . $this->formatCurrency($sale->discount) . "\n";
        }

        $content .= "TOTAL: " . $this->formatCurrency($sale->final_total) . "\n\n";

        // Payment Info
        $content .= "PEMBAYARAN:\n";
        $content .= "Metode: " . ($sale->paymentMethod->name ?? 'Cash') . "\n";
        $content .= "Bayar: " . $this->formatCurrency($sale->amount_paid) . "\n";

        if (($sale->paymentMethod->code ?? 'cash') === 'cash') {
            $change = $sale->amount_paid - $sale->final_total;
            if ($change > 0) {
                $content .= "Kembali: " . $this->formatCurrency($change) . "\n";
            }
        }

        $content .= "========================\n\n";

        // Footer
        $content .= "Terima kasih atas kunjungan Anda\n";
        $content .= "*** SELAMAT MENIKMATI ***\n\n\n";

        return $content;
    }

    /**
     * Print receipt direct (hanya untuk local)
     */
    protected function printReceiptDirect(): bool
    {
        $printer = null;
        $connector = null;

        try {
            $sale = $this->sale->load(['items.product', 'user', 'paymentMethod']);

            Log::info('🖨️ Starting direct receipt print', [
                'sale_id' => $sale->id,
                'invoice' => $sale->invoice_number,
                'printer_type' => $this->printerSettings->printer_type,
                'printer_name' => $this->printerSettings->usb_printer_name
            ]);

            // Initialize printer
            $connector = $this->createConnector();
            $printer = new Printer($connector);
            $printer->initialize();
            $this->printerInitialized = true;

            Log::info('✅ Printer initialized successfully');

            // Print sections
            $this->printHeader($printer, $sale);
            $this->printStoreInfo($printer);
            $this->printSaleInfo($printer, $sale);
            $this->printItems($printer, $sale);
            $this->printSummary($printer, $sale);
            $this->printPaymentInfo($printer, $sale);
            $this->printFooter($printer);

            $printer->cut();

            Log::info('✅ Receipt printed successfully');
            return true;

        } catch (\Exception $e) {
            Log::error('❌ Direct receipt print failed: ' . $e->getMessage());
            throw new \Exception("Gagal mencetak struk: " . $e->getMessage());

        } finally {
            $this->safeClose($printer);
        }
    }

    /**
     * Create printer connector dengan fallback
     */
    protected function createConnector()
    {
        $printerType = $this->printerSettings->printer_type;
        $printerName = $this->printerSettings->usb_printer_name ?? 'POS-58';

        Log::info('Creating printer connector', [
            'printer_type' => $printerType,
            'printer_name' => $printerName
        ]);

        try {
            switch ($printerType) {
                case 'usb':
                    Log::info('Using USB printer', ['printer_name' => $printerName]);

                    // Untuk Windows - gunakan WindowsPrintConnector
                    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                        return new WindowsPrintConnector($printerName);
                    } else {
                        return new FilePrintConnector("/dev/usb/lp0");
                    }

                case 'network':
                    $ip = $this->printerSettings->general_printer_ip ?? '192.168.1.100';
                    $port = $this->printerSettings->general_printer_port ?? 9100;
                    Log::info('Using Network printer', ['ip' => $ip, 'port' => $port]);
                    return new NetworkPrintConnector($ip, $port);

                case 'file':
                    Log::info('Using File printer (debug mode)');
                    return new FilePrintConnector("php://stdout");

                default:
                    throw new \Exception("Printer type not supported: " . $printerType);
            }
        } catch (\Exception $e) {
            Log::error('❌ Failed to create printer connector: ' . $e->getMessage());
            throw new \Exception("Tidak dapat terhubung ke printer '{$printerName}'. Pastikan printer tersedia dan terinstall.");
        }
    }

    /**
     * Safe close printer only
     */
    protected function safeClose($printer = null)
    {
        try {
            if ($printer instanceof Printer) {
                Log::info('Closing printer...');
                $printer->close();
                Log::info('Printer closed successfully');
            }
        } catch (\Exception $e) {
            Log::warning('Error closing printer: ' . $e->getMessage());
        }

        $this->printerInitialized = false;
    }


    protected function printHeader($printer, $sale)
    {
        if (!$printer) {
            throw new \Exception('Printer not initialized');
        }

        $printer->setJustification(Printer::JUSTIFY_CENTER);

        // Judul
        $printer->selectPrintMode(Printer::MODE_DOUBLE_HEIGHT | Printer::MODE_DOUBLE_WIDTH);
        $printer->text("STRUK PEMBAYARAN\n");
        $printer->selectPrintMode();

        $printer->text("========================\n");
        $printer->feed();
    }

    protected function printStoreInfo($printer)
    {
        if (!$printer) {
            throw new \Exception('Printer not initialized');
        }

        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $settings = app(GeneralSettings::class);
        $printer->text(($settings->app_name ?? config('app.name', 'Resto POS')) . "\n");
        $printer->text("Telp: " . ($settings->company_phone ?? '-') . "\n");
        $printer->text("Alamat: " . ($settings->company_address ?? '-') . "\n");
        $printer->text("========================\n");
        $printer->feed();
    }

    protected function printSaleInfo($printer, $sale)
    {
        if (!$printer) {
            throw new \Exception('Printer not initialized');
        }

        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text("No. Transaksi: " . $sale->invoice_number . "\n");

        if ($sale->split_number) {
            $printer->setEmphasis(true);
            $printer->text("** SPLIT BILL #" . $sale->split_number . " **\n");
            $printer->setEmphasis(false);
        }

        $printer->text("Tanggal: " . $sale->created_at->format('d/m/Y H:i') . "\n");
        $printer->text("Kasir: " . ($sale->user->name ?? 'System') . "\n");
        $printer->text("Customer: " . ($sale->customer_name ?? 'Umum') . "\n");
        $printer->text("Tipe Order: " . $sale->order_type . "\n");
        $printer->text("========================\n");
        $printer->feed();
    }

    protected function printItems($printer, $sale)
    {
        if (!$printer) {
            throw new \Exception('Printer not initialized');
        }

        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text("ITEM YANG DIBELI:\n");
        $printer->text("------------------------\n");

        foreach ($sale->items as $item) {
            $productName = $item->product->name ?? 'Unknown Product';

            if (strlen($productName) > 20) {
                $productName = substr($productName, 0, 17) . '...';
            }

            $printer->text($productName . "\n");

            // TAMBAHKAN NOTES JIKA ADA
            if (!empty($item->notes)) {
                $printer->setEmphasis(true);
                $printer->text("  📝 " . $item->notes . "\n");
                $printer->setEmphasis(false);
            }

            $quantityLine = sprintf(
                "  %-2d x %-10s",
                $item->quantity,
                "Rp" . number_format($item->unit_price, 0, ',', '.')
            );

            $subtotal = "Rp" . number_format($item->subtotal, 0, ',', '.');
            $printer->text($quantityLine . $subtotal . "\n");
        }

        $printer->text("------------------------\n");
        $printer->feed();
    }

    protected function printSummary($printer, $sale)
    {
        if (!$printer) {
            throw new \Exception('Printer not initialized');
        }

        $printer->setJustification(Printer::JUSTIFY_RIGHT);

        $printer->text("Subtotal: " . $this->formatCurrency($sale->subtotal) . "\n");
        $printer->text("Pajak (10%): " . $this->formatCurrency($sale->tax) . "\n");

        if ($sale->discount > 0) {
            $printer->text("Potongan: -" . $this->formatCurrency($sale->discount) . "\n");
        }

        $printer->setEmphasis(true);
        $printer->text("TOTAL: " . $this->formatCurrency($sale->final_total) . "\n");
        $printer->setEmphasis(false);
        $printer->feed();
    }

    protected function printPaymentInfo($printer, $sale)
    {
        if (!$printer) {
            throw new \Exception('Printer not initialized');
        }

        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text("PEMBAYARAN:\n");
        $printer->text("Metode: " . ($sale->paymentMethod->name ?? 'Cash') . "\n");
        $printer->text("Bayar: " . $this->formatCurrency($sale->amount_paid) . "\n");

        if (($sale->paymentMethod->code ?? 'cash') === 'cash') {
            $change = $sale->amount_paid - $sale->final_total;
            if ($change > 0) {
                $printer->text("Kembali: " . $this->formatCurrency($change) . "\n");
            }
        }

        $printer->text("========================\n");
        $printer->feed();
    }

    protected function printFooter($printer)
    {
        if (!$printer) {
            throw new \Exception('Printer not initialized');
        }

        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("Terima kasih atas kunjungan Anda\n");
        $printer->setEmphasis(true);
        $printer->text("*** SELAMAT MENIKMATI ***\n");
        $printer->setEmphasis(false);
        $printer->feed(2);
    }

    protected function formatCurrency($amount)
    {
        return "Rp" . number_format($amount, 0, ',', '.');
    }

    /**
     * Get available printers list
     */
    public function getAvailablePrinters(): array
    {
        $printers = [];

        try {
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                // Windows - get printer list
                exec('wmic printer get name', $output, $returnCode);

                if ($returnCode === 0 && !empty($output)) {
                    foreach ($output as $line) {
                        $printerName = trim($line);
                        if ($printerName && !str_contains($printerName, 'Name') && $printerName !== '') {
                            $printers[] = $printerName;
                        }
                    }
                }
            } else {
                // Linux - get printer list
                exec('lpstat -p 2>/dev/null', $output, $returnCode);

                if ($returnCode === 0 && !empty($output)) {
                    foreach ($output as $line) {
                        if (preg_match('/printer (\S+)/', $line, $matches)) {
                            $printers[] = $matches[1];
                        }
                    }
                }
            }

            Log::info('Available printers: ' . implode(', ', $printers));
            return $printers;

        } catch (\Exception $e) {
            Log::error('Error getting printers: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Test printer connection
     */
    public function testPrinter(): array
    {
        $printer = null;

        try {
            $printers = $this->getAvailablePrinters();
            $targetPrinter = $this->printerSettings->usb_printer_name;

            Log::info('Testing printer connection', [
                'target_printer' => $targetPrinter,
                'available_printers' => $printers
            ]);

            // Cek apakah printer tersedia
            if (!in_array($targetPrinter, $printers)) {
                return [
                    'success' => false,
                    'error' => "Printer '{$targetPrinter}' tidak ditemukan. Printers yang tersedia: " . implode(', ', $printers)
                ];
            }

            $connector = $this->createConnector();
            $printer = new Printer($connector);
            $printer->initialize();

            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->text("TEST PRINT\n");
            $printer->text("==========\n");
            $printer->text("Printer: " . $targetPrinter . "\n");
            $printer->text("Time: " . now()->format('Y-m-d H:i:s') . "\n");
            $printer->text("Status: OK\n");
            $printer->text("==========\n");

            $printer->cut();

            Log::info('✅ Printer test successful');

            return [
                'success' => true,
                'message' => 'Test print berhasil dikirim ke printer ' . $targetPrinter
            ];

        } catch (\Exception $e) {
            Log::error('❌ Printer test failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        } finally {
            $this->safeClose($printer);
        }
    }

    /**
     * Print raw content ke printer - untuk order printing
     */
    public function printRawContent(string $content, string $printerName): bool
    {
        $printer = null;
        $connector = null;

        try {
            Log::info("🖨️ Printing raw content", [
                'printer' => $printerName,
                'content_length' => strlen($content)
            ]);

            // Create connector berdasarkan OS
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $connector = new WindowsPrintConnector($printerName);
            } else {
                $connector = new FilePrintConnector("/dev/usb/lp0");
            }

            $printer = new Printer($connector);
            $printer->initialize();

            // Print content as-is
            $printer->text($content);
            $printer->cut();

            Log::info("✅ Raw content printed successfully");
            return true;

        } catch (\Exception $e) {
            Log::error("❌ Raw content print failed: " . $e->getMessage());
            throw new \Exception("Gagal print order: " . $e->getMessage());
        } finally {
            // Safe close
            if ($printer instanceof Printer) {
                try {
                    $printer->close();
                } catch (\Exception $e) {
                    Log::warning('Error closing printer: ' . $e->getMessage());
                }
            }
        }
    }

    /**
     * Method untuk manual print dari tombol di modal preview
     */
    public function manualPrintReceipt()
    {
        // Gunakan currentSaleIdForPrint jika available, fallback ke saleId
        $saleIdToPrint = $this->currentSaleIdForPrint ?? $this->saleId;

        logger('Manual Print Receipt - Sale ID:', [
            'currentSaleIdForPrint' => $this->currentSaleIdForPrint,
            'saleId' => $this->saleId,
            'saleIdToPrint' => $saleIdToPrint
        ]);

        if (!$saleIdToPrint) {
            Notification::make()
                ->title('Error')
                ->body('Tidak ada transaksi yang dipilih untuk dicetak.')
                ->danger()
                ->send();
            return;
        }

        $this->isPrinting = true;

        try {
            $sale = Sale::with(['items.product', 'user', 'paymentMethod'])->findOrFail($saleIdToPrint);

            // Gunakan ReceiptPrintService yang sudah ada
            $printService = new ReceiptPrintService($sale);

            // Print receipt - service akan otomatis pilih webhook atau direct berdasarkan environment
            $result = $printService->printReceipt();

            if ($result) {
                Notification::make()
                    ->title('Struk Berhasil Dicetak')
                    ->body('Struk telah dikirim ke printer.')
                    ->success()
                    ->send();

                // Kirim event untuk update UI
                $this->dispatch('printCompleted');
            } else {
                throw new \Exception('Print gagal tanpa error message');
            }

        } catch (\Exception $e) {
            logger('Manual print receipt failed: ' . $e->getMessage());

            Notification::make()
                ->title('Gagal Mencetak Struk')
                ->body($e->getMessage())
                ->warning()
                ->send();

            $this->dispatch('printFailed');
        } finally {
            $this->isPrinting = false;
        }
    }

    /**
     * Handler ketika print selesai
     */
    public function handlePrintCompleted()
    {
        logger('Print completed received in PosPaymentModal');
        $this->isPrinting = false;

        Notification::make()
            ->title('Print Selesai')
            ->body('Struk berhasil dicetak.')
            ->success()
            ->send();
    }

    /**
     * Handler ketika print gagal
     */
    public function handlePrintFailed()
    {
        logger('Print failed received in PosPaymentModal');
        $this->isPrinting = false;

        Notification::make()
            ->title('Print Gagal')
            ->body('Gagal mencetak struk. Periksa koneksi printer.')
            ->danger()
            ->send();
    }

}
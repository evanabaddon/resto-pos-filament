<?php

namespace App\Services;

use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\Printer;
use App\Models\Sale;
use App\Settings\PrinterSettings;
use Illuminate\Support\Facades\Log;

class ReceiptPrintService
{
    protected $printer;
    protected $sale;
    protected $connector;
    protected $printerInitialized = false;
    protected $printerSettings;

    public function __construct(?Sale $sale = null)
    {
        $this->sale = $sale;
        $this->printerSettings = app(PrinterSettings::class);
    }

    /**
     * Print receipt untuk customer - FIXED VERSION
     */
    public function printReceipt(): bool
    {
        if (!$this->sale) {
            throw new \Exception('Sale data is required for receipt printing');
        }

        $printer = null;
        $connector = null;

        try {
            $sale = $this->sale->load(['items.product', 'user', 'paymentMethod']);
            
            Log::info('🖨️ Starting receipt print', [
                'sale_id' => $sale->id,
                'invoice' => $sale->invoice_number,
                'printer_type' => $this->printerSettings->printer_type,
                'printer_name' => $this->printerSettings->usb_printer_name
            ]);
            
            // Initialize printer berdasarkan settings
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
            Log::error('❌ Print receipt failed: ' . $e->getMessage());
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

    // ... (method printHeader, printStoreInfo, dll tetap sama)
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
        $printer->text(config('app.name', 'Toko Saya') . "\n");
        $printer->text("Telp: 08123456789\n");
        $printer->text("Alamat: Jl. Contoh No. 123\n");
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
            
            $quantityLine = sprintf("  %-2d x %-10s", 
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
            $printer->text("Diskon: -" . $this->formatCurrency($sale->discount) . "\n");
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
}
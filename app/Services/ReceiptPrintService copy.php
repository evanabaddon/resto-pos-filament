<?php

namespace App\Services;

use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\Printer;
use Mike42\Escpos\EscposImage;
use App\Models\Sale;
use Illuminate\Support\Facades\Log;

class ReceiptPrintService
{
    protected $printer;
    protected $sale;
    protected $connector;

    public function __construct(?Sale $sale = null)
    {
        $this->sale = $sale;
        Log::info('ReceiptPrintService initialized', ['sale_id' => $sale->id, 'invoice' => $sale->invoice_number]);
        $this->initializePrinter();
    }

    protected function initializePrinter()
    {
        try {
            $connectorType = config('printing.printer.connector', 'windows');
            
            Log::info('Initializing printer', ['connector_type' => $connectorType]);
            
            switch ($connectorType) {
                case 'windows':
                    $printerName = config('printing.printer.name', 'POS-58');
                    Log::info('Using Windows printer', ['printer_name' => $printerName]);
                    $this->connector = new WindowsPrintConnector($printerName);
                    break;
                    
                case 'network':
                    $ip = config('printing.printer.ip', '192.168.1.100');
                    $port = config('printing.printer.port', 9100);
                    Log::info('Using Network printer', ['ip' => $ip, 'port' => $port]);
                    $this->connector = new NetworkPrintConnector($ip, $port);
                    break;
                    
                case 'file':
                    Log::info('Using File printer (debug mode)');
                    $this->connector = new FilePrintConnector("php://stdout");
                    break;
                    
                default:
                    throw new \Exception("Printer connector type not supported: " . $connectorType);
            }

            $this->printer = new Printer($this->connector);
            Log::info('Printer initialized successfully');
            
        } catch (\Exception $e) {
            Log::error('Printer initialization failed: ' . $e->getMessage());
            // Jangan throw exception di constructor, biarkan printer null
            $this->printer = null;
            $this->connector = null;
        }
    }

    public function printReceipt()
    {
        // Cek jika printer tidak terinisialisasi
        if (!$this->printer) {
            Log::error('Cannot print - Printer not initialized');
            throw new \Exception('Printer tidak terinisialisasi. Periksa koneksi printer.');
        }

        try {
            $sale = $this->sale->load(['items.product', 'user', 'paymentMethod']);
            
            Log::info('Starting receipt print', [
                'sale_id' => $sale->id,
                'invoice' => $sale->invoice_number,
                'items_count' => $sale->items->count()
            ]);
            
            /* Initialize printer */
            $this->printer->initialize();
            
            /* Header */
            $this->printHeader($sale);
            
            /* Store info */
            $this->printStoreInfo();
            
            /* Sale info */
            $this->printSaleInfo($sale);
            
            /* Items */
            $this->printItems($sale);
            
            /* Summary */
            $this->printSummary($sale);
            
            /* Payment info */
            $this->printPaymentInfo($sale);
            
            /* Footer */
            $this->printFooter();
            
            /* Cut paper */
            $this->printer->cut();
            
            /* Close connection */
            $this->printer->close();
            
            // Set printer ke null setelah close untuk hindari double close
            $this->printer = null;
            
            Log::info('Receipt printed successfully');
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Print receipt failed: ' . $e->getMessage());
            
            // Close printer jika masih terbuka
            if ($this->printer) {
                try {
                    $this->printer->close();
                } catch (\Exception $closeException) {
                    Log::error('Error closing printer: ' . $closeException->getMessage());
                }
                $this->printer = null;
            }
            
            throw $e;
        }
    }

    protected function printHeader($sale)
    {
        $this->printer->setJustification(Printer::JUSTIFY_CENTER);
        
        // Judul
        $this->printer->selectPrintMode(Printer::MODE_DOUBLE_HEIGHT | Printer::MODE_DOUBLE_WIDTH);
        $this->printer->text("STRUK PEMBAYARAN\n");
        $this->printer->selectPrintMode();
        
        $this->printer->text("========================\n");
        $this->printer->feed();
    }

    protected function printStoreInfo()
    {
        $this->printer->setJustification(Printer::JUSTIFY_LEFT);
        $this->printer->text(config('app.name', 'Toko Saya') . "\n");
        $this->printer->text("Telp: 08123456789\n");
        $this->printer->text("Alamat: Jl. Contoh No. 123\n");
        $this->printer->text("========================\n");
        $this->printer->feed();
    }

    protected function printSaleInfo($sale)
    {
        $this->printer->setJustification(Printer::JUSTIFY_LEFT);
        $this->printer->text("No. Transaksi: " . $sale->invoice_number . "\n");
        $this->printer->text("Tanggal: " . $sale->created_at->format('d/m/Y H:i') . "\n");
        $this->printer->text("Kasir: " . ($sale->user->name ?? 'System') . "\n");
        $this->printer->text("Customer: " . ($sale->customer_name ?? 'Umum') . "\n");
        $this->printer->text("Tipe Order: " . $sale->order_type . "\n");
        $this->printer->text("========================\n");
        $this->printer->feed();
    }

    protected function printItems($sale)
    {
        $this->printer->setJustification(Printer::JUSTIFY_LEFT);
        $this->printer->text("ITEM YANG DIBELI:\n");
        $this->printer->text("------------------------\n");
        
        foreach ($sale->items as $item) {
            $productName = $item->product->name;
            
            // Potong nama produk jika terlalu panjang
            if (strlen($productName) > 20) {
                $productName = substr($productName, 0, 17) . '...';
            }
            
            $this->printer->text($productName . "\n");
            
            $quantityLine = sprintf("  %-2d x %-10s", 
                $item->quantity, 
                "Rp" . number_format($item->unit_price, 0, ',', '.')
            );
            
            $subtotal = "Rp" . number_format($item->subtotal, 0, ',', '.');
            $this->printer->text($quantityLine . $subtotal . "\n");
        }
        
        $this->printer->text("------------------------\n");
        $this->printer->feed();
    }

    protected function printSummary($sale)
    {
        $this->printer->setJustification(Printer::JUSTIFY_RIGHT);
        
        $this->printer->text("Subtotal: " . $this->formatCurrency($sale->subtotal) . "\n");
        $this->printer->text("Pajak (10%): " . $this->formatCurrency($sale->tax) . "\n");
        
        if ($sale->discount > 0) {
            $this->printer->text("Diskon: -" . $this->formatCurrency($sale->discount) . "\n");
        }
        
        $this->printer->setEmphasis(true);
        $this->printer->text("TOTAL: " . $this->formatCurrency($sale->final_total) . "\n");
        $this->printer->setEmphasis(false);
        $this->printer->feed();
    }

    protected function printPaymentInfo($sale)
    {
        $this->printer->setJustification(Printer::JUSTIFY_LEFT);
        $this->printer->text("PEMBAYARAN:\n");
        $this->printer->text("Metode: " . ($sale->paymentMethod->name ?? 'Cash') . "\n");
        $this->printer->text("Bayar: " . $this->formatCurrency($sale->amount_paid) . "\n");
        
        if (($sale->paymentMethod->code ?? 'cash') === 'cash') {
            $change = $sale->amount_paid - $sale->final_total;
            $this->printer->text("Kembali: " . $this->formatCurrency($change) . "\n");
        }
        
        $this->printer->text("========================\n");
        $this->printer->feed();
    }

    protected function printFooter()
    {
        $this->printer->setJustification(Printer::JUSTIFY_CENTER);
        $this->printer->text("Terima kasih atas kunjungan Anda\n");
        $this->printer->setEmphasis(true);
        $this->printer->text("*** SELAMAT MENIKMATI ***\n");
        $this->printer->setEmphasis(false);
        $this->printer->feed(2);
    }

    protected function formatCurrency($amount)
    {
        return "Rp" . number_format($amount, 0, ',', '.');
    }

    public function __destruct()
    {
        try {
            // Hanya close printer jika masih terbuka
            if ($this->printer) {
                Log::info('Closing printer in destructor');
                $this->printer->close();
                $this->printer = null;
            }
            
            // Close connector jika ada
            if ($this->connector) {
                // Untuk beberapa connector mungkin perlu close secara explicit
                $this->connector = null;
            }
        } catch (\Exception $e) {
            // Log error tapi jangan throw exception di destructor
            Log::error('Error in ReceiptPrintService destructor: ' . $e->getMessage());
        }
    }
}
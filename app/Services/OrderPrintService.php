<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\Product;
use App\Settings\PrinterSettings;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class OrderPrintService
{
    protected $printerConfig;
    protected $receiptPrintService;

    public function __construct()
    {
        $this->printerConfig = $this->loadPrinterConfig();
        $this->receiptPrintService = new ReceiptPrintService();
    }

    /**
     * Load printer configuration dengan fallback
     */
    protected function loadPrinterConfig(): array
    {
        try {
            $settings = app(PrinterSettings::class);
            return [
                'printer_type' => $settings->printer_type ?? 'usb',
                'usb_printer_mode' => $settings->usb_printer_mode ?? 'single',
                'usb_printer_name' => $settings->usb_printer_name ?? 'BAR',
                'usb_kitchen_printer_name' => $settings->usb_kitchen_printer_name ?? 'BAR',
                'usb_bar_printer_name' => $settings->usb_bar_printer_name ?? 'BAR',
                'usb_general_printer_name' => $settings->usb_general_printer_name ?? 'BAR',
            ];
        } catch (\Exception $e) {
            Log::warning('PrinterSettings not loaded, using defaults: ' . $e->getMessage());
            return $this->getDefaultConfig();
        }
    }

    /**
     * Default configuration fallback
     */
    protected function getDefaultConfig(): array
    {
        return [
            'printer_type' => 'usb',
            'usb_printer_mode' => 'single',
            'usb_printer_name' => 'BAR',
            'usb_kitchen_printer_name' => 'BAR',
            'usb_bar_printer_name' => 'BAR',
            'usb_general_printer_name' => 'BAR',
        ];
    }

    /**
     * PRINT ORDER BY PRODUCT TYPE - FIXED VERSION
     */
    public function printOrderByProductType(Sale $sale): array
    {
        try {
            Log::info("🖨️ Starting order print by product type", [
                'sale_id' => $sale->id,
                'invoice' => $sale->invoice_number,
                'items_count' => $sale->items->count()
            ]);

            // Kelompokkan items berdasarkan tipe produk
            $kitchenItems = [];
            $barItems = [];
            $generalItems = [];

            foreach ($sale->items as $item) {
                $productType = $item->product->type ?? 'general';
                
                switch ($productType) {
                    case 'produced':
                        $kitchenItems[] = $item;
                        break;
                    case 'bar':
                        $barItems[] = $item;
                        break;
                    default:
                        $generalItems[] = $item;
                        break;
                }
            }

            $printResults = [];

            // Generate dan print content untuk masing-masing divisi
            if (!empty($kitchenItems)) {
                $content = $this->generateKitchenOrderContent($sale, $kitchenItems);
                $printerName = $this->getPrinterNameForDivision('kitchen');
                $printResults['kitchen'] = $this->sendToPrinter($content, $printerName, 'Kitchen');
            }

            if (!empty($barItems)) {
                $content = $this->generateBarOrderContent($sale, $barItems);
                $printerName = $this->getPrinterNameForDivision('bar');
                $printResults['bar'] = $this->sendToPrinter($content, $printerName, 'Bar');
            }

            if (!empty($generalItems)) {
                $content = $this->generateGeneralOrderContent($sale, $generalItems);
                $printerName = $this->getPrinterNameForDivision('general');
                $printResults['general'] = $this->sendToPrinter($content, $printerName, 'General');
            }

            Log::info("✅ Order printing completed for sale #{$sale->invoice_number}", $printResults);
            return $printResults;

        } catch (\Exception $e) {
            Log::error("❌ Order printing failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Send content ke printer - FIXED VERSION
     */
    protected function sendToPrinter(string $content, string $printerName, string $division): array
    {
        try {
            Log::info("Sending to printer", [
                'division' => $division,
                'printer' => $printerName,
                'content_length' => strlen($content)
            ]);

            // Untuk semua environment, gunakan ReceiptPrintService yang sudah diperbaiki
            $this->receiptPrintService->printRawContent($content, $printerName);
            
            return [
                'success' => true,
                'type' => 'direct',
                'printer' => $printerName,
                'division' => $division
            ];
            
        } catch (\Exception $e) {
            Log::error("❌ {$division} print failed: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'division' => $division,
                'printer' => $printerName
            ];
        }
    }

    /**
     * Method untuk print raw content (tambahkan di ReceiptPrintService)
     * Ini akan kita buat di ReceiptPrintService
     */
    public function printRawContent(string $content, string $printerName): bool
    {
        $printer = null;

        try {
            Log::info("🖨️ Printing raw content", [
                'printer' => $printerName,
                'content_length' => strlen($content)
            ]);

            // Create connector
            $connector = new \Mike42\Escpos\PrintConnectors\WindowsPrintConnector($printerName);
            $printer = new \Mike42\Escpos\Printer($connector);
            $printer->initialize();
            
            // Print content
            $printer->text($content);
            $printer->cut();
            
            Log::info("✅ Raw content printed successfully");
            return true;
            
        } catch (\Exception $e) {
            Log::error("❌ Raw content print failed: " . $e->getMessage());
            throw $e;
        } finally {
            if ($printer instanceof \Mike42\Escpos\Printer) {
                try {
                    $printer->close();
                } catch (\Exception $e) {
                    Log::warning('Error closing printer: ' . $e->getMessage());
                }
            }
        }
    }

    /**
     * Dapatkan nama printer berdasarkan divisi
     */
    protected function getPrinterNameForDivision(string $division): string
    {
        $mode = $this->printerConfig['usb_printer_mode'];
        $mainPrinter = $this->printerConfig['usb_printer_name'];
        
        if ($mode === 'single') {
            return $mainPrinter; // Semua ke printer utama
        }
        
        // Multiple printer mode
        return match($division) {
            'kitchen' => $this->printerConfig['usb_kitchen_printer_name'],
            'bar' => $this->printerConfig['usb_bar_printer_name'],
            'general' => $this->printerConfig['usb_general_printer_name'],
            default => $mainPrinter
        };
    }

    /**
     * PRINT HANYA ITEM BARU - UNTUK UPDATE ORDER
     */
    public function printNewItemsOnly(Sale $sale, array $newItems): array
    {
        try {
            Log::info("🔄 Printing new items only for sale #{$sale->invoice_number}", [
                'new_items_count' => count($newItems)
            ]);

            // Kelompokkan new items
            $kitchenNewItems = [];
            $barNewItems = [];
            $generalNewItems = [];

            foreach ($newItems as $itemData) {
                $product = Product::find($itemData['product_id']);
                if (!$product) continue;

                $item = (object)[
                    'product' => $product,
                    'quantity' => $itemData['quantity'],
                    'product_id' => $itemData['product_id']
                ];

                switch ($product->type) {
                    case 'produced':
                        $kitchenNewItems[] = $item;
                        break;
                    case 'bar':
                        $barNewItems[] = $item;
                        break;
                    default:
                        $generalNewItems[] = $item;
                        break;
                }
            }

            $printResults = [];

            // Print hanya item baru
            if (!empty($kitchenNewItems)) {
                $content = $this->generateNewItemsContent($sale, $kitchenNewItems, 'DAPUR');
                $printerName = $this->getPrinterNameForDivision('kitchen');
                $printResults['kitchen'] = $this->sendToPrinter($content, $printerName, 'Kitchen Update');
            }

            if (!empty($barNewItems)) {
                $content = $this->generateNewItemsContent($sale, $barNewItems, 'BAR');
                $printerName = $this->getPrinterNameForDivision('bar');
                $printResults['bar'] = $this->sendToPrinter($content, $printerName, 'Bar Update');
            }

            if (!empty($generalNewItems)) {
                $content = $this->generateNewItemsContent($sale, $generalNewItems, 'UMUM');
                $printerName = $this->getPrinterNameForDivision('general');
                $printResults['general'] = $this->sendToPrinter($content, $printerName, 'General Update');
            }

            Log::info("✅ New items printing completed", $printResults);
            return $printResults;

        } catch (\Exception $e) {
            Log::error("❌ New items printing failed: " . $e->getMessage());
            throw $e;
        }
    }

    // ==================== GENERATE CONTENT METHODS ====================

    /**
     * Generate content untuk kitchen order
     */
    protected function generateKitchenOrderContent(Sale $sale, array $items): string
    {
        $content = "ORDER DAPUR\n";
        $content .= "========================\n";
        $content .= "No: {$sale->invoice_number}\n";
        $content .= "Customer: " . ($sale->customer_name ?? 'Umum') . "\n";
        $content .= "Time: " . now()->format('H:i:s') . "\n";
        $content .= "Type: " . ($sale->order_type ?? 'Dine In') . "\n";
        $content .= "========================\n";
        $content .= "ITEMS:\n";
        
        foreach ($items as $item) {
            $productName = $item->product->name ?? 'Unknown';
            if (strlen($productName) > 20) {
                $productName = substr($productName, 0, 17) . '...';
            }
            $content .= "{$productName} x{$item->quantity}\n";
            
            // Tambah notes jika ada
            if (!empty($item->note)) {
                $content .= "  Note: {$item->note}\n";
            }
        }
        
        $content .= "========================\n";
        $content .= "*** HARAP DIPROSES ***\n";
        $content .= "========================\n\n\n";
        
        return $content;
    }

    /**
     * Generate content untuk bar order
     */
    protected function generateBarOrderContent(Sale $sale, array $items): string
    {
        $content = "ORDER BAR\n";
        $content .= "========================\n";
        $content .= "No: {$sale->invoice_number}\n";
        $content .= "Customer: " . ($sale->customer_name ?? 'Umum') . "\n";
        $content .= "Time: " . now()->format('H:i:s') . "\n";
        $content .= "Type: " . ($sale->order_type ?? 'Dine In') . "\n";
        $content .= "========================\n";
        $content .= "MINUMAN:\n";
        
        foreach ($items as $item) {
            $productName = $item->product->name ?? 'Unknown';
            if (strlen($productName) > 20) {
                $productName = substr($productName, 0, 17) . '...';
            }
            $content .= "{$productName} x{$item->quantity}\n";
            
            if (!empty($item->note)) {
                $content .= "  Note: {$item->note}\n";
            }
        }
        
        $content .= "========================\n";
        $content .= "*** READY TO SERVE ***\n";
        $content .= "========================\n\n\n";
        
        return $content;
    }

    /**
     * Generate content untuk general order
     */
    protected function generateGeneralOrderContent(Sale $sale, array $items): string
    {
        $content = "ORDER UMUM\n";
        $content .= "========================\n";
        $content .= "No: {$sale->invoice_number}\n";
        $content .= "Customer: " . ($sale->customer_name ?? 'Umum') . "\n";
        $content .= "Time: " . now()->format('H:i:s') . "\n";
        $content .= "Type: " . ($sale->order_type ?? 'Dine In') . "\n";
        $content .= "========================\n";
        $content .= "ITEMS:\n";
        
        foreach ($items as $item) {
            $productName = $item->product->name ?? 'Unknown';
            if (strlen($productName) > 20) {
                $productName = substr($productName, 0, 17) . '...';
            }
            $content .= "{$productName} x{$item->quantity}\n";
            
            if (!empty($item->note)) {
                $content .= "  Note: {$item->note}\n";
            }
        }
        
        $content .= "========================\n";
        $content .= "** ITEM READY **\n";
        $content .= "========================\n\n\n";
        
        return $content;
    }

    /**
     * Generate content untuk item baru saja
     */
    protected function generateNewItemsContent(Sale $sale, array $newItems, string $division): string
    {
        $content = "TAMBAHAN ORDER {$division}\n";
        $content .= "========================\n";
        $content .= "No: {$sale->invoice_number}\n";
        $content .= "Customer: " . ($sale->customer_name ?? 'Umum') . "\n";
        $content .= "Time: " . now()->format('H:i:s') . "\n";
        $content .= "Type: " . ($sale->order_type ?? 'Dine In') . "\n";
        $content .= "========================\n";
        $content .= "TAMBAHAN:\n";
        
        foreach ($newItems as $item) {
            $productName = $item->product->name ?? 'Unknown';
            if (strlen($productName) > 20) {
                $productName = substr($productName, 0, 17) . '...';
            }
            $content .= "+ {$productName} x{$item->quantity}\n";
        }
        
        $content .= "========================\n";
        $content .= "*** TAMBAHAN ORDER ***\n";
        $content .= "========================\n\n\n";
        
        return $content;
    }

    /**
     * Test order printing
     */
    public function testOrderPrinting(): array
    {
        try {
            $testContent = "TEST ORDER PRINTING\n";
            $testContent .= "===================\n";
            $testContent .= "Time: " . now()->format('Y-m-d H:i:s') . "\n";
            $testContent .= "Printer: " . $this->printerConfig['usb_printer_name'] . "\n";
            $testContent .= "Status: OK\n";
            $testContent .= "===================\n\n\n";

            $printerName = $this->printerConfig['usb_printer_name'];
            $this->printRawContent($testContent, $printerName);

            return [
                'success' => true,
                'message' => 'Test order printing successful'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
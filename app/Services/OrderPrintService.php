<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\Product;
use App\Settings\PrinterSettings;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class OrderPrintService
{
    protected $printService;
    protected $printerConfig;

    public function __construct()
    {
        $this->printerConfig = $this->loadPrinterConfig();
        
        // Tentukan service berdasarkan environment
        if (app()->environment('production')) {
            $this->printService = new WindowsPrintService(); // Untuk hosting
        } else {
            $this->printService = new ReceiptPrintService(); // Untuk local development
        }
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
     * PRINT ORDER BY PRODUCT TYPE - UNTUK HOSTING & LOCAL
     */
    public function printOrderByProductType(Sale $sale): array
    {
        try {
            // Kelompokkan items berdasarkan tipe produk
            $kitchenItems = [];
            $barItems = [];
            $generalItems = [];

            foreach ($sale->items as $item) {
                $productType = $item->product->type;
                
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
                $printResults['kitchen'] = $this->sendWebhookPrint($content, 'BAR', 'Kitchen');
            }

            if (!empty($barItems)) {
                $content = $this->generateBarOrderContent($sale, $barItems);
                $printerName = $this->getPrinterNameForDivision('bar');
                $printResults['bar'] = $this->sendToPrinter($content, $printerName, 'Bar');
                $printResults['bar'] = $this->sendWebhookPrint($content, 'BAR', 'Bar');
            }

            if (!empty($generalItems)) {
                $content = $this->generateGeneralOrderContent($sale, $generalItems);
                $printerName = $this->getPrinterNameForDivision('general');
                $printResults['general'] = $this->sendToPrinter($content, $printerName, 'General');
                $printResults['general'] = $this->sendWebhookPrint($content, 'BAR', 'General');
            }

            Log::info("Order printing completed for sale #{$sale->invoice_number}", $printResults);
            return $printResults;

        } catch (\Exception $e) {
            Log::error("Order printing failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Send print job via webhook
     */
    private function sendWebhookPrint(string $content, string $printer, string $division): array
    {
        try {
            $response = Http::withHeaders([
                'X-Print-Secret' => config('app.print_secret'),
            ])->post('https://pos.suralaya.id/webhook/print', [
                'content' => $content,
                'printer' => $printer,
                'division' => $division
            ]);

            $result = $response->json();
            
            if ($response->successful() && $result['success']) {
                return [
                    'success' => true,
                    'job_id' => $result['job_id'],
                    'message' => 'Print job queued'
                ];
            } else {
                throw new \Exception($result['error'] ?? 'Webhook failed');
            }
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Send content ke printer (compatible hosting & local)
     */
    protected function sendToPrinter(string $content, string $printerName, string $division): array
    {
        try {
            if (app()->environment('production')) {
                // Untuk hosting - kirim ke Windows print server
                $result = $this->printService->printToWindows($content, $printerName);
                return [
                    'success' => $result['success'] ?? false,
                    'type' => 'remote',
                    'printer' => $printerName,
                    'division' => $division,
                    'message' => $result['message'] ?? 'Sent to Windows print server'
                ];
            } else {
                // Untuk local development - print langsung
                $this->printService->printToUsbPrinter($content, $printerName);
                return [
                    'success' => true,
                    'type' => 'local',
                    'printer' => $printerName,
                    'division' => $division
                ];
            }
            
        } catch (\Exception $e) {
            Log::error("{$division} print failed: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'division' => $division
            ];
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

            return $printResults;

        } catch (\Exception $e) {
            Log::error("New items printing failed: " . $e->getMessage());
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
        $content .= "========================\n";
        $content .= "ITEMS:\n";
        $content .= "========================\n";
        
        foreach ($items as $item) {
            $content .= "{$item->product->name} x{$item->quantity}\n";
        }
        
        $content .= "========================\n";
        $content .= "*** HARAP DIPROSES ***\n\n\n";
        
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
        $content .= "========================\n";
        $content .= "MINUMAN:\n";
        $content .= "========================\n";
        
        foreach ($items as $item) {
            $content .= "{$item->product->name} x{$item->quantity}\n";
        }
        
        $content .= "========================\n";
        $content .= "*** READY TO SERVE ***\n\n\n";
        
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
        $content .= "========================\n";
        $content .= "ITEMS:\n";
        $content .= "========================\n";
        
        foreach ($items as $item) {
            $content .= "{$item->product->name} x{$item->quantity}\n";
        }
        
        $content .= "========================\n";
        $content .= "** ITEM READY **\n\n\n";
        
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
        $content .= "========================\n";
        $content .= "TAMBAHAN:\n";
        $content .= "========================\n";
        
        foreach ($newItems as $item) {
            $content .= "+ {$item->product->name} x{$item->quantity}\n";
        }
        
        $content .= "========================\n";
        $content .= "*** TAMBAHAN ORDER ***\n\n\n";
        
        return $content;
    }
}
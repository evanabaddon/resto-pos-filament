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
    protected $useWebhook;
    protected $isHostingEnvironment;

    public function __construct()
    {
        $this->printerConfig = $this->loadPrinterConfig();
        $this->receiptPrintService = new ReceiptPrintService();
        $this->useWebhook = config('app.use_webhook_printing', false);
        
        // Force environment detection di constructor
        $this->isHostingEnvironment = $this->detectHostingEnvironment();
        
        Log::info("🖨️ OrderPrintService initialized", [
            'environment' => $this->isHostingEnvironment ? 'hosting' : 'local',
            'use_webhook' => $this->useWebhook,
            'app_env' => config('app.env'),
            'app_url' => config('app.url')
        ]);
    }

    /**
     * Deteksi apakah running di hosting environment
     */
    protected function detectHostingEnvironment(): bool
    {
        // Method 1: Force untuk production - SELALU gunakan webhook di production
        if (config('app.env') === 'production') {
            Log::info("🔍 Environment detected: PRODUCTION - forcing webhook mode");
            return true;
        }
        
        // Method 2: Check host/domain
        $host = request()->getHost() ?? parse_url(config('app.url'), PHP_URL_HOST) ?? '';
        
        // Jika host mengandung domain (bukan localhost), consider sebagai hosting
        $isLocal = in_array($host, ['localhost', '127.0.0.1', '::1', '0.0.0.0']) 
                || str_contains($host, '.local')
                || str_contains($host, '.test')
                || str_contains($host, '192.168.')
                || $host === 'localhost'
                || empty($host);
        
        $isHosting = !$isLocal;
        
        Log::info("🔍 Environment detection", [
            'host' => $host,
            'app_env' => config('app.env'),
            'is_local' => $isLocal,
            'is_hosting' => $isHosting
        ]);
        
        return $isHosting;
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
     * PRINT ORDER BY PRODUCT TYPE - SMART ROUTING
     */
    public function printOrderByProductType(Sale $sale): array
    {
        try {
            Log::info("🖨️ Starting order print by product type", [
                'sale_id' => $sale->id,
                'invoice' => $sale->invoice_number,
                'environment' => $this->isHostingEnvironment ? 'hosting' : 'local',
                'use_webhook' => $this->useWebhook
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

            // **STRATEGI PRINT BERDASARKAN ENVIRONMENT**
            if ($this->isHostingEnvironment) {
                // DI HOSTING: SELALU PAKAI WEBHOOK
                $printResults = $this->printViaWebhook($sale, [
                    'kitchen' => $kitchenItems,
                    'bar' => $barItems,
                    'general' => $generalItems
                ]);
            } else {
                // DI LOCAL: PILIH WEBHOOK ATAU DIRECT
                if ($this->useWebhook) {
                    $printResults = $this->printViaWebhook($sale, [
                        'kitchen' => $kitchenItems,
                        'bar' => $barItems,
                        'general' => $generalItems
                    ]);
                } else {
                    $printResults = $this->printDirect($sale, [
                        'kitchen' => $kitchenItems,
                        'bar' => $barItems,
                        'general' => $generalItems
                    ]);
                }
            }

            Log::info("✅ Order printing completed for sale #{$sale->invoice_number}", $printResults);
            return $printResults;

        } catch (\Exception $e) {
            Log::error("❌ Order printing failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Send content ke printer - DIRECT PRINT (HANYA LOCAL)
     */
    protected function sendToPrinter(string $content, string $printerName, string $division): array
    {
        // STRICT CHECK: jangan allow direct print di hosting
        if ($this->isHostingEnvironment) {
            $errorMsg = "🚫 BLOCKED: Direct printing not allowed on hosting environment";
            Log::error($errorMsg, [
                'division' => $division,
                'printer' => $printerName,
                'environment' => 'hosting'
            ]);
            
            return [
                'success' => false,
                'error' => $errorMsg,
                'division' => $division,
                'type' => 'blocked_on_hosting'
            ];
        }

        try {
            Log::info("🖨️ Direct printing to {$division}", [
                'printer' => $printerName,
                'content_length' => strlen($content),
                'environment' => 'local'
            ]);

            $this->receiptPrintService->printRawContent($content, $printerName);
            
            return [
                'success' => true,
                'type' => 'direct',
                'printer' => $printerName,
                'division' => $division
            ];
            
        } catch (\Exception $e) {
            Log::error("❌ {$division} direct print failed: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'division' => $division,
                'printer' => $printerName
            ];
        }
    }

    /**
     * Print direct (hanya untuk local)
     */
    protected function printDirect(Sale $sale, array $itemsByDivision): array
    {
        $printResults = [];

        foreach ($itemsByDivision as $division => $items) {
            if (!empty($items)) {
                $content = $this->generateOrderContent($sale, $items, $division);
                $printerName = $this->getPrinterNameForDivision($division);
                
                $printResults[$division] = $this->sendToPrinter($content, $printerName, $division);
            }
        }

        return $printResults;
    }

    /**
     * Print via webhook (untuk hosting)
     */
    protected function printViaWebhook(Sale $sale, array $itemsByDivision): array
    {
        $printResults = [];

        foreach ($itemsByDivision as $division => $items) {
            if (!empty($items)) {
                $content = $this->generateOrderContent($sale, $items, $division);
                $printerName = $this->getPrinterNameForDivision($division);
                
                $printResults[$division] = $this->sendWebhookPrint($content, $printerName, $division, $sale->id);
            }
        }

        return $printResults;
    }

    /**
     * Send print job via webhook
     */
    protected function sendWebhookPrint(string $content, string $printer, string $division, ?int $saleId = null): array
    {
        try {
            $webhookUrl = config('app.webhook_print_url');
            $secretKey = config('app.print_secret');
            
            if (!$webhookUrl) {
                throw new \Exception('Webhook URL not configured');
            }

            Log::info("🌐 Sending webhook print to {$division}", [
                'printer' => $printer,
                'webhook_url' => $webhookUrl,
                'content_length' => strlen($content),
                'environment' => $this->isHostingEnvironment ? 'hosting' : 'local'
            ]);

            // Create HTTP client dengan options untuk handle SSL
            $httpClient = Http::timeout(15)
                ->withOptions([
                    'verify' => false, // Disable SSL verification untuk development
                    'debug' => false,
                ])
                ->withHeaders([
                    'X-Print-Secret' => $secretKey,
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'POS-System/1.0'
                ]);

            $response = $httpClient->post($webhookUrl, [
                'content' => $content,
                'printer' => $printer,
                'division' => $division,
                'sale_id' => $saleId,
                'type' => 'order'
            ]);

            if ($response->successful()) {
                $result = $response->json();
                
                if ($result['success'] ?? false) {
                    Log::info("✅ Webhook print queued: {$result['job_id']}");
                    return [
                        'success' => true,
                        'type' => 'webhook',
                        'job_id' => $result['job_id'],
                        'printer' => $printer,
                        'division' => $division,
                        'message' => 'Print job queued via webhook'
                    ];
                } else {
                    throw new \Exception($result['error'] ?? 'Webhook returned error');
                }
            } else {
                $errorBody = $response->body();
                // Truncate long HTML responses
                if (strlen($errorBody) > 200) {
                    $errorBody = substr($errorBody, 0, 200) . '...';
                }
                throw new \Exception("HTTP {$response->status()}: {$errorBody}");
            }
            
        } catch (\Exception $e) {
            Log::error("❌ Webhook print failed: " . $e->getMessage());
            
            // Fallback strategy berbeda untuk hosting vs local
            if ($this->isHostingEnvironment) {
                // Di hosting, tidak ada fallback - langsung return error
                return [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'division' => $division,
                    'type' => 'webhook_failed'
                ];
            } else {
                // Di local, fallback ke direct print
                Log::info("🔄 Falling back to direct print for {$division}");
                return $this->sendToPrinter($content, $printer, $division);
            }
        }
    }

    /**
     * Test webhook connection
     */
    public function testWebhookConnection(): array
    {
        try {
            $webhookUrl = config('app.webhook_url');
            $secretKey = config('app.print_secret');
            
            if (!$webhookUrl) {
                return [
                    'success' => false,
                    'error' => 'Webhook URL not configured'
                ];
            }

            $testContent = "TEST WEBHOOK PRINT\n";
            $testContent .= "===================\n";
            $testContent .= "Time: " . now()->format('Y-m-d H:i:s') . "\n";
            $testContent .= "Status: Webhook Test\n";
            $testContent .= "===================\n\n\n";

            $response = Http::timeout(5)
                ->withOptions([
                    'verify' => false,
                    'debug' => false,
                ])
                ->withHeaders([
                    'X-Print-Secret' => $secretKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($webhookUrl, [
                    'content' => $testContent,
                    'printer' => 'TEST',
                    'division' => 'Test',
                    'type' => 'test'
                ]);

            if ($response->successful()) {
                $result = $response->json();
                return [
                    'success' => true,
                    'message' => 'Webhook connection successful',
                    'job_id' => $result['job_id'] ?? null
                ];
            } else {
                return [
                    'success' => false,
                    'error' => "HTTP {$response->status()}: {$response->body()}"
                ];
            }
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
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
                'new_items_count' => count($newItems),
                'environment' => $this->isHostingEnvironment ? 'hosting' : 'local',
                'use_webhook' => $this->useWebhook
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

            // **GUNAKAN STRATEGI YANG SAMA DENGAN printOrderByProductType**
            if ($this->isHostingEnvironment) {
                // DI HOSTING: SELALU PAKAI WEBHOOK
                $printResults = $this->printNewItemsViaWebhook($sale, [
                    'kitchen' => $kitchenNewItems,
                    'bar' => $barNewItems,
                    'general' => $generalNewItems
                ]);
            } else {
                // DI LOCAL: PILIH WEBHOOK ATAU DIRECT
                if ($this->useWebhook) {
                    $printResults = $this->printNewItemsViaWebhook($sale, [
                        'kitchen' => $kitchenNewItems,
                        'bar' => $barNewItems,
                        'general' => $generalNewItems
                    ]);
                } else {
                    $printResults = $this->printNewItemsDirect($sale, [
                        'kitchen' => $kitchenNewItems,
                        'bar' => $barNewItems,
                        'general' => $generalNewItems
                    ]);
                }
            }

            Log::info("✅ New items printing completed", $printResults);
            return $printResults;

        } catch (\Exception $e) {
            Log::error("❌ New items printing failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Print new items via webhook (untuk hosting)
     */
    protected function printNewItemsViaWebhook(Sale $sale, array $itemsByDivision): array
    {
        $printResults = [];

        foreach ($itemsByDivision as $division => $items) {
            if (!empty($items)) {
                $content = $this->generateNewItemsContent($sale, $items, strtoupper($division));
                $printerName = $this->getPrinterNameForDivision($division);
                
                $printResults[$division] = $this->sendWebhookPrint($content, $printerName, $division . ' Update', $sale->id);
            }
        }

        return $printResults;
    }

    /**
     * Print new items direct (hanya untuk local)
     */
    protected function printNewItemsDirect(Sale $sale, array $itemsByDivision): array
    {
        $printResults = [];

        foreach ($itemsByDivision as $division => $items) {
            if (!empty($items)) {
                $content = $this->generateNewItemsContent($sale, $items, strtoupper($division));
                $printerName = $this->getPrinterNameForDivision($division);
                
                $printResults[$division] = $this->sendToPrinter($content, $printerName, $division . ' Update');
            }
        }

        return $printResults;
    }

    // ==================== GENERATE CONTENT METHODS ====================

    /**
     * Generate content berdasarkan divisi
     */
    protected function generateOrderContent(Sale $sale, array $items, string $division): string
    {
        $divisionTitles = [
            'kitchen' => 'ORDER DAPUR',
            'bar' => 'ORDER BAR', 
            'general' => 'ORDER UMUM'
        ];

        $divisionFooters = [
            'kitchen' => '*** HARAP DIPROSES ***',
            'bar' => '*** READY TO SERVE ***',
            'general' => '** ITEM READY **'
        ];

        $title = $divisionTitles[$division] ?? 'ORDER';
        $footer = $divisionFooters[$division] ?? '** ORDER **';

        $content = "{$title}\n";
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
        $content .= "{$footer}\n";
        $content .= "========================\n\n\n";
        
        return $content;
    }

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

    /**
     * Test environment detection
     */
    public function testEnvironment(): array
    {
        $host = request()->getHost() ?? parse_url(config('app.url'), PHP_URL_HOST);
        
        return [
            'is_hosting' => $this->isHostingEnvironment,
            'app_env' => config('app.env'),
            'host' => $host,
            'app_url' => config('app.url'),
            'webhook_url' => config('app.webhook_print_url'),
            'use_webhook' => $this->useWebhook,
            'recommended_method' => $this->isHostingEnvironment ? 'webhook' : ($this->useWebhook ? 'webhook' : 'direct')
        ];
    }
}
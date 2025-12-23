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
                'usb_kitchen_printer_name' => $settings->usb_kitchen_printer_name ?? ($settings->usb_printer_name ?? 'BAR'),
                'usb_bar_printer_name' => $settings->usb_bar_printer_name ?? ($settings->usb_printer_name ?? 'BAR'),
                'usb_general_printer_name' => $settings->usb_general_printer_name ?? ($settings->usb_printer_name ?? 'BAR'),
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
            $sale->refresh()->load('items.product'); // 🔥 penting

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
                $productName = $item->product_name ?? $item->product->name ?? 'Unknown Item';

                // 🔥 SKIP DP items for order prints
                if ($productName === 'Down Payment (DP)') {
                    Log::info("⏭️ Skipping DP item for order print: {$productName}");
                    continue;
                }

                $productType = $item->product->type ?? 'general';

                Log::info("🔍 Classifying item: {$productName}", [
                    'type' => $productType,
                    'product_id' => $item->product_id
                ]);

                switch ($productType) {
                    case 'produced':
                        $kitchenItems[] = $item;
                        break;
                    case 'bar':
                        $barItems[] = $item;
                        break;
                    default:
                        $generalItems[] = $item;
                        Log::warning("⚠️ Item classified as General: {$productName} (Type: {$productType})");
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
                // 🔥 MERGE ITEMS (prevent duplicates lines)
                $items = $this->mergeItemsByProduct($items);

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
                // 🔥 MERGE ITEMS (prevent duplicates lines)
                $items = $this->mergeItemsByProduct($items);

                $content = $this->generateOrderContent($sale, $items, $division);
                $printerName = $this->getPrinterNameForDivision($division);

                // Serialize items for payload
                $payload = [
                    'sale_id' => $sale->id,
                    'invoice' => $sale->invoice_number,
                    'customer' => $sale->customer_name ?? 'Umum',
                    'order_type' => $sale->order_type ?? 'Dine In',
                    'table' => $sale->table_number ?? '-',
                    'items' => array_map(function ($item) {
                        return [
                            'product_name' => $item->product_name ?? $item->product->name ?? 'Unknown',
                            'quantity' => $item->quantity + 0,
                            'notes' => $item->notes ?? '',
                        ];
                    }, $items)
                ];

                $printResults[$division] = $this->sendWebhookPrint($content, $printerName, $division, $sale->id, $payload);
            }
        }

        return $printResults;
    }

    /**
     * Send print job via webhook
     */
    protected function sendWebhookPrint(string $content, string $printer, string $division, ?int $saleId = null, array $payload = []): array
    {
        try {
            // GUNAKAN config yang benar
            $webhookUrl = config('app.webhook_print_url');
            $secretKey = config('app.print_secret');

            Log::info("🔧 Webhook config check", [
                'webhook_url' => $webhookUrl,
                'secret_set' => !empty($secretKey),
                'from_config' => 'app.webhook_print_url'
            ]);

            if (!$webhookUrl) {
                throw new \Exception('Webhook URL not configured in app.webhook_print_url');
            }

            Log::info("🌐 Sending webhook print to {$division}", [
                'printer' => $printer,
                'webhook_url' => $webhookUrl,
                'content_length' => strlen($content),
                'environment' => $this->isHostingEnvironment ? 'hosting' : 'local'
            ]);

            // 🔥 RETRY LOGIC (3x Attempts)
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::timeout(15)
                ->retry(3, 1000) // Retry 3 kali, delay 1000ms
                ->withOptions([
                    'verify' => false,
                ])
                ->withHeaders([
                    'X-Print-Secret' => $secretKey,
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'POS-System/1.0'
                ])
                ->post($webhookUrl, [
                    'content' => $content,
                    'payload' => $payload, // Send payload
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
                throw new \Exception("HTTP {$response->status()}: " . substr($response->body(), 0, 200));
            }
        } catch (\Exception $e) {
            Log::error("❌ Webhook print failed: " . $e->getMessage());

            // 🔥 FALLBACK LOGIC: If specific printer fails, try MAIN printer
            // Only if we haven't already tried printing to main printer
            $mainPrinter = $this->printerConfig['usb_printer_name'] ?? 'BAR';

            if ($printer !== $mainPrinter) {
                Log::info("🔄 Fallback: Trying to print to Main Printer ({$mainPrinter})");
                try {
                    // Recursive call but to main printer
                    // We use a different division name to indicate fallback
                    return $this->sendWebhookPrint($content, $mainPrinter, $division . ' (Fallback)');
                } catch (\Exception $ex) {
                    Log::error("❌ Fallback print also failed: " . $ex->getMessage());
                }
            }

            if ($this->isHostingEnvironment) {
                return [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'division' => $division,
                    'type' => 'webhook_failed'
                ];
            } else {
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
            // GUNAKAN config yang benar
            $webhookUrl = config('app.webhook_print_url');
            $secretKey = config('app.print_secret');

            Log::info("🔧 Test Webhook Config", [
                'webhook_url' => $webhookUrl,
                'secret_set' => !empty($secretKey)
            ]);

            if (!$webhookUrl) {
                return [
                    'success' => false,
                    'error' => 'Webhook URL not configured (app.webhook_print_url)'
                ];
            }

            $testContent = "TEST WEBHOOK PRINT\n";
            $testContent .= "===================\n";
            $testContent .= "Time: " . now()->format('Y-m-d H:i:s') . "\n";
            $testContent .= "Status: Webhook Test\n";
            $testContent .= "===================\n\n\n";

            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::timeout(10)
                ->withOptions(['verify' => false])
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
                    'job_id' => $result['job_id'] ?? null,
                    'response' => $result
                ];
            } else {
                return [
                    'success' => false,
                    'error' => "HTTP {$response->status()}",
                    'body' => substr($response->body(), 0, 200)
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
        return match ($division) {
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
        $sale->refresh()->load('items.product'); // 🔥 penting

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
                if (!$product)
                    continue;

                // 🔥 SKIP DP items for order prints
                if ($product->name === 'Down Payment (DP)') {
                    Log::info("⏭️ Skipping DP item for new order print: {$product->name}");
                    continue;
                }

                $item = (object) [
                    'product' => $product,
                    'quantity' => $itemData['quantity'],
                    'product_id' => $itemData['product_id'],
                    'notes' => $itemData['notes'] ?? ''
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

                // Serialize items for payload
                $payload = [
                    'sale_id' => $sale->id,
                    'invoice' => $sale->invoice_number,
                    'customer' => $sale->customer_name ?? 'Umum',
                    'order_type' => $sale->order_type ?? 'Dine In',
                    'table' => $sale->table_number ?? '-',
                    'is_update' => true, // Flag for specific styling if needed
                    'items' => array_map(function ($item) {
                        return [
                            'product_name' => $item->product_name ?? $item->product->name ?? 'Unknown',
                            'quantity' => $item->quantity + 0,
                            'notes' => $item->notes ?? '',
                        ];
                    }, $items)
                ];

                $printResults[$division] = $this->sendWebhookPrint($content, $printerName, $division . ' Update', $sale->id, $payload);
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
            $items = $this->mergeItemsByProduct($items);
            if (!empty($items)) {
                $content = $this->generateNewItemsContent($sale, $items, strtoupper($division));
                $printerName = $this->getPrinterNameForDivision($division);

                $printResults[$division] = $this->sendToPrinter($content, $printerName, $division . ' Update');
            }
        }

        return $printResults;
    }

    protected function mergeItemsByProduct($items)
    {
        $merged = [];

        foreach ($items as $item) {
            $id = $item->product_id;
            if (!isset($merged[$id])) {
                $merged[$id] = clone $item;
            } else {
                $merged[$id]->quantity += $item->quantity;
                // ✅ JIKA ADA NOTES BARU, TAMBAHKAN
                if (!empty($item->notes) && !empty($merged[$id]->notes)) {
                    if ($merged[$id]->notes !== $item->notes) {
                        $merged[$id]->notes .= "\n" . $item->notes;
                    }
                } elseif (!empty($item->notes)) {
                    $merged[$id]->notes = $item->notes;
                }
            }
        }

        return array_values($merged);
    }

    /**
     * Debug notes untuk testing
     */
    public function debugNotes(Sale $sale): array
    {
        try {
            $sale->load('items');

            $itemsWithNotes = [];
            foreach ($sale->items as $item) {
                if (!empty($item->notes)) {
                    $itemsWithNotes[] = [
                        'product' => $item->product_name ?? $item->product->name ?? 'Unknown',
                        'notes' => $item->notes,
                        'quantity' => $item->quantity
                    ];
                }
            }

            return [
                'sale_id' => $sale->id,
                'invoice' => $sale->invoice_number,
                'total_items' => $sale->items->count(),
                'items_with_notes' => $itemsWithNotes,
                'items_with_notes_count' => count($itemsWithNotes)
            ];
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
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

        // Format 32 Characters (58mm standard)
        $line = str_repeat('=', 32) . "\n";
        $emptyLine = "\n";

        $content = "{$title}\n";
        $content .= $line;
        $content .= "No: {$sale->invoice_number}\n";
        $content .= "Cust: " . substr($sale->customer_name ?? 'Umum', 0, 24) . "\n";
        $content .= "Table: " . ($sale->table_number ?? 'UnSet') . "\n";
        $content .= "Time: " . now()->format('H:i') . " | " . (strtoupper(str_replace('_', ' ', $sale->order_type ?? 'Dine In'))) . "\n";

        // ✅ TAMBAHKAN INFORMASI NOTES KESELURUHAN JIKA ADA
        $hasAnyNotes = false;
        foreach ($items as $item) {
            if (!empty($item->notes)) {
                $hasAnyNotes = true;
                break;
            }
        }

        if ($hasAnyNotes) {
            $content .= str_repeat('-', 32) . "\n";
            $content .= "CATATAN KHUSUS\n";
        }

        $content .= $line;
        $content .= "ITEMS:\n";
        $content .= $emptyLine;

        foreach ($items as $item) {
            $productName = $item->product_name ?? $item->product->name ?? 'Unknown';
            $qty = "x" . ($item->quantity + 0);

            // Layout: "Name (max 26) .... xQty"
            // Total 32. Qty takes ~3-4 chars. Dots/Space takes min 2. Name takes rest.
            $maxNameLen = 32 - strlen($qty) - 2;

            if (strlen($productName) > $maxNameLen) {
                // If name is too long, wrap it? Or truncate?
                // Left aligned name, dots, right aligned qty
                $content .= str_pad(substr($productName, 0, $maxNameLen) . " ", 32 - strlen($qty), ".", STR_PAD_RIGHT) . $qty . "\n";
                // Print full name on next line if really needed? 
                // For now, truncated name with dots is standard "List" view.
                // Or:
                // $content .= "{$productName}\n";
                // $content .= str_pad("", 32 - strlen($qty), " ", STR_PAD_RIGHT) . $qty . "\n";
            } else {
                $content .= str_pad($productName . " ", 32 - strlen($qty), ".", STR_PAD_RIGHT) . $qty . "\n";
            }

            // ✅ TAMBAHKAN NOTES JIKA ADA
            if (!empty($item->notes)) {
                $notesLines = explode("\n", $item->notes);
                foreach ($notesLines as $noteLine) {
                    $trimmedNote = trim($noteLine);
                    if (!empty($trimmedNote)) {
                        $content .= ": " . substr($trimmedNote, 0, 30) . "\n";
                    }
                }
            }

            if (!empty($item->note)) {
                $content .= "  Note: " . substr($item->note, 0, 24) . "\n";
            }

            $content .= $emptyLine; // Spacing per item
        }

        // $content .= $line;
        // $content .= "{$footer}\n";
        // $content .= $line . "\n\n";

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
        $content .= "Table: " . ($sale->table_number ?? 'UnSet') . "\n";
        $content .= "Time: " . now()->format('H:i:s') . "\n";
        $content .= "Type: " . (strtoupper(str_replace('_', ' ', $sale->order_type ?? 'Dine In'))) . "\n";
        // $content .= "========================\n";
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

        // $content .= "========================\n";
        // $content .= "*** HARAP DIPROSES ***\n";
        // $content .= "========================\n\n\n";

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
        $content .= "Table: " . ($sale->table_number ?? 'UnSet') . "\n";
        $content .= "Time: " . now()->format('H:i:s') . "\n";
        $content .= "Type: " . (strtoupper(str_replace('_', ' ', $sale->order_type ?? 'Dine In'))) . "\n";
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

        // $content .= "========================\n";
        // $content .= "*** READY TO SERVE ***\n";
        // $content .= "========================\n\n\n";

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
        $content .= "Table: " . ($sale->table_number ?? 'UnSet') . "\n";
        $content .= "Time: " . now()->format('H:i:s') . "\n";
        $content .= "Type: " . (strtoupper(str_replace('_', ' ', $sale->order_type ?? 'Dine In'))) . "\n";
        // $content .= "========================\n";
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
        // Format 32 Characters
        $line = str_repeat('=', 32) . "\n";
        $emptyLine = "\n";

        $content = "TAMBAHAN {$division}\n";
        $content .= $line;
        $content .= "No: {$sale->invoice_number}\n";
        $content .= "Cust: " . substr($sale->customer_name ?? 'Umum', 0, 24) . "\n";
        $content .= "Table: " . ($sale->table_number ?? 'UnSet') . "\n";
        $content .= "Time: " . now()->format('H:i') . " | " . (strtoupper(str_replace('_', ' ', $sale->order_type ?? 'Dine In'))) . "\n";

        // ✅ TAMBAHKAN INFORMASI NOTES KESELURUHAN JIKA ADA
        $hasAnyNotes = false;
        foreach ($newItems as $item) {
            if (!empty($item->notes)) {
                $hasAnyNotes = true;
                break;
            }
        }

        if ($hasAnyNotes) {
            $content .= str_repeat('-', 32) . "\n";
            $content .= "📝 CATATAN KHUSUS\n";
        }

        $content .= $line;
        $content .= "TAMBAHAN:\n";
        $content .= $emptyLine;

        foreach ($newItems as $item) {
            $productName = $item->product->name ?? 'Unknown';
            $qty = "x{$item->quantity}";

            $maxNameLen = 30 - strlen($qty) - 2; // + prefix

            if (strlen($productName) > $maxNameLen) {
                $content .= str_pad("+ " . substr($productName, 0, $maxNameLen) . " ", 32 - strlen($qty), ".", STR_PAD_RIGHT) . $qty . "\n";
            } else {
                $content .= str_pad("+ " . $productName . " ", 32 - strlen($qty), ".", STR_PAD_RIGHT) . $qty . "\n";
            }

            // ✅ TAMBAHKAN NOTES JIKA ADA
            if (!empty($item->notes)) {
                $notesLines = explode("\n", $item->notes);
                foreach ($notesLines as $noteLine) {
                    $trimmedNote = trim($noteLine);
                    if (!empty($trimmedNote)) {
                        $content .= " 📝 " . substr($trimmedNote, 0, 29) . "\n";
                    }
                }
            }
        }

        $content .= $line;
        $content .= "*** UPDATE ORDER ***\n";
        $content .= $line . "\n\n";

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

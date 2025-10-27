<?php

namespace App\Services;

use App\Models\Sale;
use PrinterSettings;
use App\Models\Product;
use Illuminate\Support\Facades\Log;

class OrderPrintService
{
    protected $printService;
    protected $printerSettings;
    protected $printerConfig;

    public function __construct()
    {
        $this->printService = new ReceiptPrintService();
        $this->printerSettings = PrinterSettings::class;
        $this->printerConfig = $this->loadPrinterConfig();
    }

    /**
     * PRINT ORDER BY PRODUCT TYPE - METHOD YANG HILANG
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
                    case 'produced': // Produk kitchen (makanan yang perlu dimasak)
                        $kitchenItems[] = $item;
                        break;
                    case 'bar': // Produk bar (minuman)
                        $barItems[] = $item;
                        break;
                    default: // Produk retail/umum
                        $generalItems[] = $item;
                        break;
                }
            }

            $printResults = [];

            // Print ke printer masing-masing
            if (!empty($kitchenItems)) {
                $printResults['kitchen'] = $this->printToKitchen($sale, $kitchenItems);
            }

            if (!empty($barItems)) {
                $printResults['bar'] = $this->printToBar($sale, $barItems);
            }

            if (!empty($generalItems)) {
                $printResults['general'] = $this->printToGeneral($sale, $generalItems);
            }

            Log::info("Order printing completed for sale #{$sale->invoice_number}", $printResults);
            return $printResults;

        } catch (\Exception $e) {
            Log::error("Order printing failed: " . $e->getMessage());
            throw $e;
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
                'usb_printer_name' => $settings->usb_printer_name ?? 'POS-58',
                'usb_kitchen_printer_name' => $settings->usb_kitchen_printer_name,
                'usb_bar_printer_name' => $settings->usb_bar_printer_name,
                'usb_general_printer_name' => $settings->usb_general_printer_name,
                'kitchen_printer_ip' => $settings->kitchen_printer_ip ?? '192.168.1.100',
                'kitchen_printer_port' => $settings->kitchen_printer_port ?? '9100',
                'bar_printer_ip' => $settings->bar_printer_ip ?? '192.168.1.101',
                'bar_printer_port' => $settings->bar_printer_port ?? '9100',
                'general_printer_ip' => $settings->general_printer_ip ?? '192.168.1.102',
                'general_printer_port' => $settings->general_printer_port ?? '9100',
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
            'usb_printer_name' => 'POS-58',
            'usb_kitchen_printer_name' => null,
            'usb_bar_printer_name' => null,
            'usb_general_printer_name' => null,
            'kitchen_printer_ip' => '192.168.1.100',
            'kitchen_printer_port' => '9100',
            'bar_printer_ip' => '192.168.1.101',
            'bar_printer_port' => '9100',
            'general_printer_ip' => '192.168.1.102',
            'general_printer_port' => '9100',
        ];
    }

    /**
     * Print ke kitchen printer (USB atau Network)
     */
    protected function printToKitchen(Sale $sale, array $items): array
    {
        try {
            $content = $this->generateKitchenOrderContent($sale, $items);
            
            if ($this->printerConfig['printer_type'] === 'usb') {
                // USB Printer
                $printerName = $this->getUsbPrinterName('kitchen');
                $this->printToUsbPrinter($content, $printerName, 'Kitchen');
                return ['success' => true, 'type' => 'usb', 'printer' => $printerName, 'mode' => 'kitchen'];
            } else {
                // Network Printer
                $printerIp = $this->printerConfig['kitchen_printer_ip'];
                $printerPort = $this->printerConfig['kitchen_printer_port'];
                $this->printToNetworkPrinter($content, $printerIp, $printerPort, 'Kitchen');
                return ['success' => true, 'type' => 'network', 'printer' => "{$printerIp}:{$printerPort}"];
            }
            
        } catch (\Exception $e) {
            Log::error("Kitchen print failed: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Print ke bar printer (USB atau Network)
     */
    protected function printToBar(Sale $sale, array $items): array
    {
        try {
            $content = $this->generateBarOrderContent($sale, $items);
            
            // ✅ PERBAIKAN: Gunakan $this->printerConfig bukan $this->printerSettings
            if ($this->printerConfig['printer_type'] === 'usb') {
                // USB Printer
                $printerName = $this->getUsbPrinterName('bar');
                $this->printToUsbPrinter($content, $printerName, 'Bar');
                return ['success' => true, 'type' => 'usb', 'printer' => $printerName, 'mode' => 'bar'];
            } else {
                // Network Printer
                $printerIp = $this->printerConfig['bar_printer_ip'];
                $printerPort = $this->printerConfig['bar_printer_port'];
                $this->printToNetworkPrinter($content, $printerIp, $printerPort, 'Bar');
                return ['success' => true, 'type' => 'network', 'printer' => "{$printerIp}:{$printerPort}"];
            }
            
        } catch (\Exception $e) {
            Log::error("Bar print failed: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Print ke general printer (USB atau Network)
     */
    protected function printToGeneral(Sale $sale, array $items): array
    {
        try {
            $content = $this->generateGeneralOrderContent($sale, $items);
            
            // ✅ PERBAIKAN: Gunakan $this->printerConfig bukan $this->printerSettings
            if ($this->printerConfig['printer_type'] === 'usb') {
                // USB Printer
                $printerName = $this->getUsbPrinterName('general');
                $this->printToUsbPrinter($content, $printerName, 'General');
                return ['success' => true, 'type' => 'usb', 'printer' => $printerName, 'mode' => 'general'];
            } else {
                // Network Printer
                $printerIp = $this->printerConfig['general_printer_ip'];
                $printerPort = $this->printerConfig['general_printer_port'];
                $this->printToNetworkPrinter($content, $printerIp, $printerPort, 'General');
                return ['success' => true, 'type' => 'network', 'printer' => "{$printerIp}:{$printerPort}"];
            }
            
        } catch (\Exception $e) {
            Log::error("General print failed: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Dapatkan nama printer USB berdasarkan divisi
     */
    protected function getUsbPrinterName(string $division): string
    {
        $mode = $this->printerConfig['usb_printer_mode'];
        $mainPrinter = $this->printerConfig['usb_printer_name'];
        
        if ($mode === 'single') {
            return $mainPrinter;
        }
        
        // Multiple printer mode
        return match($division) {
            'kitchen' => $this->printerConfig['usb_kitchen_printer_name'] ?? $mainPrinter,
            'bar' => $this->printerConfig['usb_bar_printer_name'] ?? $mainPrinter,
            'general' => $this->printerConfig['usb_general_printer_name'] ?? $mainPrinter,
            default => $mainPrinter
        };
    }

    /**
     * Print ke USB Printer
     */
    public function printToUsbPrinter(string $content, string $printerName, string $division = ''): void
    {
        try {
            $this->printService->printToUsbPrinter($content, $printerName);
            Log::info("✅ {$division} order printed to USB: {$printerName}");
            
        } catch (\Exception $e) {
            Log::error("❌ Failed to print to {$division} USB printer {$printerName} - " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Print ke network printer
     */
    public function printToNetworkPrinter(string $content, string $ip, string $port, string $printerName = ''): void
    {
        try {
            $this->printService->printToNetworkPrinter($content, $ip, $port);
            Log::info("{$printerName} order printed to {$ip}:{$port}");
        } catch (\Exception $e) {
            Log::error("Failed to print to {$printerName} printer {$ip}:{$port} - " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate content untuk kitchen order
     */
    protected function generateKitchenOrderContent(Sale $sale, array $items): string
    {
        $content = "ORDER DAPUR\n";
        $content .= "========================\n";
        $content .= "No. Order: {$sale->invoice_number}\n";
        $content .= "Waktu: " . $sale->created_at->format('d/m/Y H:i') . "\n";
        $content .= "Customer: " . ($sale->customer_name ?? 'Umum') . "\n";
        $content .= "Tipe: {$sale->order_type}\n";
        $content .= "Kasir: " . ($sale->user->name ?? 'System') . "\n";
        $content .= "========================\n";
        $content .= "ITEMS DAPUR:\n";
        $content .= "========================\n";
        
        foreach ($items as $item) {
            $content .= "{$item->product->name}\n";
            $content .= "Qty: {$item->quantity}\n";
            $content .= "----------------\n";
        }
        
        $content .= "========================\n";
        $content .= "*** HARAP SEGERA DIPROSES ***\n";
        $content .= "Order Time: " . now()->format('H:i:s') . "\n";
        $content .= "\n\n\n"; // Extra paper feed
        
        return $content;
    }

    /**
     * Generate content untuk bar order
     */
    protected function generateBarOrderContent(Sale $sale, array $items): string
    {
        $content = "ORDER BAR\n";
        $content .= "========================\n";
        $content .= "No. Order: {$sale->invoice_number}\n";
        $content .= "Waktu: " . $sale->created_at->format('d/m/Y H:i') . "\n";
        $content .= "Customer: " . ($sale->customer_name ?? 'Umum') . "\n";
        $content .= "Tipe: {$sale->order_type}\n";
        $content .= "========================\n";
        $content .= "MINUMAN:\n";
        $content .= "========================\n";
        
        foreach ($items as $item) {
            $content .= "{$item->product->name}\n";
            $content .= "Qty: {$item->quantity}\n";
            $content .= "----------------\n";
        }
        
        $content .= "========================\n";
        $content .= "*** READY TO SERVE ***\n";
        $content .= "\n\n\n"; // Extra paper feed
        
        return $content;
    }

    /**
     * Generate content untuk general order
     */
    protected function generateGeneralOrderContent(Sale $sale, array $items): string
    {
        $content = "ORDER UMUM\n";
        $content .= "========================\n";
        $content .= "No. Order: {$sale->invoice_number}\n";
        $content .= "Waktu: " . $sale->created_at->format('d/m/Y H:i') . "\n";
        $content .= "Customer: " . ($sale->customer_name ?? 'Umum') . "\n";
        $content .= "========================\n";
        $content .= "ITEMS:\n";
        $content .= "========================\n";
        
        foreach ($items as $item) {
            $content .= "{$item->product->name}\n";
            $content .= "Qty: {$item->quantity} pcs\n";
            $content .= "----------------\n";
        }
        
        $content .= "========================\n";
        $content .= "** ITEM READY **\n";
        $content .= "\n\n\n"; // Extra paper feed
        
        return $content;
    }

    /**
     * Print hanya item baru/tambahan
     */
    public function printNewItemsOnly(Sale $sale, array $newItems): array
    {
        try {
            \Log::info("🔄 Printing new items only for sale #{$sale->invoice_number}", [
                'new_items_count' => count($newItems)
            ]);

            // Kelompokkan new items berdasarkan tipe produk
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

            // Print hanya item baru ke masing-masing divisi
            if (!empty($kitchenNewItems)) {
                $content = $this->generateNewItemsContent($sale, $kitchenNewItems, 'DAPUR');
                $printerName = $this->getUsbPrinterName('kitchen');
                $this->printToUsbPrinter($content, $printerName, 'Kitchen Update');
                $printResults['kitchen'] = ['success' => true, 'new_items' => count($kitchenNewItems)];
            }

            if (!empty($barNewItems)) {
                $content = $this->generateNewItemsContent($sale, $barNewItems, 'BAR');
                $printerName = $this->getUsbPrinterName('bar');
                $this->printToUsbPrinter($content, $printerName, 'Bar Update');
                $printResults['bar'] = ['success' => true, 'new_items' => count($barNewItems)];
            }

            if (!empty($generalNewItems)) {
                $content = $this->generateNewItemsContent($sale, $generalNewItems, 'UMUM');
                $printerName = $this->getUsbPrinterName('general');
                $this->printToUsbPrinter($content, $printerName, 'General Update');
                $printResults['general'] = ['success' => true, 'new_items' => count($generalNewItems)];
            }

            return $printResults;

        } catch (\Exception $e) {
            \Log::error("❌ New items printing failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate content untuk item baru saja
     */
    protected function generateNewItemsContent(Sale $sale, array $newItems, string $division): string
    {
        $content = "TAMBAHAN ORDER {$division}\n";
        $content .= "========================\n";
        $content .= "No. Order: {$sale->invoice_number}\n";
        $content .= "Customer: " . ($sale->customer_name ?? 'Umum') . "\n";
        $content .= "Waktu: " . now()->format('d/m/Y H:i:s') . "\n";
        $content .= "========================\n";
        $content .= "TAMBAHAN ITEMS:\n";
        $content .= "========================\n";
        
        foreach ($newItems as $item) {
            $content .= "+ {$item->product->name}\n";
            $content .= "Qty: +{$item->quantity}\n";
            $content .= "----------------\n";
        }
        
        $content .= "========================\n";
        $content .= "*** TAMBAHAN ORDER - HARAP DIPROSES ***\n";
        $content .= "\n\n\n";
        
        return $content;
    }
}
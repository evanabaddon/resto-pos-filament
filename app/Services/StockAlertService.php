<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class StockAlertService
{
    /**
     * Check critical stock levels and send alerts
     */
    public function checkCriticalStock(): void
    {
        $criticalItems = $this->getAllCriticalItems();

        if ($criticalItems->isEmpty()) {
            return;
        }

        foreach ($criticalItems as $item) {
            $this->sendStockAlert($item);
        }

        Log::info('Stock alert check completed', [
            'critical_items_count' => $criticalItems->count()
        ]);
    }

    /**
     * Get items with stock below threshold (raw materials)
     */
    public function getCriticalStockItems(): Collection
    {
        return Product::where('enable_stock_alert', true)
            ->whereNotNull('minimum_stock')
            ->whereRaw('stock <= minimum_stock * 1.5') // Alert at 150% of minimum
            ->with('unit')
            ->get();
    }

    /**
     * Get items with prepared stock below threshold (produced/bar items)
     */
    public function getCriticalPreparedStock(): Collection
    {
        return Product::whereIn('type', ['produced', 'bar'])
            ->where('enable_stock_alert', true)
            ->whereNotNull('minimum_prepared_stock')
            ->whereRaw('prepared_stock <= minimum_prepared_stock * 1.5')
            ->with('unit')
            ->get();
    }

    /**
     * Get all critical items (both raw and prepared)
     */
    public function getAllCriticalItems(): Collection
    {
        return $this->getCriticalStockItems()
            ->merge($this->getCriticalPreparedStock());
    }

    /**
     * Calculate recommended restock quantity based on historical data
     */
    public function getRecommendedRestock(Product $product, int $daysBuffer = 3): float
    {
        $avgDaily = $this->getAverageDailyConsumption($product);

        if ($avgDaily <= 0) {
            // Fallback: use minimum stock * 2
            return ($product->minimum_stock ?? 0) * 2;
        }

        return round($avgDaily * $daysBuffer, 2);
    }

    /**
     * Get average daily consumption for a product
     */
    protected function getAverageDailyConsumption(Product $product, int $days = 7): float
    {
        $inventoryService = app(InventoryService::class);
        $data = $inventoryService->getForecastingData($days);

        $productData = collect($data)->firstWhere('id', $product->id);

        return $productData['average_daily'] ?? 0;
    }

    /**
     * Send stock alert notification
     */
    protected function sendStockAlert(Product $product): void
    {
        $recommended = $this->getRecommendedRestock($product);
        $currentStock = $product->stock ?? 0;
        $unit = $product->unit->name ?? 'unit';

        // Determine alert level
        $isUrgent = $currentStock <= $product->minimum_stock;

        // Send to admins and super admins
        $recipients = User::whereIn('role', ['admin', 'super_admin'])->get();

        Notification::make()
            ->warning()
            ->title($isUrgent ? "⚠️ URGENT: Stok {$product->name} Kritis!" : "⚡ Stok {$product->name} Menipis")
            ->body("Stok saat ini: **{$currentStock} {$unit}**\nMinimum: {$product->minimum_stock} {$unit}\n\n💡 Rekomendasi restock: **{$recommended} {$unit}**")
            ->actions([
                Action::make('view')
                    ->button()
                    ->label('Lihat Produk')
                    ->url(route('filament.admin.resources.products.edit', $product)),
            ])
            ->persistent() // Keep notification until dismissed
            ->sendToDatabase($recipients);
    }

    /**
     * Get stock status for a product
     */
    public function getStockStatus(Product $product): array
    {
        // Check prepared stock for produced/bar items
        if (in_array($product->type, ['produced', 'bar'])) {
            if (!$product->enable_stock_alert || !$product->minimum_prepared_stock) {
                return [
                    'status' => 'normal',
                    'color' => 'success',
                    'message' => 'Prepared stock monitoring disabled',
                    'stock_type' => 'prepared'
                ];
            }

            $stock = $product->prepared_stock ?? 0;
            $minimum = $product->minimum_prepared_stock;

            if ($stock <= $minimum) {
                return [
                    'status' => 'critical',
                    'color' => 'danger',
                    'message' => 'Ready stock critical - cook more immediately',
                    'stock_type' => 'prepared'
                ];
            } elseif ($stock <= $minimum * 1.5) {
                return [
                    'status' => 'warning',
                    'color' => 'warning',
                    'message' => 'Ready stock low - cook more soon',
                    'stock_type' => 'prepared'
                ];
            } else {
                return [
                    'status' => 'normal',
                    'color' => 'success',
                    'message' => 'Ready stock level OK',
                    'stock_type' => 'prepared'
                ];
            }
        }

        // Check raw material stock
        if (!$product->enable_stock_alert || !$product->minimum_stock) {
            return [
                'status' => 'normal',
                'color' => 'success',
                'message' => 'Stock monitoring disabled',
                'stock_type' => 'raw'
            ];
        }

        $stock = $product->stock ?? 0;
        $minimum = $product->minimum_stock;

        if ($stock <= $minimum) {
            return [
                'status' => 'critical',
                'color' => 'danger',
                'message' => 'Stock critical - immediate restock needed',
                'stock_type' => 'raw'
            ];
        } elseif ($stock <= $minimum * 1.5) {
            return [
                'status' => 'warning',
                'color' => 'warning',
                'message' => 'Stock low - restock soon',
                'stock_type' => 'raw'
            ];
        } else {
            return [
                'status' => 'normal',
                'color' => 'success',
                'message' => 'Stock level OK',
                'stock_type' => 'raw'
            ];
        }
    }
}

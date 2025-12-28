<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Services\StockAlertService;
use Filament\Widgets\Widget;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Livewire\Attributes\On;

class CriticalStockWidget extends Widget
{
    protected string $view = 'filament.widgets.critical-stock-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = -1; // Display at top

    // Poll every 5 minutes for updates
    protected static ?string $pollingInterval = '300s';

    public function getCriticalItems()
    {
        $service = app(StockAlertService::class);
        return $service->getCriticalPreparedStock(); // Only prepared stock (produced/bar)
    }

    public function getStockStatus($product)
    {
        $service = app(StockAlertService::class);
        return $service->getStockStatus($product);
    }

    public function getRecommendedRestock($product)
    {
        $service = app(StockAlertService::class);
        return $service->getRecommendedRestock($product);
    }

    public function recordProduction($productId, $quantity)
    {
        try {
            $product = Product::findOrFail($productId);

            // Update prepared stock
            $product->update([
                'prepared_stock' => $product->prepared_stock + $quantity
            ]);

            Notification::make()
                ->success()
                ->title('Production Recorded')
                ->body("Berhasil menambahkan {$quantity} porsi {$product->name}")
                ->send();

            // Close modal
            $this->dispatch('close-modal', id: "record-production-{$productId}");

            // Refresh widget
            $this->dispatch('stock-updated');

        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body($e->getMessage())
                ->send();
        }
    }

    #[On('stock-updated')]
    public function refresh(): void
    {
        // Refresh widget when stock is updated
    }
}

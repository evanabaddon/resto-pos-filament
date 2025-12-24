<?php

namespace App\Filament\Pages;

use App\Models\Product;
use App\Models\StockMovement;
use Filament\Pages\Page;
use Filament\Actions\Action;
use BackedEnum;
use UnitEnum;
use Filament\Support\Icons\Heroicon;
use Filament\Notifications\Notification;

class StockOpname extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected string $view = 'filament.pages.stock-opname';

    protected static UnitEnum|string|null $navigationGroup = 'Laporan & Analisis';

    protected static ?int $navigationSort = 50;

    protected static ?string $navigationLabel = 'Stock Opname';

    protected static ?string $title = 'Stock Opname (Bulk Input)';

    public $products = [];
    public $searchQuery = '';
    public $filterCategory = '';

    public static function canAccess(): bool
    {
        return in_array(auth()->user()->role, [\App\Enums\UserRole::SuperAdmin, \App\Enums\UserRole::Admin, \App\Enums\UserRole::Inventory]);
    }

    public function mount(): void
    {
        $this->loadProducts();
    }

    protected function getActions(): array
    {
        return [
            Action::make('reset')
                ->label('Reset All')
                ->color('gray')
                ->action(fn() => $this->resetOpname()),
            Action::make('submit')
                ->label('Submit Stock Opname')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Stock Opname')
                ->modalDescription('Apakah Anda yakin ingin submit stock opname? Ini akan membuat stock movement untuk semua variance yang terdeteksi.')
                ->modalSubmitActionLabel('Ya, Submit')
                ->modalCancelActionLabel('Batal')
                ->action(fn() => $this->submitOpname()),
        ];
    }

    public function resetOpname(): void
    {
        $this->loadProducts();
        Notification::make()
            ->title('Reset Completed')
            ->body('Semua physical count telah direset ke system stock.')
            ->success()
            ->send();
    }

    public function getItemsCheckedProperty(): int
    {
        return collect($this->products)->filter(function ($product) {
            return ($product['physical_count'] ?? 0) != $product['system_stock'];
        })->count();
    }

    public function getTotalVarianceProperty(): float
    {
        return collect($this->products)->sum(function ($product) {
            return ($product['physical_count'] ?? 0) - $product['system_stock'];
        });
    }

    public function getTotalLossProperty(): float
    {
        return collect($this->products)->sum(function ($product) {
            $variance = ($product['physical_count'] ?? 0) - $product['system_stock'];
            return $variance < 0 ? abs($variance) * $product['base_price'] : 0;
        });
    }

    public function getFilteredProductsProperty(): array
    {
        return collect($this->products)->filter(function ($product) {
            $matchSearch = empty($this->searchQuery) ||
                stripos($product['name'], $this->searchQuery) !== false;
            $matchCategory = empty($this->filterCategory) ||
                $product['category'] === $this->filterCategory;
            return $matchSearch && $matchCategory;
        })->toArray(); // Keep original keys for wire:model mapping
    }

    public function getCategoriesProperty(): array
    {
        return collect($this->products)
            ->pluck('category')
            ->unique()
            ->filter()
            ->sort()
            ->values()
            ->toArray();
    }

    public function loadProducts(): void
    {
        // Load all products that have stock tracking (Raw Material & Retail)
        $this->products = Product::with(['unit', 'category'])
            ->whereIn('type', ['raw', 'retail'])
            ->orderBy('category_id')
            ->orderBy('name')
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'category' => $product->category?->name ?? '-',
                    'unit' => $product->unit?->symbol ?? '',
                    'system_stock' => $product->stock,
                    'physical_count' => $product->stock, // Default to system stock
                    'variance' => 0,
                    'base_price' => $product->base_price,
                    'value_loss' => 0,
                ];
            })
            ->toArray();
    }

    public function submitOpname(): void
    {
        $totalVariance = 0;
        $totalLoss = 0;
        $itemsAdjusted = 0;

        // Process all products and check for variance
        foreach ($this->products as $productData) {
            $productId = $productData['id'];
            $physicalCount = (float) ($productData['physical_count'] ?? 0);
            $systemStock = (float) ($productData['system_stock'] ?? 0);

            $product = Product::find($productId);

            if (!$product) {
                continue;
            }

            // Recalculate variance
            $variance = $physicalCount - $systemStock;

            // Only create stock movement if there's a variance
            if ($variance != 0) {
                StockMovement::create([
                    'product_id' => $productId,
                    'quantity' => abs($variance),
                    'type' => $variance > 0 ? 'increase' : 'decrease',
                    'reason' => 'Stock Opname',
                    'notes' => sprintf(
                        'Stock Opname - System: %s, Physical: %s, Variance: %s',
                        number_format($systemStock, 2),
                        number_format($physicalCount, 2),
                        number_format($variance, 2)
                    ),
                ]);

                $itemsAdjusted++;
                $totalVariance += abs($variance);

                if ($variance < 0) {
                    $totalLoss += abs($variance) * $product->base_price;
                }
            }
        }

        if ($itemsAdjusted === 0) {
            Notification::make()
                ->title('No Changes')
                ->body('Tidak ada variance yang perlu disesuaikan.')
                ->warning()
                ->send();
            return;
        }

        Notification::make()
            ->title('Stock Opname Completed')
            ->body(sprintf(
                '%d items adjusted. Total variance: %s. Total loss: Rp %s',
                $itemsAdjusted,
                number_format($totalVariance, 2),
                number_format($totalLoss, 0)
            ))
            ->success()
            ->send();

        // Reload products to show updated stock
        $this->loadProducts();

        // Force page refresh to show updated stock
        $this->redirect(static::getUrl(), navigate: true);
    }
}

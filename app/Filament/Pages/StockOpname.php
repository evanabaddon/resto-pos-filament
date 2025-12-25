<?php

namespace App\Filament\Pages;

use App\Models\Product;
use App\Models\StockMovement;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;
use BackedEnum;
use UnitEnum;

class StockOpname extends Page implements HasTable
{
    use InteractsWithTable;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected string $view = 'filament.pages.stock-opname';

    protected static UnitEnum|string|null $navigationGroup = 'Produk';

    protected static ?int $navigationSort = 50;

    protected static ?string $navigationLabel = 'Stock Opname';

    protected static ?string $title = 'Stock Opname (Bulk Input)';

    // Store physical counts in Livewire state (not database)
    public array $physicalCounts = [];

    public static function canAccess(): bool
    {
        return in_array(auth()->user()->role, [\App\Enums\UserRole::SuperAdmin, \App\Enums\UserRole::Admin, \App\Enums\UserRole::Inventory]);
    }

    // Computed property: Total edited products
    public function getTotalEditedProperty(): int
    {
        return count($this->physicalCounts);
    }

    // Computed property: Total estimated loss value
    public function getTotalEstimatedLossProperty(): float
    {
        $totalLoss = 0;

        if (empty($this->physicalCounts)) {
            return 0;
        }

        $productIds = array_keys($this->physicalCounts);
        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

        foreach ($this->physicalCounts as $productId => $physicalCount) {
            $product = $products->get($productId);
            if (!$product) continue;

            $variance = (float) $physicalCount - (float) $product->stock;

            if ($variance < 0) {
                $totalLoss += abs($variance) * (float) ($product->base_price ?? 0);
            }
        }

        return $totalLoss;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Product::query()
                    ->whereIn('type', ['raw', 'retail'])
                    ->with(['unit', 'category'])
                    ->orderBy('category_id')
                    ->orderBy('name')
            )
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('name')
                    ->label('Produk')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('stock')
                    ->label('Stock Sistem')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(fn(Product $record) => ' ' . ($record->unit?->symbol ?? ''))
                    ->sortable()
                    ->alignEnd(),

                TextColumn::make('physical_count')
                    ->label('Stock Fisik')
                    ->state(function (Product $record) {
                        return $this->physicalCounts[$record->id] ?? $record->stock;
                    })
                    ->numeric(decimalPlaces: 2)
                    ->suffix(fn(Product $record) => ' ' . ($record->unit?->symbol ?? ''))
                    ->alignEnd()
                    ->badge()
                    ->color(fn(Product $record) => isset($this->physicalCounts[$record->id]) ? 'success' : 'gray'),

                TextColumn::make('variance')
                    ->label('Selisih')
                    ->state(function (Product $record) {
                        $physicalCount = $this->physicalCounts[$record->id] ?? $record->stock;
                        return $physicalCount - $record->stock;
                    })
                    ->numeric(decimalPlaces: 2)
                    ->color(fn($state) => $state < 0 ? 'danger' : ($state > 0 ? 'success' : 'gray'))
                    ->weight('bold')
                    ->alignEnd(),

                TextColumn::make('value_loss')
                    ->label('Kerugian')
                    ->state(function (Product $record) {
                        $physicalCount = $this->physicalCounts[$record->id] ?? $record->stock;
                        $variance = $physicalCount - $record->stock;
                        return $variance < 0 ? abs($variance) * ($record->base_price ?? 0) : 0;
                    })
                    ->money('IDR')
                    ->color('danger')
                    ->weight('bold')
                    ->alignEnd(),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name')
                    ->preload(),
            ])
            ->recordActions([
                Action::make('edit_count')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil')
                    ->form([
                        TextInput::make('physical_count')
                            ->label('Stock Fisik')
                            ->numeric()
                            ->step(0.01)
                            ->required()
                            ->default(fn(Product $record) => $this->physicalCounts[$record->id] ?? $record->stock)
                            ->suffix(fn(Product $record) => $record->unit?->symbol ?? ''),
                    ])
                    ->action(function (Product $record, array $data) {
                        $this->physicalCounts[$record->id] = (float) $data['physical_count'];

                        Notification::make()
                            ->title('Stock Fisik Diperbarui')
                            ->body("{$record->name} diperbarui ke {$data['physical_count']}")
                            ->success()
                            ->send();
                    }),
            ])
            ->headerActions([
                Action::make('submit_all_edited')
                    ->label('Simpan Semua Perubahan')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Simpan Semua Perubahan')
                    ->modalDescription(fn() => sprintf('Simpan stock opname untuk %d produk yang diubah?', count($this->physicalCounts)))
                    ->visible(fn() => count($this->physicalCounts) > 0)
                    ->action(function () {
                        // Get all edited products
                        $productIds = array_keys($this->physicalCounts);
                        $records = Product::whereIn('id', $productIds)->get();
                        $this->submitOpname($records);
                    }),

                Action::make('reset_all')
                    ->label('Reset Semua Perubahan')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn() => count($this->physicalCounts) > 0)
                    ->action(function () {
                        $this->physicalCounts = [];
                        Notification::make()
                            ->title('Semua perubahan direset')
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('name', 'asc')
            ->striped()
            ->paginated([10, 25, 50, 100, 'all']);
    }

    protected function submitOpname(Collection $records): void
    {
        $totalVariance = 0;
        $totalLoss = 0;
        $itemsAdjusted = 0;

        foreach ($records as $product) {
            $physicalCount = (float) ($this->physicalCounts[$product->id] ?? $product->stock);
            $systemStock = (float) $product->stock;
            $variance = $physicalCount - $systemStock;

            // Only create stock movement if there's a variance
            if ($variance != 0) {
                StockMovement::create([
                    'product_id' => $product->id,
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
                    $totalLoss += abs($variance) * (float) ($product->base_price ?? 0);
                }
            }
        }

        if ($itemsAdjusted === 0) {
            Notification::make()
                ->title('No Changes')
                ->body('No variance found in selected products.')
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

        // Clear physical counts after submit
        foreach ($records as $product) {
            unset($this->physicalCounts[$product->id]);
        }

        // Refresh table
        $this->dispatch('$refresh');
    }
}

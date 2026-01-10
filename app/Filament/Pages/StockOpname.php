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

    public static function getNavigationLabel(): string
    {
        return __('messages.stock_opname_page');
    }

    public function getTitle(): string
    {
        return __('messages.stock_opname_title');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('messages.menu_product');
    }

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
            if (!$product)
                continue;

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
                    ->where(function ($query) {
                        $query->whereIn('type', ['raw', 'retail'])
                            ->orWhere(function ($q) {
                                $q->whereIn('type', ['produced', 'bar'])
                                    ->where('enable_stock_alert', true);
                            });
                    })
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
                    ->label(__('messages.product_resource'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('stock_display')
                    ->label(__('messages.system_stock'))
                    ->state(function (Product $record) {
                        return in_array($record->type, ['produced', 'bar']) ? $record->prepared_stock : $record->stock;
                    })
                    ->numeric(decimalPlaces: 2)
                    ->suffix(fn(Product $record) => ' ' . ($record->unit?->symbol ?? ''))
                    ->sortable()
                    ->alignEnd(),

                TextColumn::make('physical_count')
                    ->label(__('messages.physical_stock'))
                    ->state(function (Product $record) {
                        $defaultStock = in_array($record->type, ['produced', 'bar']) ? ($record->prepared_stock ?? 0) : $record->stock;
                        return $this->physicalCounts[$record->id] ?? $defaultStock;
                    })
                    ->numeric(decimalPlaces: 2)
                    ->suffix(fn(Product $record) => ' ' . ($record->unit?->symbol ?? ''))
                    ->alignEnd()
                    ->badge()
                    ->color(fn(Product $record) => isset($this->physicalCounts[$record->id]) ? 'success' : 'gray'),

                TextColumn::make('variance')
                    ->label(__('messages.variance'))
                    ->state(function (Product $record) {
                        $systemStock = in_array($record->type, ['produced', 'bar']) ? ($record->prepared_stock ?? 0) : $record->stock;
                        $physicalCount = $this->physicalCounts[$record->id] ?? $systemStock;
                        return $physicalCount - $systemStock;
                    })
                    ->numeric(decimalPlaces: 2)
                    ->color(fn($state) => $state < 0 ? 'danger' : ($state > 0 ? 'success' : 'gray'))
                    ->weight('bold')
                    ->alignEnd(),

                TextColumn::make('value_loss')
                    ->label(__('messages.loss_value'))
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
                    ->label(__('messages.category'))
                    ->relationship('category', 'name')
                    ->preload(),
            ])
            ->recordActions([
                Action::make('edit_count')
                    ->label(__('messages.edit'))
                    ->icon('heroicon-o-pencil')
                    ->schema([
                        TextInput::make('physical_count')
                            ->label(__('messages.physical_stock'))
                            ->numeric()
                            ->step(0.01)
                            ->required()
                            ->default(fn(Product $record) => $this->physicalCounts[$record->id] ?? $record->stock)
                            ->suffix(fn(Product $record) => $record->unit?->symbol ?? ''),
                    ])
                    ->action(function (Product $record, array $data) {
                        $this->physicalCounts[$record->id] = (float) $data['physical_count'];

                        Notification::make()
                            ->title(__('messages.stock_updated_title'))
                            ->body("{$record->name} " . __('messages.updated_to') . " {$data['physical_count']}")
                            ->success()
                            ->send();
                    }),
            ])
            ->headerActions([
                Action::make('submit_all_edited')
                    ->label(__('messages.submit_all'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading(__('messages.submit_all'))
                    ->modalDescription(fn() => __('messages.submit_confirmation_desc', ['count' => count($this->physicalCounts)]))
                    ->visible(fn() => count($this->physicalCounts) > 0)
                    ->action(function () {
                        // Get all edited products
                        $productIds = array_keys($this->physicalCounts);
                        $records = Product::whereIn('id', $productIds)->get();
                        $this->submitOpname($records);
                    }),

                Action::make('reset_all')
                    ->label(__('messages.reset_all'))
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn() => count($this->physicalCounts) > 0)
                    ->action(function () {
                        $this->physicalCounts = [];
                        Notification::make()
                            ->title(__('messages.all_reset'))
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
            $isPrepared = in_array($product->type, ['produced', 'bar']);
            $systemStock = (float) ($isPrepared ? ($product->prepared_stock ?? 0) : $product->stock);

            // Get physical count from state, default to system stock if not set
            $physicalCount = (float) ($this->physicalCounts[$product->id] ?? $systemStock);

            $variance = $physicalCount - $systemStock;

            // Only process if there's a variance
            if ($variance != 0) {
                if ($isPrepared) {
                    // For Prepared Stock: Update directly and Log
                    // StockMovement is primarily for 'stock' column used by observers.
                    // We can opt to create a StockMovement with specific type/notes if needed, 
                    // but since Observer creates StockAdjustment, we might double count if we aren't careful?
                    // Review: StockMovement observer updates 'stock' column. 
                    // Prepared items use 'prepared_stock'. 
                    // So we should NOT use StockMovement for Prepared Items unless we modify Observer to handle it.
                    // EASIER: Directly update prepared_stock and just Log it.

                    $product->update(['prepared_stock' => $physicalCount]);

                    \Illuminate\Support\Facades\Log::info(__('messages.log_prepared_update', [
                        'product' => $product->name,
                        'system' => $systemStock,
                        'physical' => $physicalCount,
                        'variance' => $variance
                    ]));
                } else {
                    // For Raw/Retail Stock: Use StockMovement (Standard Flow)
                    StockMovement::create([
                        'product_id' => $product->id,
                        'quantity' => abs($variance),
                        'type' => $variance > 0 ? 'increase' : 'decrease',
                        'reason' => 'stock_opname',
                        'notes' => __('messages.notes_raw_retail', [
                            'system' => number_format($systemStock, 2),
                            'physical' => number_format($physicalCount, 2),
                            'variance' => number_format($variance, 2)
                        ]),
                    ]);
                }

                $itemsAdjusted++;
                $totalVariance += abs($variance);

                if ($variance < 0) {
                    // Calculate loss value
                    // note: for prepared items, base_price might be cost of ingredients? 
                    // simplified: use product base_price (HPP)
                    $totalLoss += abs($variance) * (float) ($product->base_price ?? 0);
                }
            }
        }

        if ($itemsAdjusted === 0) {
            Notification::make()
                ->title(__('messages.no_changes'))
                ->body(__('messages.no_variance'))
                ->warning()
                ->send();
            return;
        }

        Notification::make()
            ->title(__('messages.opname_completed'))
            ->body(__('messages.opname_completed_body', [
                'count' => $itemsAdjusted,
                'variance' => number_format($totalVariance, 2),
                'loss' => 'Rp ' . number_format($totalLoss, 0)
            ]))
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

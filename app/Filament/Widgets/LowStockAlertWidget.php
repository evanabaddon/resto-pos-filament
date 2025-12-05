<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Filament\Resources\Products\ProductResource;

class LowStockAlertWidget extends BaseWidget
{
    protected static ?int $sort = 7;

    /**
     * Make widget full width
     */
    protected int|string|array $columnSpan = 'full';
    
    public function table(Table $table): Table
    {
        return $table
            ->query(function (): Builder {
                return Product::query()
                    ->where('type', 'raw')
                    ->where('stock', '<=', 10)
                    ->orderBy('stock', 'asc');
            })
            ->columns([
                TextColumn::make('name')
                    ->label('NAMA BAHAN BAKU')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('stock')
                    ->label('STOK')
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state <= 0 => 'danger',
                        $state <= 5 => 'danger',
                        $state <= 10 => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (int $state): string => match (true) {
                        $state <= 0 => 'HABIS',
                        $state <= 5 => $state . ' (KRITIS)',
                        $state <= 10 => $state . ' (RENDAH)',
                        default => (string) $state,
                    })
                    ->alignCenter()
                    ->size('sm'),
                    
                TextColumn::make('unit.name')
                    ->label('UNIT')
                    ->sortable()
                    ->alignCenter()
                    ->size('sm'),
                    
                TextColumn::make('base_price')
                    ->label('HARGA/UNIT')
                    ->money('IDR')
                    ->sortable()
                    ->alignRight()
                    ->tooltip('Harga beli per unit')
                    ->description(fn (Product $record): string => $record->unit ? "per {$record->unit->name}" : '')
                    ->size('sm'),
                    
                TextColumn::make('total_stock_value')
                    ->label('NILAI STOK')
                    ->getStateUsing(fn (Product $record): float => $record->stock * $record->base_price)
                    ->money('IDR')
                    ->alignRight()
                    ->weight('semibold')
                    ->tooltip('Stok × Harga/Unit')
                    ->color(function (Product $record): string {
                        $totalValue = $record->stock * $record->base_price;
                        return $totalValue <= 0 ? 'danger' : 'success';
                    })
                    ->size('sm'),
                    
            ])
            ->recordActions([
                // edit
                Action::make('edit')
                    ->label('')
                    ->icon('heroicon-o-pencil')
                    ->tooltip('Edit produk')
                    ->url(fn (Product $record): string => ProductResource::getUrl('edit', ['record' => $record->id])),
                // quick purchase action
                Action::make('quick_purchase')
                    ->label('')
                    ->icon('heroicon-o-shopping-cart')
                    ->color('warning')
                    ->tooltip('Buat pembelian')
                    ->visible(fn (Product $record): bool => $record->stock <= 10)
                    ->action(function (Product $record) {
                        // Logic untuk redirect ke purchase creation dengan product_id
                        return redirect()->route('filament.admin.resources.purchases.create', [
                            'product_id' => $record->id,
                            'quantity' => max(20 - $record->stock, 10) // Rekomendasi jumlah
                        ]);
                    }),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->label('Kategori'),
                    
                Filter::make('stock_level')
                    ->label('Level Stok')
                    ->schema([
                        Select::make('level')
                            ->options([
                                'critical' => 'Kritis (≤5)',
                                'low' => 'Rendah (6-10)',
                                'out' => 'Habis (0)',
                            ])
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['level'] ?? null) {
                            'critical' => $query->where('stock', '>', 0)->where('stock', '<=', 5),
                            'low' => $query->where('stock', '>', 5)->where('stock', '<=', 10),
                            'out' => $query->where('stock', '<=', 0),
                            default => $query,
                        };
                    }),
            ])
            ->headerActions([
                Action::make('refresh')
                    ->label('Refresh')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->action(function () {
                        $this->dispatch('refreshTable');
                    }),
            ])
            ->emptyStateHeading('✅ Stok bahan baku aman')
            ->emptyStateDescription('Semua bahan baku memiliki stok yang cukup (lebih dari 10 unit)')
            ->emptyStateIcon('heroicon-o-check-circle')
            ->emptyStateActions([
                Action::make('view_all_raw_materials')
                    ->label('Lihat semua bahan baku')
                    ->icon('heroicon-o-list-bullet')
                    ->url(fn (): string => ProductResource::getUrl('index', [
                        'tableFilters' => [
                            'type' => ['value' => 'raw']
                        ]
                    ])),
            ])
            ->paginated(false);
    }
    
    protected function getTableHeading(): ?string
    {
        return '⚠️ Alert Stok Bahan Baku Rendah';
    }
    
    protected function getTableDescription(): ?string
    {
        // Hitung jumlah bahan baku dengan stok rendah
        $lowStockCount = Product::where('type', 'raw')
            ->where('stock', '<=', 10)
            ->count();
            
        return $lowStockCount > 0 
            ? "{$lowStockCount} bahan baku dengan stok ≤ 10 unit" 
            : 'Semua stok aman';
    }
    
    public function refreshTable(): void
    {
        // Filament/Livewire akan handle refresh otomatis
        // Method ini hanya sebagai placeholder
    }
    
    public static function canView(): bool
    {
        // Hanya tampilkan jika ada bahan baku dengan stok rendah
        return Product::where('type', 'raw')
            ->where('stock', '<=', 10)
            ->exists();
    }
}
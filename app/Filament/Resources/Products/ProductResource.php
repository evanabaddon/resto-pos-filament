<?php

namespace App\Filament\Resources\Products;

use UnitEnum;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use App\Models\Product;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\Schemas\ProductForm;
use App\Filament\Resources\Products\Tables\ProductsTable;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static string|UnitEnum|null $navigationGroup = 'Produk';

    protected static ?string $navigationLabel = 'Produk';

    // RBAC: super_admin, admin, inventory
    public static function canViewAny(): bool
    {
        return in_array(auth()->user()->role, ['super_admin', 'admin', 'inventory']);
    }

    public static function canCreate(): bool
    {
        return in_array(auth()->user()->role, ['super_admin', 'admin', 'inventory']);
    }

    public static function canEdit(Model $record): bool
    {
        return in_array(auth()->user()->role, ['super_admin', 'admin', 'inventory']);
    }

    public static function canDelete(Model $record): bool
    {
        return in_array(auth()->user()->role, ['super_admin', 'admin', 'inventory']);
    }

    public static function form(Schema $schema): Schema
    {
        return ProductForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                RepeatableEntry::make('stockMovements')
                    ->label('Riwayat Pergerakan Stok (20 Terakhir)')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Tanggal')
                                    ->dateTime('d M Y H:i')
                                    ->icon('heroicon-o-calendar')
                                    ->columnSpan(2),

                                TextEntry::make('type')
                                    ->label('Tipe')
                                    ->badge()
                                    ->colors([
                                        'success' => 'increase',
                                        'danger' => 'decrease',
                                    ])
                                    ->formatStateUsing(fn($state) => $state === 'increase' ? 'Masuk' : 'Keluar')
                                    ->icon(fn($state) => $state === 'increase' ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down'),

                                TextEntry::make('quantity')
                                    ->label('Jumlah')
                                    ->numeric(2)
                                    ->weight('bold')
                                    ->size('lg'),

                                TextEntry::make('reason')
                                    ->label('Alasan')
                                    ->color('gray')
                                    ->columnSpan(4),

                                TextEntry::make('notes')
                                    ->label('Catatan')
                                    ->columnSpan(4)
                                    ->placeholder('-')
                                    ->prose(),
                            ])
                    ])
                    ->grid(2)
                    ->columnSpanFull()
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->where('name', '!=', 'Down Payment (DP)');
    }
}

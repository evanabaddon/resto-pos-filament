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

    public static function getNavigationLabel(): string
    {
        return __('messages.products');
    }

    public static function getModelLabel(): string
    {
        return __('messages.product');
    }

    public static function getPluralModelLabel(): string
    {
        return __('messages.products');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('messages.menu_product'); // Or just 'Produk' if we want to keep it simple, but let's translate
    }

    // RBAC: super_admin, admin, inventory
    public static function canViewAny(): bool
    {
        return auth()->user()->role === \App\Enums\UserRole::SuperAdmin || auth()->user()->role === \App\Enums\UserRole::Admin || auth()->user()->role === \App\Enums\UserRole::Inventory;
    }

    public static function canCreate(): bool
    {
        return auth()->user()->role === \App\Enums\UserRole::SuperAdmin || auth()->user()->role === \App\Enums\UserRole::Admin || auth()->user()->role === \App\Enums\UserRole::Inventory;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()->role === \App\Enums\UserRole::SuperAdmin || auth()->user()->role === \App\Enums\UserRole::Admin || auth()->user()->role === \App\Enums\UserRole::Inventory;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()->role === \App\Enums\UserRole::SuperAdmin || auth()->user()->role === \App\Enums\UserRole::Admin || auth()->user()->role === \App\Enums\UserRole::Inventory;
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
                    ->label(__('messages.stock_movement_history'))
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label(__('messages.date'))
                                    ->dateTime('d M Y H:i')
                                    ->icon('heroicon-o-calendar')
                                    ->columnSpan(2),

                                TextEntry::make('type')
                                    ->label(__('messages.type'))
                                    ->badge()
                                    ->colors([
                                        'success' => 'increase',
                                        'danger' => 'decrease',
                                    ])
                                    ->formatStateUsing(fn($state) => $state === 'increase' ? 'Masuk' : 'Keluar')
                                    ->icon(fn($state) => $state === 'increase' ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down'),

                                TextEntry::make('quantity')
                                    ->label(__('messages.quantity'))
                                    ->numeric(2)
                                    ->weight('bold')
                                    ->size('lg'),

                                TextEntry::make('reason')
                                    ->label(__('messages.reason'))
                                    ->color('gray')
                                    ->columnSpan(4),

                                TextEntry::make('notes')
                                    ->label(__('messages.notes'))
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

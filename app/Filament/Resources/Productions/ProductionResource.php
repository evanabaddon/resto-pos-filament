<?php

namespace App\Filament\Resources\Productions;

use App\Filament\Resources\Productions\Pages;
use App\Models\Production;
use App\Models\Product;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Enums\UserRole;
use BackedEnum;
use UnitEnum;
use App\Filament\Resources\Productions\Schemas\ProductionForm;
use App\Filament\Resources\Productions\Tables\ProductionsTable;

class ProductionResource extends Resource
{
    protected static ?string $model = Production::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cake';

    protected static string|UnitEnum|null $navigationGroup = 'Produk';

    public static function getNavigationLabel(): string
    {
        return __('messages.productions');
    }

    public static function getModelLabel(): string
    {
        return __('messages.production');
    }

    public static function getPluralModelLabel(): string
    {
        return __('messages.productions');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('messages.menu_product');
    }

    protected static ?int $navigationSort = 3;

    public static function canViewAny(): bool
    {
        // Adjust RBAC as needed
        return in_array(auth()->user()->role, [UserRole::SuperAdmin, UserRole::Admin, UserRole::Inventory, UserRole::Kitchen]);
    }

    public static function form(Schema $schema): Schema
    {
        return ProductionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductionsTable::configure($table);
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
            'index' => Pages\ListProductions::route('/'),
            'create' => Pages\CreateProduction::route('/create'),
        ];
    }
}

<?php

namespace App\Filament\Clusters\Crm\Resources\LoyaltyTiers;

use App\Filament\Clusters\Crm\CrmCluster;
use App\Filament\Clusters\Crm\Resources\LoyaltyTiers\Pages\CreateLoyaltyTier;
use App\Filament\Clusters\Crm\Resources\LoyaltyTiers\Pages\EditLoyaltyTier;
use App\Filament\Clusters\Crm\Resources\LoyaltyTiers\Pages\ListLoyaltyTiers;
use App\Filament\Clusters\Crm\Resources\LoyaltyTiers\Schemas\LoyaltyTierForm;
use App\Filament\Clusters\Crm\Resources\LoyaltyTiers\Tables\LoyaltyTiersTable;
use App\Models\LoyaltyTier;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use BackedEnum;
use UnitEnum;

class LoyaltyTierResource extends Resource
{
    protected static ?string $model = LoyaltyTier::class;

    protected static string|UnitEnum|null $navigationGroup = 'Kemitraan (CRM)';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-star';

    protected static ?string $modelLabel = 'Level';
    public static function shouldRegisterNavigation(): bool
    {
        return app(\App\Settings\GeneralSettings::class)->enable_crm;
    }

    public static function form(Schema $schema): Schema
    {
        return LoyaltyTierForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LoyaltyTiersTable::configure($table);
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
            'index' => ListLoyaltyTiers::route('/'),
            'create' => CreateLoyaltyTier::route('/create'),
            'edit' => EditLoyaltyTier::route('/{record}/edit'),
        ];
    }
}

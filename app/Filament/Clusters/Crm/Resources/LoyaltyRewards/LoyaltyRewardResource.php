<?php

namespace App\Filament\Clusters\Crm\Resources\LoyaltyRewards;

use App\Filament\Clusters\Crm\CrmCluster;
use App\Filament\Clusters\Crm\Resources\LoyaltyRewards\Pages\CreateLoyaltyReward;
use App\Filament\Clusters\Crm\Resources\LoyaltyRewards\Pages\EditLoyaltyReward;
use App\Filament\Clusters\Crm\Resources\LoyaltyRewards\Pages\ListLoyaltyRewards;
use App\Filament\Clusters\Crm\Resources\LoyaltyRewards\Schemas\LoyaltyRewardForm;
use App\Filament\Clusters\Crm\Resources\LoyaltyRewards\Tables\LoyaltyRewardsTable;
use App\Models\LoyaltyReward;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use BackedEnum;
use UnitEnum;

class LoyaltyRewardResource extends Resource
{
    protected static ?string $model = LoyaltyReward::class;

    protected static string|UnitEnum|null $navigationGroup = 'Kemitraan (CRM)';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-gift';

    protected static ?string $modelLabel = 'Katalog Hadiah';
    public static function shouldRegisterNavigation(): bool
    {
        return app(\App\Settings\GeneralSettings::class)->enable_crm;
    }

    public static function form(Schema $schema): Schema
    {
        return LoyaltyRewardForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LoyaltyRewardsTable::configure($table);
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
            'index' => ListLoyaltyRewards::route('/'),
            'create' => CreateLoyaltyReward::route('/create'),
            'edit' => EditLoyaltyReward::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Clusters\Hrm\Resources\Contracts;

use App\Filament\Clusters\Hrm\HrmCluster;
use App\Filament\Clusters\Hrm\Resources\Contracts\Pages;
use App\Filament\Clusters\Hrm\Resources\Contracts\Schemas\ContractForm;
use App\Filament\Clusters\Hrm\Resources\Contracts\Tables\ContractsTable;
use App\Models\Contract;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ContractResource extends Resource
{
    protected static ?string $model = Contract::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 2;

    protected static ?string $cluster = HrmCluster::class;

    public static function shouldRegisterNavigation(): bool
    {
        return app(\App\Settings\GeneralSettings::class)->enable_hrm;
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.contracts_resource');
    }

    public static function getModelLabel(): string
    {
        return __('messages.contract_resource');
    }

    public static function getPluralModelLabel(): string
    {
        return __('messages.contracts_resource');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('messages.hrm_cluster');
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()->role === \App\Enums\UserRole::SuperAdmin || auth()->user()->role === \App\Enums\UserRole::Admin;
    }

    public static function form(Schema $schema): Schema
    {
        return ContractForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContractsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContracts::route('/'),
            'create' => Pages\CreateContract::route('/create'),
            'edit' => Pages\EditContract::route('/{record}/edit'),
        ];
    }
}

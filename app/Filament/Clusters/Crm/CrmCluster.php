<?php

namespace App\Filament\Clusters\Crm;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class CrmCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;
    public static function getNavigationLabel(): string
    {
        return __('messages.crm_cluster');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('messages.crm_cluster');
    }

    protected static ?string $slug = 'crm';

    public static function shouldRegisterNavigation(): bool
    {
        return in_array(auth()->user()->role, [\App\Enums\UserRole::SuperAdmin, \App\Enums\UserRole::Admin, \App\Enums\UserRole::Cashier]);
    }
}

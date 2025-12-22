<?php

namespace App\Filament\Clusters\Crm;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class CrmCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;
    protected static ?string $navigationLabel = 'CRM';

    protected static string|UnitEnum|null $navigationGroup = 'Kemitraan CRM';

    protected static ?string $slug = 'crm';

    public static function shouldRegisterNavigation(): bool
    {
        return in_array(auth()->user()->role, ['super_admin', 'admin', 'cashier']);
    }
}

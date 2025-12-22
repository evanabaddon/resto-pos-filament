<?php

namespace App\Filament\Clusters\Hrm;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class HrmCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;
    protected static ?string $navigationLabel = 'HRM';

    protected static string|UnitEnum|null $navigationGroup = 'Manajemen SDM';

    public static function shouldRegisterNavigation(): bool
    {
        return in_array(auth()->user()->role, ['super_admin', 'admin', 'accountant']);
    }
}

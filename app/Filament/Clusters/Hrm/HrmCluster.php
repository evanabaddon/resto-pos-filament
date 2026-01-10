<?php

namespace App\Filament\Clusters\Hrm;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class HrmCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;
    public static function getNavigationLabel(): string
    {
        return __('messages.hrm_cluster');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('messages.hrm_cluster');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->role === \App\Enums\UserRole::SuperAdmin || auth()->user()->role === \App\Enums\UserRole::Admin;
    }
}

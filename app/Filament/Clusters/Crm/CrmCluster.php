<?php

namespace App\Filament\Clusters\Crm;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class CrmCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;
    protected static ?string $navigationLabel = 'Kemitraan (CRM)';
    protected static ?string $slug = 'crm';
}

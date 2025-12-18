<?php

namespace App\Filament\Clusters\Crm\Resources\LoyaltyTiers\Pages;

use App\Filament\Clusters\Crm\Resources\LoyaltyTiers\LoyaltyTierResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLoyaltyTier extends CreateRecord
{
    protected static string $resource = LoyaltyTierResource::class;
}

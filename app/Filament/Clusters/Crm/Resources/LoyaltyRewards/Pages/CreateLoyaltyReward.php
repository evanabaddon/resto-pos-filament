<?php

namespace App\Filament\Clusters\Crm\Resources\LoyaltyRewards\Pages;

use App\Filament\Clusters\Crm\Resources\LoyaltyRewards\LoyaltyRewardResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLoyaltyReward extends CreateRecord
{
    protected static string $resource = LoyaltyRewardResource::class;
}

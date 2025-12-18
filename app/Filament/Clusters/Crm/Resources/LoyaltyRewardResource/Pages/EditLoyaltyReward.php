<?php

namespace App\Filament\Clusters\Crm\Resources\LoyaltyRewardResource\Pages;

use App\Filament\Clusters\Crm\Resources\LoyaltyRewardResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLoyaltyReward extends EditRecord
{
    protected static string $resource = LoyaltyRewardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
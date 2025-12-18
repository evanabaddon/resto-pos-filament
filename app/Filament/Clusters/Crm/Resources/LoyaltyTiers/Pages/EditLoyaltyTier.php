<?php

namespace App\Filament\Clusters\Crm\Resources\LoyaltyTiers\Pages;

use App\Filament\Clusters\Crm\Resources\LoyaltyTiers\LoyaltyTierResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLoyaltyTier extends EditRecord
{
    protected static string $resource = LoyaltyTierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Clusters\Crm\Resources\LoyaltyTierResource\Pages;

use App\Filament\Clusters\Crm\Resources\LoyaltyTierResource;
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
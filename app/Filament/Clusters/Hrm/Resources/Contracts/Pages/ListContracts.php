<?php

namespace App\Filament\Clusters\Hrm\Resources\Contracts\Pages;

use App\Filament\Clusters\Hrm\Resources\Contracts\ContractResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListContracts extends ListRecords
{
    protected static string $resource = ContractResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

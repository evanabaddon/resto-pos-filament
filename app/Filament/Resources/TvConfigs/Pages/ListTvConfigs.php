<?php

namespace App\Filament\Resources\TvConfigs\Pages;

use App\Filament\Resources\TvConfigs\TvConfigResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTvConfigs extends ListRecords
{
    protected static string $resource = TvConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

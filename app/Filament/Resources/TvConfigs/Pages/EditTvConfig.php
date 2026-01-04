<?php

namespace App\Filament\Resources\TvConfigs\Pages;

use App\Filament\Resources\TvConfigs\TvConfigResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTvConfig extends EditRecord
{
    protected static string $resource = TvConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

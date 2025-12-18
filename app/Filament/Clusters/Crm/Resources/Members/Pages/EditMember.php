<?php

namespace App\Filament\Clusters\Crm\Resources\Members\Pages;

use App\Filament\Clusters\Crm\Resources\Members\MemberResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMember extends EditRecord
{
    protected static string $resource = MemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
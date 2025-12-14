<?php

namespace App\Filament\Clusters\Hrm\Resources\Contracts\Pages;

use App\Filament\Clusters\Hrm\Resources\Contracts\ContractResource;
use Filament\Resources\Pages\EditRecord;

class EditContract extends EditRecord
{
    protected static string $resource = ContractResource::class;
}

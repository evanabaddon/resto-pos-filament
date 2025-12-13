<?php

namespace App\Filament\Clusters\Hrm\Resources\PayrollResource\Pages;

use App\Filament\Clusters\Hrm\Resources\PayrollResource\PayrollResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPayroll extends EditRecord
{
    protected static string $resource = PayrollResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

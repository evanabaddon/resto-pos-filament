<?php

namespace App\Filament\Clusters\Hrm\Resources\PayrollResource\Pages;

use App\Filament\Clusters\Hrm\Resources\PayrollResource\PayrollResource;
use Filament\Resources\Pages\ListRecords;

class ListPayrolls extends ListRecords
{
    protected static string $resource = PayrollResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No Create Action, only Generate via Resource Header Action (or move it here)
        ];
    }
}

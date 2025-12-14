<?php

namespace App\Filament\Clusters\Hrm\Resources\PayrollFormulaResource\Pages;

use App\Filament\Clusters\Hrm\Resources\PayrollFormulaResource\PayrollFormulaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPayrollFormulas extends ListRecords
{
    protected static string $resource = PayrollFormulaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

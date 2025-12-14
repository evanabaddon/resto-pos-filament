<?php

namespace App\Filament\Clusters\Hrm\Resources\PayrollFormulaResource\Pages;

use App\Filament\Clusters\Hrm\Resources\PayrollFormulaResource\PayrollFormulaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPayrollFormula extends EditRecord
{
    protected static string $resource = PayrollFormulaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (empty($data['settings']['is_advanced'])) {
            $data['script'] = PayrollFormulaResource::calculateScript($data['settings']);
        }

        return $data;
    }
}

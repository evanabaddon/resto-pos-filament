<?php

namespace App\Filament\Clusters\Hrm\Resources\PayrollFormulaResource\Pages;

use App\Filament\Clusters\Hrm\Resources\PayrollFormulaResource\PayrollFormulaResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePayrollFormula extends CreateRecord
{
    protected static string $resource = PayrollFormulaResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Force script generation for Simple Mode
        // We check if "is_advanced" is false, or just always generate if script is empty/hidden
        if (empty($data['settings']['is_advanced'])) {
            $data['script'] = PayrollFormulaResource::calculateScript($data['settings']);
        }

        return $data;
    }
}

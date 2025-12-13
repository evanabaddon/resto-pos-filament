<?php

namespace App\Filament\Clusters\Hrm\Resources\PayrollResource\Pages;

use App\Filament\Clusters\Hrm\Resources\PayrollResource\PayrollResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPayroll extends ViewRecord
{
    protected static string $resource = PayrollResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('print')
                ->label('Print PDF')
                ->icon('heroicon-o-printer')
                ->url(fn($record) => route('payroll.print', $record))
                ->openUrlInNewTab(),
            Actions\EditAction::make(),
        ];
    }
}

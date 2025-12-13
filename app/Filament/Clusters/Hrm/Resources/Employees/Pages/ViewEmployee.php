<?php

namespace App\Filament\Clusters\Hrm\Resources\Employees\Pages;

use App\Filament\Clusters\Hrm\Resources\Employees\EmployeeResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewEmployee extends ViewRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\EmployeeAttendanceStats::class,
        ];
    }
}

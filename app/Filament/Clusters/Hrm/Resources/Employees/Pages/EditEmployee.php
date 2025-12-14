<?php

namespace App\Filament\Clusters\Hrm\Resources\Employees\Pages;

use App\Filament\Clusters\Hrm\Resources\Employees\EmployeeResource;
use App\Filament\Clusters\Hrm\Resources\Employees\Widgets\EmployeeAttendanceStats;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEmployee extends EditRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            EmployeeAttendanceStats::class,
        ];
    }
}

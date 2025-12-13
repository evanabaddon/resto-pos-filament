<?php

namespace App\Filament\Clusters\Hrm\Resources\Employees\Pages;

use App\Filament\Clusters\Hrm\Resources\Employees\EmployeeResource;
use Filament\Resources\Pages\EditRecord;

class EditEmployee extends EditRecord
{
    protected static string $resource = EmployeeResource::class;
}

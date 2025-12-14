<?php

namespace App\Filament\Clusters\Hrm\Resources\Employees\Pages;

use App\Filament\Clusters\Hrm\Resources\Employees\EmployeeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;
}

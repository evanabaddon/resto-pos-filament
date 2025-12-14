<?php

namespace App\Filament\Clusters\Hrm\Resources\LeaveRequests\Pages;

use App\Filament\Clusters\Hrm\Resources\LeaveRequests\LeaveRequestResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLeaveRequest extends CreateRecord
{
    protected static string $resource = LeaveRequestResource::class;
}

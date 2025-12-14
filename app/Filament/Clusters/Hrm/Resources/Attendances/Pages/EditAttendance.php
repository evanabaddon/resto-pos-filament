<?php

namespace App\Filament\Clusters\Hrm\Resources\Attendances\Pages;

use App\Filament\Clusters\Hrm\Resources\Attendances\AttendanceResource;
use Filament\Resources\Pages\EditRecord;

class EditAttendance extends EditRecord
{
    protected static string $resource = AttendanceResource::class;
}

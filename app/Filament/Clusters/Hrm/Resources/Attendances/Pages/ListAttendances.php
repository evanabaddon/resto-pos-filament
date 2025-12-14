<?php

namespace App\Filament\Clusters\Hrm\Resources\Attendances\Pages;

use App\Filament\Clusters\Hrm\Resources\Attendances\AttendanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAttendances extends ListRecords
{
    protected static string $resource = AttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use BackedEnum;

class AttendanceKiosk extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected string $view = 'filament.pages.attendance-kiosk-wrapper';

    protected static ?string $navigationLabel = 'Kiosk Absensi';

    protected static ?string $slug = 'attendance-kiosk';

    protected static bool $shouldRegisterNavigation = false;
}

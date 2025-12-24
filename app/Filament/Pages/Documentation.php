<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use BackedEnum;
use UnitEnum;
use Filament\Support\Icons\Heroicon;

class Documentation extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-book-open';

    protected string $view = 'filament.pages.documentation';

    protected static UnitEnum|string|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 100;

    protected static ?string $navigationLabel = 'Dokumentasi Sistem';

    protected static ?string $title = 'Dokumentasi & Manual Guide';

    public static function canAccess(): bool
    {
        // Accessible by all authenticated users
        return auth()->check();
    }
}

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

    public static function getNavigationGroup(): ?string
    {
        return __('messages.settings');
    }

    protected static ?int $navigationSort = 100;

    protected static ?string $navigationLabel = 'Dokumentasi Sistem';

    protected static ?string $title = 'Dokumentasi & Manual Guide';

    public static function canAccess(): bool
    {
        // Accessible by all authenticated users
        return auth()->check();
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('exportPdf')
                ->label('Download PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.documentation');

                    return response()->streamDownload(function () use ($pdf) {
                        echo $pdf->output();
                    }, 'Dokumentasi-Sistem-' . date('Ymd') . '.pdf');
                }),
        ];
    }
}

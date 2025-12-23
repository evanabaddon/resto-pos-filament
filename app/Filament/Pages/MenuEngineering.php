<?php

namespace App\Filament\Pages;

use App\Services\DeepSeekService;
use App\Services\MenuEngineeringService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;
use BackedEnum;
use UnitEnum;

class MenuEngineering extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static ?string $navigationLabel = 'Menu Engineering (AI)';

    public static function canAccess(): bool
    {
        return auth()->user()->role === \App\Enums\UserRole::SuperAdmin || auth()->user()->role === \App\Enums\UserRole::Admin;
    }
    protected static ?string $title = 'AI Menu Engineering';
    protected static string|UnitEnum|null $navigationGroup = 'Laporan & Analisis';
    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.menu-engineering';

    public $matrixData = [];
    public $aiAdvice = null;
    public $lastGeneratedAt = null;

    public static function shouldRegisterNavigation(): bool
    {
        $settings = app(\App\Settings\GeneralSettings::class);
        return $settings->enable_menu_engineering && !empty($settings->menu_engineering_license_key);
    }

    public function mount(): void
    {
        $settings = app(\App\Settings\GeneralSettings::class);
        if (!$settings->enable_menu_engineering) {
            abort(403);
        }

        // Load cached results
        $cached = Cache::get('menu_engineering_analysis');
        if ($cached) {
            $this->matrixData = $cached['matrix'];
            $this->aiAdvice = $cached['advice'];
            $this->lastGeneratedAt = $cached['timestamp'];
        }
    }

    public function generateMatrix()
    {
        try {
            $service = new MenuEngineeringService();
            $this->matrixData = $service->getMatrix(30);

            $deepSeek = new DeepSeekService();
            $advice = $deepSeek->analyzeMenuMatrix($this->matrixData);

            if (!$advice || !isset($advice['overall_analysis'])) {
                throw new \Exception('Gagal mendapatkan saran strategis dari AI. Silakan coba lagi.');
            }

            $this->aiAdvice = $advice;
            $this->lastGeneratedAt = now()->format('d M Y, H:i');

            // Cache for 24 hours
            Cache::put('menu_engineering_analysis', [
                'matrix' => $this->matrixData,
                'advice' => $this->aiAdvice,
                'timestamp' => $this->lastGeneratedAt,
            ], now()->addHours(24));

            Notification::make()
                ->title('Analisis Berhasil')
                ->body('Data profitabilitas menu dan saran AI telah diperbarui.')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Gagal melakukan analisis')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function exportPdf()
    {
        if (!$this->matrixData || empty($this->matrixData['items'])) {
            Notification::make()
                ->title('Export Gagal')
                ->body('Data analisis belum tersedia untuk di-export.')
                ->danger()
                ->send();
            return;
        }

        try {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.menu-engineering', [
                'matrixData' => $this->matrixData,
                'aiAdvice' => $this->aiAdvice,
                'lastGeneratedAt' => $this->lastGeneratedAt,
            ]);

            return response()->streamDownload(
                fn() => print($pdf->output()),
                'menu-engineering-analysis-' . now()->timestamp . '.pdf'
            );
        } catch (\Exception $e) {
            Notification::make()
                ->title('Gagal melakukan export')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}

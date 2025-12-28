<?php

namespace App\Filament\Pages;

use App\Services\InventoryService;
use App\Services\DeepSeekService;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Colors\Color;
use BackedEnum;
use UnitEnum;
use Illuminate\Support\Facades\Cache;

class InventoryForecasting extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';
    protected static string|UnitEnum|null $navigationGroup = 'Laporan & Analisis';
    protected static ?string $navigationLabel = 'Forecasting Stok (AI)';

    public static function canAccess(): bool
    {
        return auth()->user()->role === \App\Enums\UserRole::SuperAdmin || auth()->user()->role === \App\Enums\UserRole::Admin;
    }
    protected static ?string $title = 'AI Smart Inventory';
    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        return app(\App\Settings\GeneralSettings::class)->enable_ai_forecasting;
    }

    protected string $view = 'filament.pages.inventory-forecasting';

    public array $historyData = [];
    public ?array $aiResults = null;
    public ?string $lastGeneratedAt = null;
    public bool $isLoading = false;
    public int $forecastDays = 7;
    public string $forecastMode = 'weekly'; // weekly | daily

    public function mount(InventoryService $inventoryService)
    {
        if (!app(\App\Settings\GeneralSettings::class)->enable_ai_forecasting) {
            abort(403, 'Akses Modul AI Forecasting ditolak. Silakan aktifkan di Pengaturan.');
        }

        $this->historyData = $inventoryService->getForecastingData(30); // Get 30 days history for better pattern detection
        $this->loadCachedResults();
    }

    public function updatedForecastMode()
    {
        $this->loadCachedResults();
    }

    protected function loadCachedResults()
    {
        $cacheKey = 'inventory_forecast_result_' . $this->forecastMode;
        $cached = Cache::get($cacheKey);

        if ($cached) {
            $this->aiResults = $cached['results'];
            $this->lastGeneratedAt = $cached['timestamp'];
        } else {
            $this->aiResults = null;
            $this->lastGeneratedAt = null;
        }
    }

    public function generateAiForecast(InventoryService $inventoryService, DeepSeekService $deepSeekService)
    {
        $this->isLoading = true;

        try {
            // Determine days based on mode
            $daysToForecast = $this->forecastMode === 'daily' ? 1 : 7;

            // Get data (always get 30 days to see patterns)
            $data = $inventoryService->getForecastingData(30);

            $result = $deepSeekService->forecastStock($data, $daysToForecast);

            if ($result && isset($result['recommendations'])) {
                $this->aiResults = $result;
                $this->lastGeneratedAt = now()->format('d M Y, H:i');

                // Cache the results for 24 hours per mode
                Cache::put('inventory_forecast_result_' . $this->forecastMode, [
                    'results' => $this->aiResults,
                    'timestamp' => $this->lastGeneratedAt,
                ], now()->addHours(24));

                Notification::make()
                    ->title('Prediksi Berhasil')
                    ->success()
                    ->send();
            } else {
                throw new \Exception('Invalid AI response format.');
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Gagal generate prediksi')
                ->body($e->getMessage())
                ->danger()
                ->send();
        } finally {
            $this->isLoading = false;
        }
    }

    public function exportToPdf()
    {
        if (!$this->aiResults) {
            Notification::make()
                ->title('Belum ada data untuk diexport')
                ->warning()
                ->send();
            return;
        }

        try {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.inventory-forecast', [
                'aiResults' => $this->aiResults,
                'historyData' => $this->historyData,
                'timestamp' => $this->lastGeneratedAt,
            ]);

            return response()->streamDownload(
                fn() => print ($pdf->output()),
                'forecasting-report-' . now()->timestamp . '.pdf'
            );
        } catch (\Exception $e) {
            Notification::make()
                ->title('Gagal export PDF')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}

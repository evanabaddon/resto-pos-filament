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
    public ?string $selectedDay = null; // New property for day selection

    public function mount(InventoryService $inventoryService)
    {
        if (!app(\App\Settings\GeneralSettings::class)->enable_ai_forecasting) {
            abort(403, 'Akses Modul AI Forecasting ditolak. Silakan aktifkan di Pengaturan.');
        }

        $this->historyData = $inventoryService->getForecastingData(30);
        $this->selectedDay = date('l'); // Default to today (English)

        // Load cached results
        $cached = Cache::get('inventory_forecast_consolidated_v5');
        if ($cached) {
            $this->aiResults = $cached['results'];
            $this->lastGeneratedAt = $cached['timestamp'];
        }
    }

    public function generateAiForecast(InventoryService $inventoryService, DeepSeekService $deepSeekService)
    {
        $this->isLoading = true;

        try {
            // Always get 30 days history for patterns
            $data = $inventoryService->getForecastingData(30);

            // Forecast for 7 days, but prompt will now also ask for specialized TOMORROW forecast
            // Pass the selected day manually to override "Today" context
            $result = $deepSeekService->forecastStock($data, 7, $this->selectedDay);

            if ($result) {
                $this->aiResults = $result;
                $this->lastGeneratedAt = now()->format('d M Y, H:i');

                // Cache the consolidated results
                Cache::put('inventory_forecast_consolidated_v5', [
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

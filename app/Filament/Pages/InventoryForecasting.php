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
    protected static string|UnitEnum|null $navigationGroup = 'Produk';
    protected static ?string $navigationLabel = 'Prediksi Restock (AI)';
    protected static ?string $title = 'Smart Inventory Forecasting';

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

    public function mount(InventoryService $inventoryService)
    {
        if (!app(\App\Settings\GeneralSettings::class)->enable_ai_forecasting) {
            abort(403, 'Akses Modul AI Forecasting ditolak. Silakan aktifkan di Pengaturan.');
        }

        $this->historyData = $inventoryService->getForecastingData();

        // Load cached results if available
        $cached = Cache::get('inventory_forecast_result');
        if ($cached) {
            $this->aiResults = $cached['results'];
            $this->lastGeneratedAt = $cached['timestamp'];
        }
    }

    public function getHeaderActions(): array
    {
        return [
            Action::make('generateForecast')
                ->label('Generate AI Forecast')
                ->icon('heroicon-o-sparkles')
                ->color(Color::Indigo)
                ->action('generateAiForecast'),

            Action::make('exportPdf')
                ->label('Export PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color(Color::Gray)
                ->action('exportToPdf')
                ->visible(fn() => $this->aiResults !== null),
        ];
    }

    public function generateAiForecast(InventoryService $inventoryService, DeepSeekService $deepSeekService)
    {
        $this->isLoading = true;

        try {
            $data = $inventoryService->getForecastingData();
            $result = $deepSeekService->forecastStock($data, $this->forecastDays);

            if ($result && isset($result['recommendations'])) {
                $this->aiResults = $result;
                $this->lastGeneratedAt = now()->format('d M Y, H:i');

                // Cache the results for 24 hours
                Cache::put('inventory_forecast_result', [
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
                fn() => print($pdf->output()),
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

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
    public bool $isLoading = false;
    public int $forecastDays = 7;

    public function mount(InventoryService $inventoryService)
    {
        if (!app(\App\Settings\GeneralSettings::class)->enable_ai_forecasting) {
            abort(403, 'Akses Modul AI Forecasting ditolak. Silakan aktifkan di Pengaturan.');
        }

        $this->historyData = $inventoryService->getForecastingData();
    }

    public function getHeaderActions(): array
    {
        return [
            Action::make('generateForecast')
                ->label('Generate AI Forecast')
                ->icon('heroicon-o-sparkles')
                ->color(Color::Indigo)
                ->action('generateAiForecast'),
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
}

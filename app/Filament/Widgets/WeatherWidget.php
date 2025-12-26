<?php

namespace App\Filament\Widgets;

use App\Settings\GeneralSettings;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class WeatherWidget extends Widget
{
    protected static ?int $sort = -2; // Always on top
    protected string $view = 'filament.widgets.weather-widget';
    protected int|string|array $columnSpan = 'full';

    public $weatherData = null;
    public $locationName = null;
    public $error = null;

    public function mount()
    {
        $settings = app(GeneralSettings::class);
        $code = $settings->bmkg_location_code;

        if (!$code) {
            return;
        }

        // Cache Key based on location code
        $cacheKey = "bmkg_weather_{$code}";

        // Cache 
        $this->weatherData = Cache::remember($cacheKey, 3600, function () use ($code) {
            try {
                $response = Http::timeout(5)->get("https://api.bmkg.go.id/publik/prakiraan-cuaca?adm4={$code}");
                if ($response->successful()) {
                    return $response->json();
                }
            } catch (\Exception $e) {
                // Fail silently or log
            }
            return null;
        });

        if ($this->weatherData) {
            $lokasi = $this->weatherData['lokasi'] ?? [];
            $this->locationName = "{$lokasi['desa']}, {$lokasi['kotkab']}";
        }
    }

    public function getData(): array
    {
        $settings = app(GeneralSettings::class);
        $code = $settings->bmkg_location_code;

        if (!$code) {
            return [];
        }

        $service = app(\App\Services\BmkgWeatherService::class);
        $weatherData = $service->getWeather($code);

        if (!$weatherData || !isset($weatherData['data'][0]['cuaca'])) {
            return [];
        }

        $cuacaList = collect($weatherData['data'][0]['cuaca'])->flatten(1);

        // Filter: Start from current time (allow 3 hours back for current active weather) and take next 6 slots
        $now = Carbon::now();
        $forecast = collect($cuacaList)
            ->map(function ($item) {
                $item['carbon_date'] = Carbon::parse($item['local_datetime']);
                return $item;
            })
            ->filter(function ($item) use ($now) {
                // Only show future items or the immediate past one (current weather)
                return $item['carbon_date']->gte($now->copy()->subHours(4));
            })
            ->values()
            ->take(4); // Limit to 4 items for a cleaner look

        return $forecast->toArray();
    }
}

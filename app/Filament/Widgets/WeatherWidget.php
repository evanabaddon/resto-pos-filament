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
        // The weather data fetching logic is now handled by getData() using BmkgWeatherService.
        // This mount method can be used to set locationName if needed, or removed if not.
        // For now, we'll keep it as is, but its weatherData fetching part is effectively superseded.
        $settings = app(GeneralSettings::class);
        $code = $settings->bmkg_location_code;

        if (!$code) {
            return;
        }

        // Cache Key based on location code
        $cacheKey = "bmkg_weather_{$code}";

        // Cache for 1 hour
        // This part is now redundant if getData() fetches its own data.
        // However, if the view relies on $this->weatherData or $this->locationName directly,
        // this mount method might still be needed to populate them.
        // For this specific change, we are only replacing getData's logic.
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
        $settings = app(\App\Settings\GeneralSettings::class);
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

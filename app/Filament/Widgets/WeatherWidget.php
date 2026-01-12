<?php

namespace App\Filament\Widgets;

use App\Settings\GeneralSettings;
use App\Services\OpenWeatherService;
use Filament\Widgets\Widget;
use Carbon\Carbon;

class WeatherWidget extends Widget
{
    protected static ?int $sort = -2; // Always on top
    protected string $view = 'filament.widgets.weather-widget';
    protected int|string|array $columnSpan = 'full';

    // Enable lazy loading for better performance
    protected static bool $isLazy = true;

    public $weatherData = null;
    public $forecast = [];
    public $locationName = null;
    public $error = null;

    public function mount()
    {
        $settings = app(GeneralSettings::class);
        $lat = $settings->latitude;
        $lon = $settings->longitude;

        if (!$lat || !$lon) {
            $this->error = 'coordinates_not_set';
            return;
        }

        try {
            $service = app(OpenWeatherService::class);

            // Get current weather
            $this->weatherData = $service->getWeatherByCoordinates($lat, $lon);

            // Get 3-day forecast
            $this->forecast = $service->getForecast($lat, $lon, 3) ?? [];

            if ($this->weatherData) {
                $this->locationName = $this->weatherData['location'];
            }
        } catch (\Exception $e) {
            $this->error = 'api_error';
            \Log::error('Weather Widget Error: ' . $e->getMessage());
        }
    }

    public function getData(): array
    {
        return $this->forecast;
    }

    public function getCurrentWeather(): ?array
    {
        return $this->weatherData;
    }
}

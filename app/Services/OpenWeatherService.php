<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenWeatherService
{
    private string $apiKey;
    private string $baseUrl = 'https://api.openweathermap.org/data/2.5';

    public function __construct()
    {
        // Get API key from Settings (DB) first, then .env
        $settings = app(\App\Settings\GeneralSettings::class);
        $this->apiKey = $settings->openweather_api_key ?? config('services.openweather.api_key', env('OPENWEATHER_API_KEY', ''));
    }

    /**
     * Get current weather by coordinates
     *
     * @param float $lat Latitude
     * @param float $lon Longitude
     * @param string|null $apiKey Optional API key override
     * @param bool $forceRefresh Force refresh from API (bypass cache)
     * @return array|null
     */
    public function getWeatherByCoordinates(float $lat, float $lon, ?string $apiKey = null, bool $forceRefresh = false): ?array
    {
        $key = $apiKey ?? $this->apiKey;

        if (empty($key)) {
            Log::warning('OpenWeather API key not configured');
            return null;
        }

        $cacheKey = "openweather_{$lat}_{$lon}_" . app()->getLocale();

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, 1800, function () use ($lat, $lon, $key) {
            try {
                $lang = app()->getLocale(); // 'en' or 'id'

                $response = Http::withoutVerifying()->timeout(5)->get("{$this->baseUrl}/weather", [
                    'lat' => $lat,
                    'lon' => $lon,
                    'appid' => $key,
                    'units' => 'metric', // Celsius
                    'lang' => $lang,
                ]);

                if (!$response->successful()) {
                    Log::error('OpenWeather API error', [
                        'status' => $response->status(),
                        'body' => $response->body()
                    ]);
                    return null;
                }

                $data = $response->json();

                return $this->formatWeatherData($data);
            } catch (\Exception $e) {
                Log::error('OpenWeather API exception: ' . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Get weather forecast (3 days)
     *
     * @param float $lat Latitude
     * @param float $lon Longitude
     * @param int $days Number of days (default 3)
     * @return array|null
     */
    public function getForecast(float $lat, float $lon, int $days = 3): ?array
    {
        if (empty($this->apiKey)) {
            return null;
        }

        $cacheKey = "openweather_forecast_{$lat}_{$lon}_{$days}_" . app()->getLocale();

        return Cache::remember($cacheKey, 1800, function () use ($lat, $lon, $days) {
            try {
                $lang = app()->getLocale();

                // Use 5-day forecast API (free tier)
                $response = Http::withoutVerifying()->timeout(5)->get("{$this->baseUrl}/forecast", [
                    'lat' => $lat,
                    'lon' => $lon,
                    'appid' => $this->apiKey,
                    'units' => 'metric',
                    'lang' => $lang,
                    'cnt' => $days * 8, // 8 data points per day (3-hour intervals)
                ]);

                if (!$response->successful()) {
                    Log::error('OpenWeather Forecast API error', [
                        'status' => $response->status(),
                        'body' => $response->body()
                    ]);
                    return null;
                }

                $data = $response->json();

                return $this->formatForecastData($data, $days);
            } catch (\Exception $e) {
                Log::error('OpenWeather Forecast API exception: ' . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Format current weather data
     */
    private function formatWeatherData(array $data): array
    {
        return [
            'location' => $data['name'] ?? 'Unknown',
            'temperature' => round($data['main']['temp'] ?? 0),
            'feels_like' => round($data['main']['feels_like'] ?? 0),
            'humidity' => $data['main']['humidity'] ?? 0,
            'description' => $data['weather'][0]['description'] ?? '',
            'icon' => $data['weather'][0]['icon'] ?? '01d',
            'weather_code' => $data['weather'][0]['id'] ?? 800,
            'wind_speed' => $data['wind']['speed'] ?? 0,
            'timestamp' => $data['dt'] ?? time(),
        ];
    }

    /**
     * Format forecast data (get daily forecast at noon)
     */
    private function formatForecastData(array $data, int $days): array
    {
        if (!isset($data['list']) || empty($data['list'])) {
            return [];
        }

        $forecast = [];
        $processedDates = [];

        foreach ($data['list'] as $item) {
            $date = date('Y-m-d', $item['dt']);
            $hour = (int) date('H', $item['dt']);

            // Get forecast around noon (12:00) for each day
            if (!in_array($date, $processedDates) && $hour >= 11 && $hour <= 14) {
                $forecast[] = [
                    'date' => $date,
                    'day_name' => $this->getDayName($item['dt']),
                    'temperature' => round($item['main']['temp'] ?? 0),
                    'description' => $item['weather'][0]['description'] ?? '',
                    'icon' => $item['weather'][0]['icon'] ?? '01d',
                    'weather_code' => $item['weather'][0]['id'] ?? 800,
                ];

                $processedDates[] = $date;

                if (count($forecast) >= $days) {
                    break;
                }
            }
        }

        return $forecast;
    }

    /**
     * Get localized day name
     */
    private function getDayName(int $timestamp): string
    {
        $dayOfWeek = date('l', $timestamp);

        $days = [
            'Monday' => __('messages.day_monday'),
            'Tuesday' => __('messages.day_tuesday'),
            'Wednesday' => __('messages.day_wednesday'),
            'Thursday' => __('messages.day_thursday'),
            'Friday' => __('messages.day_friday'),
            'Saturday' => __('messages.day_saturday'),
            'Sunday' => __('messages.sunday'),
        ];

        return $days[$dayOfWeek] ?? $dayOfWeek;
    }

    /**
     * Get weather icon emoji
     */
    public static function getWeatherEmoji(int $weatherCode): string
    {
        // OpenWeatherMap weather condition codes
        // https://openweathermap.org/weather-conditions
        return match (true) {
            $weatherCode >= 200 && $weatherCode < 300 => '⛈️', // Thunderstorm
            $weatherCode >= 300 && $weatherCode < 400 => '🌦️', // Drizzle
            $weatherCode >= 500 && $weatherCode < 600 => '🌧️', // Rain
            $weatherCode >= 600 && $weatherCode < 700 => '❄️', // Snow
            $weatherCode >= 700 && $weatherCode < 800 => '🌫️', // Atmosphere (fog, mist, etc)
            $weatherCode === 800 => '☀️', // Clear
            $weatherCode === 801 => '🌤️', // Few clouds
            $weatherCode === 802 => '⛅', // Scattered clouds
            $weatherCode >= 803 => '☁️', // Broken/Overcast clouds
            default => '🌡️',
        };
    }
}

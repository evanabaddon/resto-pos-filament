<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class BmkgWeatherService
{
    public function getWeather(string $code)
    {
        $cacheKey = "bmkg_weather_{$code}";

        return Cache::remember($cacheKey, 3600, function () use ($code) {
            try {
                $response = Http::timeout(5)->get("https://api.bmkg.go.id/publik/prakiraan-cuaca?adm4={$code}");
                if ($response->successful()) {
                    return $response->json();
                }
            } catch (\Exception $e) {
                // Log error silently
            }
            return null;
        });
    }

    public function getForecastSummary(string $code): string
    {
        $data = $this->getWeather($code);
        if (!$data || !isset($data['data'][0]['cuaca'])) {
            return "Data cuaca tidak tersedia saat ini.";
        }

        // The API returns an array of arrays (one per day). We need to flatten them.
        $cuacaList = collect($data['data'][0]['cuaca'])->flatten(1);
        $now = Carbon::now();

        // Group by Day (Today, Tomorrow, Day After)
        $summary = [];
        $days = collect($cuacaList)
            ->map(function ($item) {
                $item['carbon'] = Carbon::parse($item['local_datetime']);
                return $item;
            })
            ->filter(function ($item) use ($now) {
                // Start from now until next 3 days
                return $item['carbon']->gte($now);
            })
            ->groupBy(function ($item) {
                return $item['carbon']->format('Y-m-d');
            });

        foreach ($days as $date => $items) {
            $dayName = Carbon::parse($date)->locale('id')->dayName;
            $formattedDate = Carbon::parse($date)->format('d/m');

            // Analyze the day's weather (Simplified)
            $descriptors = $items->pluck('weather_desc')->unique()->implode(', ');
            $minTemp = $items->min('t');
            $maxTemp = $items->max('t');

            $summary[] = "{$dayName} ({$formattedDate}): {$descriptors} (Suhu: {$minTemp}-{$maxTemp}°C)";

            // Limit to 3 days only
            if (count($summary) >= 3)
                break;
        }

        return implode("\n", $summary);
    }
}

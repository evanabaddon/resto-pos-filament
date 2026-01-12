<x-filament-widgets::widget>
    @if($weatherData)
        <x-filament::section class="h-full">
            <div class="flex flex-col md:flex-row md:items-center justify-between mb-4 gap-3 md:gap-0">
                <div>
                    <h2 class="text-lg font-bold text-gray-800 dark:text-white flex flex-wrap items-center gap-2">
                        <span>{{ \App\Services\OpenWeatherService::getWeatherEmoji($weatherData['weather_code']) }}
                            {{ __('messages.weather_forecast') }}</span>
                        <span
                            class="text-xs font-normal text-gray-500 bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded-full">
                            {{ $locationName }}
                        </span>
                    </h2>
                    <p class="text-xs text-gray-500 mt-1 md:mt-0">Powered by OpenWeatherMap</p>
                </div>
                <div class="text-left md:text-right">
                    <p class="text-xs text-gray-400">{{ __('messages.last_update') }}: {{ now()->format('H:i') }}</p>
                </div>
            </div>

            {{-- Current Weather (Large Card) --}}
            <div
                class="mb-4 p-4 rounded-xl border-2 border-primary-200 dark:border-primary-800 bg-gradient-to-br from-primary-50 to-blue-50 dark:from-gray-800 dark:to-gray-700">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="text-6xl">
                            {{ \App\Services\OpenWeatherService::getWeatherEmoji($weatherData['weather_code']) }}
                        </div>
                        <div>
                            <div class="text-4xl font-bold text-gray-800 dark:text-white">
                                {{ $weatherData['temperature'] }}°C
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-300 capitalize">
                                {{ $weatherData['description'] }}
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col gap-2 text-right">
                        <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                            <x-heroicon-o-cloud class="w-4 h-4" />
                            {{ $weatherData['humidity'] }}%
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                            <x-heroicon-o-flag class="w-4 h-4" />
                            {{ round($weatherData['wind_speed']) }} m/s
                        </div>
                    </div>
                </div>
            </div>

            {{-- 3-Day Forecast --}}
            @if(count($forecast) > 0)
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    @foreach($forecast as $day)
                        <div
                            class="flex flex-col items-center p-3 rounded-xl border border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50 hover:bg-white dark:hover:bg-gray-700 transition shadow-sm">
                            <div class="text-sm font-semibold text-gray-600 dark:text-gray-300 mb-2">
                                {{ $day['day_name'] }}
                            </div>
                            <div class="text-4xl mb-2">
                                {{ \App\Services\OpenWeatherService::getWeatherEmoji($day['weather_code']) }}
                            </div>
                            <div class="text-2xl font-bold text-gray-800 dark:text-white">
                                {{ $day['temperature'] }}°C
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 text-center capitalize mt-1">
                                {{ $day['description'] }}
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-filament::section>
    @elseif($error === 'coordinates_not_set')
        <x-filament::section>
            <div class="flex flex-col items-center justify-center py-8 text-center">
                <x-heroicon-o-map-pin class="w-12 h-12 text-gray-400 mb-3" />
                <p class="text-gray-600 dark:text-gray-300 font-medium">{{ __('messages.weather_not_configured') }}</p>
                <p class="text-sm text-gray-500 mt-1">{{ __('messages.weather_configure_hint') }}</p>
                <a href="{{ route('filament.admin.pages.app-settings') }}"
                    class="mt-3 text-primary-600 hover:text-primary-700 text-sm font-medium">
                    {{ __('messages.go_to_settings') }} →
                </a>
            </div>
        </x-filament::section>
    @elseif($error === 'api_error')
        <x-filament::section>
            <div class="flex items-center justify-center py-4 text-gray-500">
                <x-heroicon-o-exclamation-circle class="w-6 h-6 mr-2 text-warning-500" />
                {{ __('messages.weather_api_error') }}
            </div>
        </x-filament::section>
    @endif
</x-filament-widgets::widget>
<x-filament-widgets::widget>
    @if($weatherData)
        <x-filament::section class="h-full">
            <div class="flex flex-col md:flex-row md:items-center justify-between mb-4 gap-3 md:gap-0">
                <div>
                    <h2 class="text-lg font-bold text-gray-800 dark:text-white flex flex-wrap items-center gap-2">
                        <span>🌦️ Prakiraan Cuaca</span>
                        <span
                            class="text-xs font-normal text-gray-500 bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded-full">
                            {{ $locationName }}
                        </span>
                    </h2>
                    <p class="text-xs text-gray-500 mt-1 md:mt-0">Sumber: BMKG (Badan Meteorologi, Klimatologi, dan
                        Geofisika)</p>
                </div>
                <div class="text-left md:text-right">
                    <p class="text-xs text-gray-400">Update terakhir: {{ now()->format('H:i') }}</p>
                </div>
            </div>

            <div
                class="grid grid-flow-col auto-cols-[85%] md:auto-cols-[minmax(240px,1fr)] gap-3 overflow-x-auto pb-4 snap-x snap-mandatory scrollbar-hide">
                @foreach($this->getData() as $index => $item)
                    <div
                        class="flex flex-row items-center justify-between p-3 rounded-xl border border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50 hover:bg-white dark:hover:bg-gray-700 transition shadow-sm snap-center h-full gap-3 {{ $index === 0 ? 'ring-2 ring-primary-500/20' : '' }}">

                        {{-- Left: Time --}}
                        <div
                            class="flex flex-col items-center justify-center border-r border-gray-200 dark:border-gray-600 pr-3 h-full">
                            <span class="text-sm font-bold text-gray-600 dark:text-gray-300">
                                {{ $item['carbon_date']->format('H:i') }}
                            </span>
                        </div>

                        {{-- Center: Icon --}}
                        <div class="relative w-10 h-10 flex-shrink-0">
                            <img src="{{ $item['image'] }}" class="w-full h-full object-contain filter drop-shadow-sm"
                                alt="{{ $item['weather_desc'] }}">
                        </div>

                        {{-- Right: Info Group --}}
                        <div class="flex flex-col items-start justify-center flex-1 min-w-0 pl-1">
                            <div class="flex items-center justify-between w-full">
                                <span class="text-base font-bold text-gray-800 dark:text-white leading-none">
                                    {{ $item['t'] }}°C
                                </span>
                                {{-- Metadata (Top Right for compactness) --}}
                                <div class="flex gap-2 opacity-80">
                                    <div class="flex items-center gap-0.5 text-[8px] text-blue-500" title="Humidity">
                                        <x-heroicon-o-cloud class="w-3 h-3" />
                                        {{ $item['hu'] }}%
                                    </div>
                                    <div class="flex items-center gap-0.5 text-[8px] text-orange-500" title="Wind">
                                        <x-heroicon-o-flag class="w-3 h-3" />
                                        {{ $item['ws'] }}
                                    </div>
                                </div>
                            </div>

                            <span
                                class="text-[10px] font-medium text-gray-500 dark:text-gray-400 line-clamp-1 w-full overflow-hidden text-ellipsis leading-tight mt-0.5">
                                {{ $item['weather_desc'] }}
                            </span>
                        </div>

                    </div>
                @endforeach
            </div>
        </x-filament::section>
    @else
        @if(app(\App\Settings\GeneralSettings::class)->bmkg_location_code)
            {{-- Skeleton Loading or Error State --}}
            <x-filament::section>
                <div class="flex items-center justify-center py-4 text-gray-500">
                    <x-filament::loading-indicator class="h-6 w-6 mr-2" /> Memuat data cuaca...
                </div>
            </x-filament::section>
        @endif
    @endif
</x-filament-widgets::widget>
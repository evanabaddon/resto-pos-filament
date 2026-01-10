<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center space-x-4">
            {{-- Icon Section --}}
            <div class="flex-shrink-0">
                <div class="p-3 bg-primary-100 dark:bg-primary-900/30 rounded-xl">
                    <x-heroicon-o-sparkles class="w-8 h-8 text-primary-600 dark:text-primary-400 animate-pulse" />
                </div>
            </div>

            {{-- Text Section --}}
            <div class="flex-1">
                <div class="flex items-center justify-between mb-1">
                    <h3 class="text-xs font-bold uppercase tracking-widest text-primary-600 dark:text-primary-400">
                        {{ __('messages.ai_suggestion_title') }}
                    </h3>
                    <span class="text-[10px] text-gray-400 dark:text-gray-500 italic">
                        {{ __('messages.ai_suggestion_subtitle') }}
                    </span>
                </div>

                <p class="text-sm font-medium text-gray-800 dark:text-gray-200 leading-relaxed italic">
                    "{{ $suggestion }}"
                </p>
            </div>

            {{-- Refresh Button (Optional/Subtle) --}}
            <div class="flex-shrink-0">
                <button wire:click="refreshSuggestion" wire:loading.attr="disabled"
                    class="text-gray-400 hover:text-primary-500 transition-colors">
                    <x-heroicon-o-arrow-path class="w-4 h-4" wire:loading.class="animate-spin" />
                </button>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
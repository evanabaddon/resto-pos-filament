<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <span class="text-lg font-semibold">{{ __('messages.critical_stock_title') }}</span>
                @if($this->getCriticalItems()->count() > 0)
                    <x-filament::badge color="danger">
                        {{ $this->getCriticalItems()->count() }} {{ __('messages.items') }}
                    </x-filament::badge>
                @endif
            </div>
        </x-slot>

        @php
            $criticalItems = $this->getCriticalItems();
        @endphp

        @if($criticalItems->isEmpty())
            <div class="text-center py-8">
                <div class="text-gray-400 dark:text-gray-600">
                    <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="text-lg font-medium">{{ __('messages.all_stock_safe') }}</p>
                    <p class="text-sm mt-1">{{ __('messages.no_critical_items_desc') }}</p>
                </div>
            </div>
        @else
            <div class="space-y-3">
                @foreach($criticalItems as $item)
                    @php
                        $status = $this->getStockStatus($item);
                        $recommended = $this->getRecommendedRestock($item);
                        $unit = $item->unit->name ?? 'unit';
                    @endphp

                    <div
                        class="border rounded-lg p-4 {{ $status['status'] === 'critical' ? 'border-red-300 bg-red-50 dark:bg-red-950/20' : 'border-yellow-300 bg-yellow-50 dark:bg-yellow-950/20' }}">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-2">
                                    <h4 class="font-semibold text-gray-900 dark:text-white">
                                        {{ $item->name }}
                                    </h4>
                                    <x-filament::badge :color="$status['color']">
                                        {{ $status['status'] }}
                                    </x-filament::badge>
                                </div>

                                <div class="grid grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <span class="text-gray-600 dark:text-gray-400">
                                            {{ $status['stock_type'] === 'prepared' ? __('messages.ready_stock_label') : __('messages.current_stock_label') }}
                                        </span>
                                        <span
                                            class="font-semibold ml-1 {{ $status['status'] === 'critical' ? 'text-red-600' : 'text-yellow-600' }}">
                                            {{ number_format($status['stock_type'] === 'prepared' ? $item->prepared_stock : $item->stock, 2) }}
                                            {{ $unit }}
                                        </span>
                                        @if($status['stock_type'] === 'prepared')
                                            <span class="text-xs text-gray-500">{{ __('messages.already_cooked') }}</span>
                                        @endif
                                    </div>
                                    <div>
                                        <span
                                            class="text-gray-600 dark:text-gray-400">{{ __('messages.minimum_stock_label') }}</span>
                                        <span class="font-semibold ml-1">
                                            {{ number_format($status['stock_type'] === 'prepared' ? $item->minimum_prepared_stock : $item->minimum_stock, 2) }}
                                            {{ $unit }}
                                        </span>
                                    </div>
                                </div>

                                @if($status['stock_type'] === 'prepared')
                                    <div class="mt-2 p-2 bg-blue-50 dark:bg-blue-950/20 rounded border border-blue-200">
                                        <span
                                            class="text-xs text-blue-600 dark:text-blue-400">{{ __('messages.produced_item_badge') }}</span>
                                        <span class="text-xs text-gray-600 dark:text-gray-400 ml-2">
                                            {{ __('messages.cook_more_alert') }}
                                        </span>
                                    </div>
                                @else
                                    <div class="mt-2 p-2 bg-white/50 dark:bg-gray-900/50 rounded">
                                        <span
                                            class="text-xs text-gray-600 dark:text-gray-400">{{ __('messages.restock_recommendation') }}</span>
                                        <span class="font-bold text-green-600 dark:text-green-400 ml-1">
                                            {{ number_format($recommended, 2) }} {{ $unit }}
                                        </span>
                                        <span class="text-xs text-gray-500">{{ __('messages.for_3_days') }}</span>
                                    </div>
                                @endif
                            </div>

                            <div class="ml-4">
                                <x-filament::button x-data=""
                                    x-on:click="$dispatch('open-modal', { id: 'record-production-{{ $item->id }}' })" size="sm"
                                    color="success">
                                    {{ __('messages.cook_more_btn') }}
                                </x-filament::button>
                            </div>
                        </div>
                    </div>

                    {{-- Production Modal --}}
                    <x-filament::modal id="record-production-{{ $item->id }}" width="md">
                        <x-slot name="heading">
                            {{ __('messages.record_production_modal_title') }} {{ $item->name }}
                        </x-slot>

                        <div class="space-y-4">
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ __('messages.current_ready_stock') }}
                                    <strong>{{ number_format($item->prepared_stock, 2) }} {{ $unit }}</strong>
                                </p>
                            </div>

                            <form wire:submit.prevent="recordProduction({{ $item->id }}, $event.target.quantity.value)">
                                <x-filament::input.wrapper>
                                    <x-filament::input type="number" name="quantity"
                                        placeholder="{{ __('messages.quantity_placeholder') }}" min="1" step="0.01" required />
                                </x-filament::input.wrapper>

                                <div class="mt-4 flex flex-col gap-2">
                                    <div
                                        class="flex justify-between items-center bg-gray-50 dark:bg-gray-800 p-2 rounded text-xs text-gray-500">
                                        <span>{{ __('messages.stock_deduction_info') }}</span>
                                    </div>

                                    <div class="flex justify-end gap-2 mt-2">
                                        <x-filament::button type="button" color="danger" size="sm" class="mr-auto"
                                            x-on:click="$dispatch('open-modal', { id: 'confirm-reset-{{ $item->id }}' })">
                                            {{ __('messages.reset_waste_btn') }}
                                        </x-filament::button>

                                        <x-filament::button type="button" color="gray"
                                            x-on:click="$dispatch('close-modal', { id: 'record-production-{{ $item->id }}' })">
                                            {{ __('messages.cancel') }}
                                        </x-filament::button>

                                        <x-filament::button type="submit" color="success">
                                            {{ __('messages.save_production_btn') }}
                                        </x-filament::button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </x-filament::modal>

                    {{-- Reset Stock Confirmation Modal --}}
                    <x-filament::modal id="confirm-reset-{{ $item->id }}" width="md">
                        <x-slot name="heading">
                            {{ __('messages.reset_stock_confirm_title') }}
                        </x-slot>

                        <div class="space-y-4">
                            <div class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-500 p-4">
                                <p class="font-semibold text-yellow-900 dark:text-yellow-100">{{ __('messages.warning') }}</p>
                                <p class="text-sm text-yellow-800 dark:text-yellow-200 mt-1">
                                    {{ __('messages.waste_stock_warning') }} <strong>{{ $item->name }}</strong>
                                    <strong>{{ number_format($item->prepared_stock, 2) }} {{ $unit }}</strong>.
                                </p>
                            </div>

                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                <p>{{ __('messages.reset_to_zero_warning') }}</p>
                                <p>{{ __('messages.ingredients_not_returned') }}</p>
                                <p>{{ __('messages.action_cannot_undo') }}</p>
                            </div>

                            <div class="flex justify-end gap-2 mt-4">
                                <x-filament::button type="button" color="gray"
                                    x-on:click="$dispatch('close-modal', { id: 'confirm-reset-{{ $item->id }}' })">
                                    {{ __('messages.cancel') }}
                                </x-filament::button>

                                <x-filament::button type="button" color="danger" wire:click="resetStock({{ $item->id }})"
                                    x-on:click="$dispatch('close-modal', { id: 'confirm-reset-{{ $item->id }}' })">
                                    {{ __('messages.confirm_reset_btn') }}
                                </x-filament::button>
                            </div>
                        </div>
                    </x-filament::modal>
                @endforeach
            </div>

            <div class="mt-4 text-xs text-gray-500 dark:text-gray-400 text-center">
                {{ __('messages.auto_refresh_info') }} • Last updated: {{ now()->format('H:i') }}
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
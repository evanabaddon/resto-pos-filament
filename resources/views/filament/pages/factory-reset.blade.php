<x-filament-panels::page>
    {{-- Warning Banner --}}
    <div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                        clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-red-800 dark:text-red-200">{{ __('messages.danger_zone') }}</h3>
                <div class="mt-2 text-sm text-red-700 dark:text-red-300">
                    <p>{{ __('messages.danger_zone_desc') }}</p>
                    <p class="mt-1"><strong>{{ __('messages.backup_automatis_desc') }}</strong></p>
                </div>
            </div>
        </div>
    </div>

    {{-- Backup Management Section --}}
    <x-filament::section>
        <x-slot name="heading">
            {{ __('messages.backup_management') }}
        </x-slot>

        <x-slot name="description">
            {{ __('messages.backup_management_desc') }}
        </x-slot>

        <div class="space-y-3">
            @php
            $backupService = app(\App\Services\BackupService::class);
            $backups = $backupService->getBackups();
            @endphp

            @if (count($backups) > 0)
            @foreach ($backups as $backup)
            <div
                class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="flex-1">
                    <div class="font-semibold text-sm">{{ $backup['filename'] }}</div>
                    <div class="text-xs text-gray-500 mt-1">
                        {{ $backup['created_at'] }} • {{ $backup['size'] }}
                    </div>
                </div>
                <div class="flex gap-2">
                    <x-filament::button
                        size="sm"
                        color="success"
                        x-on:click="$dispatch('open-modal', { id: 'confirm-restore-{{ $loop->index }}' })">
                        {{ __('messages.restore') }}
                    </x-filament::button>
                    <x-filament::button size="sm" color="danger"
                        wire:click="deleteBackup('{{ $backup['filename'] }}')"
                        wire:confirm="{{ __('messages.confirm_delete_backup') }}">
                        {{ __('messages.delete') }}
                    </x-filament::button>
                </div>
            </div>

            {{-- Restore Confirmation Modal --}}
            <x-filament::modal id="confirm-restore-{{ $loop->index }}" width="md">
                <x-slot name="heading">
                    {{ __('messages.confirm_restore_database') }}
                </x-slot>

                <div class="space-y-4">
                    <div class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-400 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700 dark:text-yellow-200">
                                    <strong>{{ __('messages.warning_restore') }}</strong>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="text-sm space-y-2">
                        <p><strong>{{ __('messages.backup_file') }}:</strong> {{ $backup['filename'] }}</p>
                        <p><strong>{{ __('messages.created_at') }}:</strong> {{ $backup['created_at'] }}</p>
                        <p><strong>{{ __('messages.size') }}:</strong> {{ $backup['size'] }}</p>
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-800 p-3 rounded text-sm">
                        <p class="font-semibold mb-2">{{ __('messages.what_will_happen') }}</p>
                        <ul class="list-disc pl-5 space-y-1 text-gray-600 dark:text-gray-400">
                            <li>{{ __('messages.data_replaced') }}</li>
                            <li>{{ __('messages.database_reverted') }}</li>
                            <li>{{ __('messages.process_duration') }}</li>
                        </ul>
                    </div>
                </div>

                <x-slot name="footerActions">
                    <x-filament::button
                        color="gray"
                        x-on:click="$dispatch('close-modal', { id: 'confirm-restore-{{ $loop->index }}' })">
                        {{ __('messages.cancel') }}
                    </x-filament::button>

                    <x-filament::button
                        color="success"
                        wire:click="restoreBackup('{{ $backup['filename'] }}')"
                        x-on:click="$dispatch('close-modal', { id: 'confirm-restore-{{ $loop->index }}' })">
                        {{ __('messages.yes_restore_now') }}
                    </x-filament::button>
                </x-slot>
            </x-filament::modal>
            @endforeach
            @else
            <div class="text-center py-8 text-gray-500">
                <svg class="w-12 h-12 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4">
                    </path>
                </svg>
                <p>{{ __('messages.no_backups_available') }}</p>
            </div>
            @endif
        </div>
    </x-filament::section>

    {{-- Reset Options Section --}}
    <x-filament::section class="mt-6">
        <x-slot name="heading">
            {{ __('messages.reset_options') }}
        </x-slot>

        <x-slot name="description">
            {{ __('messages.reset_options_desc') }}
        </x-slot>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Mode 1: Operational Reset --}}
            <div class="border-2 border-orange-300 dark:border-orange-700 rounded-lg p-6 bg-orange-50 dark:bg-orange-900/20">
                <div class="flex items-center gap-2 mb-3">
                    <svg class="w-6 h-6 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                        </path>
                    </svg>
                    <h3 class="text-lg font-bold text-orange-900 dark:text-orange-100">{{ __('messages.operational_reset') }}</h3>
                </div>

                <div class="text-sm text-orange-800 dark:text-orange-200 space-y-2 mb-4">
                    <p class="font-semibold">{{ __('messages.will_be_deleted') }}</p>
                    <ul class="list-disc pl-5 space-y-1">
                        <li>{{ __('messages.operational_deleted_list_1') }}</li>
                        <li>{{ __('messages.operational_deleted_list_2') }}</li>
                        <li>{{ __('messages.operational_deleted_list_3') }}</li>
                        <li>{{ __('messages.operational_deleted_list_4') }}</li>
                        <li>{{ __('messages.operational_deleted_list_5') }}</li>
                        <li>{{ __('messages.operational_deleted_list_6') }}</li>
                    </ul>

                    <p class="font-semibold mt-3">{{ __('messages.will_be_saved') }}</p>
                    <ul class="list-disc pl-5 space-y-1">
                        <li>{{ __('messages.operational_saved_list_1') }}</li>
                        <li>{{ __('messages.operational_saved_list_2') }}</li>
                        <li>{{ __('messages.operational_saved_list_3') }}</li>
                        <li>{{ __('messages.operational_saved_list_4') }}</li>
                    </ul>
                </div>

                <div class="bg-orange-100 dark:bg-orange-800 p-3 rounded text-xs text-orange-900 dark:text-orange-100 mb-4">
                    {{ __('messages.operational_reset_tip') }}
                </div>
            </div>

            {{-- Mode 2: Factory Reset --}}
            <div class="border-2 border-red-300 dark:border-red-700 rounded-lg p-6 bg-red-50 dark:bg-red-900/20">
                <div class="flex items-center gap-2 mb-3">
                    <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                        </path>
                    </svg>
                    <h3 class="text-lg font-bold text-red-900 dark:text-red-100">{{ __('messages.factory_reset_total') }}</h3>
                </div>

                <div class="text-sm text-red-800 dark:text-red-200 space-y-2 mb-4">
                    <p class="font-semibold">{{ __('messages.will_be_deleted') }}</p>
                    <ul class="list-disc pl-5 space-y-1">
                        <li>{{ __('messages.all_from_mode_1') }}</li>
                        <li>{{ __('messages.factory_deleted_list_1') }}</li>
                        <li>{{ __('messages.factory_deleted_list_2') }}</li>
                        <li>{{ __('messages.factory_deleted_list_3') }}</li>
                        <li>{{ __('messages.factory_deleted_list_4') }}</li>
                        <li>{{ __('messages.factory_deleted_list_5') }}</li>
                    </ul>

                    <p class="font-semibold mt-3">{{ __('messages.will_be_saved') }}</p>
                    <ul class="list-disc pl-5 space-y-1">
                        <li>{{ __('messages.factory_saved_list_1') }}</li>
                        <li>{{ __('messages.factory_saved_list_2') }}</li>
                        <li>{{ __('messages.factory_saved_list_3') }}</li>
                    </ul>
                </div>

                <div class="bg-red-100 dark:bg-red-800 p-3 rounded text-xs text-red-900 dark:text-red-100 mb-4">
                    {{ __('messages.factory_reset_warning') }}
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-panels::page>
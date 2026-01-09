<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-filament::section>
                <x-slot name="heading">
                    {{ __('messages.total_products') }}
                </x-slot>
                <div class="text-3xl font-bold">
                    {{ $this->getTableRecords()->count() }}
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">
                    {{ __('messages.edited_products') }}
                </x-slot>
                <div class="text-3xl font-bold {{ $this->totalEdited > 0 ? 'text-success-600' : 'text-gray-400' }}">
                    {{ $this->totalEdited }}
                </div>
                <div class="text-xs text-gray-500 mt-1">
                    {{ $this->totalEdited > 0 ? __('messages.ready_to_submit') : __('messages.no_changes_yet') }}
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">
                    {{ __('messages.estimated_loss_value') }}
                </x-slot>
                <div
                    class="text-3xl font-bold {{ $this->totalEstimatedLoss > 0 ? 'text-danger-600' : 'text-gray-400' }}">
                    Rp {{ number_format($this->totalEstimatedLoss, 0, ',', '.') }}
                </div>
                <div class="text-xs text-gray-500 mt-1">
                    {{ $this->totalEstimatedLoss > 0 ? __('messages.from_negative_variance') : __('messages.no_loss') }}
                </div>
            </x-filament::section>
        </div>

        {{-- Filament Table --}}
        {{ $this->table }}
    </div>
</x-filament-panels::page>
<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-filament::section>
                <x-slot name="heading">
                    Total Produk
                </x-slot>
                <div class="text-3xl font-bold">
                    {{ $this->getTableRecords()->count() }}
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">
                    Produk yang Diubah
                </x-slot>
                <div class="text-3xl font-bold {{ $this->totalEdited > 0 ? 'text-success-600' : 'text-gray-400' }}">
                    {{ $this->totalEdited }}
                </div>
                <div class="text-xs text-gray-500 mt-1">
                    {{ $this->totalEdited > 0 ? 'Siap untuk disubmit' : 'Belum ada perubahan' }}
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">
                    Nilai Kerugian yang Diestimasi
                </x-slot>
                <div class="text-3xl font-bold {{ $this->totalEstimatedLoss > 0 ? 'text-danger-600' : 'text-gray-400' }}">
                    Rp {{ number_format($this->totalEstimatedLoss, 0, ',', '.') }}
                </div>
                <div class="text-xs text-gray-500 mt-1">
                    {{ $this->totalEstimatedLoss > 0 ? 'Dari kerugian negatif' : 'Tidak ada kerugian' }}
                </div>
            </x-filament::section>
        </div>

        {{-- Filament Table --}}
        {{ $this->table }}
    </div>
</x-filament-panels::page>
<x-filament-panels::page>
    <div class="flex items-center justify-between mb-2">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">Laba & Rugi (P&L)</h2>
            <p class="text-sm text-gray-500">Analisis performa keuangan berdasarkan modal barang terjual (Accrual).</p>
        </div>
        <div class="flex items-center gap-3">
            {{-- Filter status info --}}
            <div class="hidden md:flex flex-col items-end">
                <span class="text-[10px] font-bold text-gray-400 uppercase">Periode Laporan</span>
                <span class="text-xs font-medium text-gray-600">
                    {{ \Carbon\Carbon::parse($date_start)->format('d M Y') }} -
                    {{ \Carbon\Carbon::parse($date_end)->format('d M Y') }}
                </span>
            </div>
        </div>
    </div>

    {{-- Filter Form --}}
    <div class="mb-6">
        {{ $this->form }}
    </div>

    {{-- Stats Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        {{-- Net Sales --}}
        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm relative overflow-hidden group">
            <div class="absolute right-0 top-0 h-full w-16 bg-gradient-to-l from-green-50 to-transparent"></div>
            <div class="relative z-10">
                <p class="text-sm font-medium text-gray-500 mb-1">Net Sales (Omzet)</p>
                <h3 class="text-2xl font-bold text-green-600">
                    Rp {{ number_format($totalRevenue, 0, ',', '.') }}
                </h3>
                <p class="text-[10px] text-green-400 mt-1">Gross: Rp {{ number_format($totalGrossSales, 0, ',', '.') }}
                </p>
                <div class="mt-2 text-xs flex items-center gap-1">
                     <span class="{{ $growthRevenue >= 0 ? 'text-green-600' : 'text-red-600' }} font-bold">
                        {{ $growthRevenue >= 0 ? '+' : '' }}{{ number_format($growthRevenue, 1) }}%
                    </span>
                    <span class="text-gray-400 text-[10px]">vs periode lalu</span>
                </div>
            </div>
            <div class="absolute bottom-4 right-4 text-green-500 opacity-20 group-hover:opacity-100 transition-opacity">
                <x-heroicon-o-presentation-chart-line class="w-12 h-12" />
            </div>
        </div>

        {{-- Total HPP --}}
        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm relative overflow-hidden group">
            <div class="absolute right-0 top-0 h-full w-16 bg-gradient-to-l from-orange-50 to-transparent"></div>
            <div class="relative z-10">
                <p class="text-sm font-medium text-gray-500 mb-1">Total HPP (Accrual)</p>
                <h3 class="text-2xl font-bold text-orange-600">
                    Rp {{ number_format($totalHpp, 0, ',', '.') }}
                </h3>
                <p class="text-[10px] text-orange-400 mt-1">Estimasi Modal Penjualan</p>
                 <div class="mt-2 text-xs flex items-center gap-1">
                     <span class="{{ $growthHpp <= 0 ? 'text-green-600' : 'text-orange-600' }} font-bold">
                        {{ $growthHpp >= 0 ? '+' : '' }}{{ number_format($growthHpp, 1) }}%
                    </span>
                    <span class="text-gray-400 text-[10px]">vs periode lalu</span>
                </div>
            </div>
            <div
                class="absolute bottom-4 right-4 text-orange-500 opacity-20 group-hover:opacity-100 transition-opacity">
                <x-heroicon-o-circle-stack class="w-12 h-12" />
            </div>
        </div>

        {{-- Expenses --}}
        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm relative overflow-hidden group">
            <div class="absolute right-0 top-0 h-full w-16 bg-gradient-to-l from-red-50 to-transparent"></div>
            <div class="relative z-10">
                <p class="text-sm font-medium text-gray-500 mb-1">Biaya Operasional</p>
                <h3 class="text-2xl font-bold text-red-600">
                    Rp {{ number_format($totalExpenses, 0, ',', '.') }}
                </h3>
                <p class="text-[10px] text-red-400 mt-1">Ops + Gaji (Payroll)</p>
                 <div class="mt-2 text-xs flex items-center gap-1">
                     <span class="{{ $growthExpenses <= 0 ? 'text-green-600' : 'text-red-600' }} font-bold">
                        {{ $growthExpenses >= 0 ? '+' : '' }}{{ number_format($growthExpenses, 1) }}%
                    </span>
                    <span class="text-gray-400 text-[10px]">vs periode lalu</span>
                </div>
            </div>
            <div class="absolute bottom-4 right-4 text-red-500 opacity-20 group-hover:opacity-100 transition-opacity">
                <x-heroicon-o-banknotes class="w-12 h-12" />
            </div>
        </div>

        {{-- Net Profit --}}
        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm relative overflow-hidden group">
            <div class="absolute right-0 top-0 h-full w-16 bg-gradient-to-l from-blue-50 to-transparent"></div>
            <div class="relative z-10">
                <p class="text-sm font-medium text-gray-500 mb-1">Net Profit (Laba Bersih)</p>
                <h3 class="text-2xl font-bold {{ $netProfit >= 0 ? 'text-blue-600' : 'text-red-600' }}">
                    Rp {{ number_format($netProfit, 0, ',', '.') }}
                </h3>
                <p class="text-xs text-gray-400 mt-1">
                    Margin: {{ $totalRevenue > 0 ? number_format(($netProfit / $totalRevenue) * 100, 1) : 0 }}%
                </p>
                 <div class="mt-2 text-xs flex items-center gap-1">
                     <span class="{{ $growthNetProfit >= 0 ? 'text-green-600' : 'text-red-600' }} font-bold">
                        {{ $growthNetProfit >= 0 ? '+' : '' }}{{ number_format($growthNetProfit, 1) }}%
                    </span>
                    <span class="text-gray-400 text-[10px]">vs periode lalu</span>
                </div>
            </div>
            <div class="absolute bottom-4 right-4 text-blue-500 opacity-20 group-hover:opacity-100 transition-opacity">
                <x-heroicon-o-currency-dollar class="w-12 h-12" />
            </div>
        </div>
    </div>

    {{-- Profit Calculation Flow --}}
    <div class="lg:col-span-1 bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <h3 class="text-sm font-bold text-gray-800 uppercase tracking-widest mb-6 border-b pb-2">Laporan Laba Rugi
        </h3>

        <div class="space-y-4">
            {{-- Income Flow --}}
            <div class="space-y-2">
                <div class="flex justify-between items-center text-xs">
                    <span class="text-gray-500">Pendapatan Kotor (Gross)</span>
                    <span class="font-medium text-gray-900">Rp
                        {{ number_format($totalGrossSales, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between items-center text-xs">
                    <span class="text-gray-500">(-) Potongan / Diskon</span>
                    <span class="font-medium text-red-500">(Rp
                        {{ number_format($totalDiscounts, 0, ',', '.') }})</span>
                </div>
                <div
                    class="flex justify-between items-center py-2 bg-gray-50 px-3 rounded border border-dashed border-gray-200">
                    <span class="text-xs font-bold text-gray-700">Pendapatan Bersih (Net)</span>
                    <span class="text-sm font-bold text-gray-900">Rp
                        {{ number_format($totalRevenue, 0, ',', '.') }}</span>
                </div>
            </div>

            {{-- HPP Flow --}}
            <div class="pt-2">
                <div class="flex justify-between items-center text-xs">
                    <span class="text-gray-500">(-) Harga Pokok Penjualan</span>
                    <span class="font-medium text-orange-600">(Rp
                        {{ number_format($totalHpp, 0, ',', '.') }})</span>
                </div>
            </div>

            {{-- Gross Profit Result --}}
            <div class="flex justify-between items-center py-3 bg-blue-50 px-3 rounded-lg border border-blue-100">
                <span class="text-xs font-bold text-blue-800 uppercase">Gross Profit</span>
                <span class="font-bold text-blue-900 text-lg">Rp
                    {{ number_format($totalGrossProfit, 0, ',', '.') }}</span>
            </div>

            {{-- Expenses --}}
            <div class="space-y-2">
                <div class="flex justify-between items-center text-xs">
                    <span class="text-gray-500">(-) Biaya Operasional</span>
                    <span class="font-medium text-red-600">(Rp
                        {{ number_format($totalExpenses, 0, ',', '.') }})</span>
                </div>
                @if($totalPayroll > 0)
                    <div class="flex justify-between items-center text-[10px] italic text-gray-400 pl-2">
                        <span>(Termasuk Gaji: Rp {{ number_format($totalPayroll, 0, ',', '.') }})</span>
                    </div>
                @endif
            </div>

            {{-- Net Profit Result --}}
            <div
                class="flex justify-between items-center py-5 bg-violet-600 px-5 rounded-2xl shadow-lg shadow-violet-200">
                <span class="text-sm font-bold text-white uppercase tracking-wider">Net Profit</span>
                <span class="text-xl font-bold text-white">Rp
                    {{ number_format($netProfit, 0, ',', '.') }}</span>
            </div>

            <div class="pt-4 mt-4 border-t border-gray-100">
                <div class="flex justify-between items-center text-[10px] text-gray-400 italic">
                    <span>Titipan Pajak (PB1)</span>
                    <span>Rp {{ number_format($totalTaxes, 0, ',', '.') }}</span>
                </div>
                <p class="text-[9px] text-gray-400 mt-1 leading-tight">
                    * Pajak (PB1) tidak dihitung sebagai pendapatan maupun biaya dalam laporan Laba/Rugi bersih
                    (Non-Revenue).
                </p>
            </div>


        </div>
    </div>

    {{-- Valuation Section --}}
    <x-filament::section>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="md:col-span-1">
                <div class="bg-indigo-900 rounded-xl p-6 text-white shadow-lg relative overflow-hidden">
                    <div
                        class="absolute -right-6 -top-6 w-32 h-32 bg-white rounded-full opacity-10 blur-2xl pointer-events-none">
                    </div>
                    <div
                        class="absolute -left-6 -bottom-6 w-32 h-32 bg-indigo-400 rounded-full opacity-10 blur-2xl pointer-events-none">
                    </div>

                    <div class="relative z-10">
                        <h3 class="text-indigo-200 text-sm font-medium uppercase tracking-wider mb-2">Valuasi Aset Stok
                        </h3>
                        <div class="text-3xl font-bold mb-1">
                            Rp {{ number_format($currentStockValue, 0, ',', '.') }}
                        </div>
                        <p class="text-xs text-indigo-300">Estimasi nilai jual seluruh stok saat ini</p>

                        <div class="mt-6 pt-6 border-t border-white/10">
                            <div class="flex items-center gap-2 text-xs text-indigo-200">
                                <x-heroicon-o-information-circle class="w-4 h-4" />
                                <span>Berdasarkan harga dasar (base price)</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="md:col-span-2">
                <x-filament::section>
                    <x-slot name="heading">
                        Top 10 Aset Terbesar
                    </x-slot>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left divide-y divide-gray-200 dark:divide-white/5">
                            <thead class="bg-gray-50 dark:bg-white/5">
                                <tr>
                                    <th class="px-4 py-3 font-medium text-gray-500">Produk</th>
                                    <th class="px-4 py-3 font-medium text-gray-500 text-center">Stok</th>
                                    <th class="px-4 py-3 font-medium text-gray-500 text-right">Nilai Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                                @foreach($topStockAssets as $asset)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                                        <td class="px-4 py-2">
                                            <div class="font-medium text-gray-900 dark:text-white">{{ $asset['name'] }}
                                            </div>
                                            <div class="text-xs text-gray-500">@ Rp
                                                {{ number_format($asset['price'], 0, ',', '.') }} /
                                            </div>
                                        </td>
                                        <td class="px-4 py-2 text-center text-gray-600 dark:text-gray-400">
                                            {{ $asset['stock'] }} {{ $asset['unit'] }}
                                        </td>
                                        <td class="px-4 py-2 text-right font-bold text-indigo-600 dark:text-indigo-400">
                                            Rp {{ number_format($asset['total_value'], 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>
            </div>
        </div>
    </x-filament::section>

    {{-- Table Breakdown COGS --}}
    <x-filament::section collapsible>
        <x-slot name="heading">
            Rincian Biaya Modal (HPP)
        </x-slot>

        <div class="overflow-x-auto max-h-[500px] overflow-y-auto">
            <table class="w-full text-sm text-left divide-y divide-gray-200 dark:divide-white/5">
                <thead class="bg-gray-50 dark:bg-white/5 sticky top-0">
                    <tr>
                        <th
                            class="px-4 py-3 text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400">
                            Produk</th>
                        <th
                            class="px-4 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase dark:text-gray-400">
                            Qty</th>
                        <th
                            class="px-4 py-3 text-xs font-medium tracking-wider text-right text-gray-500 uppercase dark:text-gray-400">
                            Total HPP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                    @forelse ($breakdownCogs as $item)
                        <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $item['name'] }}</td>
                            <td class="px-4 py-3 text-center text-gray-500 dark:text-gray-400">{{ $item['qty'] }}
                            </td>
                            <td class="px-4 py-3 text-right text-orange-600 dark:text-orange-400 font-bold">Rp
                                {{ number_format($item['total_hpp'], 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-6 text-center text-gray-400 text-sm">Belum ada data
                                penjualan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>

    {{-- Table Breakdown Expenses --}}
    <x-filament::section collapsible>
        <x-slot name="heading">
            Rincian Biaya Operasional
        </x-slot>

        <div class="overflow-x-auto max-h-[500px] overflow-y-auto">
            <table class="w-full text-sm text-left divide-y divide-gray-200 dark:divide-white/5">
                <thead class="bg-gray-50 dark:bg-white/5 sticky top-0">
                    <tr>
                        <th
                            class="px-4 py-3 text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400">
                            Tanggal</th>
                        <th
                            class="px-4 py-3 text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400">
                            Keterangan</th>
                        <th
                            class="px-4 py-3 text-xs font-medium tracking-wider text-right text-gray-500 uppercase dark:text-gray-400">
                            Jumlah</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                    @forelse ($breakdownExpenses as $expense)
                        <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($expense['date'])->format('d M Y') }}
                            </td>
                            <td class="px-4 py-3 text-gray-900 dark:text-white">
                                <span class="block font-medium">{{ $expense['category'] }}</span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $expense['description'] }}</span>
                            </td>
                            <td class="px-4 py-3 text-right text-red-600 dark:text-red-400 font-bold whitespace-nowrap">
                                Rp {{ number_format($expense['amount'], 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-6 text-center text-gray-400 text-sm">Belum ada
                                pengeluaran operasional.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>

    {{-- Top HPP Contributors --}}
    <x-filament::section>
        <x-slot name="heading">
            Top 5 Modal (HPP)
        </x-slot>
        <div class="space-y-4">
            @foreach($hppContributors as $item)
                <div
                    class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-white/5 last:border-0">
                    <div class="flex flex-col">
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $item['name'] }}</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $item['qty'] }} terjual</span>
                    </div>
                    <div class="text-right">
                        <span class="text-sm font-bold text-orange-600 dark:text-orange-400">Rp
                            {{ number_format($item['total_hpp'], 0, ',', '.') }}</span>
                        <div class="text-[10px] text-gray-400">Modal per porsi: Rp
                            {{ number_format($item['unit_hpp'], 0, ',', '.') }}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </x-filament::section>

    {{-- Top Profit Contributors --}}
    <x-filament::section>
        <x-slot name="heading">
            Top 5 Profit Maker
        </x-slot>
        <div class="space-y-4">
            @foreach($profitContributors as $item)
                <div
                    class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-white/5 last:border-0">
                    <div class="flex flex-col">
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $item['name'] }}</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $item['qty'] }} terjual</span>
                    </div>
                    <div class="text-right">
                        <span class="text-sm font-bold text-green-600 dark:text-green-400">Rp
                            {{ number_format($item['total_profit'], 0, ',', '.') }}</span>
                        <div class="text-[10px] text-gray-400">Realisasi Cuan per porsi</div>
                    </div>
                </div>
            @endforeach
        </div>
    </x-filament::section>



</x-filament-panels::page>
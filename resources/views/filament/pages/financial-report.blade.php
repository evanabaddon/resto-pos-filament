<x-filament-panels::page>
    {{ $this->form }}

    {{-- Diagnosis Alert --}}
    @if($totalRevenue > 0 && $grossMargin < 10)
        <div class="mt-4 p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50" role="alert">
            <span class="font-medium">⚠️ Peringatan Data:</span> Margin Keuntungan Kotor hanya
            <strong>{{ number_format($grossMargin, 1) }}%</strong> (Sangat Rendah).
            <p class="mt-1">
                Sistem mendeteksi selisih kecil antara Omzet dan HPP.
                Kemungkinan besar <strong>Harga Pokok (HPP)</strong> di data Produk diinput sama dengan atau mendekati
                <strong>Harga Jual</strong>.
                Silakan cek menu <em>Produk -> Edit -> Harga Pokok</em>.
            </p>
        </div>
    @endif

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-2">
        {{-- Total Revenue --}}
        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm relative overflow-hidden group">
            <div class="absolute right-0 top-0 h-full w-16 bg-gradient-to-l from-green-50 to-transparent"></div>
            <div class="relative z-10">
                <p class="text-sm font-medium text-gray-500 mb-1">Total Omzet (Revenue)</p>
                <h3 class="text-2xl font-bold text-gray-900">
                    Rp {{ number_format($totalRevenue, 0, ',', '.') }}
                </h3>
            </div>
            <div class="absolute bottom-4 right-4 text-green-500 opacity-20 group-hover:opacity-100 transition-opacity">
                <x-heroicon-o-currency-dollar class="w-12 h-12" />
            </div>
        </div>

        {{-- Total COGS (HPP) --}}
        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm relative overflow-hidden group">
            <div class="absolute right-0 top-0 h-full w-16 bg-gradient-to-l from-orange-50 to-transparent"></div>
            <div class="relative z-10">
                <p class="text-sm font-medium text-gray-500 mb-1">Total HPP (COGS)</p>
                <h3 class="text-2xl font-bold text-orange-600">
                    Rp {{ number_format($totalCogs, 0, ',', '.') }}
                </h3>
                <p class="text-xs text-orange-400 mt-1">*Estimasi berdasarkan resep saat ini</p>
            </div>
            <div
                class="absolute bottom-4 right-4 text-orange-500 opacity-20 group-hover:opacity-100 transition-opacity">
                <x-heroicon-o-shopping-bag class="w-12 h-12" />
            </div>
        </div>

        {{-- Total Expenses --}}
        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm relative overflow-hidden group">
            <div class="absolute right-0 top-0 h-full w-16 bg-gradient-to-l from-red-50 to-transparent"></div>
            <div class="relative z-10">
                <p class="text-sm font-medium text-gray-500 mb-1">Biaya Operasional</p>
                <h3 class="text-2xl font-bold text-red-600">
                    Rp {{ number_format($totalExpenses, 0, ',', '.') }}
                </h3>
            </div>
            <div class="absolute bottom-4 right-4 text-red-500 opacity-20 group-hover:opacity-100 transition-opacity">
                <x-heroicon-o-receipt-percent class="w-12 h-12" />
            </div>
        </div>

        {{-- Net Profit --}}
        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm relative overflow-hidden group">
            <div class="absolute right-0 top-0 h-full w-16 bg-gradient-to-l from-blue-50 to-transparent"></div>
            <div class="relative z-10">
                <p class="text-sm font-medium text-gray-500 mb-1">Laba Bersih (Net Profit)</p>
                <h3 class="text-2xl font-bold {{ $netProfit >= 0 ? 'text-blue-600' : 'text-red-600' }}">
                    Rp {{ number_format($netProfit, 0, ',', '.') }}
                </h3>
                <p class="text-xs text-gray-400 mt-1">
                    Margin: {{ $totalRevenue > 0 ? number_format(($netProfit / $totalRevenue) * 100, 1) : 0 }}%
                </p>
            </div>
            <div class="absolute bottom-4 right-4 text-blue-500 opacity-20 group-hover:opacity-100 transition-opacity">
                <x-heroicon-o-banknotes class="w-12 h-12" />
            </div>
        </div>
    </div>

    {{-- Detail Section --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Profit Calculation Flow --}}
        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Perhitungan Laba Rugi</h3>

            <div class="space-y-4">
                {{-- Income --}}
                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                    <span class="text-gray-600">Pendapatan Penjualan (Gross Sales)</span>
                    <span class="font-bold text-gray-900">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</span>
                </div>

                {{-- HPP --}}
                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                    <span class="text-gray-600">(-) Harga Pokok Penjualan (HPP)</span>
                    <span class="font-bold text-orange-600">(Rp {{ number_format($totalCogs, 0, ',', '.') }})</span>
                </div>

                {{-- Gross Profit Result --}}
                <div class="flex justify-between items-center py-3 bg-gray-50 px-3 rounded-lg border border-gray-200">
                    <span class="font-bold text-gray-700">LABA KOTOR (GROSS PROFIT)</span>
                    <span class="font-bold text-gray-900">Rp {{ number_format($totalGrossProfit, 0, ',', '.') }}</span>
                </div>

                {{-- Expenses --}}
                <div class="flex justify-between items-center py-2 border-b border-gray-100 mt-4">
                    <span class="text-gray-600">(-) Biaya Operasional</span>
                    <span class="font-bold text-red-600">(Rp {{ number_format($totalExpenses, 0, ',', '.') }})</span>
                </div>

                {{-- Net Profit Result --}}
                <div
                    class="flex justify-between items-center py-4 bg-violet-50 px-4 rounded-xl border border-violet-100 mt-4">
                    <span class="text-xl font-bold text-violet-800">LABA BERSIH (NET PROFIT)</span>
                    <span class="text-xl font-bold text-violet-800">Rp
                        {{ number_format($netProfit, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        {{-- Expense Breakdown --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Rincian Pengeluaran</h3>

            @if(count($expenseBreakdown) > 0)
                <div class="space-y-3">
                    @foreach($expenseBreakdown as $category => $amount)
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-600">{{ $category ?: 'Tanpa Kategori' }}</span>
                            <span class="font-medium text-gray-900">Rp {{ number_format($amount, 0, ',', '.') }}</span>
                        </div>
                        {{-- Bar --}}
                        <div class="w-full bg-gray-100 rounded-full h-1.5 mb-2">
                            <div class="bg-red-500 h-1.5 rounded-full"
                                style="width: {{ $totalExpenses > 0 ? ($amount / $totalExpenses) * 100 : 0 }}%"></div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="flex flex-col items-center justify-center h-40 text-gray-400">
                    <x-heroicon-o-clipboard class="w-10 h-10 mb-2" />
                    <p class="text-sm">Tidak ada data pengeluaran</p>
                </div>
            @endif
        </div>
    </div>
    {{-- Analysis Section --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        {{-- Top Cost Contributors --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">🏆 Top Cost (Penyumbang HPP)</h3>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                        <tr>
                            <th class="px-4 py-3">Produk</th>
                            <th class="px-4 py-3 text-right">Total HPP</th>
                            <th class="px-4 py-3 text-right">%</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($hppContributors as $item)
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-900">
                                    {{ $item['name'] }}
                                    <div class="text-xs text-gray-400">{{ $item['qty'] }} Sold</div>
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-orange-600">
                                    Rp {{ number_format($item['total_hpp'], 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-3 text-right text-gray-400">
                                    {{ $totalCogs > 0 ? number_format(($item['total_hpp'] / $totalCogs) * 100, 1) : 0 }}%
                                </td>
                            </tr>
                        @endforeach
                        @if(empty($hppContributors))
                            <tr>
                                <td colspan="3" class="px-4 py-8 text-center text-gray-400">No Data</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Top Profit Contributors --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">💎 Top Profit (Penyumbang Laba)</h3>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                        <tr>
                            <th class="px-4 py-3">Produk</th>
                            <th class="px-4 py-3 text-right">Total Profit</th>
                            <th class="px-4 py-3 text-right">%</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($profitContributors as $item)
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-900">
                                    {{ $item['name'] }}
                                    <div class="text-xs text-gray-400">{{ $item['qty'] }} Sold</div>
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-green-600">
                                    Rp {{ number_format($item['total_profit'], 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-3 text-right text-gray-400">
                                    {{ $totalGrossProfit > 0 ? number_format(($item['total_profit'] / $totalGrossProfit) * 100, 1) : 0 }}%
                                </td>
                            </tr>
                        @endforeach
                        @if(empty($profitContributors))
                            <tr>
                                <td colspan="3" class="px-4 py-8 text-center text-gray-400">No Data</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
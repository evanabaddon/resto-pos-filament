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

        {{-- Total HPP (COGS) --}}
        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm relative overflow-hidden group">
            <div class="absolute right-0 top-0 h-full w-16 bg-gradient-to-l from-orange-50 to-transparent"></div>
            <div class="relative z-10">
                <p class="text-sm font-medium text-gray-500 mb-1">Total HPP (COGS)</p>
                <h3 class="text-2xl font-bold text-orange-600">
                    Rp {{ number_format($totalHpp, 0, ',', '.') }}
                </h3>
                <p class="text-[10px] text-orange-400 mt-1">Estimasi HPP + Pembelian Stok</p>
            </div>
            <div
                class="absolute bottom-4 right-4 text-orange-500 opacity-20 group-hover:opacity-100 transition-opacity">
                <x-heroicon-o-shopping-bag class="w-12 h-12" />
            </div>
        </div>

        {{-- Total Expenses (Operational) --}}
        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm relative overflow-hidden group">
            <div class="absolute right-0 top-0 h-full w-16 bg-gradient-to-l from-red-50 to-transparent"></div>
            <div class="relative z-10">
                <p class="text-sm font-medium text-gray-500 mb-1">Biaya Operasional</p>
                <h3 class="text-2xl font-bold text-red-600">
                    Rp {{ number_format($totalExpenses, 0, ',', '.') }}
                </h3>
                <p class="text-[10px] text-red-400 mt-1">Listrik, Sewa, Gaji, dll</p>
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
        <div class="lg:col-span-1 bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Perhitungan Laba Rugi</h3>

            <div class="space-y-4">
                {{-- Income --}}
                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                    <span class="text-xs text-gray-600">Pendapatan (Revenue)</span>
                    <span class="font-bold text-gray-900">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</span>
                </div>

                {{-- HPP --}}
                <div class="space-y-1 py-2 border-b border-gray-100">
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-gray-600">(-) Total HPP</span>
                        <span class="font-bold text-orange-600">(Rp {{ number_format($totalHpp, 0, ',', '.') }})</span>
                    </div>
                </div>

                {{-- Gross Profit Result --}}
                <div class="flex justify-between items-center py-3 bg-gray-50 px-3 rounded-lg border border-gray-200">
                    <span class="text-xs font-bold text-gray-700 uppercase">Gross Profit</span>
                    <span class="font-bold text-gray-900">Rp {{ number_format($totalGrossProfit, 0, ',', '.') }}</span>
                </div>

                {{-- Expenses --}}
                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                    <span class="text-xs text-gray-600">(-) Biaya Operasional</span>
                    <span class="font-bold text-red-600">(Rp {{ number_format($totalExpenses, 0, ',', '.') }})</span>
                </div>

                {{-- Net Profit Result --}}
                <div
                    class="flex justify-between items-center py-4 bg-violet-50 px-4 rounded-xl border border-violet-100">
                    <span class="text-sm font-bold text-violet-800 uppercase">Net Profit</span>
                    <span class="text-lg font-bold text-violet-800">Rp
                        {{ number_format($netProfit, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        {{-- Purchase Breakdown (HPP Details) --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider">Belanja per Produk</h3>
                <span class="text-xs font-bold text-orange-600">Rp
                    {{ number_format($totalPurchases, 0, ',', '.') }}</span>
            </div>

            @if(count($purchaseBreakdown) > 0)
                <div class="space-y-3 max-h-60 overflow-y-auto pr-2 custom-scrollbar">
                    @foreach($purchaseBreakdown as $product => $amount)
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-gray-600 truncate mr-2">{{ $product }}</span>
                            <span class="font-medium text-gray-900 shrink-0">Rp {{ number_format($amount, 0, ',', '.') }}</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-1 mb-2">
                            <div class="bg-orange-500 h-1 rounded-full"
                                style="width: {{ $totalPurchases > 0 ? ($amount / $totalPurchases) * 100 : 0 }}%"></div>
                        </div>
                    @endforeach
                </div>
                <div class="pt-2 italic text-[9px] text-gray-400 mt-2">
                    * Menampilkan daftar belanja stok kumulatif per item.
                </div>
            @else
                <div class="flex flex-col items-center justify-center h-40 text-gray-400">
                    <x-heroicon-o-shopping-cart class="w-10 h-10 mb-2" />
                    <p class="text-xs">Tidak ada data pembelian</p>
                </div>
            @endif
        </div>

        {{-- Expense Breakdown (Operational) --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider">Biaya Operasional per Kategori</h3>
                <span class="text-xs font-bold text-red-600">Rp
                    {{ number_format($totalExpenses, 0, ',', '.') }}</span>
            </div>

            @if(count($expenseBreakdown) > 0)
                <div class="space-y-3 max-h-60 overflow-y-auto pr-2 custom-scrollbar">
                    @foreach($expenseBreakdown as $category => $amount)
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-gray-600 truncate mr-2">{{ $category ?: 'Tanpa Kategori' }}</span>
                            <span class="font-medium text-gray-900 shrink-0">Rp {{ number_format($amount, 0, ',', '.') }}</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-1 mb-2">
                            <div class="bg-red-500 h-1 rounded-full"
                                style="width: {{ $totalExpenses > 0 ? ($amount / $totalExpenses) * 100 : 0 }}%"></div>
                        </div>
                    @endforeach
                </div>
                <div class="pt-2 italic text-[9px] text-gray-400 mt-2">
                    * Menampilkan daftar biaya operasional kumulatif per kategori.
                </div>
            @else
                <div class="flex flex-col items-center justify-center h-40 text-gray-400">
                    <x-heroicon-o-clipboard-document-list class="w-10 h-10 mb-2" />
                    <p class="text-xs">Tidak ada data operasional</p>
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
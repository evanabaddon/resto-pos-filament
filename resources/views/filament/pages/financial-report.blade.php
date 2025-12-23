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
            </div>
            <div class="absolute bottom-4 right-4 text-blue-500 opacity-20 group-hover:opacity-100 transition-opacity">
                <x-heroicon-o-currency-dollar class="w-12 h-12" />
            </div>
        </div>
    </div>

    {{-- Detail Section --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
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
                    * Menampilkan daftar belanja stok kumulatif per item (Informasional).
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
                <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider">Biaya per Kategori</h3>
                <span class="text-xs font-bold text-red-600">Rp
                    {{ number_format($totalExpenses, 0, ',', '.') }}</span>
            </div>

            @if(count($expenseBreakdown) > 0)
                <div class="space-y-3 max-h-60 overflow-y-auto pr-2 custom-scrollbar">
                    @foreach($expenseBreakdown as $category => $amount)
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-gray-600 truncate mr-2">{{ $category ?: 'Lainnya' }}</span>
                            <span class="font-medium text-gray-900 shrink-0">Rp {{ number_format($amount, 0, ',', '.') }}</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-1 mb-2">
                            <div class="bg-red-500 h-1 rounded-full"
                                style="width: {{ $totalExpenses > 0 ? ($amount / $totalExpenses) * 100 : 0 }}%"></div>
                        </div>
                    @endforeach
                </div>
                <div class="pt-2 italic text-[9px] text-gray-400 mt-2">
                    * Menampilkan daftar biaya kumulatif per kategori (Ops + Gaji).
                </div>
            @else
                <div class="flex flex-col items-center justify-center h-40 text-gray-400">
                    <x-heroicon-o-clipboard-document-list class="w-10 h-10 mb-2" />
                    <p class="text-xs">Tidak ada data biaya</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Asset Analysis Section --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 mb-6">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h3 class="text-lg font-bold text-gray-800">Analisis Aset Stok</h3>
                <p class="text-xs text-gray-500">Valuasi stok barang saat ini berdasarkan harga modal (HPP).</p>
            </div>
            <div class="flex flex-col items-end">
                <span class="text-xs font-bold text-yellow-600 uppercase">Total Nilai Aset</span>
                <span class="text-2xl font-bold text-yellow-600">Rp
                    {{ number_format($currentStockValue, 0, ',', '.') }}</span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50/50">
                    <tr>
                        <th scope="col" class="px-4 py-3">Nama Produk</th>
                        <th scope="col" class="px-4 py-3 text-right">Stok</th>
                        <th scope="col" class="px-4 py-3 text-right">Harga Modal</th>
                        <th scope="col" class="px-4 py-3 text-right">Total Nilai</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topStockAssets as $asset)
                        <tr class="border-b hover:bg-gray-50/50 transition duration-150">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $asset['name'] }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format($asset['stock'], 2) }} {{ $asset['unit'] }}
                            </td>
                            <td class="px-4 py-3 text-right">Rp {{ number_format($asset['price'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right font-bold text-yellow-600">Rp
                                {{ number_format($asset['total_value'], 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-gray-400">
                                Belum ada data stok
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Top Contributors Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        {{-- Top HPP Contributors --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <h3 class="text-sm font-bold text-gray-800 mb-4 uppercase tracking-widest border-b pb-2">Top 5 Modal (HPP)
            </h3>
            <div class="space-y-4">
                @foreach($hppContributors as $item)
                    <div class="flex justify-between items-center">
                        <div class="flex flex-col">
                            <span class="text-xs font-bold text-gray-900">{{ $item['name'] }}</span>
                            <span class="text-[10px] text-gray-400">{{ $item['qty'] }} terjual</span>
                        </div>
                        <div class="text-right">
                            <span class="text-xs font-bold text-orange-600">Rp
                                {{ number_format($item['total_hpp'], 0, ',', '.') }}</span>
                            <div class="text-[10px] text-gray-400">Modal per porsi: Rp
                                {{ number_format($item['unit_hpp'], 0, ',', '.') }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Top Profit Contributors --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <h3 class="text-sm font-bold text-gray-800 mb-4 uppercase tracking-widest border-b pb-2">Top 5 Profit Maker
            </h3>
            <div class="space-y-4">
                @foreach($profitContributors as $item)
                    <div class="flex justify-between items-center">
                        <div class="flex flex-col">
                            <span class="text-xs font-bold text-gray-900">{{ $item['name'] }}</span>
                            <span class="text-[10px] text-gray-400">{{ $item['qty'] }} terjual</span>
                        </div>
                        <div class="text-right">
                            <span class="text-xs font-bold text-green-600">Rp
                                {{ number_format($item['total_profit'], 0, ',', '.') }}</span>
                            <div class="text-[10px] text-gray-400">Realisasi Cuan per porsi</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #e2e8f0;
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #cbd5e1;
        }
    </style>
</x-filament-panels::page>
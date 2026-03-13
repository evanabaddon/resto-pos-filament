<x-filament-panels::page>
    <div class="flex items-center justify-between mb-2">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
                {{ __('messages.financial_report') }}</h2>
            <p class="text-sm text-gray-500">{{ __('messages.financial_analysis_desc') }}</p>
        </div>
        <div class="flex items-center gap-3">
            {{-- Filter status info --}}
            <div class="hidden md:flex flex-col items-end">
                <span class="text-[10px] font-bold text-gray-400 uppercase">{{ __('messages.report_period') }}</span>
                <span class="text-xs font-medium text-gray-600">
                    {{ \Carbon\Carbon::parse($date_start)->translatedFormat('d M Y') }} -
                    {{ \Carbon\Carbon::parse($date_end)->translatedFormat('d M Y') }}
                </span>
            </div>
        </div>
    </div>

    {{-- Filter Form --}}
    <div class="">
        {{ $this->form }}
    </div>

    {{-- Header Widgets (Chart) --}}
    <x-filament-widgets::widgets :widgets="$this->getManualHeaderWidgets()" :columns="[
        'md' => 1,
        'xl' => 1,
    ]"
        :data="$this->getWidgetData()" />

    {{-- Stats Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        {{-- Net Sales --}}
        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm relative overflow-hidden group">
            <div class="absolute right-0 top-0 h-full w-16 bg-gradient-to-l from-green-50 to-transparent"></div>
            <div class="relative z-10">
                <p class="text-sm font-medium text-gray-500 mb-1">{{ __('messages.net_sales') }}</p>
                <h3 class="text-2xl font-bold text-green-600">
                    Rp {{ number_format($totalRevenue, 0, ',', '.') }}
                </h3>
                <p class="text-[10px] text-green-400 mt-1">{{ __('messages.gross') }}: Rp
                    {{ number_format($totalGrossSales, 0, ',', '.') }}
                </p>
                <div class="mt-2 text-xs flex items-center gap-1">
                    <span class="{{ $growthRevenue >= 0 ? 'text-green-600' : 'text-red-600' }} font-bold">
                        {{ $growthRevenue >= 0 ? '+' : '' }}{{ number_format($growthRevenue, 1) }}%
                    </span>
                    <span class="text-gray-400 text-[10px]">{{ __('messages.vs_last_period') }}</span>
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
                <p class="text-sm font-medium text-gray-500 mb-1">{{ __('messages.total_hpp_accrual') }}</p>
                <h3 class="text-2xl font-bold text-orange-600">
                    Rp {{ number_format($totalHpp, 0, ',', '.') }}
                </h3>
                <p class="text-[10px] text-orange-400 mt-1">{{ __('messages.sales_modal_estimated') }}</p>
                <div class="mt-2 text-xs flex items-center gap-1">
                    <span class="{{ $growthHpp <= 0 ? 'text-green-600' : 'text-orange-600' }} font-bold">
                        {{ $growthHpp >= 0 ? '+' : '' }}{{ number_format($growthHpp, 1) }}%
                    </span>
                    <span class="text-gray-400 text-[10px]">{{ __('messages.vs_last_period') }}</span>
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
                <p class="text-sm font-medium text-gray-500 mb-1">{{ __('messages.operational_expenses') }}</p>
                <h3 class="text-2xl font-bold text-red-600">
                    Rp {{ number_format($totalExpenses, 0, ',', '.') }}
                </h3>
                <p class="text-[10px] text-red-400 mt-1">{{ __('messages.ops_plus_payroll') }}</p>
                <div class="mt-2 text-xs flex items-center gap-1">
                    <span class="{{ $growthExpenses <= 0 ? 'text-green-600' : 'text-red-600' }} font-bold">
                        {{ $growthExpenses >= 0 ? '+' : '' }}{{ number_format($growthExpenses, 1) }}%
                    </span>
                    <span class="text-gray-400 text-[10px]">{{ __('messages.vs_last_period') }}</span>
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
                <p class="text-sm font-medium text-gray-500 mb-1">{{ __('messages.net_profit') }}</p>
                <h3 class="text-2xl font-bold {{ $netProfit >= 0 ? 'text-blue-600' : 'text-red-600' }}">
                    Rp {{ number_format($netProfit, 0, ',', '.') }}
                </h3>
                <p class="text-xs text-gray-400 mt-1">
                    {{ __('messages.margin') }}:
                    {{ $totalRevenue > 0 ? number_format(($netProfit / $totalRevenue) * 100, 1) : 0 }}%
                </p>
                <div class="mt-2 text-xs flex items-center gap-1">
                    <span class="{{ $growthNetProfit >= 0 ? 'text-green-600' : 'text-red-600' }} font-bold">
                        {{ $growthNetProfit >= 0 ? '+' : '' }}{{ number_format($growthNetProfit, 1) }}%
                    </span>
                    <span class="text-gray-400 text-[10px]">{{ __('messages.vs_last_period') }}</span>
                </div>
            </div>
            <div class="absolute bottom-4 right-4 text-blue-500 opacity-20 group-hover:opacity-100 transition-opacity">
                <x-heroicon-o-currency-dollar class="w-12 h-12" />
            </div>
        </div>

        {{-- Total Purchase (Cash Out) --}}
        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm relative overflow-hidden group">
            <div class="absolute right-0 top-0 h-full w-16 bg-gradient-to-l from-purple-50 to-transparent"></div>
            <div class="relative z-10">
                <p class="text-sm font-medium text-gray-500 mb-1">{{ __('messages.total_purchases') }}</p>
                <h3 class="text-2xl font-bold text-purple-600">
                    Rp {{ number_format($totalPurchases, 0, ',', '.') }}
                </h3>
                <p class="text-[10px] text-purple-400 mt-1">{{ __('messages.total_purchases_desc') }}</p>
                <p class="text-[9px] text-gray-400 mt-1 italic">{{ __('messages.accrual_help_text') ?? 'Pembelian stok tidak mengurangi laba sampai barang terjual (HPP).' }}</p>
            </div>
            <div class="absolute bottom-4 right-4 text-purple-500 opacity-20 group-hover:opacity-100 transition-opacity">
                <x-heroicon-o-shopping-cart class="w-12 h-12" />
            </div>
        </div>
    </div>

    {{-- Warning/Info Alert --}}
    <div class="bg-amber-50 border-l-4 border-amber-400 p-4 rounded shadow-sm">
        <div class="flex">
            <div class="flex-shrink-0">
                <x-heroicon-s-exclamation-triangle class="h-5 w-5 text-amber-400" />
            </div>
            <div class="ml-3">
                <p class="text-sm text-amber-700">
                    <strong>Penting:</strong> {{ __('messages.accrual_info_alert') ?? 'Laporan ini menggunakan metode Accrual. Jika Anda mencatat belanja stok di menu Expense, pastikan untuk mencentang "Pembelian Stok" agar profit tidak terhitung minus dua kali.' }}
                </p>
            </div>
        </div>
    </div>

    {{-- Profit Calculation Flow --}}
    <div class="lg:col-span-1 bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <h3 class="text-sm font-bold text-gray-800 uppercase tracking-widest mb-6 border-b pb-2">
            {{ __('messages.loss_profit_report') }}
        </h3>

        <div class="space-y-4">
            {{-- Income Flow --}}
            <div class="space-y-2">
                <div class="flex justify-between items-center text-xs">
                    <span class="text-gray-500">{{ __('messages.gross_revenue') }}</span>
                    <span class="font-medium text-gray-900">Rp
                        {{ number_format($totalGrossSales, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between items-center text-xs">
                    <span class="text-gray-500">{{ __('messages.discounts_deduction') }}</span>
                    <span class="font-medium text-red-500">(Rp
                        {{ number_format($totalDiscounts, 0, ',', '.') }})</span>
                </div>
                <div
                    class="flex justify-between items-center py-2 bg-gray-50 px-3 rounded border border-dashed border-gray-200">
                    <span class="text-xs font-bold text-gray-700">{{ __('messages.net_revenue') }}</span>
                    <span class="text-sm font-bold text-gray-900">Rp
                        {{ number_format($totalRevenue, 0, ',', '.') }}</span>
                </div>
            </div>

            {{-- HPP Flow --}}
            <div class="pt-2">
                <div class="flex justify-between items-center text-xs">
                    <span class="text-gray-500">{{ __('messages.cogs_deduction') }}</span>
                    <span class="font-medium text-orange-600">(Rp
                        {{ number_format($totalHpp, 0, ',', '.') }})</span>
                </div>
            </div>

            {{-- Gross Profit Result --}}
            <div class="flex justify-between items-center py-3 bg-blue-50 px-3 rounded-lg border border-blue-100">
                <span class="text-xs font-bold text-blue-800 uppercase">{{ __('messages.gross_profit') }}</span>
                <span class="font-bold text-blue-900 text-lg">Rp
                    {{ number_format($totalGrossProfit, 0, ',', '.') }}</span>
            </div>

            {{-- Expenses --}}
            <div class="space-y-2">
                <div class="flex justify-between items-center text-xs">
                    <span class="text-gray-500">{{ __('messages.expenses_deduction') }}</span>
                    <span class="font-medium text-red-600">(Rp
                        {{ number_format($totalExpenses, 0, ',', '.') }})</span>
                </div>
                @if($totalPayroll > 0)
                    <div class="flex justify-between items-center text-[10px] italic text-gray-400 pl-2">
                        <span>{{ __('messages.includes_payroll', ['amount' => 'Rp ' . number_format($totalPayroll, 0, ',', '.')]) }}</span>
                    </div>
                @endif
            </div>

            {{-- Net Profit Result --}}
            <div
                class="flex justify-between items-center py-5 bg-violet-600 px-5 rounded-2xl shadow-lg shadow-violet-200">
                <span
                    class="text-sm font-bold text-white uppercase tracking-wider">{{ __('messages.net_profit') }}</span>
                <span class="text-xl font-bold text-white">Rp
                    {{ number_format($netProfit, 0, ',', '.') }}</span>
            </div>

            <div class="pt-4 mt-4 border-t border-gray-100">
                <div class="flex justify-between items-center text-[10px] text-gray-400 italic">
                    <span>{{ __('messages.tax_deposit') }}</span>
                    <span>Rp {{ number_format($totalTaxes, 0, ',', '.') }}</span>
                </div>
                <p class="text-[9px] text-gray-400 mt-1 leading-tight">
                    {{ __('messages.tax_note') }}
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
                        <h3 class="text-indigo-200 text-sm font-medium uppercase tracking-wider mb-2">
                            {{ __('messages.asset_valuation') }}
                        </h3>
                        <div class="text-3xl font-bold mb-1">
                            Rp {{ number_format($currentStockValue, 0, ',', '.') }}
                        </div>
                        <p class="text-xs text-indigo-300">{{ __('messages.asset_value_desc') }}</p>

                        <div class="mt-6 pt-6 border-t border-white/10">
                            <div class="flex items-center gap-2 text-xs text-indigo-200">
                                <x-heroicon-o-information-circle class="w-4 h-4" />
                                <span>{{ __('messages.based_on_base_price') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="md:col-span-2">
                <x-filament::section>
                    <x-slot name="heading">
                        {{ __('messages.top_10_assets') }}
                    </x-slot>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left divide-y divide-gray-200 dark:divide-white/5">
                            <thead class="bg-gray-50 dark:bg-white/5">
                                <tr>
                                    <th class="px-4 py-3 font-medium text-gray-500">{{ __('messages.product') }}</th>
                                    <th class="px-4 py-3 font-medium text-gray-500 text-center">
                                        {{ __('messages.stock') }}</th>
                                    <th class="px-4 py-3 font-medium text-gray-500 text-right">
                                        {{ __('messages.total_value') }}</th>
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
            {{ __('messages.cogs_breakdown') }}
        </x-slot>

        <div class="overflow-x-auto max-h-[500px] overflow-y-auto">
            <table class="w-full text-sm text-left divide-y divide-gray-200 dark:divide-white/5">
                <thead class="bg-gray-50 dark:bg-white/5 sticky top-0">
                    <tr>
                        <th
                            class="px-4 py-3 text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400">
                            {{ __('messages.product') }}
                        </th>
                        <th
                            class="px-4 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase dark:text-gray-400">
                            Qty</th>
                        <th
                            class="px-4 py-3 text-xs font-medium tracking-wider text-right text-gray-500 uppercase dark:text-gray-400">
                            {{ __('messages.total_hpp') }}
                        </th>
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
                            <td colspan="3" class="px-4 py-6 text-center text-gray-400 text-sm">
                                {{ __('messages.no_sales_data') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>

    {{-- Table Breakdown Expenses --}}
    <x-filament::section collapsible>
        <x-slot name="heading">
            {{ __('messages.expenses_breakdown') }}
        </x-slot>

        <div class="overflow-x-auto max-h-[500px] overflow-y-auto">
            <table class="w-full text-sm text-left divide-y divide-gray-200 dark:divide-white/5">
                <thead class="bg-gray-50 dark:bg-white/5 sticky top-0">
                    <tr>
                        <th
                            class="px-4 py-3 text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400">
                            {{ __('messages.date') }}
                        </th>
                        <th
                            class="px-4 py-3 text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400">
                            {{ __('messages.description') }}
                        </th>
                        <th
                            class="px-4 py-3 text-xs font-medium tracking-wider text-right text-gray-500 uppercase dark:text-gray-400">
                            {{ __('messages.amount') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                    @forelse ($breakdownExpenses as $expense)
                        <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($expense['date'])->translatedFormat('d M Y') }}
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
                            <td colspan="3" class="px-4 py-6 text-center text-gray-400 text-sm">
                                {{ __('messages.no_expenses_data') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>

    {{-- Table Breakdown Purchases --}}
    <x-filament::section collapsible collapsed>
        <x-slot name="heading">
            {{ __('messages.purchases_breakdown') }}
        </x-slot>

        <div class="overflow-x-auto max-h-[500px] overflow-y-auto">
            <table class="w-full text-sm text-left divide-y divide-gray-200 dark:divide-white/5">
                <thead class="bg-gray-50 dark:bg-white/5 sticky top-0">
                    <tr>
                        <th
                            class="px-4 py-3 text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400">
                            {{ __('messages.product') }}
                        </th>
                        <th
                            class="px-4 py-3 text-xs font-medium tracking-wider text-right text-gray-500 uppercase dark:text-gray-400">
                            {{ __('messages.total_value') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                    @forelse ($purchaseBreakdown as $productName => $totalAmount)
                        <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $productName }}</td>
                            <td class="px-4 py-3 text-right text-purple-600 dark:text-purple-400 font-bold">
                                Rp {{ number_format($totalAmount, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="px-4 py-6 text-center text-gray-400 text-sm">
                                {{ __('messages.no_purchases_data') ?? 'Belum ada data pembelian (Received).' }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>

    {{-- Top HPP Contributors --}}
    <x-filament::section>
        <x-slot name="heading">
            {{ __('messages.top_5_hpp') }}
        </x-slot>
        <div class="space-y-4">
            @foreach($hppContributors as $item)
                <div
                    class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-white/5 last:border-0">
                    <div class="flex flex-col">
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $item['name'] }}</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $item['qty'] }}
                            {{ __('messages.sold') }}</span>
                    </div>
                    <div class="text-right">
                        <span class="text-sm font-bold text-orange-600 dark:text-orange-400">Rp
                            {{ number_format($item['total_hpp'], 0, ',', '.') }}</span>
                        <div class="text-[10px] text-gray-400">{{ __('messages.modal_per_portion') }}: Rp
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
            {{ __('messages.top_5_profit') }}
        </x-slot>
        <div class="space-y-4">
            @foreach($profitContributors as $item)
                <div
                    class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-white/5 last:border-0">
                    <div class="flex flex-col">
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $item['name'] }}</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $item['qty'] }}
                            {{ __('messages.sold') }}</span>
                    </div>
                    <div class="text-right">
                        <span class="text-sm font-bold text-green-600 dark:text-green-400">Rp
                            {{ number_format($item['total_profit'], 0, ',', '.') }}</span>
                        <div class="text-[10px] text-gray-400">{{ __('messages.realized_cuan') }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </x-filament::section>

</x-filament-panels::page>




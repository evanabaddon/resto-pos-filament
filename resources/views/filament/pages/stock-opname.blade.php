<x-filament-panels::page>
    <div>
        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
                <div class="text-sm text-gray-500 dark:text-gray-400">Total Items</div>
                <div class="text-2xl font-bold">{{ count($products) }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
                <div class="text-sm text-gray-500 dark:text-gray-400">Items Checked</div>
                <div class="text-2xl font-bold">{{ $this->itemsChecked }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
                <div class="text-sm text-gray-500 dark:text-gray-400">Total Variance</div>
                <div class="text-2xl font-bold {{ $this->totalVariance < 0 ? 'text-red-600' : 'text-green-600' }}">
                    {{ number_format(abs($this->totalVariance), 2) }}
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
                <div class="text-sm text-gray-500 dark:text-gray-400">Value Loss</div>
                <div class="text-2xl font-bold text-red-600">Rp {{ number_format($this->totalLoss, 0) }}</div>
            </div>
        </div>

        {{-- Search & Filter --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow mb-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Search Products
                    </label>
                    <input type="text" wire:model.live.debounce.300ms="searchQuery" placeholder="Type product name..."
                        class="w-full px-4 py-3 text-base rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Filter by Category
                    </label>
                    <select wire:model.live="filterCategory"
                        class="w-full px-4 py-3 text-base rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="">All Categories</option>
                        @foreach($this->categories as $category)
                            <option value="{{ $category }}">{{ $category }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- Stock Opname Table --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                <button wire:click="toggleSort('name')"
                                    class="flex items-center gap-1 hover:text-primary-600 transition">
                                    PRODUCTS
                                    @if($sortBy === 'name')
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            @if($sortDirection === 'asc')
                                                <path d="M5 10l5-5 5 5H5z" />
                                            @else
                                                <path d="M15 10l-5 5-5-5h10z" />
                                            @endif
                                        </svg>
                                    @endif
                                </button>
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                CATEGORY
                            </th>
                            <th
                                class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                <button wire:click="toggleSort('stock')"
                                    class="flex items-center gap-1 hover:text-primary-600 transition ml-auto">
                                    SYSTEM STOCK
                                    @if($sortBy === 'stock')
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            @if($sortDirection === 'asc')
                                                <path d="M5 10l5-5 5 5H5z" />
                                            @else
                                                <path d="M15 10l-5 5-5-5h10z" />
                                            @endif
                                        </svg>
                                    @endif
                                </button>
                            </th>
                            <th
                                class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                Physical Count
                            </th>
                            <th
                                class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                Variance
                            </th>
                            <th
                                class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                Value Loss
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($this->filteredProducts as $index => $product)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-4 py-3">
                                    <div class="font-medium">{{ $product['name'] }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ $product['category'] }}</td>
                                <td class="px-4 py-3 text-right">
                                    <span class="text-sm">{{ number_format($product['system_stock'], 2) }}
                                        {{ $product['unit'] }}</span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <input type="number" step="0.01" wire:model.live="products.{{ $index }}.physical_count"
                                        class="w-32 text-right rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
                                    <span class="ml-1 text-sm text-gray-500">{{ $product['unit'] }}</span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @php
                                        $variance = ($product['physical_count'] ?? 0) - $product['system_stock'];
                                        $varianceClass = $variance < 0 ? 'text-red-600' : ($variance > 0 ? 'text-green-600' : 'text-gray-500');
                                    @endphp
                                    <span class="font-medium text-sm {{ $varianceClass }}">
                                        {{ $variance >= 0 ? '+' : '' }}{{ number_format($variance, 2) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @php
                                        $valueLoss = $variance < 0 ? abs($variance) * $product['base_price'] : 0;
                                    @endphp
                                    <span class="font-medium text-sm text-red-600">
                                        {{ $valueLoss > 0 ? 'Rp ' . number_format($valueLoss, 0) : '-' }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Actions moved to page header via getActions() --}}
    </div>
</x-filament-panels::page>
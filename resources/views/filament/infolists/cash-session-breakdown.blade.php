<div class="space-y-3">
    @php
    $sales = $getRecord()->sales->load('paymentMethod');
    $byMethod = $sales->groupBy(fn($sale) => $sale->paymentMethod->name ?? 'Unknown');
    $total = $sales->sum('final_total');
    @endphp

    <div class="rounded-lg border border-gray-200 divide-y divide-gray-200">
        @foreach($byMethod as $method => $group)
        <div class="flex justify-between items-center px-4 py-2">
            <span class="text-sm font-medium text-gray-700">{{ $method }}</span>
            <span class="text-sm text-gray-900 font-semibold">
                Rp {{ number_format($group->sum('final_total'), 0, ',', '.') }}
            </span>
        </div>
        @endforeach

        <div class="flex justify-between items-center px-4 py-2 bg-gray-50">
            <span class="text-sm font-bold text-gray-700">{{ __('messages.total_sales') }}</span>
            <span class="text-sm font-bold text-gray-900">
                Rp {{ number_format($total, 0, ',', '.') }}
            </span>
        </div>
    </div>
</div>
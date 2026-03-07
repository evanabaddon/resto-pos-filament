<div class="space-y-3">
    @php
        $sales = $getRecord()->sales->load(['payments', 'paymentMethod']);
        $detailsMap = [];
        $total = 0;

        foreach ($sales as $sale) {
            $total += $sale->final_total;
            if ($sale->payments->isNotEmpty()) {
                foreach ($sale->payments as $p) {
                    $mName = $p->payment_method_name ?: 'Metode';
                    $detailsMap[$mName] = ($detailsMap[$mName] ?? 0) + $p->amount;
                }
            } else {
                $mName = $sale->payment_method ?: ($sale->paymentMethod->name ?? 'Metode');
                $detailsMap[$mName] = ($detailsMap[$mName] ?? 0) + $sale->final_total;
            }
        }
    @endphp

    <div class="rounded-lg border border-gray-200 divide-y divide-gray-200">
        @foreach($detailsMap as $method => $amount)
            <div class="flex justify-between items-center px-4 py-2">
                <span class="text-sm font-medium text-gray-700">{{ $method }}</span>
                <span class="text-sm text-gray-900 font-semibold">
                    Rp {{ number_format($amount, 0, ',', '.') }}
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
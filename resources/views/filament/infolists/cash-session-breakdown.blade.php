<div class="space-y-3">
    @php
        $allSales = $getRecord()->sales;
        $completedSales = $allSales->where('status', 'completed')->load(['payments', 'paymentMethod']);
        $unpaidSales = $allSales->whereIn('status', ['draft', 'pending']);

        $detailsMap = [];
        $totalCompleted = 0;
        $totalUnpaid = $unpaidSales->sum('final_total');

        foreach ($completedSales as $sale) {
            $totalCompleted += (float) $sale->final_total;

            if ($sale->payments->isNotEmpty()) {
                $nonCashAmount = 0;
                $cashMethodName = 'Tunai';

                foreach ($sale->payments as $p) {
                    $mName = $p->payment_method_name ?: 'Metode';
                    $lowerName = strtolower($mName);

                    // Stricter Cash Detection
                    $isCash = (str_contains($lowerName, 'tunai') || str_contains($lowerName, 'cash'))
                        && !str_contains($lowerName, 'non');

                    if ($isCash) {
                        $cashMethodName = $mName;
                    } else {
                        $amount = min((float) $p->amount, (float) $sale->final_total - $nonCashAmount);
                        $nonCashAmount += $amount;
                        $detailsMap[$mName] = ($detailsMap[$mName] ?? 0) + $amount;
                    }
                }

                $effectiveCash = max(0, (float) $sale->final_total - $nonCashAmount);
                if ($effectiveCash > 0) {
                    $detailsMap[$cashMethodName] = ($detailsMap[$cashMethodName] ?? 0) + $effectiveCash;
                }
            } else {
                // FALLBACK LEGACY
                $mName = $sale->payment_method ?: ($sale->paymentMethod->name ?? 'Metode');
                $detailsMap[$mName] = ($detailsMap[$mName] ?? 0) + (float) $sale->final_total;
            }
        }
    @endphp

    <div class="rounded-lg border border-gray-200 divide-y divide-gray-200">
        {{-- Completed Payments --}}
        @foreach($detailsMap as $method => $amount)
            <div class="flex justify-between items-center px-4 py-2">
                <span class="text-sm font-medium text-gray-700">{{ $method }}</span>
                <span class="text-sm text-gray-900 font-semibold">
                    Rp {{ number_format($amount, 0, ',', '.') }}
                </span>
            </div>
        @endforeach

        {{-- Unpaid Payments --}}
        @if($totalUnpaid > 0)
            <div class="flex justify-between items-center px-4 py-2 text-red-600 bg-red-50/30">
                <span class="text-sm font-medium italic">Belum Terbayar</span>
                <span class="text-sm font-semibold">
                    Rp {{ number_format($totalUnpaid, 0, ',', '.') }}
                </span>
            </div>
        @endif

        <div class="flex justify-between items-center px-4 py-2 bg-gray-50">
            <span class="text-sm font-bold text-gray-700">Total Penjualan (Selesai)</span>
            <span class="text-sm font-bold text-gray-900">
                Rp {{ number_format($totalCompleted, 0, ',', '.') }}
            </span>
        </div>
    </div>
</div>
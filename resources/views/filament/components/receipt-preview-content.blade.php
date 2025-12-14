@php
    $sale = $sale ?? null;
@endphp

@if($sale)
    @inject('settings', 'App\Settings\GeneralSettings')
    @php
        $width = $settings->printer_width ?? '58mm';
        $containerWidth = $width === '58mm' ? '58mm' : '80mm';
        $fontSize = $width === '58mm' ? '12px' : '14px';
    @endphp

    <!-- Container with dynamic width -->
    <div
        style="width: {{ $containerWidth }}; font-family: 'Courier New', Courier, monospace; font-size: {{ $fontSize }}; line-height: 1.2; color: black; background: white; padding: 10px; margin: 0 auto;">
        <div style="width: 100%;">

            <!-- Header -->
            <div style="text-align: center; margin-bottom: 16px;">
                <h1 style="font-weight: bold; font-size: 1.2em; text-transform: uppercase; margin: 0 0 8px 0;">STRUK
                    PEMBAYARAN</h1>
                <p style="font-size: 1.1em; font-weight: bold; margin: 0 0 4px 0;">
                    {{ $settings->app_name ?? config('app.name') }}
                </p>
                @if($settings->company_phone)
                    <p style="font-size: 0.9em; margin: 0;">Telp: {{ $settings->company_phone }}</p>
                @endif
                @if($settings->company_address)
                    <p style="font-size: 0.9em; margin: 0;">{{ $settings->company_address }}</p>
                @endif
            </div>

            <div style="border-top: 1px dashed #d1d5db; margin: 8px 0;"></div>

            <!-- Info Transaksi -->
            <div style="margin-bottom: 16px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                    <span>No. Tr.:</span>
                    <span style="font-weight: 600;">{{ $sale->invoice_number }}</span>
                </div>

                @if($sale->split_number)
                    <div style="font-weight: bold; text-align: center; margin: 4px 0;">
                        ** SPLIT BILL #{{ $sale->split_number }} **
                    </div>
                @endif

                <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                    <span>Tanggal:</span>
                    <span>{{ $sale->created_at->format('d/m/Y H:i') }}</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                    <span>Kasir:</span>
                    <span>{{ $sale->user->name ?? 'System' }}</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                    <span>Customer:</span>
                    <span>{{ $sale->customer_name ?? 'Umum' }}</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                    <span>Tipe Order:</span>
                    <span style="font-weight: 600;">{{ $sale->order_type }}</span>
                </div>
            </div>

            <div style="border-top: 1px dashed #d1d5db; margin: 8px 0;"></div>

            <!-- Items -->
            <div style="margin-bottom: 16px;">
                <div style="font-weight: bold; margin-bottom: 8px;">ITEM YANG DIBELI:</div>
                @foreach($sale->items as $item)
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                        <div style="flex: 1;">
                            <div style="font-weight: 600; margin-bottom: 2px;">{{ $item->product->name ?? 'Produk' }}</div>
                            @if(!empty($item->notes))
                                <div style="font-size: 11px; font-style: italic; margin-bottom: 2px;">📝 {{ $item->notes }}</div>
                            @endif
                            <div style="font-size: 11px; color: #6b7280;">
                                {{ $item->quantity }} × Rp{{ number_format($item->unit_price, 0, ',', '.') }}
                            </div>
                        </div>
                        <div style="font-weight: 600;">Rp{{ number_format($item->subtotal, 0, ',', '.') }}</div>
                    </div>
                    @if(!$loop->last)
                        <div style="border-top: 1px dashed #f3f4f6; margin: 4px 0;"></div>
                    @endif
                @endforeach
            </div>

            <div style="border-top: 1px dashed #d1d5db; margin: 8px 0;"></div>

            <!-- Summary -->
            <div style="margin-bottom: 16px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                    <span>Subtotal:</span>
                    <span>Rp{{ number_format($sale->subtotal, 0, ',', '.') }}</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                    <span>Pajak (10%):</span>
                    <span>Rp{{ number_format($sale->tax, 0, ',', '.') }}</span>
                </div>
                @if($sale->discount > 0)
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px; color: #059669;">
                        <span>Potongan:</span>
                        <span>- Rp{{ number_format($sale->discount, 0, ',', '.') }}</span>
                    </div>
                @endif
                <div style="border-top: 1px solid #d1d5db; padding-top: 4px;">
                    <div style="display: flex; justify-content: space-between; font-weight: bold;">
                        <span>TOTAL:</span>
                        <span>Rp{{ number_format($sale->final_total, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <div style="border-top: 1px dashed #d1d5db; margin: 8px 0;"></div>

            <!-- Payment Info -->
            <div style="margin-bottom: 16px;">
                <div style="font-weight: bold; margin-bottom: 4px;">PEMBAYARAN:</div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                    <span>Metode:</span>
                    <span style="font-weight: 600;">{{ $sale->paymentMethod->name ?? 'Cash' }}</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                    <span>Bayar:</span>
                    <span>Rp{{ number_format($sale->amount_paid, 0, ',', '.') }}</span>
                </div>
                @if(($sale->paymentMethod->code ?? 'cash') === 'cash')
                    @php $change = $sale->amount_paid - $sale->final_total; @endphp
                    @if($change > 0)
                        <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                            <span>Kembali:</span>
                            <span style="font-weight: 600;">Rp{{ number_format($change, 0, ',', '.') }}</span>
                        </div>
                    @endif
                @endif
            </div>

            <div style="border-top: 1px dashed #d1d5db; margin: 8px 0;"></div>

            <!-- Footer -->
            <div style="text-align: center; font-size: 12px;">
                <p style="margin: 0 0 8px 0;">Terima kasih atas kunjungan Anda</p>
                <p style="font-weight: 600; margin: 0;">*** SELAMAT MENIKMATI ***</p>
            </div>
        </div>
    </div>
@else
    <div class="p-4 text-center text-red-600">
        <p>Data transaksi tidak ditemukan</p>
    </div>
@endif
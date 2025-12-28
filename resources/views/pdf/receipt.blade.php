<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Struk {{ $sale->invoice_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        @page {
            margin: 0;
            padding: 0;
        }
        
        html, body {
            height: auto;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 11px;
            line-height: 1.3;
            color: #000;
            padding: 5px;
            margin: 0;
        }
        
        .container {
            width: 100%;
            max-width: 72mm;
            margin: 0 auto;
            page-break-after: avoid;
        }

        .center {
            text-align: center;
        }

        .bold {
            font-weight: bold;
        }

        .divider {
            border-top: 1px dashed #000;
            margin: 8px 0;
        }

        .header {
            text-align: center;
            margin-bottom: 12px;
        }

        .header h1 {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .header .store-name {
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .header .store-info {
            font-size: 10px;
            margin: 2px 0;
        }

        .info-row {
            display: table;
            width: 100%;
            margin-bottom: 4px;
        }

        .info-row .label {
            display: table-cell;
            width: 40%;
        }

        .info-row .value {
            display: table-cell;
            width: 60%;
            text-align: right;
            font-weight: 600;
        }

        .items {
            margin: 10px 0;
        }

        .items .title {
            font-weight: bold;
            margin-bottom: 6px;
        }

        .item {
            margin-bottom: 6px;
        }

        .item .name {
            font-weight: 600;
            margin-bottom: 2px;
        }

        .item .notes {
            font-size: 10px;
            font-style: italic;
            margin-bottom: 2px;
        }

        .item .details {
            font-size: 11px;
            color: #666;
        }

        .item-row {
            display: table;
            width: 100%;
        }

        .item-row .left {
            display: table-cell;
            width: 70%;
        }

        .item-row .right {
            display: table-cell;
            width: 30%;
            text-align: right;
            font-weight: 600;
        }

        .summary .row {
            display: table;
            width: 100%;
            margin-bottom: 4px;
        }

        .summary .row .label {
            display: table-cell;
            width: 50%;
        }

        .summary .row .value {
            display: table-cell;
            width: 50%;
            text-align: right;
        }

        .summary .total {
            border-top: 1px solid #000;
            padding-top: 4px;
            margin-top: 4px;
        }

        .summary .total .row {
            font-weight: bold;
            font-size: 13px;
        }

        .footer {
            text-align: center;
            font-size: 10px;
            margin-top: 10px;
        }

        .footer p {
            margin: 3px 0;
        }

        .footer .thanks {
            font-weight: 600;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>STRUK PEMBAYARAN</h1>
            <div class="store-name">{{ $settings->app_name ?? config('app.name') }}</div>
            @if($settings->company_phone)
                <div class="store-info">Telp: {{ $settings->company_phone }}</div>
            @endif
            @if($settings->company_address)
                <div class="store-info">{{ $settings->company_address }}</div>
            @endif
        </div>

        <div class="divider"></div>

        <!-- Transaction Info -->
        <div class="info-section">
            <div class="info-row">
                <span class="label">No. Tr.:</span>
                <span class="value">{{ $sale->invoice_number }}</span>
            </div>

            @if($sale->split_number)
                <div class="center bold" style="margin: 4px 0;">
                    ** SPLIT BILL #{{ $sale->split_number }} **
                </div>
            @endif

            <div class="info-row">
                <span class="label">Tanggal:</span>
                <span class="value">{{ $sale->created_at->format('d/m/Y H:i') }}</span>
            </div>
            <div class="info-row">
                <span class="label">Kasir:</span>
                <span class="value">{{ $sale->user->name ?? 'System' }}</span>
            </div>
            <div class="info-row">
                <span class="label">Customer:</span>
                <span class="value">{{ $sale->customer_name ?? 'Umum' }}</span>
            </div>
            <div class="info-row">
                <span class="label">Tipe Order:</span>
                <span class="value">{{ $sale->order_type }}</span>
            </div>
        </div>

        <div class="divider"></div>

        <!-- Items -->
        <div class="items">
            <div class="title">ITEM YANG DIBELI:</div>
            @foreach($sale->items as $item)
                <div class="item">
                    <div class="item-row">
                        <div class="left">
                            <div class="name">{{ $item->product->name ?? 'Produk' }}</div>
                            @if(!empty($item->notes))
                                <div class="notes">📝 {{ $item->notes }}</div>
                            @endif
                            <div class="details">
                                {{ $item->quantity }} × Rp{{ number_format($item->unit_price, 0, ',', '.') }}
                            </div>
                        </div>
                        <div class="right">
                            Rp{{ number_format($item->subtotal, 0, ',', '.') }}
                        </div>
                    </div>
                </div>
                @if(!$loop->last)
                    <div style="border-top: 1px dashed #ddd; margin: 4px 0;"></div>
                @endif
            @endforeach
        </div>

        <div class="divider"></div>

        <!-- Summary -->
        <div class="summary">
            <div class="row">
                <span class="label">Subtotal:</span>
                <span class="value">Rp{{ number_format($sale->subtotal, 0, ',', '.') }}</span>
            </div>
            <div class="row">
                <span class="label">Pajak (10%):</span>
                <span class="value">Rp{{ number_format($sale->tax, 0, ',', '.') }}</span>
            </div>
            @if($sale->discount > 0)
                <div class="row" style="color: #059669;">
                    <span class="label">Potongan:</span>
                    <span class="value">- Rp{{ number_format($sale->discount, 0, ',', '.') }}</span>
                </div>
            @endif
            <div class="total">
                <div class="row">
                    <span class="label">TOTAL:</span>
                    <span class="value">Rp{{ number_format($sale->final_total, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <div class="divider"></div>

        <!-- Payment Info -->
        <div class="payment">
            <div class="bold" style="margin-bottom: 4px;">PEMBAYARAN:</div>
            <div class="info-row">
                <span class="label">Metode:</span>
                <span class="value">{{ $sale->paymentMethod->name ?? 'Cash' }}</span>
            </div>
            <div class="info-row">
                <span class="label">Bayar:</span>
                <span class="value">Rp{{ number_format($sale->amount_paid, 0, ',', '.') }}</span>
            </div>
            @if(($sale->paymentMethod->code ?? 'cash') === 'cash')
                @php $change = $sale->amount_paid - $sale->final_total; @endphp
                @if($change > 0)
                    <div class="info-row">
                        <span class="label">Kembali:</span>
                        <span class="value">Rp{{ number_format($change, 0, ',', '.') }}</span>
                    </div>
                @endif
            @endif
        </div>

        <div class="divider"></div>

        <!-- Footer -->
        <div class="footer">
            <p>Terima kasih atas kunjungan Anda</p>
            <p class="thanks">*** SELAMAT MENIKMATI ***</p>
        </div>
    </div>
</body>

</html>
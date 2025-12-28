<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Struk {{ $sale->invoice_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 11px;
            line-height: 1.3;
            color: #000;
            background: #f5f5f5;
            padding: 20px;
        }

        .receipt-container {
            width: 72mm;
            margin: 0 auto;
            background: white;
            padding: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
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
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
        }

        .info-row .value {
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
            font-size: 10px;
            color: #666;
        }

        .item-row {
            display: flex;
            justify-content: space-between;
        }

        .item-row .left {
            flex: 1;
        }

        .item-row .right {
            font-weight: 600;
            margin-left: 10px;
        }

        .summary .row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
        }

        .summary .total {
            border-top: 1px solid #000;
            padding-top: 4px;
            margin-top: 4px;
        }

        .summary .total .row {
            font-weight: bold;
            font-size: 12px;
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

        /* Print styles */
        @media print {
            body {
                background: white;
                padding: 0;
                margin: 0;
            }

            .receipt-container {
                width: 72mm;
                max-width: 72mm;
                box-shadow: none;
                padding: 5px;
                margin: 0;
            }

            @page {
                size: 72mm auto;
                margin: 0;
            }

            html,
            body {
                width: 72mm;
                max-width: 72mm;
            }
        }

        /* Print button */
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .print-button:hover {
            background: #2563eb;
        }

        @media print {
            .print-button {
                display: none;
            }
        }
    </style>
</head>

<body>
    <button class="print-button" onclick="window.print()">🖨️ Print</button>

    <div class="receipt-container">
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
                <span>No. Tr.:</span>
                <span class="value">{{ $sale->invoice_number }}</span>
            </div>

            @if($sale->split_number)
                <div class="center bold" style="margin: 4px 0;">
                    ** SPLIT BILL #{{ $sale->split_number }} **
                </div>
            @endif

            <div class="info-row">
                <span>Tanggal:</span>
                <span class="value">{{ $sale->created_at->format('d/m/Y H:i') }}</span>
            </div>
            <div class="info-row">
                <span>Kasir:</span>
                <span class="value">{{ $sale->user->name ?? 'System' }}</span>
            </div>
            <div class="info-row">
                <span>Customer:</span>
                <span class="value">{{ $sale->customer_name ?? 'Umum' }}</span>
            </div>
            <div class="info-row">
                <span>Tipe Order:</span>
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
                <span>Subtotal:</span>
                <span>Rp{{ number_format($sale->subtotal, 0, ',', '.') }}</span>
            </div>
            <div class="row">
                <span>Pajak (10%):</span>
                <span>Rp{{ number_format($sale->tax, 0, ',', '.') }}</span>
            </div>
            @if($sale->discount > 0)
                <div class="row" style="color: #059669;">
                    <span>Potongan:</span>
                    <span>- Rp{{ number_format($sale->discount, 0, ',', '.') }}</span>
                </div>
            @endif
            <div class="total">
                <div class="row">
                    <span>TOTAL:</span>
                    <span>Rp{{ number_format($sale->final_total, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <div class="divider"></div>

        <!-- Payment Info -->
        <div class="payment">
            <div class="bold" style="margin-bottom: 4px;">PEMBAYARAN:</div>
            <div class="info-row">
                <span>Metode:</span>
                <span class="value">{{ $sale->paymentMethod->name ?? 'Cash' }}</span>
            </div>
            <div class="info-row">
                <span>Bayar:</span>
                <span class="value">Rp{{ number_format($sale->amount_paid, 0, ',', '.') }}</span>
            </div>
            @if(($sale->paymentMethod->code ?? 'cash') === 'cash')
                @php $change = $sale->amount_paid - $sale->final_total; @endphp
                @if($change > 0)
                    <div class="info-row">
                        <span>Kembali:</span>
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
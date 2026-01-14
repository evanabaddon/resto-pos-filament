<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Stock Opname Form</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }

        .header h1 {
            font-size: 18pt;
            margin-bottom: 5px;
        }

        .header p {
            font-size: 10pt;
            color: #666;
        }

        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }

        .info-row {
            display: table-row;
        }

        .info-label {
            display: table-cell;
            width: 120px;
            font-weight: bold;
            padding: 3px 0;
        }

        .info-value {
            display: table-cell;
            border-bottom: 1px solid #000;
            padding: 3px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th {
            background: #333;
            color: white;
            padding: 8px;
            text-align: left;
            font-size: 9pt;
        }

        td {
            border: 1px solid #ddd;
            padding: 6px;
            font-size: 9pt;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .signature-section {
            margin-top: 40px;
            display: table;
            width: 100%;
        }

        .signature-box {
            display: table-cell;
            width: 50%;
            text-align: center;
            padding: 10px;
        }

        .signature-line {
            border-top: 1px solid #000;
            margin-top: 60px;
            padding-top: 5px;
        }

        .footer {
            text-align: center;
            font-size: 8pt;
            color: #999;
            margin-top: 20px;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>FORM STOCK OPNAME</h1>
        <p>{{ config('app.name') }}</p>
    </div>

    <div class="info-grid">
        <div class="info-row">
            <div class="info-label">Tanggal:</div>
            <div class="info-value">{{ \Carbon\Carbon::parse($date)->format('d F Y') }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Shift:</div>
            <div class="info-value">{{ $shift }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Filter:</div>
            <div class="info-value">{{ $filter_info }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Penanggung Jawab:</div>
            <div class="info-value">_________________________________</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 8%;">No</th>
                <th style="width: 45%;">Nama Produk</th>
                <th style="width: 12%;">Satuan</th>
                <th style="width: 15%;">Stock Sistem</th>
                <th style="width: 20%;">Stock Fisik</th>
            </tr>
        </thead>
        <tbody>
            @foreach($products as $index => $product)
                @php
                    $isPrepared = in_array($product->type, ['produced', 'bar']);
                    $systemStock = $isPrepared ? ($product->prepared_stock ?? 0) : $product->stock;
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $product->name }}</td>
                    <td class="text-center">{{ $product->unit?->symbol ?? '-' }}</td>
                    <td class="text-right">{{ number_format($systemStock, 2) }}</td>
                    <td style="background: #f9f9f9;"></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="signature-section">
        <div class="signature-box">
            <strong>Diperiksa Oleh:</strong>
            <div class="signature-line">
                ( ___________________ )
            </div>
        </div>
        <div class="signature-box">
            <strong>Disetujui Oleh:</strong>
            <div class="signature-line">
                ( ___________________ )
            </div>
        </div>
    </div>

    <div class="footer">
        Dicetak pada: {{ $printed_at->format('d/m/Y H:i') }} | Total Item: {{ $products->count() }}
    </div>
</body>

</html>
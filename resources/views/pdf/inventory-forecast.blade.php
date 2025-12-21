<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Smart Inventory Forecast Report</title>
    <style>
        @page {
            margin: 1.5cm;
        }

        body {
            font-family: sans-serif;
            font-size: 9pt;
            color: #333;
            line-height: 1.5;
        }

        .header {
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }

        .header h1 {
            color: #4f46e5;
            margin: 0;
            font-size: 18pt;
            letter-spacing: -0.5px;
        }

        .header p {
            margin: 5px 0 0 0;
            color: #6b7280;
            font-size: 9pt;
        }

        h3 {
            color: #111827;
            font-size: 12pt;
            margin-top: 30px;
            margin-bottom: 10px;
            border-left: 4px solid #4f46e5;
            padding-left: 10px;
        }

        .ai-section {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .ai-section h2 {
            margin-top: 0;
            font-size: 13pt;
            color: #1e40af;
            margin-bottom: 10px;
        }

        .ai-analysis {
            font-style: italic;
            color: #475569;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px dashed #cbd5e1;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            table-layout: fixed;
        }

        thead {
            display: table-header-group;
        }

        th,
        td {
            border: 1px solid #e2e8f0;
            padding: 8px 10px;
            text-align: left;
            word-wrap: break-word;
        }

        th {
            background-color: #f1f5f9;
            font-weight: bold;
            color: #475569;
            font-size: 8pt;
            text-transform: uppercase;
        }

        tr {
            page-break-inside: avoid;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .urgency-high {
            color: #dc2626;
            font-weight: bold;
        }

        .urgency-medium {
            color: #d97706;
            font-weight: bold;
        }

        .urgency-low {
            color: #2563eb;
            font-weight: bold;
        }

        .reason-text {
            font-size: 8pt;
            color: #64748b;
            font-style: italic;
            display: block;
            margin-top: 4px;
        }

        .restock-amount {
            font-size: 10pt;
            color: #111827;
            font-weight: 800;
        }

        .footer {
            position: fixed;
            bottom: -1cm;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8pt;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
        }

        .page-number:after {
            content: counter(page);
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>SMART INVENTORY FORECAST</h1>
        <p>
            Dicetak Oleh: <strong>{{ auth()->user()->name }}</strong> |
            Analisis Terakhir: <strong>{{ $timestamp ?? now()->format('d M Y, H:i') }}</strong>
        </p>
    </div>

    @if($aiResults)
    <div class="ai-section">
        <h2>AI Analysis & Insights</h2>
        <div class="ai-analysis">
            "{{ $aiResults['analysis'] ?? 'Tidak ada analisis tersedia.' }}"
        </div>

        <table style="border: none;">
            <thead>
                <tr>
                    <th style="width: 25%;">Produk / Bahan</th>
                    <th style="width: 15%; text-align: center;">Urgency</th>
                    <th style="width: 20%; text-align: right;">Saran Restock</th>
                    <th style="width: 40%;">Alasan & Prediksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($aiResults['recommendations'] as $rec)
                @if(($rec['suggested_restock'] ?? 0) > 0)
                <tr>
                    <td style="font-weight: 600;">{{ $rec['product_name'] }}</td>
                    <td class="text-center">
                        <span class="urgency-{{ $rec['urgency'] ?? 'low' }}">
                            {{ strtoupper($rec['urgency'] ?? 'MEDIUM') }}
                        </span>
                    </td>
                    <td class="text-right">
                        <span class="restock-amount">+{{ $rec['suggested_restock'] }}</span>
                    </td>
                    <td>
                        <span style="font-size: 8pt;">Prediksi Kebutuhan: {{ $rec['predicted_need'] }}</span>
                        <span class="reason-text">"{{ $rec['reason'] }}"</span>
                    </td>
                </tr>
                @endif
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <h3>Riwayat Konsumsi Bahan & Retail (7 Hari Terakhir)</h3>
    <table>
        <thead>
            <tr>
                <th style="width: 40%;">Produk / Bahan</th>
                <th style="width: 15%; text-align: center;">Stok</th>
                <th style="width: 15%; text-align: right;">Total Pakai</th>
                <th style="width: 15%; text-align: right;">Rata-rata/Hari</th>
                <th style="width: 15%;">Satuan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($historyData as $data)
            <tr>
                <td>{{ $data['name'] }}</td>
                <td class="text-center">{{ $data['current_stock'] }}</td>
                <td class="text-right">{{ number_format($data['total_consumed'], 2) }}</td>
                <td class="text-right">{{ $data['average_daily'] }}</td>
                <td>{{ $data['unit'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Dicetak otomatis oleh Sistem AI Intelligence - {{ app(\App\Settings\GeneralSettings::class)->app_name }}
        | Halaman <span class="page-number"></span>
    </div>
</body>

</html>
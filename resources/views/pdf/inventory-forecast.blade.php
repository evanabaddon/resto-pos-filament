<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Smart Inventory Forecast Report</title>
    <style>
        @page {
            margin: 100px 25px;
        }

        body {
            font-family: sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.4;
        }

        .header {
            position: fixed;
            top: -70px;
            left: 0px;
            right: 0px;
            text-align: center;
            border-bottom: 2px solid #4338ca;
            padding-bottom: 10px;
            height: 60px;
        }

        .header h1 {
            margin: 0;
            color: #111;
            font-size: 20px;
        }

        .header p {
            margin: 2px 0;
            color: #666;
            font-size: 10px;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            margin: 20px 0 10px 0;
            border-bottom: 1px solid #eee;
            padding-bottom: 3px;
            color: #1f2937;
        }

        .ai-box {
            background: #f5f3ff;
            border: 1px solid #ddd6fe;
            padding: 15px;
            border-radius: 10px;
            margin-top: 25px;
            margin-bottom: 25px;
            page-break-inside: avoid;
        }

        .ai-box h3 {
            margin: 0 0 8px 0;
            color: #5b21b6;
            font-size: 13px;
            font-weight: bold;
        }

        .ai-box p {
            font-style: italic;
            color: #4b5563;
            margin: 0;
            font-size: 11px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        thead {
            display: table-header-group;
        }

        th {
            background: #f9fafb;
            text-align: left;
            padding: 12px 8px;
            font-size: 9px;
            text-transform: uppercase;
            border-bottom: 1px solid #e5e7eb;
            color: #4b5563;
            font-weight: bold;
        }

        tr {
            page-break-inside: avoid;
        }

        td {
            padding: 10px 8px;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: middle;
        }

        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 8px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .urgency-high {
            background: #fee2e2;
            color: #991b1b;
        }

        .urgency-medium {
            background: #fef3c7;
            color: #92400e;
        }

        .urgency-low {
            background: #e0e7ff;
            color: #3730a3;
        }

        .footer {
            position: fixed;
            bottom: -60px;
            left: 0px;
            right: 0px;
            height: 40px;
            text-align: center;
            font-size: 9px;
            color: #999;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }

        .page-number:after {
            content: counter(page);
        }

        .font-bold {
            font-weight: bold;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
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
    <div class="ai-box">
        <h3>AI Analysis & Strategic Insights</h3>
        <p>"{{ $aiResults['analysis'] ?? 'Tidak ada analisis tersedia.' }}"</p>

        <table style="margin-top: 15px; border: none;">
            <thead>
                <tr>
                    <th style="width: 30%;">Produk / Bahan</th>
                    <th style="width: 15%; text-align: center;">Urgency</th>
                    <th style="width: 20%; text-align: right;">Saran Restock</th>
                    <th style="width: 35%;">Alasan & Prediksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($aiResults['recommendations'] as $rec)
                @if(($rec['suggested_restock'] ?? 0) > 0)
                <tr>
                    <td class="font-bold">{{ $rec['product_name'] }}</td>
                    <td class="text-center">
                        <span class="badge urgency-{{ $rec['urgency'] ?? 'low' }}">
                            {{ strtoupper($rec['urgency'] ?? 'MEDIUM') }}
                        </span>
                    </td>
                    <td class="text-right">
                        <span class="font-bold" style="color: #4338ca;">+{{ $rec['suggested_restock'] }}</span>
                    </td>
                    <td>
                        <span style="font-size: 8px; color: #6b7280; display: block;">Prediksi Kebutuhan: {{ $rec['predicted_need'] }}</span>
                        <span style="font-size: 9px; font-style: italic; color: #4b5563;">"{{ $rec['reason'] }}"</span>
                    </td>
                </tr>
                @endif
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="section-title">Riwayat Konsumsi Bahan & Retail (7 Hari Terakhir)</div>
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
                <td class="font-bold">{{ $data['name'] }}</td>
                <td class="text-center">{{ $data['current_stock'] }}</td>
                <td class="text-right font-bold">{{ number_format($data['total_consumed'], 2) }}</td>
                <td class="text-right">{{ $data['average_daily'] }}</td>
                <td>{{ $data['unit'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Dicetak otomatis oleh Sistem AI Intelligence {{ app(\App\Settings\GeneralSettings::class)->ai_assistant_name }} - {{ app(\App\Settings\GeneralSettings::class)->app_name }}
        | Halaman <span class="page-number"></span>
    </div>
</body>

</html>
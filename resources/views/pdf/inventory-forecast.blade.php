<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Smart Inventory Forecast Report</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 10pt;
            color: #333;
            line-height: 1.4;
        }

        .header {
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .header h1 {
            color: #4f46e5;
            margin: 0;
            font-size: 18pt;
        }

        .header p {
            margin: 5px 0;
            color: #666;
        }

        .ai-section {
            background-color: #f5f3ff;
            border: 1px solid #ddd6fe;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .ai-section h2 {
            margin-top: 0;
            font-size: 14pt;
            color: #4338ca;
        }

        .ai-analysis {
            font-style: italic;
            color: #4b5563;
            margin-bottom: 15px;
        }

        .recommendation-grid {
            margin-bottom: 20px;
        }

        .rec-card {
            border: 1px solid #e5e7eb;
            border-left: 4px solid #ccc;
            padding: 10px;
            margin-bottom: 10px;
        }

        .rec-card.high {
            border-left-color: #ef4444;
            background-color: #fef2f2;
        }

        .rec-card.medium {
            border-left-color: #f59e0b;
            background-color: #fffbeb;
        }

        .rec-card.low {
            border-left-color: #3b82f6;
            background-color: #eff6ff;
        }

        .rec-title {
            font-weight: bold;
            font-size: 11pt;
            margin-bottom: 5px;
        }

        .rec-detail {
            font-size: 9pt;
            color: #6b7280;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #e5e7eb;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f9fafb;
            font-weight: bold;
            font-size: 9pt;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
        }

        .badge-danger {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .badge-warning {
            background-color: #fef3c7;
            color: #92400e;
        }

        .badge-info {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 8pt;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>SMART INVENTORY FORECAST</h1>
        <p>Dicetak Oleh: {{ auth()->user()->name }} | Tanggal Analisis: {{ $timestamp ?? now()->format('d M Y, H:i') }}</p>
    </div>

    @if($aiResults)
    <div class="ai-section">
        <h2>AI Analysis & Insights</h2>
        <div class="ai-analysis">
            "{{ $aiResults['analysis'] ?? 'Tidak ada analisis tersedia.' }}"
        </div>

        <div class="recommendation-grid">
            @foreach($aiResults['recommendations'] as $rec)
            @if(($rec['suggested_restock'] ?? 0) > 0)
            <div class="rec-card {{ $rec['urgency'] ?? 'low' }}">
                <div class="rec-title">
                    {{ $rec['product_name'] }}
                    <span class="badge badge-{{ match($rec['urgency'] ?? '') { 'high' => 'danger', 'medium' => 'warning', 'low' => 'info', default => 'info' } }}">
                        {{ $rec['urgency'] ?? 'NORMAL' }}
                    </span>
                </div>
                <div class="rec-detail">
                    Kebutuhan: {{ $rec['predicted_need'] }} |
                    <strong>Saran Restock: +{{ $rec['suggested_restock'] }}</strong>
                </div>
                <div class="rec-detail" style="margin-top: 3px; font-size: 8pt; font-style: italic;">
                    "{{ $rec['reason'] }}"
                </div>
            </div>
            @endif
            @endforeach
        </div>
    </div>
    @endif

    <h3>Riwayat Konsumsi Bahan & Retail (7 Hari)</h3>
    <table>
        <thead>
            <tr>
                <th>Produk / Bahan</th>
                <th class="text-center">Stok Saat Ini</th>
                <th class="text-right">Total Terpakai</th>
                <th class="text-right">Rata-rata/Hari</th>
                <th>Satuan</th>
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
        © {{ date('Y') }} {{ app(\App\Settings\GeneralSettings::class)->app_name }} - Intelligent Restaurant Ecosystem
    </div>
</body>

</html>
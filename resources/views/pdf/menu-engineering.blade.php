<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Menu Engineering Report</title>
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

        .summary-grid {
            width: 100%;
            margin-bottom: 20px;
            table-layout: fixed;
        }

        .summary-card {
            border: 1px solid #e5e7eb;
            padding: 10px;
            border-radius: 8px;
            text-align: center;
        }

        .summary-card h4 {
            margin: 0 0 5px 0;
            font-size: 9px;
            text-transform: uppercase;
            color: #6b7280;
            font-weight: 900;
            letter-spacing: 0.05em;
        }

        .summary-card .value {
            font-size: 18px;
            font-weight: bold;
            color: #111;
        }

        .summary-card .desc {
            font-size: 8px;
            color: #9ca3af;
            margin-top: 2px;
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

        .priority-badge {
            display: inline-block;
            background: #ede9fe;
            color: #6d28d9;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: bold;
            margin-right: 5px;
            margin-top: 5px;
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

        .badge-star {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-plowhorse {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-puzzle {
            background: #e0e7ff;
            color: #3730a3;
        }

        .badge-dog {
            background: #fee2e2;
            color: #991b1b;
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

        .text-emerald {
            color: #059669;
        }

        .font-bold {
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Menu Engineering Analysis</h1>
        <p>Laporan Profitabilitas & Popularitas Produk (30 Hari Terakhir)</p>
        <p>Analisa per: <strong>{{ $lastGeneratedAt }}</strong></p>
    </div>

    @if($aiAdvice)
    <div class="ai-box">
        <h3>AI Strategic Insights</h3>
        <p>"{{ $aiAdvice['overall_analysis'] ?? '' }}"</p>

        @if(isset($aiAdvice['top_priorities']))
        <div style="margin-top: 10px;">
            @foreach($aiAdvice['top_priorities'] as $priority)
            <span class="priority-badge">- {{ $priority }}</span>
            @endforeach
        </div>
        @endif
    </div>
    @endif

    <div class="section-title">Ringkasan Matriks</div>
    <table style="width: 100%; border: none;">
        <tr>
            @php
            $grouped = collect($matrixData['items'])->groupBy('category');
            @endphp
            <td style="width: 25%; border: none; padding: 0 5px 0 0;">
                <div class="summary-card" style="border-left: 4px solid #10b981;">
                    <h4>Unit Unggulan</h4>
                    <div class="value">{{ $grouped->get('UNIT UNGGULAN')?->count() ?: 0 }}</div>
                    <div class="desc">Kinerja & Potensi Tinggi</div>
                </div>
            </td>
            <td style="width: 25%; border: none; padding: 0 5px;">
                <div class="summary-card" style="border-left: 4px solid #f59e0b;">
                    <h4>Unit Andalan</h4>
                    <div class="value">{{ $grouped->get('UNIT ANDALAN')?->count() ?: 0 }}</div>
                    <div class="desc">Kinerja Tinggi, Potensi Rendah</div>
                </div>
            </td>
            <td style="width: 25%; border: none; padding: 0 5px;">
                <div class="summary-card" style="border-left: 4px solid #6366f1;">
                    <h4>Unit Potensial</h4>
                    <div class="value">{{ $grouped->get('UNIT POTENSIAL')?->count() ?: 0 }}</div>
                    <div class="desc">Potensi Tinggi, Kinerja Rendah</div>
                </div>
            </td>
            <td style="width: 25%; border: none; padding: 0 0 0 5px;">
                <div class="summary-card" style="border-left: 4px solid #ef4444;">
                    <h4>Kurang Berkembang</h4>
                    <div class="value">{{ $grouped->get('UNIT KURANG BERKEMBANG')?->count() ?: 0 }}</div>
                    <div class="desc">Kinerja & Potensi Rendah</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="section-title">Detail Performa Produk</div>
    <table>
        <thead>
            <tr>
                <th style="width: 30%;">Produk</th>
                <th style="width: 15%;">Klasifikasi</th>
                <th style="width: 15%;">Popularitas (Qty)</th>
                <th style="width: 20%;">Margin (Keuntungan)</th>
                <th style="width: 20%;">HPP & Harga Jual</th>
            </tr>
        </thead>
        <tbody>
            @foreach(collect($matrixData['items'])->sortByDesc('popularity') as $item)
            <tr>
                <td>
                    <span class="font-bold">{{ $item['name'] }}</span><br>
                    <span style="color:#9ca3af; font-size: 8px; text-transform: uppercase;">{{ $item['type'] }}</span>
                </td>
                <td>
                    <span class="badge 
                        {{ match($item['category']) {
                            'UNIT UNGGULAN' => 'badge-star',
                            'UNIT ANDALAN' => 'badge-plowhorse',
                            'UNIT POTENSIAL' => 'badge-puzzle',
                            default => 'badge-dog'
                        } }}">
                        {{ $item['category'] }}
                    </span>
                </td>
                <td style="text-align: center;"><strong>{{ number_format($item['popularity'], 0) }}</strong></td>
                <td>
                    <span class="text-emerald font-bold">Rp {{ number_format($item['margin'], 0, ',', '.') }}</span>
                </td>
                <td>
                    <span style="color: #6b7280; font-size: 8px;">HPP: Rp{{ number_format($item['cogs'], 0, ',', '.') }}</span><br>
                    <strong>Rp{{ number_format($item['sell_price'], 0, ',', '.') }}</strong>
                </td>
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
<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Laporan Laba Rugi (Profit & Loss)</title>
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
            text-transform: uppercase;
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
            padding: 8px 8px;
            /* Adjusted padding */
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
            padding: 8px 8px;
            /* Adjusted padding */
            border-bottom: 1px solid #f3f4f6;
            vertical-align: middle;
            font-size: 11px;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .font-bold {
            font-weight: bold;
        }

        /* Color helpers matching web/theme */
        .text-green {
            color: #166534;
        }

        .text-red {
            color: #991b1b;
        }

        .text-orange {
            color: #9a3412;
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
    </style>
</head>

<body>
    <div class="header">
        <h1>LAPORAN LABA RUGI (PROFIT & LOSS)</h1>
        <p>
            Periode: <strong>{{ \Carbon\Carbon::parse($startDate)->format('d F Y') }} -
                {{ \Carbon\Carbon::parse($endDate)->format('d F Y') }}</strong> |
            Dicetak Oleh: <strong>{{ auth()->user()->name ?? 'System' }}</strong>
        </p>
    </div>

    <!-- A. RINGKASAN KINERJA -->
    <div class="section-title">A. RINGKASAN KINERJA</div>
    <table>
        <thead>
            <tr>
                <th style="width: 60%;">Keterangan</th>
                <th style="width: 40%; text-align: right;">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Total Pendapatan (Net Revenue)</strong></td>
                <td class="text-right font-bold text-green" style="font-size: 12px;">Rp
                    {{ number_format($totalRevenue, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>(Kurang) Harga Pokok Penjualan (HPP)</td>
                <td class="text-right text-orange">Rp {{ number_format($totalHpp, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td style="background-color: #f9fafb;"><strong>Laba Kotor (Gross Profit)</strong></td>
                <td class="text-right font-bold" style="background-color: #f9fafb;">Rp
                    {{ number_format($grossProfit, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>(Kurang) Biaya Operasional & Gaji</td>
                <td class="text-right text-red">Rp {{ number_format($totalExpenses, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td style="background-color: #ecfccb; border-bottom: 2px solid #84cc16;"><strong>LABA BERSIH (NET
                        PROFIT)</strong></td>
                <td class="text-right font-bold text-green"
                    style="font-size: 14px; background-color: #ecfccb; border-bottom: 2px solid #84cc16;">Rp
                    {{ number_format($netProfit, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <!-- B. RINCIAN BIAYA MODAL (HPP) -->
    <div style="page-break-inside: avoid;">
        <div class="section-title">B. RINCIAN BIAYA MODAL (HPP)</div>
        <table>
            <thead>
                <tr>
                    <th style="width: 40%;">Nama Produk</th>
                    <th style="width: 20%; text-align: center;">Qty Terjual</th>
                    <th style="width: 20%; text-align: right;">Unit HPP</th>
                    <th style="width: 20%; text-align: right;">Total HPP</th>
                </tr>
            </thead>
            <tbody>
                @forelse($breakdownCogs as $item)
                    <tr>
                        <td class="font-bold">{{ $item['name'] }}</td>
                        <td class="text-center">{{ $item['qty'] }}</td>
                        <td class="text-right">{{ number_format($item['unit_hpp'], 0, ',', '.') }}</td>
                        <td class="text-right text-orange">{{ number_format($item['total_hpp'], 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center" style="color: #999;">Belum ada data penjualan.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-right font-bold" style="background: #fff7ed;">TOTAL HPP</td>
                    <td class="text-right font-bold text-orange" style="background: #fff7ed;">
                        {{ number_format($totalHpp, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- C. RINCIAN BIAYA OPERASIONAL -->
    <div style="page-break-inside: avoid;">
        <div class="section-title">C. RINCIAN BIAYA OPERASIONAL</div>
        <table>
            <thead>
                <tr>
                    <th style="width: 15%; text-align: center;">Tanggal</th>
                    <th style="width: 25%;">Kategori</th>
                    <th style="width: 40%;">Keterangan</th>
                    <th style="width: 20%; text-align: right;">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @forelse($breakdownExpenses as $expense)
                    <tr>
                        <td class="text-center">{{ \Carbon\Carbon::parse($expense['date'])->format('d/m/Y') }}</td>
                        <td><span
                                style="background: #f3f4f6; padding: 2px 6px; border-radius: 4px; font-size: 8px; font-weight: bold;">{{ strtoupper($expense['category'] ?? '-') }}</span>
                        </td>
                        <td>{{ $expense['description'] }}</td>
                        <td class="text-right text-red font-bold">{{ number_format($expense['amount'], 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center" style="color: #999;">Tidak ada pengeluaran operasional.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-right font-bold" style="background: #fef2f2;">TOTAL BIAYA</td>
                    <td class="text-right font-bold text-red" style="background: #fef2f2;">
                        {{ number_format($totalExpenses, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Footer -->
    <div class="footer">
        Dicetak otomatis oleh Sistem {{ app(\App\Settings\GeneralSettings::class)->app_name }}
        | Halaman <span class="page-number"></span>
    </div>
</body>

</html>
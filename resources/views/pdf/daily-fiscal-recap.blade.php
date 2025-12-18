<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Rekap Laporan Pajak Harian</title>
    <style>
        body { font-family: sans-serif; font-size: 11pt; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 0; }
        .header p { margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 6px; text-align: left; }
        th { background-color: #f0f0f0; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .footer { margin-top: 30px; text-align: right; font-size: 10pt; }
    </style>
</head>
<body>
    <div class="header">
        <h2>REKAP LAPORAN PAJAK HARIAN</h2>
        <p>Periode: {{ $startDate }} - {{ $endDate }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th class="text-center">Tanggal</th>
                <th class="text-right">Total Omzet</th>
                <th class="text-right">Pajak (10%)</th>
            </tr>
        </thead>
        <tbody>
            @php $grandTotal = 0; $grandTax = 0; @endphp
            @foreach($data as $row)
                @php 
                    // Calculate strictly based on Final Total (Inclusive Tax)
                    // Assumption: Final Total includes 10% Tax (Tax base is 10/110 of Final)
                    $final = $row->total_sales;
                    $tax = $final - ($final / 1.1);

                    $grandTotal += $final;
                    $grandTax += $tax;
                @endphp
                <tr>
                    <td class="text-center">{{ \Carbon\Carbon::parse($row->date)->format('d/m/Y') }}</td>
                    <td class="text-right">{{ number_format($final, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($tax, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th class="text-right">Total Periode Ini</th>
                <th class="text-right">{{ number_format($grandTotal, 0, ',', '.') }}</th>
                <th class="text-right">{{ number_format($grandTax, 0, ',', '.') }}</th>
            </tr>
        </tfoot>
    </table>

    <table style="width: 100%; margin-top: 50px; border: none;">
        <tr>
            <td style="border: none;"></td>
            <td style="border: none; width: 40%; text-align: center;">
                <p style="margin-bottom: 5px;">Diketahui Oleh,</p>
                <p>Admin / Staff Keuangan</p>
                <br><br><br><br>
                <p style="border-bottom: 1px solid black; width: 80%; margin: 0 auto;"></p>
            </td>
        </tr>
    </table>

    <div class="footer">
        <p>Dicetak pada: {{ now()->format('d/m/Y H:i') }}</p>
    </div>
</body>
</html>

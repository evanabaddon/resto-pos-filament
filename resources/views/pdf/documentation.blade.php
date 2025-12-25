<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Dokumentasi Sistem</title>
    <style>
        @page {
            margin: 20mm;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10pt;
            line-height: 1.6;
            color: #000;
        }

        /* Cover Page */
        .cover-page {
            text-align: center;
            padding: 100px 0;
            background-color: #4c51bf;
            color: white;
            margin: -20mm -20mm 20px -20mm;
            page-break-after: always;
        }

        .cover-page h1 {
            font-size: 28pt;
            margin: 20px 0;
            font-weight: bold;
        }

        .cover-page .subtitle {
            font-size: 14pt;
            margin: 10px 0;
        }

        .cover-page .date {
            font-size: 10pt;
            margin-top: 30px;
        }

        /* Headers */
        h2 {
            color: #1e3a8a;
            font-size: 14pt;
            margin: 20px 0 10px 0;
            padding: 8px 12px;
            background-color: #e0e7ff;
            border-left: 4px solid #3b82f6;
        }

        h3 {
            color: #1e40af;
            font-size: 12pt;
            margin: 15px 0 8px 0;
            font-weight: bold;
        }

        h4 {
            color: #374151;
            font-size: 11pt;
            margin: 10px 0 6px 0;
            font-weight: bold;
        }

        /* Alert Boxes */
        .alert {
            padding: 10px 12px;
            margin: 12px 0;
            border: 1px solid #ccc;
            border-left-width: 4px;
        }

        .alert-info {
            border-left-color: #3b82f6;
            background-color: #eff6ff;
        }

        .alert-warning {
            border-left-color: #f59e0b;
            background-color: #fffbeb;
        }

        .alert-success {
            border-left-color: #10b981;
            background-color: #f0fdf4;
        }

        .alert strong {
            display: block;
            margin-bottom: 4px;
            font-size: 10.5pt;
        }

        /* Example Boxes */
        .example-box {
            background-color: #f9fafb;
            border: 1px solid #d1d5db;
            padding: 10px 12px;
            margin: 10px 0;
        }

        .example-box strong {
            display: block;
            margin-bottom: 6px;
            color: #000;
        }

        /* Tables */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0;
        }

        table th {
            background-color: #3b82f6;
            color: white;
            font-weight: bold;
            padding: 8px 10px;
            border: 1px solid #2563eb;
            text-align: left;
        }

        table td {
            padding: 7px 10px;
            border: 1px solid #d1d5db;
        }

        table tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }

        /* Lists */
        ul,
        ol {
            margin: 8px 0;
            padding-left: 20px;
        }

        li {
            margin: 4px 0;
        }

        /* Code */
        code {
            background-color: #f3f4f6;
            padding: 2px 6px;
            font-family: 'Courier New', monospace;
            font-size: 9pt;
            color: #dc2626;
            border: 1px solid #e5e7eb;
        }

        /* Numbered Steps */
        ol.steps {
            counter-reset: step;
            list-style: none;
            padding-left: 0;
        }

        ol.steps li {
            counter-increment: step;
            margin: 10px 0;
            padding-left: 35px;
            position: relative;
        }

        ol.steps li:before {
            content: counter(step);
            position: absolute;
            left: 0;
            top: 0;
            background-color: #3b82f6;
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 12px;
            text-align: center;
            line-height: 24px;
            font-weight: bold;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>

<body>
    <!-- Cover Page -->
    <div class="cover-page">
        <h1>DOKUMENTASI SISTEM</h1>
        <div class="subtitle">Intelligent Restaurant Ecosystem</div>
        <div class="subtitle" style="margin-top: 15px;">Panduan Lengkap Penggunaan Sistem</div>
        <div class="date">Dicetak pada: {{ date('d F Y, H:i') }}</div>
    </div>

    <!-- Content -->
    <h2>FUNDAMENTAL SISTEM</h2>

    <div class="alert alert-info">
        <strong>WAJIB DIBACA</strong>
        Pahami fundamental sistem ini sebelum menggunakan fitur-fitur lanjutan untuk menghindari kesalahan interpretasi
        data keuangan.
    </div>

    <h3>Sistem Akuntansi: Accrual Method</h3>
    <p>Sistem ini menggunakan <strong>Accrual Accounting</strong> untuk perhitungan HPP (Harga Pokok Penjualan / COGS).
    </p>

    <h4>Prinsip Dasar:</h4>
    <ul>
        <li><strong>Pembelian Bahan Baku ≠ Expense</strong> - Pembelian dicatat sebagai Aset (Stock Value), bukan biaya.
        </li>
        <li><strong>COGS/HPP dicatat saat penjualan</strong> - Biaya bahan baku baru dikurangi dari profit saat menu
            terjual.</li>
        <li><strong>Matching Principle</strong> - Biaya di-match dengan revenue yang dihasilkan.</li>
    </ul>

    <h4>Contoh Ilustrasi:</h4>
    <div class="example-box">
        <strong>Hari 1: Beli Beras 10kg @ Rp 15,000/kg = Rp 150,000</strong>
        <ul>
            <li>Cash: -Rp 150,000</li>
            <li>Stock Value (Aset): +Rp 150,000</li>
            <li>COGS: Rp 0 (belum ada penjualan)</li>
            <li>Profit: Rp 0 (tidak berubah)</li>
        </ul>
    </div>

    <div class="example-box">
        <strong>Hari 2: Jual Nasi Goreng 5 porsi @ Rp 25,000</strong>
        <ul>
            <li>Revenue: +Rp 125,000</li>
            <li>COGS: -Rp 40,000 (5 × 200g × Rp 15/g + bumbu)</li>
            <li>Stock Value: -Rp 40,000 (beras berkurang 1kg)</li>
            <li>Gross Profit: Rp 85,000 (Revenue - COGS)</li>
            <li>Stock Value tersisa: Rp 110,000 (9kg beras)</li>
        </ul>
    </div>

    <h3>Sistem Inventory: Real-time Stock Tracking</h3>
    <p>Setiap transaksi yang melibatkan produk akan otomatis membuat <code>StockMovement</code> dan mengupdate stok
        secara real-time.</p>

    <table>
        <thead>
            <tr>
                <th>Transaksi</th>
                <th>Stock Movement</th>
                <th>Dampak</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Purchase (Pembelian)</td>
                <td>+Increase</td>
                <td>Stock bertambah, Stock Value naik</td>
            </tr>
            <tr>
                <td>POS Sale (Menu terjual)</td>
                <td>-Decrease</td>
                <td>Stock berkurang, COGS dicatat</td>
            </tr>
            <tr>
                <td>Stock Opname</td>
                <td>+/- Adjustment</td>
                <td>Koreksi variance fisik vs sistem</td>
            </tr>
            <tr>
                <td>Wastage (Rusak/Basi)</td>
                <td>-Decrease</td>
                <td>Stock berkurang, masuk Expense</td>
            </tr>
        </tbody>
    </table>

    <h3>Recipe System: Automatic Ingredient Deduction</h3>
    <p>Menu dengan recipe akan otomatis mengurangi stok bahan baku saat terjual.</p>
    <ul>
        <li><strong>Unit Conversion</strong> - Sistem otomatis convert unit (misal: recipe pakai gram, stock dalam kg).
        </li>
        <li><strong>Multi-Channel</strong> - Berlaku untuk POS, Waiter App, dan Self-Order.</li>
        <li><strong>HPP Calculation</strong> - HPP dihitung dari total harga bahan baku dalam recipe.</li>
    </ul>

    <div class="alert alert-warning">
        <strong>PENTING</strong>
        Pastikan setiap menu <code>produced</code> memiliki recipe yang lengkap agar HPP dan stock deduction akurat.
    </div>

    <div class="page-break"></div>

    <h2>GETTING STARTED</h2>
    <h3>Login & Dashboard</h3>
    <p>Akses sistem melalui browser dengan URL yang telah ditentukan. Gunakan kredensial yang diberikan oleh
        administrator.</p>

    <h3>Role & Permission</h3>
    <table>
        <thead>
            <tr>
                <th>Role</th>
                <th>Akses</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Super Admin</td>
                <td>Full access ke semua fitur</td>
            </tr>
            <tr>
                <td>Admin</td>
                <td>Manajemen operasional & laporan</td>
            </tr>
            <tr>
                <td>Cashier</td>
                <td>POS & CRM Member</td>
            </tr>
            <tr>
                <td>Waiter</td>
                <td>Input order & KDS status</td>
            </tr>
            <tr>
                <td>Kitchen</td>
                <td>KDS (Kitchen Display System)</td>
            </tr>
            <tr>
                <td>Inventory</td>
                <td>Stock management & purchases</td>
            </tr>
        </tbody>
    </table>

    <div class="page-break"></div>

    <h2>POS & KASIR</h2>
    <h3>Membuat Transaksi</h3>
    <ol class="steps">
        <li>Buka menu <strong>POS</strong></li>
        <li>Pilih produk dari daftar menu</li>
        <li>Atur quantity dan tambahkan notes jika perlu</li>
        <li>Klik <strong>Checkout</strong></li>
        <li>Pilih metode pembayaran</li>
        <li>Klik <strong>Bayar</strong></li>
    </ol>

    <h3>Fitur POS</h3>
    <ul>
        <li><strong>Member Integration</strong> - Scan/input nomor member untuk loyalty points</li>
        <li><strong>Split Bill</strong> - Pisahkan tagihan untuk beberapa customer</li>
        <li><strong>Merge Bill</strong> - Gabungkan beberapa transaksi</li>
        <li><strong>Void Transaction</strong> - Batalkan transaksi (restore stock)</li>
        <li><strong>Auto Print</strong> - Cetak struk otomatis ke printer</li>
    </ul>

    <div class="page-break"></div>

    <h2>INVENTORY MANAGEMENT</h2>
    <h3>Purchase (Pembelian)</h3>
    <ol class="steps">
        <li>Buka menu <strong>Purchases</strong></li>
        <li>Klik <strong>Create Purchase</strong></li>
        <li>Pilih supplier dan tanggal</li>
        <li>Tambahkan item yang dibeli</li>
        <li>Submit dan ubah status ke <strong>Received</strong></li>
    </ol>

    <h3>Stock Opname</h3>
    <ol class="steps">
        <li>Buka menu <strong>Stock Opname</strong></li>
        <li>Input physical count untuk setiap produk</li>
        <li>Sistem otomatis hitung variance</li>
        <li>Klik <strong>Submit Stock Opname</strong></li>
        <li>Stock akan disesuaikan dengan physical count</li>
    </ol>

    <div class="alert alert-info">
        <strong>TIPS</strong>
        Gunakan fitur search dan filter kategori untuk mempercepat input stock opname.
    </div>

    <div class="page-break"></div>

    <h2>LAPORAN & ANALISIS</h2>
    <h3>Financial Report (Laba/Rugi)</h3>
    <p>Laporan keuangan lengkap dengan perbandingan periode sebelumnya.</p>
    <ul>
        <li><strong>Revenue</strong> - Total penjualan bersih</li>
        <li><strong>COGS/HPP</strong> - Harga pokok penjualan (accrual)</li>
        <li><strong>Gross Profit</strong> - Revenue - COGS</li>
        <li><strong>Expenses</strong> - Biaya operasional + payroll + wastage</li>
        <li><strong>Net Profit</strong> - Gross Profit - Expenses</li>
        <li><strong>Stock Valuation</strong> - Nilai aset stok saat ini</li>
    </ul>

    <h3>AI Menu Engineering</h3>
    <p>Analisis profitabilitas menu menggunakan AI untuk optimasi menu.</p>
    <table>
        <thead>
            <tr>
                <th style="width: 30%;">Kategori</th>
                <th style="width: 35%;">Karakteristik</th>
                <th style="width: 35%;">Strategi</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Unit Unggulan</td>
                <td>High profit, high popularity</td>
                <td>Maintain & promote</td>
            </tr>
            <tr>
                <td>Unit Andalan</td>
                <td>High popularity, low profit</td>
                <td>Increase price/reduce cost</td>
            </tr>
            <tr>
                <td>Unit Potensial</td>
                <td>High profit, low popularity</td>
                <td>Increase marketing</td>
            </tr>
            <tr>
                <td>Unit Kurang Berkembang</td>
                <td>Low profit, low popularity</td>
                <td>Consider removal</td>
            </tr>
        </tbody>
    </table>

    <div class="page-break"></div>

    <h2>CRM & LOYALTY</h2>
    <h3>Member Management</h3>
    <ul>
        <li>Auto-register via WhatsApp di POS/Self-Order</li>
        <li>Loyalty points otomatis berdasarkan pembelian</li>
        <li>Tier system dengan benefit berbeda</li>
        <li>Track visit history & spending</li>
    </ul>

    <h3>Loyalty Automation</h3>
    <p>Re-engagement otomatis untuk member tidak aktif >30 hari.</p>
    <ul>
        <li>AI-generated personalized messages</li>
        <li>Scheduled weekly (Senin 9 AM)</li>
        <li>Anti-spam protection (7 hari cooldown)</li>
    </ul>

    <div class="page-break"></div>

    <h2>AI FEATURES</h2>
    <h3>AI Daily Suggestion</h3>
    <p>Widget dashboard yang memberikan saran bisnis harian berdasarkan data 30 hari terakhir.</p>

    <h3>AI WhatsApp Assistant</h3>
    <ul>
        <li>Auto-reply customer inquiries</li>
        <li>Reservation confirmation</li>
        <li>Order confirmation</li>
        <li>Personalized messaging</li>
    </ul>

    <h3>AI Inventory Forecasting</h3>
    <p>Prediksi kebutuhan stok untuk 7-30 hari ke depan dengan rekomendasi restocking.</p>

    <div class="page-break"></div>

    <h2>WAITER & SELF-ORDER</h2>
    <h3>Waiter App</h3>
    <p>Aplikasi untuk waiter input order dari meja customer.</p>
    <ul>
        <li><strong>Input Order:</strong> Pilih menu, quantity, notes</li>
        <li><strong>Table Assignment:</strong> Assign order ke nomor meja</li>
        <li><strong>Auto Print:</strong> Otomatis print ke kitchen/bar</li>
        <li><strong>Member Registration:</strong> Daftar member baru via WhatsApp</li>
    </ul>

    <h3>Self-Order (QR Code)</h3>
    <p>Customer scan QR di meja untuk order mandiri.</p>
    <ol class="steps">
        <li>Customer scan QR code di meja</li>
        <li>Pilih menu dari katalog</li>
        <li>Input nama & WhatsApp (optional)</li>
        <li>Submit order</li>
        <li>Konfirmasi via WhatsApp (jika ada nomor)</li>
    </ol>

    <div class="page-break"></div>

    <h2>WHATSAPP CENTER</h2>
    <h3>Auto-Reply System</h3>
    <p>AI-powered WhatsApp assistant untuk customer service otomatis.</p>
    <ul>
        <li><strong>Reservation:</strong> Booking meja via WhatsApp</li>
        <li><strong>Menu Inquiry:</strong> Tanya menu & harga</li>
        <li><strong>Order Status:</strong> Cek status pesanan</li>
        <li><strong>Personalized:</strong> AI menyesuaikan tone dengan customer</li>
    </ul>

    <h3>Broadcast & Campaign</h3>
    <ul>
        <li>Kirim promo ke member tertentu</li>
        <li>Birthday greetings otomatis</li>
        <li>Re-engagement untuk inactive members</li>
    </ul>

    <div class="page-break"></div>

    <h2>HRM & PAYROLL</h2>
    <h3>Employee Management</h3>
    <ul>
        <li><strong>User Roles:</strong> Super Admin, Admin, Cashier, Waiter, Kitchen, Inventory</li>
        <li><strong>Shift Management:</strong> Atur jadwal kerja</li>
        <li><strong>Performance Tracking:</strong> Monitor produktivitas</li>
    </ul>

    <h3>Payroll System</h3>
    <p>Perhitungan gaji otomatis dengan komponen:</p>
    <ul>
        <li><strong>Base Salary:</strong> Gaji pokok</li>
        <li><strong>Allowances:</strong> Tunjangan (transport, makan, dll)</li>
        <li><strong>Deductions:</strong> Potongan (kasbon, BPJS, dll)</li>
        <li><strong>Bonus:</strong> Bonus kinerja</li>
    </ul>

    <div class="page-break"></div>

    <h2>TROUBLESHOOTING</h2>
    <h3>Stock tidak berkurang saat penjualan</h3>
    <ul>
        <li>Pastikan produk memiliki recipe yang lengkap</li>
        <li>Cek ingredient_id menunjuk ke produk raw/retail</li>
        <li>Verifikasi unit conversion rate sudah benar</li>
    </ul>

    <h3>Profit tidak sesuai ekspektasi</h3>
    <ul>
        <li>Pahami sistem accrual accounting</li>
        <li>Pembelian bahan baku bukan expense</li>
        <li>COGS dicatat saat penjualan, bukan saat pembelian</li>
    </ul>

    <div class="alert alert-success">
        <strong>BUTUH BANTUAN?</strong>
        Hubungi tim support untuk assistance lebih lanjut.
    </div>

    <p style="text-align: center; margin-top: 30px; font-size: 9pt; color: #666;">
        © {{ date('Y') }} Intelligent Restaurant Ecosystem - Dokumentasi Sistem
    </p>
</body>

</html>
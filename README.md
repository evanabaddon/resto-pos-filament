# 🍽️ Resto POS System (Laravel 11 + Filament 4)

Sistem manajemen restoran terpadu (All-in-One) yang mencakup Point of Sales (POS), Manajemen Inventori & Resep, serta Human Resource Management (HRM). Aplikasi ini dirancang untuk operasional restoran modern dengan dukungan **Multi-Unit**, **Resep (COGS)**, **Manajemen Karyawan (Payroll/Absensi)**, dan **Reservasi Meja**.

---

## 🚀 Fitur Utama

### 🧾 Kasir & Penjualan (POS)
- **Tampilan Kasir Baru**: Antarmuka modern dengan dukungan mobile/tablet.
- **Cash Sessions**: Fitur Buka/Tutup Kasir (Shift) untuk melacak uang tunai di laci.
- **Draft Orders**: Simpan pesanan pending (belum bayar).
- **Split Payment**: Dukungan pembayaran sebagian atau gabungan metode (Tunai, Transfer, Kartu).
- **Diskon & Voucher**: Manajemen kode diskon dan promo.
- **Print Agent**: Integrasi langsung ke printer thermal via aplikasi **Electron Print Agent** (USB/Network/Bluetooth).

### �‍🍳 Kitchen Display System (KDS) [BARU]
Layar khusus dapur/bar untuk manajemen pesanan realtime:
- **Standalone Layout**: Tampilan fullscreen tanpa sidebar, fokus penuh pada pengerjaan order.
- **Department Routing**: Filter otomatis berdasarkan departemen (Dapur, Bar, Ritel).
- **Smart Task Batching**: Tambahan pesanan di meja yang sama otomatis muncul sebagai kartu tugas baru jika pesanan sebelumnya sudah diproses (Smart Sync).
- **Workflow Status**: *Pending* (Masuk) ➝ *Cooking* (Proses) ➝ *Ready* (Siap) ➝ *Served* (Disajikan).
- **Dual Tracking**: Tab "Siap" (Ready) khusus untuk melihat semua pesanan yang sudah matang sebelum disajikan oleh waiter.
- **Real-time Notifications**: Notifikasi instan ke kasir/waiter saat pesanan selesai dimasak.

### �👥 Human Resource Management (HRM)
Modul lengkap untuk menajemen karyawan:
- **Karyawan**: Data lengkap karyawan termasuk foto wajah.
- **Shift Kerja**: Pengaturan jadwal shift (Pagi, Siang, Malam).
- **Absensi (Attendance)**: Pencatatan kehadiran dengan validasi foto dan status terlambat.
- **Penggajian (Payroll)**: 
  - Hitung gaji otomatis berdasarkan formula.
  - Tunjangan & Potongan Variable.
  - Slip Gaji (Payslip).
- **Pinjaman (Loans)**: Manajemen pinjaman karyawan dan cicilan potong gaji.
- **Cuti (Leave Requests)**: Pengajuan dan persetujuan cuti.

### 📅 Reservasi (Reservations)
- **Booking Table**: Kelola reservasi pelanggan.
- **Pre-Order Menu**: Input pesanan menu di awal (terintegrasi dengan data Produk).
- **Status Tracking**: Pending, Confirmed, Seated, Cancelled.
- **Assign Table**: Menetapkan nomor meja untuk reservasi.

### 🍳 Resep & COGS (Food Cost)
- **Recipe Management**: Tentukan bahan baku untuk setiap produk jadi.
- **Auto Stock Deduction**: Stok bahan baku berkurang otomatis saat menu terjual.
- **HPP Calculation**: Perhitungan Harga Pokok Produksi otomatis dari total biaya bahan.

### 📦 Inventori & Stok
- **Multi-Unit Conversion**: Beli dalam Kg, pakai dalam Gram (Otomatis konversi).
- **Stock Movements**: Riwayat lengkap keluar masuk barang (Purchase, Sale, Adjustment, Production).
- **Stock Adjustment**: Opname stok manual untuk penyesuaian selisih fisik.

### 💰 Laporan Pajak (Fiscal)
Fitur manajemen pelaporan fiskal yang terintegrasi:
- **Target Omzet Harian**: Pengaturan parameter omzet harian untuk keperluan pelaporan fiskal.
- **Automated Sampling**: Algoritma seleksi data transaksi secara otomatis dengan variasi natural (±15%) mendekati target.
- **Export Template**: Download laporan langsung dalam format Excel pemerintah yang kolomnya bisa disesuaikan (Mapping Kolom).
- **PDF Rekap Harian**: Ringkasan omzet dan pajak harian siap cetak dengan kolom tanda tangan.

---

## 🛠️ Teknologi yang Digunakan

- **Framework**: [Laravel 11](https://laravel.com) / 12 (Bleeding Edge)
- **Admin & UI**: [FilamentPHP v4](https://filamentphp.com)
- **Styling**: [TailwindCSS v4](https://tailwindcss.com)
- **Database**: MySQL / MariaDB
- **Desktop Agent**: [Electron](https://www.electronjs.org) (Untuk Direct Printing)

---

## 🏗️ Struktur Database & Modul (Ringkasan)

| Kategori | Tabel / Modul Utama | Fungsi |
|----------|---------------------|--------|
| **Core** | `products`, `categories`, `units` | Manajemen data master produk dan satuan |
| **Sales** | `sales`, `sale_items`, `cash_sessions` | Transaksi POS dan Sesi Kasir |
| **Production** | `SaleItems (KDS Status)` | Alur kerja dapur dan bar (KDS) |
| **Inventory** | `purchases`, `stock_movements`, `recipes` | Stok masuk, resep, dan log pergerakan |
| **HRM** | `employees`, `payrolls`, `attendances`, `shifts` | Karyawan, Gaji, Absensi, Jadwal |
| **Reservations** | `reservations`, `reservation_items` | Booking meja dan pre-order menu |
| **Finance** | `expenses`, `loans` | Pengeluaran operasional dan pinjaman karyawan |
| **Settings** | `settings`, `print_jobs`, `payment_methods` | Konfigurasi sistem dan printer |
| **Tax / Fiskal** | `sales (is_tax_reported)` | **Laporan Pajak Fleksibel** (Target Omzet & Randomizer) |

---

## 💻 Instalasi & Setup

### 1️⃣ Persiapan Backend (Laravel)

Pastikan PHP >= 8.2 dan Composer sudah terinstall.

```bash
# Clone repository
git clone https://github.com/evanabaddon/resto-pos-filament.git
cd resto-pos-filament

# Install Dependencies
composer install
npm install

# Setup Environment
cp .env.example .env
# (Sesuaikan konfigurasi DB_DATABASE, DB_USERNAME, dll di file .env)

# Generate Key & Migrate
php artisan key:generate
php artisan storage:link
php artisan migrate --seed

# Jalankan Server Development (Tailwind v4 Build)
npm run dev
php artisan serve
```

---

## 🗺️ Roadmap Pengembangan

### 🚀 Prioritas Selanjutnya (Next Phase)
1. ** Visual Table Management**
   - Denah lantai (Floor Plan) interaktif.
   - Indikator status meja: *Kosong, Terisi, Reserved*.
   - Drag & drop untuk memindahkan pesanan atau menggabungkan meja.

2. **🤝 Membership & Loyalty**
   - **Simple Mode**: Cukup catat nama pelanggan (kebiasaan lokal), tanpa wajib No HP.
   - **Full Mode**: Registrasi lengkap dengan No HP untuk mengumpulkan **Poin Loyalty**.
   - Sistem penukaran poin dengan diskon atau produk gratis.

### 🔮 Rencana Jangka Panjang (Future)
- **📱 Self-Order Menu (QR Code)**: Pelanggan scan QR di meja untuk pesan mandiri.
- **💳 Payment Gateway**: Integrasi QRIS otomatis (Midtrans/Xendit) untuk status pembayaran realtime.

---

## 🔄 Alur Kerja Sistem

1. **Setup Data**: Buat Unit -> Bahan Baku -> Resep -> Produk Jadi.
2. **Daily Operation**:
   - Kasir membuka **Cash Session** (Modal Awal).
   - Penjualan POS ➝ **Notifikasi Realtime ke KDS**.
   - Chef/Barista memproses di KDS ➝ Waiter mendapat **Notifikasi Siap**.
   - Waiter menyajikan ➝ Status pesanan selesai (Served).
3. **End of Month**: 
   - Tarik laporan Laba/Rugi.
   - Generate Payroll Karyawan (Otomatis hitung gaji).

---

## ⚠️ Catatan Pengembang
- **Filament V4**: Project ini menggunakan versi filament/laravel terbaru. Pastikan environment mendukung.
- **Node Modules**: Diperlukan untuk build assets Tailwind v4.

---

📍 **Maintainer:** Evan Helga

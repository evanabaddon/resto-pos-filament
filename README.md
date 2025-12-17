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

### 👥 Human Resource Management (HRM) [BARU]
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

### 📅 Reservasi (Reservations) [BARU]
- **Booking Table**: Kelola reservasi pelanggan.
- **Status Trakcing**: Pending, Confirmed, Seated, Cancelled.
- **Assign Table**: Menetapkan nomor meja untuk reservasi.

### 🍳 Resep & COGS (Food Cost)
- **Recipe Management**: Tentukan bahan baku untuk setiap produk jadi.
- **Auto Stock Deduction**: Stok bahan baku berkurang otomatis saat menu terjual.
- **HPP Calculation**: Perhitungan Harga Pokok Produksi otomatis dari total biaya bahan.

### 📦 Inventori & Stok
- **Multi-Unit Conversion**: Beli dalam Kg, pakai dalam Gram (Otomatis konversi).
- **Stock Movements**: Riwayat lengkap keluar masuk barang (Purchase, Sale, Adjustment, Production).
- **Stock Adjustment**: Opname stok manual untuk penyesuaian selisih fisik.

---

## 🛠️ Tekonologi yang Digunakan

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
| **Inventory** | `purchases`, `stock_movements`, `recipes` | Stok masuk, resep, dan log pergerakan |
| **HRM** | `employees`, `payrolls`, `attendances`, `shifts` | Karyawan, Gaji, Absensi, Jadwal |
| **Reservations** | `reservations`, `reservation_items` | Booking meja dan pre-order menu |
| **Finance** | `expenses`, `loans` | Pengeluaran operasional dan pinjaman karyawan |
| **Settings** | `settings`, `print_jobs`, `payment_methods` | Konfigurasi sistem dan printer |

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

# Jalankan Server Development
npm run dev
php artisan serve
```

### 2️⃣ Jalankan Print Agent (Opsional)

Jika ingin menggunakan fitur Direct Printing ke Thermal Printer:

```bash
cd electron-print-agent
npm install
npm start
```

---

## 🔄 Alur Kerja Sistem

1. **Setup Data**: Buat Unit -> Bahan Baku -> Resep -> Produk Jadi.
2. **HRM Setup**: Input data Karyawan -> Atur Shift & Formula Gaji.
3. **Daily Operation**:
   - Kasir membuka **Cash Session** (Modal Awal).
   - Transaksi Penjualan (Stok berkurang otomatis).
   - Karyawan melakukan Absensi Masuk/Pulang.
   - Kasir menutup **Cash Session** (Setor uang).
4. **End of Month**: 
   - Tarik laporan Laba/Rugi.
   - Generate Payroll Karyawan (Otomatis hitung gaji + lembur - pinjaman).

---

## ⚠️ Catatan Pengembang
- **Filament V4**: Project ini menggunakan versi filament/laravel terbaru. Pastikan environment mendukung.
- **Node Modules**: Diperlukan untuk build assets Tailwind v4.

---

📍 **Maintainer:** Evan Helga

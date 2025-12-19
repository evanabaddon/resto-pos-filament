# 🍽️ Resto POS System (Laravel 11 + Filament 4)

**The Ultimate Restaurant Management System** — Dirancang dengan standar Enterprise untuk operasional F&B modern yang kompleks dan dinamis. Menghadirkan pengalaman **Point of Sale (POS)** kelas dunia, **CRM Berbasis AI-Logic**, **Kitchen Display System (KDS)** Realtime, hingga **Laporan Fiskal & Profitabilitas Otomatis**.

---

## ⚡️ Keunggulan Utama (Highlight Features)

### 💎 Smart POS (Point of Sale)
Antarmuka kasir yang dirancang untuk kecepatan dan pengalaman pengguna (User Experience) premium.
- **Space-Saving Layout (Responsive)**: Tata letak responsif yang menyesuaikan otomatis dengan layar Tablet (iPad) atau Desktop.
- **⚡️ Quick Add Member**: Registrasi member baru **langsung dari layar transaksi** tanpa berpindah menu (menggunakan Custom Modal Native).
- **🔔 Live POS Notifications**: Sistem notifikasi internal yang tidak mengganggu alur kerja (non-blocking) untuk status pembayaran, print, dan error.
- **Seamless Modal Experience**: Penggunaan custom native modals (Livewire) untuk input diskon dan member, memberikan nuansa aplikasi mobile yang halus.
- **Smart Cart Logic**: Split payment, merged tables, dan draft order (pending transaction).
- **Direct Printing**: Integrasi ke printer thermal via **Electron Agent** (Mendukung USB/LAN/Bluetooth).

### 🤝 Advanced CRM & Loyalty (Smart SOP) 🆕
Bukan sekadar mencatat data pelanggan, tapi membangun hubungan jangka panjang.
- **🎯 Smart WhatsApp SOP Integration**:
  - **Context-Aware SOP**: Sistem otomatis menyarankan pesan WhatsApp yang personal berdasarkan "Fase Pelanggan" (Baru / Repeat / High Value).
  - **Dynamic Templates**: Template pesan yang berubah otomatis sesuai data pelanggan (Nama, Saldo Poin, Tier).
  - **Click-to-Followup**: Tombol aksi cepat di daftar member untuk mengirim sapaan rutin atau FAQ.
  - **Activity Tracking**: Melacak kapan terakhir kali member dihubungi (`last_contacted_at`), dengan fitur **Reset Status** jika batal kirim.
- **💰 Smart Point System**:
  - **Dynamic Exchange Rate**: Nilai tukar poin bisa diatur bebas di pengaturan (misal: 1 Poin = Rp 10).
  - **Realtime Redemption Validation**: POS otomatis menghitung sisa saldo member saat penukaran dan mencegah "Over-Redemption".
  - **Instant Balance Preview**: Kasir bisa melihat langsung nilai rupiah dari poin yang ditukar.
- **Dynamic Loyalty Tiers**: Level member yang naik/turun otomatis berdasarkan frekuensi kunjungan atau total belanja.

### 📅 Intelligent Reservation (WA Confirmation) 🆕
Sistem reservasi yang terintegrasi langsung dengan komunikasi pelanggan.
- **One-Click WhatsApp Confirmation**: Kirim konfirmasi reservasi formal via WhatsApp langsung dari kalender. format template dinamis.
- **Pre-Order to Sales**: Fitur input pesanan (menu) saat reservasi. Saat tamu datang, pesanan **langsung dikonversi menjadi transaksi Sales** di POS tanpa input ulang.
- **Encoding Safety**: Menggunakan *Direct API Link* untuk memastikan Emoji (📅 ⏰ 😊) terkirim sempurna tanpa error encoding.
- **Status Automation**: Status reservasi otomatis berubah menjadi "Confirmed" saat pesan WA dikirim.

### ‍🍳 Intelligent Kitchen Display System (KDS)
Orkestrasi dapur digital tanpa kertas.
- **Zero-Latency Realtime Sync**: Pesanan muncul di dapur **detik itu juga** saat kasir klik simpan.
- **Smart Task Batching**: Menggabungkan item yang sama untuk efisiensi masak.
- **Department Routing**: Memisahkan otomatis pesanan Bar (Minuman) dan Dapur (Makanan).
- **Status Workflow**: *Pending* ➝ *Cooking* ➝ *Ready* ➝ *Served*.

### 👥 Modern HRM (Payroll & Attendance)
Full-suite manajemen sumber daya manusia yang terintegrasi penuh dengan keuangan.
- **Smart Attendance System**:
  - **Face Recognition Ready**: Validasi kehadiran menggunakan biometrik wajah untuk mencegah "titip absen".
  - **GPS Geofencing**: Memastikan karyawan clock-in/out hanya di area outlet.
  - **Late Penalty Logic**: Pemotongan otomatis untuk keterlambatan berdasarkan durasi (menit).
- **Automated Payroll Engine**:
  - **Dynamic & Flexible Formula**: Rumus penggajian sepenuhnya **bisa dikustomisasi** sesuai kebijakan restoran (bisa menambah komponen baru tanpa coding).
    > *Contoh default*: `(Gaji Pokok + Tunjangan Jabatan + Overtime) - (Denda Terlambat + Cicilan Kasbon + PPh 21)`
  - **Payslip Generation**: Cetak slip gaji digital otomatis setiap akhir periode.
  - **Take Home Pay Tuning**: Penyesuaian manual (Adjustment) untuk bonus atau denda dadakan.
- **Employee Loan Management**:
  - **Installment Tracking**: Manajemen pinjaman karyawan dengan skema cicilan tenor (Bulan).
  - **Auto-Deduction**: Cicilan otomatis memotong gaji bulanan (Payroll Integration) sehingga tidak perlu penagihan manual.
- **Shift & Rostering**: Pengaturan jadwal shift fleksibel dengan dukungan tukar shift.

### 📱 Self-Service Kiosk & PWA 🆕
- **Progressive Web App (PWA)**: Dapat diinstall di tablet Android/iPad sebagai aplikasi native.
- **Attendance Kiosk Mode**: Mode khusus tablet dinding untuk absensi karyawan yang cepat dan akurat.

### 🧩 Modular Architecture (On-Demand Features)
Fitur canggih yang bisa disesuaikan dengan kebutuhan outlet.
- **Toggleable Modules**: Aktifkan/Matikan modul CRM, HRM, KDS, atau Fiskal sesuai lisensi atau kebutuhan outlet.
- **Centralized Settings**: Panel pengaturan terpusat untuk semua konfigurasi modul.

### 📊 Financial Intelligence
- **Realtime COGS Calculation**: Menghitung HPP setiap detik untuk akurasi laba bersih.
- **Fiscal Planning (Tax Control)**: Fitur "Target Omzet" untuk membantu perencanaan pelaporan pajak yang fleksibel.

---

## 🛠️ Stack Teknologi (Enterprise Grade)

Dibangun di atas pondasi teknologi paling modern dan stabil di tahun 2025.

- **Framework**: [Laravel 11](https://laravel.com) (PHP 8.2+)
- **Admin & UI**: [FilamentPHP v4](https://filamentphp.com) (Bleeding Edge)
- **Engine**: [Livewire 3](https://livewire.laravel.com) (Reactive UI)
- **Styling**: [TailwindCSS v4](https://tailwindcss.com) (Zero-runtime CSS)
- **Database**: MySQL 8 / MariaDB
- **State Management**: Alpine.js v3
- **Local Agent**: Electron (Untuk Bridge Hardware)

---

## 🗺️ Cara Penggunaan (Quick Start)

### 1. POS Operation
1.  Buka menu **POS**.
2.  Pilih produk ➝ Masuk ke Keranjang.
3.  **Member**: Cari member atau klik **Tanda Tambah (+)** untuk buat member baru kilat.
4.  **Bayar**: Pilih metode bayar (Tunai/QRIS/Card). Struk otomatis tercetak.

### 2. CRM Follow-up
1.  Buka menu **Kemitraan (Member)**.
2.  Lihat kolom **"Terakhir Followup"**.
3.  Klik **Action WhatsApp**: Pilih template (Sapaan Rutin, FAQ Benefit, dll).
4.  Kirim pesan personal dalam satu klik.

### 3. Reservasi & Confirmation
1.  Buka Kalender Reservasi.
2.  Klik reservasi untuk melihat detail.
3.  Tekan tombol **"Confirm via WA"** (Icon Centang Hijau).
4.  Sistem otomatis update status & membuka WhatsApp dengan template yang sudah diset.

---

## ⚠️ Requirements
- PHP 8.2+
- Composer 2+
- Node.js & NPM (untuk build assets)

---

📍 **Developed by Evan Helga** — *Crafting Digital Excellence for F&B Business.*

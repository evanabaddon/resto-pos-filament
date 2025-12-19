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

### 📅 Intelligent Reservation & DP System 🆕
Sistem reservasi yang terintegrasi langsung dengan komunikasi pelanggan dan manajemen keuangan.
- **One-Click WhatsApp Confirmation**: Kirim konfirmasi reservasi formal via WhatsApp langsung dari kalender. Format template dinamis dengan dukungan Emoji sempurna (📅 ⏰ 😊).
- **💰 Robust Down Payment (DP) System**:
  - **Pay DP Anywhere**: Kelola pembayaran uang muka melalui Tabel Reservasi maupun Kalender.
  - **Automated DP Ledger**: Setiap pembayaran DP otomatis membuat transaksi di POS dengan prefix `DP-` untuk pelacakan finansial yang akurat.
  - **Self-Cleaning Catalog**: Produk "Down Payment (DP)" dikelola sebagai *System Item*—otomatis tersembunyi dari master data dan katalog POS agar tidak mengganggu operasional harian.
- **🔄 Smart Pre-Order to Sales**:
  - **Conversion Logic**: Konversi reservasi menjadi transaksi aktif di POS dalam satu klik.
  - **Automatic DP Deduction**: Sistem otomatis mendeteksi uang muka dan menambahkannya sebagai item pengurang (minus) di transaksi kasir.
  - **Double-Transaction Protection**: Tombol konversi otomatis hilang setelah digunakan (ketika status berubah menjadi `Seated`), mencegah duplikasi data penjualan.
- **Snapshot Integrity**: Nama produk disnapshot saat konversi untuk memastikan data historis tetap akurat meskipun produk asli dihapus atau diubah.

### ‍🍳 Intelligent Kitchen Display System (KDS) & Printing
Orkestrasi dapur digital tanpa kertas dengan sistem penyaringan cerdas.
- **Zero-Latency Realtime Sync**: Pesanan muncul di dapur detik itu juga saat kasir klik simpan.
- **Smart Task Batching**: Menggabungkan item yang sama untuk efisiensi masak.
- **Department Routing**: Memisahkan otomatis pesanan Bar (Minuman) dan Dapur (Makanan).
- **🚫 System Item Filtering**: Item finansial seperti "Down Payment (DP)" secara cerdas difilter agar tidak muncul di KDS maupun struk pesanan dapur/bar, menjaga fokus area produksi pada menu yang harus disiapkan.
- **Status Workflow**: *Pending* ➝ *Cooking* ➝ *Ready* ➝ *Served*.

### 👥 Modern HRM (Payroll & Attendance)
Full-suite manajemen sumber daya manusia yang terintegrasi penuh dengan keuangan.
- **Smart Attendance System**:
  - **Face Recognition Ready**: Validasi kehadiran menggunakan biometrik wajah.
  - **GPS Geofencing**: Memastikan karyawan clock-in/out hanya di area outlet.
  - **Late Penalty Logic**: Pemotongan otomatis untuk keterlambatan.
- **Automated Payroll Engine**:
  - **Dynamic & Flexible Formula**: Rumus penggajian yang bisa dikustomisasi sepenuhnya (Gaji Pokok, Tunjangan, Overtime, Denda).
  - **Employee Loan System**: Manajemen pinjaman dengan fitur **Auto-Deduction** pada slip gaji bulanan.

### 🧩 Modular Architecture & SEO Ready
- **Toggleable Modules**: Aktifkan/Matikan modul CRM, HRM, KDS, atau Fiskal sesuai kebutuhan.
- **SEO Optimization**: Struktur HTML semantik, Meta Tags dinamis, dan performa super cepat untuk visibilitas maksimal.

---

## 🛠️ Stack Teknologi (Enterprise Grade)

Dibangun di atas pondasi teknologi paling modern dan stabil di tahun 2025.

- **Framework**: [Laravel 11](https://laravel.com)
- **Admin & UI**: [FilamentPHP v4](https://filamentphp.com)
- **Engine**: [Livewire 3](https://livewire.laravel.com)
- **Styling**: [TailwindCSS v4](https://tailwindcss.com) & Vanilla CSS
- **Database**: MySQL 8 / MariaDB
- **State Management**: Alpine.js v3
- **Local Agent**: Electron (Hardware Bridge)

---

## 🗺️ Cara Penggunaan (Quick Start)

### 1. POS Operation
1.  Buka menu **POS**.
2.  Pilih produk ➝ Masuk ke Keranjang.
3.  **Member**: Cari member atau klik **(+)** untuk buat member baru kilat.
4.  **Bayar**: Pilih metode bayar. Struk otomatis tercetak.

### 2. CRM Follow-up
1.  Buka menu **Kemitraan (Member)**.
2.  Gunakan Tombol WhatsApp untuk kirim template sapaan personal.

### 3. Reservasi & DP
1.  **Input**: Catat reservasi & menu pre-order tamu.
2.  **Bayar DP**: Klik **"Bayar DP"** untuk mencatat uang muka.
3.  **Konfirmasi**: Tekan tombol **"Confirm via WA"** untuk kirim link konfirmasi otomatis.
4.  **Tamu Datang**: Klik **"Proses ke Kasir"**. Transaksi akan muncul di POS dengan potongan DP otomatis.

---

## ⚠️ Requirements
- PHP 8.2+
- Composer 2+
- Node.js & NPM (untuk build assets)

---

📍 **Developed by Evan Helga** — *Crafting Digital Excellence for F&B Business.*

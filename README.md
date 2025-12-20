# 🍽️ Resto Intelligence: Advanced F&B Ecosystem 🚀
### Next-Gen Restaurant OS — Powered by Adaptive AI & Native WhatsApp Gateway

**Resto Intelligence** adalah **Enterprise-Grade F&B Ecosystem** yang dirancang untuk mendefinisikan ulang standar operasional restoran modern. Mengintegrasikan teknologi **Laravel 11**, **Filament 4**, dan **Hybrid AI Intelligence**, sistem ini menghadirkan perpaduan sempurna antara **High-Speed Point of Sale**, **Native WhatsApp Gateway**, dan **Autonomous Business Intelligence**.

Ini bukan sekadar alat pencatat transaksi; ini adalah pusat komando digital yang memberdayakan outlet Anda dengan **AI-Driven CRM**, **Real-time Kitchen Orchestration (KDS)**, hingga **Automated Fiscal & P&L Analysis**. Sistem ini memastikan setiap detik operasional Anda optimal, setiap pelanggan merasa dihargai secara personal melalui **Hyper-Personalized WhatsApp**, dan setiap keputusan bisnis didukung oleh kecerdasan buatan yang akurat.

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
  - **🚀 Productivity Boost**: Semua perintah WhatsApp (SOP/FAQ/AI) otomatis terbuka di **Tab Baru**, menjaga konsistensi alur kerja admin tanpa menutup dashboard utama.
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

### 🧩 Modular Architecture (On-Demand Features)
Fitur canggih yang bisa disesuaikan dengan kebutuhan outlet.
- **Toggleable Modules**: Aktifkan/Matikan modul CRM, HRM, KDS, atau Fiskal sesuai kebutuhan.
- **Centralized Settings**: Panel pengaturan terpusat untuk semua konfigurasi modul.

### 🧠 AI-Powered Intelligence (Powered by Nirmala AI) 🚀
Membawa operasional restoran ke level otonom dengan integrasi LLM tercanggih untuk keputusan bisnis yang lebih tajam.
- **🤖 AI Daily Suggestion [READY]**: Widget pintar di dashboard yang memberikan saran strategi bisnis dan peringatan stok kritis secara otomatis setiap hari. Menganalisis data penjualan 30 hari terakhir dan status inventaris secara mendalam.
- **💬 AI Business Assistant ("Tanya Nirmala") [READY]**: Konsultasi performa bisnis via chat bahasa natural dengan **Conversation Memory**.
    - **Premium UI/UX**: Interface modern dengan efek *Glassmorphism*, input dinamis, dan responsivitas penuh.
    - **Live Context**: AI memiliki akses ke data Penjualan, Menu Terlaris, dan Stok Kritis (Retail & Bahan Baku).
    - **Persona Config**: Ubah Nama AI (misal: "Sarah", "Jarvis") di Settings untuk pengalaman yang lebih personal.
- **💌 AI Smart Message & Reply (Grounded AI) [READY]**: Generasi draf pesan yang sangat personal dengan **Zero-Hallucination Logic**.
    - **Product Grounding**: AI hanya akan menyebutkan **Menu Asli** (Top 5 Terlaris) dari database, bukan menu fiktif.
    - **Promo Awareness**: Otomatis menyertakan kode promo aktif yang tersedia di sistem kasir.
    - **Signature System**: Penutup pesan otomatis sesuai nama Assistant yang dikonfigurasi.
- **📅 AI Reservation Awareness [NEW]**: AI secara cerdas mengecek ketersediaan jam reservasi dengan membaca jadwal 7 hari ke depan sebelum memberikan draf balasan.

### 💬 Integrated WhatsApp Center (Native Chat) 🚀
Menghadirkan pengalaman WhatsApp Web lengkap langsung di dalam dashboard admin.
- **✨ Full-Featured Interface**: Tampilan chat yang familiar, responsif, dan elegan dengan **Dark Mode** support.
- **🤖 Grounded AI Reply (Anti-Hallucination)**: Generate balasan cerdas otomatis yang didukung oleh **Knowledge Base** bisnis Bos:
  - **Menu Awareness**: AI tahu daftar menu terlaris Bos secara realtime.
  - **Promo Awareness**: AI tahu promo apa yang sedang berlangsung di kasir.
  - **Reservation Availability**: AI dapat mengecek ketersediaan meja dengan membaca data reservasi 7 hari ke depan secara otomatis.
- **↩️ Reply with Quote**: Fitur balas pesan (Reply) dengan kutipan asli, persis seperti di aplikasi native.
- **⚡️ Realtime Architecture**:
  - **Live Notifications**: Notifikasi suara dan visual instan saat pesan baru masuk tanpa refresh.
  - **Direct Avatar Proxy**: Sistem penanganan avatar otomatis untuk performa tinggi (Fixing JID cleaning & device sync).
- **📁 Advanced Media Handling**:
  - **Ratio-Perfect Video**: Pemutar video pintar yang menghormati rasio asli (Portrait/Landscape).
  - **Drag & Drop**: Kirim gambar/dokumen tinggal tarik & lepas dengan preview cepat.
  - **Voice & Documents**: Dukungan penuh untuk voice note dan dokumen PDF.
- **🚧 Group Mention Autocomplete [WIP]**: Fitur tagging anggota grup (`@user`) sedang dalam tahap pengembangan (Beta).
- **🔄 Smart Conversion Actions**:
  - **Quick Member**: Konversi chat pelanggan baru menjadi Member CRM langsung dari header chat. Auto-detect jika sudah terdaftar.
  - **Create Reservation**: Buat jadwal reservasi langsung saat chatting tanpa perlu pindah menu.

### 📊 Financial Intelligence
- **Realtime COGS Calculation**: Menghitung HPP setiap detik untuk akurasi laba bersih.
- **Fiscal Planning (Tax Control)**: Fitur "Target Omzet" untuk membantu perencanaan pelaporan pajak yang fleksibel.

---

## 🗺️ Roadmap Strategis 2026: Strategic Expansion

Rencana pengembangan fitur masa depan untuk memaksimalkan ROI dan efisiensi operasional.

### 1. 🍳 AI Menu Engineering (Profit & Popularity Matrix) [NEXT]
- **Stars, Plowhorses, Puzzles, & Dogs**: AI mengklasifikasikan menu berdasarkan profitabilitas vs popularitas.
- **Dynamic Pricing Advice**: AI menyarankan kenaikan harga atau penggantian bahan baku untuk menu yang populer tapi kurang menguntungkan.

### 2. 💌 Loyalty Automation 2.0 (The AI CRM Agent)
- **Automatic Re-engagement**: Pesan otomatis ke pelanggan yang tidak datang >30 hari.
- **Birthday & Milestone Alerts**: Pengiriman otomatis gift dan ucapan untuk meningkatkan *Retention Rate*.

### 3. ⏱️ Kitchen Productivity & Performance Analytics
- **Preparation Time Tracking**: Melacak durasi masak per item dari KDS masuk hingga selesai.
- **Kitchen Bottleneck Detection**: Menganalisis menu mana yang paling sering membuat antrean dapur macet.

### 4. 📉 Smart Inventory Forecasting
- **Predictive Restocking**: AI memprediksi kebutuhan bahan baku minggu depan berdasarkan tren historis dan sisa stok saat ini.

### 5. 💰 Integrated P&L Dashboard (Daily Net Profit)
- **Expense vs Revenue**: Dashboard laba rugi harian otomatis yang sudah memotong Gaji, Operasional, dan HPP.

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

## 🚀 Deployment & Background Services

Agar notifikasi WhatsApp dan AI berjalan realtime, service di bawah ini **WAJIB** dijalankan di server.

### 1. Queue & Scheduler Setup

#### **A. VPS / Dedicated (Rekomendasi: Supervisor)**
Gunakan **Supervisor** agar proses `queue:work` otomatis restart jika mati.
```ini
[program:resto-pos-queue]
command=php /path/to/project/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=2
```

#### **B. Shared Hosting (Alternatif: Cron Job)**
Jika menggunakan Shared Hosting (cPanel/DirectAdmin) yang tidak mendukung Supervisor, gunakan **Cron Job** untuk menjalankan scheduler harian dan antrean pesan.

**1. Setel Cron Job harian (Per Menit):**
Tambahkan perintah ini di menu "Cron Jobs" hosting (Sesuaikan path folder project Bos):
```bash
* * * * * cd /home/suralaya.id/pos.suralaya.id && php artisan schedule:run >> /dev/null 2>&1
```
*(Contoh path di atas jika project berada di `/home/suralaya.id/pos.suralaya.id`)*

**2. Jalankan Queue via Scheduler:**
Pastikan di file `routes/console.php` (Laravel 11), antrean dijalankan secara berkala jika tidak ada worker yang stand-by:
```php
// Contoh di routes/console.php
use Illuminate\Support\Facades\Schedule;

Schedule::command('queue:work --stop-when-empty')->everyMinute();
```
*Metode ini akan memproses antrean notifikasi/WA setiap 1 menit.*

### 2. WhatsApp Gateway (Node.js)
Service penghubung antara aplikasi POS dengan server WhatsApp.

**Jalankan Service:**
```bash
cd wa-gateway
npm install
npm start
```
*Gunakan `pm2` atau `systemd` untuk menjalankan service ini di background pada production.*

### 3. Requirements Setup
- **PHP 8.2+** & **MySQL 8+**
- **DeepSeek API Key**: `DEEPSEEK_API_KEY` di `.env` untuk fitur AI.
- **Microphone Permission**: Izin browser diperlukan untuk fitur Voice Note.

---

📍 **Developed by Evan Helga** — *Crafting Digital Excellence for F&B Business.*

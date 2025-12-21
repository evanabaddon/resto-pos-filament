<div align="center">

# 🍽️ Intelligent Restaurant Ecosystem 🚀
### Next-Gen Restaurant OS — Powered by Adaptive AI & Native WhatsApp Gateway

**Intelligent Restaurant Ecosystem** adalah **Enterprise-Grade F&B Ecosystem** yang dirancang untuk mendefinisikan ulang standar operasional restoran modern. Mengintegrasikan teknologi **Laravel 11**, **Filament 4**, dan **Hybrid AI Intelligence**, sistem ini menghadirkan perpaduan sempurna antara **High-Speed Point of Sale**, **Native WhatsApp Gateway**, dan **Autonomous Business Intelligence**.

Ini bukan sekadar alat pencatat transaksi; ini adalah pusat komando digital yang memberdayakan outlet Anda dengan **AI-Driven CRM**, **Real-time Kitchen Orchestration (KDS)**, hingga **Automated Fiscal & P&L Analysis**. Sistem ini memastikan setiap detik operasional Anda optimal, setiap pelanggan merasa dihargai secara personal melalui **Hyper-Personalized WhatsApp**, dan setiap keputusan bisnis didukung oleh kecerdasan buatan yang akurat.

</div>

---

## ⚡️ Keunggulan Utama (Highlight Features)

### 💎 Smart POS (Point of Sale)
Antarmuka kasir yang dirancang untuk kecepatan dan pengalaman pengguna (User Experience) premium.
- **Space-Saving Layout (Responsive)**: Tata letak responsif yang menyesuaikan otomatis dengan layar Tablet (iPad) atau Desktop.
- **⚡️ Quick Add Member**: Registrasi member baru **langsung dari layar transaksi** tanpa berpindah menu (menggunakan Custom Modal Native).
- **🔔 Live POS Notifications**: Sistem notifikasi internal yang tidak mengganggu alur kerja (non-blocking) untuk status pembayaran, print, dan error.
- **Seamless Modal Experience**: Penggunaan custom native modals (Livewire) untuk input diskon dan member, memberikan nuansa aplikasi mobile yang halus.
- **Smart Cart Logic**: Split payment, merged tables, dan draft order (pending transaction).
- **🖨️ Multi-Printer Infrastructure**: Mendukung banyak printer sekaligus (Kasir, Dapur, & Bar) via **Electron Agent** (Mendukung USB/LAN/Bluetooth).
- **Direct Printing**: Pencetakan struk dan order otomatis tanpa dialog print browser, memberikan kecepatan transaksi maksimal.

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
- **🔄 Smart Pre-Order to Sales (Flexible)**:
  - **Flexible Item Management**: Tambahkan menu pre-order dengan harga fleksibel (bisa diedit manual) dan hitungan otomatis.
  - **Instant Conversion upon Arrival**: Saat pelanggan datang, konversi seluruh data reservasi beserta item pre-order menjadi transaksi penjualan (Sales) di POS dalam satu klik.
  - **Automatic DP Deduction**: Sistem otomatis mendeteksi uang muka dan menambahkannya sebagai item pengurang (minus) di transaksi kasir.
  - **Double-Transaction Protection**: Tombol konversi otomatis hilang setelah digunakan (ketika status berubah menjadi `Seated`), mencegah duplikasi data penjualan.
- **Snapshot Integrity**: Nama produk disnapshot saat konversi untuk memastikan data historis tetap akurat meskipun produk asli dihapus atau diubah.

### ‍🍳 Intelligent Kitchen Display System (KDS) & Printing
Orkestrasi dapur digital tanpa kertas dengan sistem penyaringan cerdas.
- **Zero-Latency Realtime Sync**: Pesanan muncul di dapur detik itu juga saat kasir klik simpan.
- **Smart Task Batching**: Menggabungkan item yang sama untuk efisiensi masak.
- **Department Routing & Auto-Print**: Memisahkan otomatis pesanan Bar (Minuman) dan Dapur (Makanan). Order otomatis terwujud dalam bentuk **Print-Out di masing-masing divisi** begitu pesanan disimpan oleh kasir.
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
- **Toggleable Modules**: Bebas aktifkan/matikan fitur sesuai skala bisnis Anda, mulai dari **CRM (Loyalty)**, **HRM (Payroll)**, **KDS (Kitchen)**, **WhatsApp Center**, **Fiscal Planning**, hingga modul cerdas **AI Forecasting** dan **AI Menu Engineering**.
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
- **� AI Provider Agnostic & Dynamic Models [NEW] [READY]**:
    - **Multi-Provider Support**: Bebas pilih antara **DeepSeek (Native)**, **OpenRouter (Free/Paid)**, atau **Custom OpenAI API**.
    - **One-Click Presets**: Setup instan untuk OpenRouter & DeepSeek dengan auto-fill URL dan konfigurasi model.
    - **Dynamic Model Fetching**: Daftar model ditarik secara real-time dari API OpenRouter dengan indikator model **(FREE)**.
    - **Zero-Code Configuration**: Atur API Keys dan Model langsung melalui Dashboard Admin tanpa menyentuh file `.env`.
- **�📅 AI Reservation Awareness & Weather Intelligence [NEW]**:
    - **Smart Availability Check**: AI secara cerdas mengecek ketersediaan jam reservasi dengan membaca jadwal 7 hari ke depan.
    - **🌤️ Hyper-Local Weather Forecaster**: Terintegrasi langsung dengan BMKG. Data cuaca digunakan untuk memberikan saran presisi saat konfirmasi reservasi:
        - **"Hujan"**: AI mengingatkan pelanggan membawa payung atau naik mobil.
        - **"Panas"**: AI menyarankan menu minuman dingin yang menyegarkan.
        - **"Neutral"**: AI memberikan sentuhan ramah tentang cuaca yang bersahabat.
    - **Visual Weather Widget**: Widget prakiraan cuaca 12-jam (per 3 jam) di dashboard yang mengambil data real-time dari kode wilayah kelurahan setempat.
    - **🗺️ BMKG Location Sync**: Konfigurasi lokasi cuaca presisi hingga tingkat Kelurahan menggunakan Kode Wilayah BMKG untuk akurasi data.
- 📉 **Smart Inventory Forecasting (AI Restock) [PRO] [READY]**:
    - **Predictive Restocking**: AI memprediksi kebutuhan bahan baku untuk 7 hari ke depan berdasarkan tren historis dan sisa stok saat ini.
    - **Recipe-Aware Analytics**: Otomatis menghitung kebutuhan *raw material* (misal: biji kopi) berdasarkan penjualan menu (misal: Latte) menggunakan data resep dengan **Unit Conversion Logic** (Gram/Kg/Pcs).
    - **Persistence & Speed**: Hasil analisis disimpan dalam cache selama 24 jam untuk akses instan tanpa perlu generate ulang setiap kali halaman dibuka.
    - **Urgency Insights**: Memberikan label urgensi (High/Medium/Low) dan alasan logis di balik setiap saran restock.
    - **📄 Professional PDF Export**: Generate laporan restock resmi dalam format PDF yang rapi, lengkap dengan tabel rekomendasi, tingkat urgensi, dan alasan logis AI.
- 🍳 **AI Menu Engineering (Profit & Popularity Matrix) [READY]**:
    - **Strategic Classification**: AI mengklasifikasikan menu ke dalam 4 kategori strategis: **Unit Unggulan** (Stars), **Unit Andalan** (Plowhorses), **Unit Potensial** (Puzzles), dan **Unit Kurang Berkembang** (Dogs).
    - **Ultra-Accurate COGS**: Perhitungan HPP super akurat yang mendukung **Unit Conversion Rate** (misal: Harga beli sak/karung dikonversi otomatis ke gram pada resep).
    - **AI Strategic Advice**: Dapatkan saran taktis langsung dari AI (misal: saran kenaikan harga 10%, pengecilkan porsi, atau rekomendasi promosi khusus).
    - **Premium Matrix UI**: Dashboard visual dengan bar popularitas, badge kategori indigo, dan insight box gradient yang elegan.
    - **📄 Integrated PDF Report**: Ekspor hasil analisis ke PDF dengan layout profesional yang siap dipresentasikan di rapat manajemen.

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
- **⚙️ Storage Management**:
  - **Auto Download Control**: Opsi *"Auto Download Media Whatsapp"* di menu Settings untuk menghemat penyimpanan server. Jika dinonaktifkan, media hanya akan diunduh saat tombol "Unduh" diklik.

### 📊 Laporan & Analisis (Financial & Analytical Intelligence) 🚀
Pusat kendali data yang menggabungkan kecerdasan buatan dan perhitungan fiskal akurat.

- 🏛️ **Laporan Pajak (Fiskal)**: Perencanaan pajak yang fleksibel dengan fitur Target Omzet.
- 📈 **Laba/Rugi (Profit & Loss)**: Dashboard laba bersih harian yang menghitung HPP (COGS) secara realtime.
- 📦 **Prediksi Restock (AI)**: Analisis kebutuhan bahan baku 7 hari ke depan berbasis tren historis.
- 🍽️ **Analisis Menu (AI)**: Klasifikasi profitabilitas menu (Stars, Plowhorses, etc.) dengan saran strategis AI.

---

## 🗺️ Roadmap Strategis 2026: Strategic Expansion

Rencana pengembangan fitur masa depan untuk memaksimalkan ROI dan efisiensi operasional.

### 💌 Loyalty Automation 2.0 (The AI CRM Agent) [NEXT]
- **Automatic Re-engagement**: Pesan otomatis ke pelanggan yang tidak datang >30 hari.
- **Birthday & Milestone Alerts**: Pengiriman otomatis gift dan ucapan untuk meningkatkan *Retention Rate*.

### ⏱️ Kitchen Productivity & Performance Analytics
- **Preparation Time Tracking**: Melacak durasi masak per item dari KDS masuk hingga selesai.
- **Kitchen Bottleneck Detection**: Menganalisis menu mana yang paling sering membuat antrean dapur macet.

### 💰 Integrated P&L Dashboard (Daily Net Profit)
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

Agar orkestrasi WhatsApp dan AI berjalan secara *real-time*, service di bawah ini **WAJIB** dijalankan di latar belakang (background).

### 1. Queue & Scheduler Setup

Pilih salah satu metode berdasarkan lingkungan server Anda:

#### **A. VPS / Dedicated (Highly Recommended)**
Gunakan **Supervisor** untuk menjaga worker tetap hidup.
```ini
[program:resto-pos-queue]
command=php /path/to/project/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=2
```

#### **B. Modern Orchestration (PM2)**
Cara termuda untuk mengelola Queue Laravel dan WhatsApp Gateway dalam satu panel.
```bash
# Jalankan Worker Laravel
pm2 start "php artisan queue:work" --name "resto-worker"

# Jalankan WhatsApp Gateway
cd wa-gateway && pm2 start index.js --name "wa-gateway"

# Simpan Konfigurasi
pm2 save && pm2 startup
```

#### **C. Shared Hosting (Cron Job)**
Gunakan jika Anda tidak memiliki akses SSH root. Tambahkan di menu Cron Jobs:
```bash
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

> [!TIP]
> **Optimasi Real-time (15 Detik):** Jika 1 menit terlalu lama, Anda bisa menyulap Cron Job menjadi per-15 detik dengan menambahkan 4 baris job menggunakan perintah `sleep 15`, `sleep 30`, dan `sleep 45`.

---

### 🚨 Penting: Keamanan & Performa
> [!IMPORTANT]
> **Double-Check `routes/console.php`**: Pastikan perintah queue menggunakan flag `--stop-when-empty` jika dijalankan via Scheduler di Shared Hosting. Tanpa ini, server Anda berisiko **OVERLOAD** karena proses yang menumpuk.

```php
// Contoh di routes/console.php (Laravel 11)
use Illuminate\Support\Facades\Schedule;

Schedule::command('queue:work --stop-when-empty')->everyMinute();
```

---

### 🛠️ Persyaratan Sistem (Requirements)
- **PHP 8.2+** (Extension: BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML)
- **MySQL 8.0+** / MariaDB 10.4+
- **AI API Integrated**: Mendukung DeepSeek, OpenRouter, atau OpenAI-compatible API.
    - **Quick Setup**: Pilih provider (DeepSeek/OpenRouter) di menu Settings untuk konfigurasi otomatis.
    - **Dynamic Models**: Daftar model ditarik secara real-time dari API (khusus OpenRouter).
    - **Local Dev**: Jika jalan di lokal (Windows/MacOS) dan kena error SSL, tambahkan `DEEPSEEK_VERIFY_SSL=false` di `.env`.
- **Node.js 18+**: Diperlukan khusus untuk modul WhatsApp Gateway.
- **Microphone & Camera**: Diperlukan izin browser untuk fitur Voice Note & Face Recognition.

---

<p align="center">
    📍 <b>Developed by Evan Helga</b> — <i>Crafting Digital Excellence for F&B Business.</i>
</p>

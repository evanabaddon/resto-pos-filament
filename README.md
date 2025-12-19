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

### 🧠 AI-Powered Intelligence (Powered by DeepSeek) 🚀
Membawa operasional restoran ke level otonom dengan integrasi LLM tercanggih untuk keputusan bisnis yang lebih tajam.
- **🤖 AI Daily Suggestion [READY]**: Widget pintar di dashboard yang memberikan saran strategi bisnis dan peringatan stok kritis secara otomatis setiap hari. Menganalisis data penjualan 30 hari terakhir dan status inventaris secara mendalam.
- **💬 AI Business Assistant ("Tanya Bos") [READY]**: Konsultasi performa bisnis via chat bahasa natural dengan **Conversation Memory**.
    - **Premium UI/UX**: Interface modern dengan efek *Glassmorphism*, input dinamis, dan responsivitas penuh untuk Mobile & Desktop.
    - **Live Context**: AI memiliki akses ke data Penjualan, Menu Terlaris, dan Stok Rendah (realtime).
- **💌 AI Smart Message (CRM) [READY]**: Generasi draf WhatsApp yang sangat personal untuk member dengan kecerdasan kontekstual.
    - **Logic Aggregator**: AI menganalisis menu favorit pelanggan, level loyalty, dan riwayat kunjungan terakhir.
    - **Promo Awareness**: Otomatis mendeteksi **Kode Promo Aktif** di database dan menyelipkannya secara natural dalam draf pesan.
    - **Configurable Prompt**: Owner bisa melatih AI dengan gaya bahasa sendiri melalui menu Pengaturan.
- **📉 Smart Inventory Forecasting [ROADMAP]**: Prediksi stok bahan baku mingguan untuk meminimalisir *waste* menggunakan pola belanja historis.
- **🍳 AI Menu Engineering [ROADMAP]**: Saran optimasi harga dan deskripsi menu yang menggugah selera untuk meningkatkan penjualan.
- **📲 Integrated WhatsApp Gateway (Native Chat) [ROADMAP]**: Membangun gateway internal (via Baileys/WAHA) agar Bos bisa melakukan chat dua arah langsung di dalam aplikasi tanpa pindah tab.

### 📊 Financial Intelligence
- **Realtime COGS Calculation**: Menghitung HPP setiap detik untuk akurasi laba bersih.
- **Fiscal Planning (Tax Control)**: Fitur "Target Omzet" untuk membantu perencanaan pelaporan pajak yang fleksibel.

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

3.  **Reservasi & DP**:
    1.  **Input**: Catat reservasi & menu pre-order tamu di menu Reservasi.
    2.  **Bayar DP**: Klik **"Bayar DP"** untuk mencatat uang muka otomatis.
    3.  **WhatsApp**: Gunakan tombol konfirmasi untuk kirim detail reservasi ke tamu.
    4.  **Check-in**: Klik **"Proses ke Kasir"** saat tamu datang untuk potong DP otomatis di POS.

4.  **AI Strategy**:
    1.  Pantau widget **AI Daily Suggestion** di Dashboard untuk insight harian.
    2.  Buka menu **"Tanya Bos"** untuk analisis data mendalam via chat.

---

### ⚙️ Requirements & Setup
- **PHP 8.2+** & **MySQL 8+**
- **DeepSeek API Key**: Masukkan `DEEPSEEK_API_KEY` di file `.env` untuk mengaktifkan fitur AI.
- **Node.js & NPM**: Untuk kompilasi assets (Vite).
- **Composer**: Untuk manajemen dependensi PHP.

---

📍 **Developed by Evan Helga** — *Crafting Digital Excellence for F&B Business.*

<div align="center">

# 🍽️ Intelligent Restaurant Ecosystem 🚀
### Next-Gen Restaurant OS — Powered by Adaptive AI & Native WhatsApp Gateway

**Intelligent Restaurant Ecosystem** adalah **Enterprise-Grade F&B Ecosystem** yang dirancang untuk mendefinisikan ulang standar operasional restoran modern. Mengintegrasikan teknologi **Laravel 11**, **Filament 4**, dan **Hybrid AI Intelligence**, sistem ini menghadirkan perpaduan sempurna antara **High-Speed Point of Sale**, **Native WhatsApp Gateway**, dan **Autonomous Business Intelligence**.

Ini bukan sekadar alat pencatat transaksi; ini adalah pusat komando digital yang memberdayakan outlet Anda dengan **AI-Driven CRM**, **Real-time Kitchen Orchestration (KDS)**, hingga **Automated Fiscal & P&L Analysis**. Sistem ini memastikan setiap detik operasional Anda optimal, setiap pelanggan merasa dihargai secara personal melalui **Hyper-Personalized WhatsApp**, dan setiap keputusan bisnis didukung oleh kecerdasan buatan yang akurat.

</div>

---

## 📘 Fundamental Sistem

### Sistem Akuntansi: Accrual Method
Sistem ini menggunakan **Accrual Accounting** untuk perhitungan HPP (Harga Pokok Penjualan / COGS), bukan Cash Basis.

**Prinsip Dasar:**
- **Pembelian Bahan Baku ≠ Expense** - Pembelian dicatat sebagai **Aset (Stock Value)**, bukan biaya langsung.
- **COGS/HPP dicatat saat penjualan** - Biaya bahan baku baru dikurangi dari profit saat menu terjual.
- **Matching Principle** - Biaya di-match dengan revenue yang dihasilkan.

**Contoh:**
```
Hari 1: Beli Beras 10kg @ Rp 15,000/kg = Rp 150,000
├─ Cash: -Rp 150,000
├─ Stock Value (Aset): +Rp 150,000
├─ COGS: Rp 0 (belum ada penjualan)
└─ Profit: Rp 0 (tidak berubah)

Hari 2: Jual Nasi Goreng 5 porsi @ Rp 25,000
├─ Revenue: +Rp 125,000
├─ COGS: -Rp 40,000 (bahan baku terpakai)
├─ Stock Value: -Rp 40,000 (beras berkurang 1kg)
└─ Gross Profit: Rp 85,000 (Revenue - COGS)
```

### Sistem Inventory: Real-time Stock Tracking
Setiap transaksi otomatis membuat `StockMovement` dan mengupdate stok secara real-time:
- **Purchase** → Stock bertambah, Stock Value naik
- **POS Sale** → Stock berkurang (via recipe), COGS dicatat
- **Stock Opname** → Koreksi variance fisik vs sistem
- **Wastage** → Stock berkurang, masuk Expense

### Recipe System: Automatic Ingredient Deduction
Menu dengan recipe otomatis mengurangi stok bahan baku saat terjual:
- **Unit Conversion** - Sistem otomatis convert unit (misal: recipe pakai gram, stock dalam kg)
- **Multi-Channel** - Berlaku untuk POS, Waiter App, dan Self-Order
- **HPP Calculation** - HPP dihitung dari total harga bahan baku dalam recipe

> **⚠️ Penting:** Pastikan setiap menu `produced` memiliki recipe yang lengkap agar HPP dan stock deduction akurat.

### Recipe Stock Validation: Prevent Negative Stock
Sistem validasi stok bahan baku yang mencegah overselling dan negative stock:
- **Real-time Availability Check** - Cek ketersediaan bahan baku sebelum item ditambahkan ke cart
- **Draft Sales Consideration** - Memperhitungkan qty yang sudah di draft sales (belum dibayar)
- **Cross-Channel Sync** - Auto-refresh setiap 5 detik untuk sync antar POS, Waiter, dan Self-Order
- **Visual Indicators** - Badge "Tersedia: X porsi" di POS/Waiter, overlay "HABIS" saat stock habis
- **Cart Increment Protection** - Validasi saat user increment qty di cart
- **Toast Notifications** - Notifikasi real-time via Livewire events (tanpa page reload)

**Fitur per Channel:**
| Feature | POS | Waiter App | Self-Order |
|---------|-----|------------|------------|
| Availability Badge | ✅ "Tersedia: X porsi" | ✅ "X porsi" | ❌ (validation only) |
| Stock Validation | ✅ | ✅ | ✅ |
| Cart Increment Check | ✅ | ✅ | ✅ |
| Auto-disable when out | ✅ | ✅ | ✅ |
| "HABIS" Overlay | ✅ | ✅ | ✅ |
| Toast Notifications | ✅ (Filament) | ✅ (Alpine.js) | ✅ (Alpine.js) |
| Auto-refresh (Polling) | ✅ 5s | ✅ 5s | ✅ 5s |

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
- **🔄 Dynamic Cash Session Orchestration**: Perhitungan `Expected Cash` (Uang Seharusnya) dilakukan secara dinamis dan real-time dengan menjumlahkan Penjualan (Cash) serta mengurangi Biaya (Expenses) dan Pembelian (Purchases) yang menggunakan dana kasir.

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
- **🖨️ Pre-Order Printing System**:
  - **Print Before Arrival**: Cetak order menu pre-order ke divisi Dapur/Bar **sebelum** pelanggan datang untuk persiapan yang lebih matang.
  - **Smart Division Routing**: Order otomatis dikelompokkan dan dikirim ke printer yang sesuai berdasarkan tipe produk (Produced → Dapur, Bar → Bar, General → Kasir).
  - **Unified Print Infrastructure**: Menggunakan sistem print yang sama dengan POS (mendukung webhook untuk hosting dan direct print untuk lokal).
  - **Reservation Context**: Struk order mencantumkan informasi khusus reservasi (Nama pelanggan, tanggal & waktu reservasi) untuk identifikasi yang jelas.
- **🔄 Smart Pre-Order to Sales (Flexible)**:
  - **Flexible Item Management**: Tambahkan menu pre-order dengan harga fleksibel (bisa diedit manual) dan hitungan otomatis.
  - **Instant Conversion upon Arrival**: Saat pelanggan datang, konversi seluruh data reservasi beserta item pre-order menjadi transaksi penjualan (Sales) di POS dalam satu klik.
  - **Automatic DP Deduction**: Sistem otomatis mendeteksi uang muka dan menambahkannya sebagai item pengurang (minus) di transaksi kasir.
  - **Double-Transaction Protection**: Tombol konversi otomatis hilang setelah digunakan (ketika status berubah menjadi `Seated`), mencegah duplikasi data penjualan.
- **Snapshot Integrity**: Nama produk disnapshot saat konversi untuk memastikan data historis tetap akurat meskipun produk asli dihapus atau diubah.

### 📱 QR Self-Order Menu (Table Ordering) [PRO] 🆕
Sistem pemesanan mandiri yang elegan untuk pelanggan langsung dari meja mereka.
- **Scan to Order**: Pelanggan scan QR di meja -> Pilih Menu -> Pesanan terkirim otomatis ke KDS/POS linked dengan sesi kasir aktif.
- **✨ Premium QR Generator**: Sistem cerdas untuk generate kartu QR meja dengan desain estetik, menyertakan Nama Resto & Sosial Media otomatis.
- **�️ One-Click Printing**: Fitur cetak QR langsung dari dashboard ke printer atau simpan sebagai PDF dengan layout kartu yang sudah dioptimasi.
- **Glassmorphism UI**: Antarmuka menu mobile yang modern dengan navigasi kategori yang halus dan animasi transisi.
- **🚀 PWA Enabled**: Dapat diinstal sebagai aplikasi smartphone untuk kemudahan akses pelanggan reguler.
- **🤖 Automated AI WhatsApp Broadcast**: Jika pelanggan mengisi nomor WhatsApp saat checkout, sistem akan mengirimkan pesan konfirmasi **otomatis** yang drafnya digenerate secara cerdas oleh AI (DeepSeek).
- **AI-Powered Notifications**: Notifikasi WhatsApp otomatis yang *personalized* menggunakan AI setelah pesanan diterima.
- **Pro Module**: Fitur ini adalah modul berbayar yang dilindungi lisensi (`EnsureSelfOrderEnabled` Middleware).
- **📖 Dokumentasi**: Lihat [Panduan Self-Order](SYSTEM_GUIDE.md#2-qr-self-order-menu-table-ordering) untuk detail fitur.

### 🤵 Waiter Digital Order Panel 🆕
Pusat komando mobile untuk waiter guna mempercepat pelayanan dan meningkatkan omzet.
- **High-Speed Ordering**: Input pesanan pelanggan secara instan via smartphone/tablet dengan sinkronisasi realtime ke kasir.
- **� Featured Upselling Section**: AI secara cerdas menampilkan menu favorit atau menu dengan margin tinggi di bagian atas untuk memudahkan waiter menyarankan menu terbaik (Upselling).
- **🚀 Mobile PWA App**: Dapat diinstal di HP waiter sebagai aplikasi native (Progressive Web App), memastikan performa tinggi dan akses satu-klik dari home screen.
- **🔄 Flexible Table Mapping**: Mendukung penginputan nomor meja secara fleksibel sesuai kondisi lapangan.
- **🤖 Automated AI WhatsApp Broadcast**: Sama seperti *Self-Order*, jika nomor WhatsApp diisi oleh Waiter, sistem akan otomatis mengirimkan pesan konfirmasi ke nomor pelanggan dengan gaya bahasa natural dari AI.
- **🖨️ Automated Division Printing**: Setiap pesanan otomatis mencantumkan detail krusial (**Nomor Meja**, **Tipe Order**, **Notes**) dan dikirim ke printer divisi terkait (Dapur/Bar).
- **📖 Dokumentasi**: Lihat [Panduan Waiter App](SYSTEM_GUIDE.md#1-waiter-digital-order-panel) untuk alur kerja lengkap.

### ‍🍳 Intelligent Kitchen Display System (KDS) & Printing
Orkestrasi dapur digital tanpa kertas dengan sistem penyaringan cerdas.
- **Department Routing & Auto-Print**: Memisahkan otomatis pesanan Bar (Minuman) dan Dapur (Makanan). Order otomatis terwujud dalam bentuk **Print-Out di masing-masing divisi** begitu pesanan disimpan oleh kasir.
- **🔍 Detailed Print-Outs**: Setiap struk pesanan (Kitchen/Bar/General) kini menyertakan informasi lengkap: **Nomor Meja**, **Tipe Order**, dan **Catatan Khusus (Notes)** per item.
- **🚫 System Item Filtering**: Item finansial seperti "Down Payment (DP)" secara cerdas difilter agar tidak muncul di KDS maupun struk pesanan dapur/bar.
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
- 📉 **Forecasting Stok (AI) [PRO] [READY]**:
    - **Hybrid Intelligence (Daily Focus + Weekly Plan) [NEW]**: Sistem prediksi ganda yang cerdas:
        - **Fokus Besok (Daily High-Precision)**: Menggunakan algoritma **"Apple-to-Apple Comparison"** (misal: membandingkan rata-rata hari Senin vs Senin-Senin sebelumnya) untuk mendeteksi lonjakan kebutuhan spesifik harian dengan akurasi tinggi.
        - **Rencana Mingguan**: Proyeksi belanja standar 7 hari ke depan untuk stok jangka panjang.
    - **Predictive Restocking**: AI memprediksi kebutuhan bahan baku secara holistik berdasarkan tren historis.
    - **Recipe-Aware Analytics**: Otomatis menghitung kebutuhan *raw material* (misal: biji kopi) berdasarkan penjualan menu (misal: Latte) menggunakan data resep dengan **Unit Conversion Logic** (Gram/Kg/Pcs).
    - **Persistence & Speed**: Hasil analisis disimpan dalam cache selama 24 jam untuk akses instan tanpa perlu generate ulang setiap kali halaman dibuka.
    - **Urgency Insights**: Memberikan label urgensi (High/Medium/Low) dan alasan logis di balik setiap saran restock.
    - **📄 Professional PDF Export**: Generate laporan restock resmi dalam format PDF yang rapi, lengkap dengan tabel rekomendasi, tingkat urgensi, dan alasan logis AI.
- 🍳 **Menu Engineering (AI) (Profit & Popularity Matrix) [READY]**:
    - **Strategic Classification**: AI mengklasifikasikan menu ke dalam 4 kategori strategis: **Unit Unggulan** (Stars), **Unit Andalan** (Plowhorses), **Unit Potensial** (Puzzles), dan **Unit Kurang Berkembang** (Dogs).
    - **Ultra-Accurate COGS**: Perhitungan HPP super akurat yang mendukung **Unit Conversion Rate** (misal: Harga beli sak/karung dikonversi otomatis ke gram pada resep).
    - **🧙‍♂️ One-Click HPP Calibration (Magic Button)**: Fitur "Hitung Ulang Semua HPP" yang secara otomatis memperbaiki harga modal bahan baku berdasarkan pembelian terakhir dan mengkalkulasi ulang HPP seluruh resep menu secara massal. Solusi ampuh jika terjadi lonjakan harga bahan baku.
    - **AI Strategic Advice**: Dapatkan saran taktis langsung dari AI (misal: saran kenaikan harga 10%, pengecilkan porsi, atau rekomendasi promosi khusus).
    - **Premium Matrix UI**: Dashboard visual dengan bar popularitas, badge kategori indigo, dan insight box gradient yang elegan.
    - **📄 Integrated PDF Report**: Ekspor hasil analisis ke PDF dengan layout profesional yang siap dipresentasikan di rapat manajemen.

### 🍳 Kitchen Production (Prepared Stock Management) [NEW]
Sistem pelacakan stok untuk makanan siap saji atau *semi-finished goods* (misal: Ayam ungkep, Nasi Putih).
- **📝 Catat Masak (Production Record)**:
    - **Concept**: Konversi bahan baku mentah menjadi stok jadi.
    - **Action**: Staff mencatat output produksi, sistem otomatis potong bahan baku.
    - **Contoh Real**:
        > **"Masak Nasi Putih"** (Output: 50 Porsi)
        > - **Otomatis Kurangi (Raw)**: Beras 5 Kg, Air Galon 2 Liter.
        > - **Otomatis Tambah (Prepared)**: Nasi Putih +50 Porsi.
    - **Inventory Accuracy**: Menjaga akurasi stok gudang secara real-time tanpa menunggu penjualan terjadi.
    - **Smart Stock Info**: Form produksi menampilkan estimasi maksimal produksi berdasarkan ketersediaan bahan baku.
    - **Stock Validation**: Sistem otomatis mencegah produksi jika bahan baku tidak mencukupi dengan notifikasi yang jelas.
    - **Production History**: Semua aktivitas produksi tercatat lengkap dengan user, tanggal, dan catatan.
- **🔄 Daily Stock Reset**:
    - **Fitur Khusus**: Tombol "Reset Stock Harian" untuk produk tipe *fresh daily* (misal: Nasi, Masakan Padang).
    - **Logic**: Menghapus sisa *prepared stock* di akhir hari (dicatat sebagai waste) tanpa mengembalikan bahan baku. Memastikan stok hari besok dimulai dari 0 agar produksi baru tercatat rapi.
- **📊 Stock Movement Tracking**: Setiap produksi tercatat di `Stock Movements` dengan referensi polimorfik untuk audit trail yang lengkap.


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
- 📈 **Laporan Keuangan (Financial Report) 2.0**: Dashboard laba bersih yang menghitung margin kotor secara akurat dengan memisahkan **Biaya Operasional** (Listrik, Sewa) dan **Beban Pokok Penjualan/HPP** (Belanja Stok & Estimasi Resep).
    - **Period Comparison**: Fitur perbandingan performa (Omzet, HPP, Expenses, Net Profit) dengan periode sebelumnya (Month-to-Month) beserta indikator pertumbuhan (Growth %) yang ditampilkan secara real-time di setiap metrik.
    - **Stock Valuation Analysis**: Analisis valuasi stok inventaris dengan breakdown per kategori (Retail & Bahan Baku), menampilkan total nilai aset yang tersimpan di gudang.
    - **Interactive Trend Chart**: Visualisasi grafik tren keuangan (Revenue vs Expenses) dengan filter periode yang responsif.
    - **Enterprise PDF Export**: Ekspor laporan keuangan profesional dalam format PDF yang mencakup semua metrik utama, breakdown biaya operasional, valuasi stok, dan ringkasan eksekutif.
- 📦 **Forecasting Stok (AI)**: Analisis kebutuhan bahan baku 7 hari ke depan berbasis tren historis.
- 🍽️ **Analisis Menu (AI)**: Klasifikasi profitabilitas menu (Stars, Plowhorses, etc.) dengan saran strategis AI.
- 🔍 **Granular Cost Analysis**: Breakdown belanja stok **per produk** dan rincian biaya operasional **per kategori** dengan visualisasi progress bar untuk kontrol biaya yang ketat.
- 📝 **Smart Stock Movement (Adjustment) 🆕**: Form penyesuaian stok yang cerdas dengan **Dynamic Unit Suffix** untuk mencegah kesalahan input operator.
    - **Auto Unit Display**: Suffix unit otomatis muncul di input jumlah saat produk dipilih (misal: `[1500] g`, `[2] Kg`).
    - **Base Unit Enforcement**: Sistem memastikan stock opname selalu menggunakan unit dasar untuk konsistensi data.
    - **Reactive UX**: Unit suffix update otomatis saat produk berubah tanpa reload halaman.
- 📋 **Smart Stock Opname (Audit) 🆕**: Interface stock opname cerdas untuk audit inventaris menyeluruh.
    - **Multi-Type Support**: Audit **Bahan Baku** (Raw) dan **Stok Masakan** (Prepared) dalam satu halaman.
    - **Smart Filtering**: Produk masakan (Produced/Bar) hanya muncul jika fitur **Stock Alert** diaktifkan, memfilter menu non-stok.
    - **Auto-Column Logic**: Otomatis mendeteksi kolom yang harus diaudit (`stock` vs `prepared_stock`) berdasarkan tipe produk.
    - **Real-time Variance**: Kalkulasi selisih fisik vs sistem dan estimasi kerugian (Loss Value) secara real-time.
    - **One-Click Adjustment**: Tombol simpan massal yang aman dengan konfirmasi variance.
- 🤝 **Loyalty Automation (Re-engagement) 🆕**: Sistem otomatis bertenaga AI untuk mendekati kembali pelanggan yang sudah lama tidak berkunjung.
    - **Soft-Greeting Strategy**: AI dilatih untuk menyapa secara emosional (menanyakan kabar & kesehatan), menghindari kesan *hard-selling* yang mengganggu.
    - **Auto-detect Inactive Members**: Filter otomatis member dengan kunjungan terakhir >30 hari.
    - **AI Assistant Persona**: Pesan diatur sesuai profil asisten digital (Asisten Nama, Gaya Bahasa) di Settings.
    - **🚀 One-Click Manual Re-engage**: Tombol aksi langsung di dashboard CRM untuk mentrigger jangkauan AI secara manual per pelanggan.
    - **Smart Scheduling**: Eksekusi massal otomatis setiap Senin pagi via scheduler.
    - **Anti-spam Logic**: Cooldown followup selama 7 hari untuk menjaga kenyamanan pelanggan.

### 🛡️ Role-Based Access Control (RBAC) [NEW]
Sistem keamanan bertingkat yang ketat. **Tombol Delete** secara global **DISEMBUNYIKAN** (Hidden) dari tampilan untuk seluruh role kecuali **Super Admin** & **Admin**.

| Role | Deskripsi & Akses Utama |
| :--- | :--- |
| **Super Admin** | **Akses Penuh** & Root Privileges. Satu-satunya yang bisa Mengelola User System, Backup, & Audit Log. |
| **Admin Operasional** | Kelola Produk, Stok, Karyawan. **Bisa Delete Data**. Tidak bisa akses User System & Settings Inti. |
| **Accountant** | Akses Penuh Keuangan (Laba/Rugi, Payroll). **Read-only** ke Stok & Transaksi. **NO DELETE** (Tombol Hidden). |
| **Inventory** | Full Akses Stok & Pembelian. **Harga Jual & Profit Hidden** (Blind Access). **NO DELETE** (Tombol Hidden). |
| **Kitchen** | Akses Khusus KDS & Lapor Stok. **Tidak bisa lihat** Harga/Customer. **NO DELETE**. |
| **Cashier** | Akses Khusus POS & CRM Member. Edit Terbatas. **Tidak bisa lihat** Laporan/Profit. **NO DELETE** (Void Transaksi Hidden). |
| **Waiter** | Input Order Only. View KDS Status. **No Access** to Data Member/Finance. **NO DELETE**. |

### 🧩 Modular Architecture (On-Demand Features)
Fitur canggih yang bisa disesuaikan dengan kebutuhan outlet.
- **Toggleable Modules**: Bebas aktifkan/matikan fitur sesuai skala bisnis Anda, mulai dari **CRM (Loyalty)**, **HRM (Payroll)**, **KDS (Kitchen)**, **WhatsApp Center**, **Fiscal Planning**, hingga modul cerdas **AI Forecasting** dan **AI Menu Engineering**.
- **Centralized Settings**: Panel pengaturan terpusat untuk semua konfigurasi modul.

---

## 🗺️ Roadmap Strategis 2026: Strategic Expansion

Rencana pengembangan fitur masa depan untuk memaksimalkan ROI dan efisiensi operasional.

### 🗺️ Visual Table Management (Interactive Floor Plan) [MEDIUM PRIORITY]
- **Tampilan Denah Resto**: Editor "Drag & Drop" untuk mengatur posisi meja sesuai denah asli.
- **Live Status Indicator**: Indikator visual real-time (Meja Kosong = Hijau, Terisi = Merah, Kotor = Kuning).
- **Impact**: Meningkatkan *Look & Feel* aplikasi menjadi premium dan memudahkan waiter memantau meja.

### � Real-time Dashboard 2.0 (Live Analytics) [HIGH PRIORITY]
- **Live Widgets**: Menambahkan widget "Live Sales Tick", "Top Items Today", dan "Hourly Heatmap" (Jam sibuk).
- **Owner Mode**: Mode tampilan ringkas khusus owner untuk pantau omzet dari HP secara real-time.

### 💳 Intelligent Payment Gateway (Auto-Settlement)
- **Dynamic QRIS**: Integrasi Midtrans/Xendit untuk pembayaran otomatis di Self-Order.
- **Auto-Fulfillment**: Pesanan otomatis berubah "Terbayar" dan KDS berbunyi sesaat setelah dana diterima.

---

## � Akses Aplikasi Mobile (PWA)

Sistem ini dirancang dengan pendekatan *Mobile-First*. Anda bisa mengakses halaman-halaman berikut melalui Google Chrome di smartphone dan pilih **"Tambahkan ke Layar Utama" (Add to Home Screen)** untuk menginstalnya sebagai aplikasi native (PWA).

| Komponen | URL Akses | Deskripsi |
| :--- | :--- | :--- |
| **QR Self-Order** | `/scan/{meja-slug}` | Scan QR di meja untuk akses menu mandiri (PWA Pelanggan). |
| **Waiter App** | `/waiter/order` | Pesanan instan di genggaman Waiter (PWA Waiter). |
| **Kiosk Absensi** | `/kiosk` | Panel absensi wajah/biometrik di pintu masuk (PWA Kiosk). |

> [!TIP]
> **PWA Installation**: Setelah membuka URL di atas via Mobile Chrome/Safari, klik menu browser lalu pilih **"Install App"** agar aplikasi muncul di menu HP dengan performa yang lebih kencang dan stabil.

---

## �🛠️ Stack Teknologi (Enterprise Grade)

Dibangun di atas pondasi teknologi paling modern dan stabil di tahun 2025.

- **Framework**: [Laravel 11](https://laravel.com)
- **Admin & UI**: [FilamentPHP v4](https://filamentphp.com)
- **Engine**: [Livewire 3](https://livewire.laravel.com)
- **Styling**: [TailwindCSS v4](https://tailwindcss.com) & Vanilla CSS
- **Database**: MySQL 8 / MariaDB
- **State Management**: Alpine.js v3
- **Local Agent**: [Electron Bridge](https://electronjs.org) (Hardware Bridge)

---

## 🚀 Deployment & Background Services

Agar orkestrasi WhatsApp dan AI berjalan secara *real-time*, service di bawah ini **WAJIB** dijalankan di latar belakang (background).

### 1. Queue & Scheduler Setup

#### **A. Menggunakan PM2 (Windows/Desktop/VPS)**
Metode paling stabil dan mudah untuk sistem manajemen service dalam satu panel.
```bash
# 1. Jalankan WhatsApp Gateway
cd wa-gateway && pm2 start index.js --name "wa-gateway"

# 2. Jalankan Laravel Scheduler (Otomasi AI & Laporan)
pm2 start "php artisan schedule:work" --name "resto-scheduler"

# 3. Jalankan Worker Laravel (Pengiriman Notifikasi)
pm2 start "php artisan queue:work" --name "resto-worker"
```

#### **B. PM2 Windows Autostart**
Agar semua proses langsung jalan otomatis saat Windows menyala/restart:
1. Jalankan CMD/PowerShell sebagai **Administrator**.
2. Install utilitas startup: `npm install pm2-windows-startup -g`.
3. Pasang utilitas: `pm2-startup install`.
4. Simpan konfigurasi aktif: `pm2 save`.

#### **C. VPS / Shared Hosting (Standard Cron)**
Tambahkan di Cron Jobs (`crontab -e`):
```bash
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

> [!TIP]
> **Optimasi Windows Dev:** Gunakan PM2 untuk mematikan beban memori saat tidak digunakan dengan perintah `pm2 stop all`.

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
- **PHP 8.2+** (Extension: BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML, GD)
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

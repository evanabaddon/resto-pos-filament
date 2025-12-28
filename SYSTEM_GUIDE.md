# 📖 Panduan Teknis & Operasional: Waiter & Self-Order

Dokumen ini menjelaskan detail teknis, alur kerja, dan konfigurasi untuk modul **Waiter App** dan **QR Self-Order**.

---

## 🤵 1. Waiter Digital Order Panel
Modul ini dirancang untuk perangkat mobile yang digunakan oleh staff pelayan (Waiter) untuk mengambil pesanan langsung di meja pelanggan.

### Alur Kerja (Workflow)
1. **Login**: Waiter harus login menggunakan akun dengan role `Waiter` atau `Admin`.
2. **Akses**: Melalui URL `/waiter/order` (Optimasi PWA).
3. **Penyusunan Pesanan**: 
   - Pilih menu dan masukkan jumlah.
   - Tambahkan catatan (*notes*) per item (misal: "Tanpa sambal").
   - Data keranjang disimpan secara instan di sisi server (Base on User ID).
4. **Checkout**:
   - Masukkan **Nomor Meja** (Fleksibel).
   - Masukkan nama pelanggan dan nomor WhatsApp (Opsional).
   - Sistem akan mencari apakah pelanggan tersebut adalah Member lama berdasarkan nomor WA. Jika baru, sistem akan meregistrasi Member secara otomatis.
5. **Finalisasi**:
   - Pesanan disimpan sebagai transaksi `Draft`.
   - **Kitchen Printing**: Struk otomatis terkirim ke Dapur/Bar sesuai kategori produk.
   - **AI Notification**: Jika nomor WA diisi, pelanggan menerima pesan konfirmasi otomatis yang dibuat oleh AI.

### Detail Teknis
- **Routes**: `routes/web.php` (prefix: `/waiter`).
- **Livewire Components**: `App\Livewire\WaiterOrder\*`.
- **Keamanan**: Menggunakan middleware `Authenticate`. Waiter tidak memiliki akses ke dashboard admin utama.

---

## 📱 2. QR Self-Order Menu (Table Ordering)
Modul mandiri di mana pelanggan memindai kode QR untuk memesan tanpa bantuan pelayan.

### Alur Kerja (Workflow)
1. **QR Scan**: Pelanggan memindai QR di meja. URL format: `/scan/{slug_meja}`.
2. **Session Start**: Sistem mendeteksi `table_id` dari URL dan menyimpannya di session pelanggan.
3. **Self-Browsing**: Pelanggan memilih menu melalui antarmuka *Glassmorphism* yang responsif.
4. **Checkout**:
   - Pelanggan wajib mengisi **Nama**.
   - Nomor WhatsApp digunakan untuk registrasi Member otomatis dan pengiriman struk digital.
5. **Payment Routing**:
   - Pesanan dikirim ke sistem sebagai `Draft`.
   - Kasir akan menerima notifikasi *real-time* di dashboard POS untuk memproses pembayaran saat pelanggan selesai makan.
6. **AI Orchestration**:
   - **DeepSeek AI** menghasilkan draf konfirmasi pesanan yang unik.
   - Pesan dikirim secara *native* melalui WhatsApp Gateway.

### Detail Teknis
- **Routes**: `routes/web.php` (middleware: `self-order.enabled`).
- **Livewire Components**: `App\Livewire\SelfOrder\*`.
- **Lisensi**: Memerlukan pengaturan `enable_self_order` di **General Settings**.
- **QR Generation**: Menu **Table Management** di dashboard admin memungkinkan Admin mencetak kartu QR estetik per meja.

---

## ⚙️ Konfigurasi & Setup
Untuk memastikan kedua fitur ini berjalan maksimal, pastikan:
1. **WhatsApp Gateway**: Service `wa-gateway` (Node.js) harus berjalan (`pm2 start index.js`).
2. **AI Provider**: API Key (DeepSeek/OpenRouter) sudah terpasang di menu **Settings > AI Configuration**.
3. **Cash Session**: Salah satu kasir harus memiliki **Cash Session** yang sedang aktif (`open`), karena pesanan Waiter/Self-Order akan otomatis di-assign ke sesi tersebut agar bisa diproses pembayarannya.
4. **Division Printer**: Pastikan produk sudah memiliki kategori yang terhubung dengan printer (Kitchen/Bar).

---

> [!NOTE]
> Kedua modul ini menggunakan **PWA (Progressive Web App)**. Untuk pengalaman terbaik, pilih "Add to Home Screen" di browser smartphone Anda.

---

## 📉 3. Smart Inventory Forecasting (AI Intelligence)
Modul cerdas ini menggunakan Artificial Intelligence (DeepSeek) untuk memprediksi kebutuhan stok bahan baku Anda, mencegah over-stocking maupun under-stocking.

### Fitur Utama: Hybrid Forecasting
Sistem ini menggunakan dua layer analisis sekaligus dalam satu klik:

1.  **🔥 Fokus Besok (High-Precision Daily Focus)**:
    - **Algoritma**: Menggunakan metode *"Apple-to-Apple Comparison"*. Sistem tidak hanya melihat rata-rata total, tapi spesifik membandingkan **Hari yang Sama** (misal: memprediksi kebutuhan Senin besok dengan melihat rata-rata penggunaan di 4 hari Senin terakhir).
    - **Tujuan**: Mendeteksi pola lonjakan harian spesifik (misal: "Sabtu Ramai") yang sering terlewat oleh rata-rata mingguan biasa.
    - **Tampilan**: Muncul di bagian paling atas dalam kotak oranye (Highlight) jika ada item yang urgensinya tinggi untuk besok.

2.  **📅 Rencana Mingguan (7 Days Projection)**:
    - **Algoritma**: Menganalisis tren total 30 hari terakhir untuk memproyeksikan kebutuhan belanja stok aman selama satu minggu ke depan.
    - **Output**: Daftar rekomendasi *Restock Qty* lengkap dengan alasan logis (misal: "Tren naik 20% minggu ini").

### Alur Kerja (Workflow)
1.  **Akses**: Menu sidebar **Smart Inventory**.
2.  **Generate**: Klik tombol **"Generate Analisis Sekarang"**. AI akan memproses data penjualan, resep, dan sisa stok Anda (Proses 5-10 detik).
3.  **Review**:
    - Perhatikan bagian **"Fokus Besok"** untuk tindakan segera.
    - Gunakan **"Rencana Mingguan"** untuk membuat daftar belanja ke supplier.
4.  **Export**: Klik tombol **Export PDF** untuk mencetak laporan resmi yang bisa dibawa belanja.

### Requirements
- **Data**: Membutuhkan minimal data penjualan 3-7 hari agar AI bisa mulai melihat pola.
- **Recipe**: Pastikan menu Anda sudah memiliki *Recipe* yang benar agar pengurangan stok bahan baku terdeteksi.

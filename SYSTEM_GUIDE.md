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

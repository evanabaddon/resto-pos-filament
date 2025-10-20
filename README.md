# 🍽️ Resto POS System (Laravel 11 + Filament 4)

Aplikasi kasir dan manajemen inventori untuk restoran dengan fitur lengkap — termasuk penjualan berbasis resep (COGS), pembelian bahan baku, pengeluaran operasional, serta sistem stok otomatis dengan riwayat pergerakan stok yang transparan.

---

## 🚀 Fitur Utama

### 🧾 Penjualan (Sales)
- Menangani transaksi penjualan produk.
- Mendukung **produk jadi langsung** maupun **produk berbasis resep (COGS)**.
- Fitur **Order Pending / Draft** untuk pesanan yang belum dibayar.
- Pembayaran ganda (split payment) dan berbagai metode pembayaran.
- Setelah pembayaran diterima → stok berkurang otomatis.

### 🍳 Resep / COGS (Recipe)
- Setiap produk jadi bisa memiliki **resep bahan baku**.
- Saat produk terjual, stok bahan baku dikurangi otomatis.
- Perhitungan **HPP (harga pokok produksi)** otomatis dari resep.
- Dapat digunakan untuk analisa profit per produk.

### 📦 Inventori (Inventory)
- Dua kategori barang:
  - **Bahan Baku (Raw Material)**
  - **Produk Jadi (Finished Goods)**
- Tiap pergerakan stok tercatat dalam tabel **stock_movements** dengan alasan yang jelas:
  - `purchase` → bertambah
  - `sale` → berkurang
  - `adjustment` → bisa bertambah / berkurang
  - `production` → jika dari proses produksi / resep

### ⚙️ Unit & Konversi (Units)
- Sistem mendukung **multi-unit** dan **konversi otomatis** antar unit.
  - Contoh:
    - Pembelian: Gula 1 kg
    - Resep: Gula 10 gram
    - Sistem otomatis mengonversi 1 kg = 1000 gram.
- Setiap produk menyimpan **unit dasar (base unit)**, misalnya gram.
- Setiap pembelian atau resep dapat menggunakan unit berbeda namun tetap konsisten.

### 🛒 Pembelian (Purchases)
- Mencatat pembelian dari supplier.
- Dapat menambahkan item pembelian beserta harga, unit, dan kuantitas.
- Saat status “Diterima”, stok otomatis bertambah (tercatat di stock_movements).
- Menyimpan informasi supplier dan total pembelian.

### 💸 Pengeluaran (Expenses)
- Mencatat pengeluaran operasional (gaji, listrik, sewa, bahan non-stok).
- Tidak mempengaruhi stok, tetapi masuk ke laporan keuangan.

### 🧮 Stock Adjustment
- Untuk koreksi stok manual (barang rusak, kadaluwarsa, atau selisih fisik).
- Bisa **tambah** atau **kurangi** stok.
- Setiap perubahan otomatis tercatat di `stock_movements` dengan alasan “adjustment”.

### 📊 Laporan (Reports)
- Laporan penjualan (harian, mingguan, bulanan).
- Laporan pembelian & pengeluaran.
- Laporan pergerakan stok (stock card).
- Laporan pemakaian bahan berdasarkan resep.
- Laporan laba rugi (opsional).

---

## 🧩 Struktur Database (Ringkasan)

| Tabel | Deskripsi |
|-------|------------|
| **products** | Data semua produk (bahan & jadi) |
| **units** | Data unit dasar dan konversi antar-unit |
| **recipes** | Komposisi bahan per produk |
| **sales** | Data transaksi penjualan |
| **sale_items** | Detail item pada penjualan |
| **purchases** | Data pembelian dari supplier |
| **purchase_items** | Detail item per pembelian |
| **expenses** | Catatan pengeluaran |
| **stock_adjustments** | Penyesuaian stok manual |
| **stock_movements** | Log semua pergerakan stok |

---

## 🔄 Alur Pergerakan Stok

1. **Purchase diterima**
   - Tambah stok produk sesuai jumlah pembelian.
   - Catat `stock_movements` dengan type `increase` dan reason `purchase`.

2. **Sale dibayar**
   - Kurangi stok produk (atau bahan jika produk punya resep).
   - Catat `stock_movements` dengan type `decrease` dan reason `sale`.

3. **Stock Adjustment**
   - Tambah atau kurangi stok sesuai penyesuaian.
   - Catat `stock_movements` dengan reason `adjustment`.

4. **Production / Recipe**
   - Saat membuat produk jadi dari bahan baku, stok bahan berkurang.
   - Jika hasilnya berupa produk jadi yang disimpan, stok produk bertambah.
   - Dua catatan pada `stock_movements` akan muncul (decrease bahan, increase produk).

---

## 🧱 Struktur Relasi Antar Model

Product
├── hasMany(Recipe)
├── hasMany(StockMovement)
├── hasMany(PurchaseItem)
├── hasMany(SaleItem)
└── belongsTo(Unit)

Recipe
├── belongsTo(Product, as parent_product)
└── belongsTo(Product, as material_product)

Sale
├── hasMany(SaleItem)
└── belongsTo(User, as cashier)

Purchase
├── hasMany(PurchaseItem)
└── belongsTo(Supplier)

StockMovement
└── belongsTo(Product)

Unit
├── hasMany(Product)
└── bisa memiliki relasi konversi ke unit lain


---

## 🧰 Modul di Filament

| Modul | Resource | Fungsi |
|--------|-----------|--------|
| **ProductResource** | CRUD | Kelola bahan baku & produk jadi |
| **UnitResource** | CRUD | Kelola unit dan konversi antar unit |
| **RecipeResource** | Relation | Kelola komposisi bahan per produk |
| **PurchaseResource** | CRUD | Transaksi pembelian dari supplier |
| **SaleResource** | CRUD | Transaksi penjualan (POS) |
| **ExpenseResource** | CRUD | Catatan pengeluaran |
| **StockAdjustmentResource** | CRUD | Penyesuaian stok manual |
| **StockMovementResource** | Read-only | Riwayat semua pergerakan stok |

---

## 🧠 Konsep Tambahan (Opsional)

- **Draft Order**:  
  Pesanan pelanggan yang belum dibayar, tetap tersimpan dan bisa diedit sebelum pembayaran.
  
- **Multi Outlet / Branch Support (opsional)**:  
  Setiap data stok dan transaksi bisa dibedakan per cabang.

- **Role Management (Filament Shield / Spatie Permission)**:  
  Pisahkan role: Admin, Kasir, Gudang, Manajer.

---

## 💡 Tujuan Akhir

Membangun sistem kasir restoran yang:
- Mudah digunakan (UI berbasis Filament 4).
- Akurat dalam pengelolaan stok dan HPP.
- Memiliki laporan keuangan dan operasional yang lengkap.
- Dapat dikembangkan ke fitur lanjutan seperti multi outlet, printer kitchen, hingga integrasi QRIS.

---

## 🏗️ Tahapan Pengerjaan (Rekomendasi)

1. Setup project Laravel + Filament 4  
2. Buat module: **Unit → Product → StockMovement**  
3. Tambahkan **Purchase, Sale, Expense**  
4. Tambahkan **Recipe (COGS)**  
5. Tambahkan **Stock Adjustment & Reports**  
6. Terakhir, buat POS UI (kasir) dengan fitur **draft order + payment**  

---

## 🧾 Lisensi
Proyek ini bersifat internal dan dapat dikembangkan secara bebas oleh tim pengembang. Tidak untuk distribusi publik kecuali dengan izin pemilik proyek.

---

📍**Dibuat oleh:** Evan Helga  
📅 **Versi Draft:** v0.1 — Struktur Konseptual & Arsitektur Sistem


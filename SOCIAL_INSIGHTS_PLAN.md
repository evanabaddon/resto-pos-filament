# Strategic Plan: Social-to-Sales Correlation Intelligence

Intelligence module untuk menghubungkan data performa sosial media (Instagram/TikTok) dengan Point of Sale (POS) guna mengukur ROI marketing digital secara presisi.

## 🎯 Vision
Menciptakan sistem "Feedback Loop" otomatis dimana setiap postingan viral bisa diukur dampaknya langsung ke laci kasir (direct revenue attribution).

## 🏗️ Architecture
1. **Link Attribution**: Menggunakan parameter `?src=ig` atau `?src=tt` pada link Self-Order.
2. **Engagement Scraper**: Service Laravel yang mengambil data views/likes publik secara berkala (Zero-cost API).
3. **Correlation Engine**: Membandingkan timestamp lonjakan engagement dengan timestamp lonjakan transaksi.
4. **AI Sarah (Nirmala)**: Memberikan rekomendasi jadwal posting berdasarkan data historis tersibuk.

## 🚀 Implementation Roadmap

### ✅ Phase 1: Database & Foundation
- [x] Migrasi tabel `social_analytics` (id, platform, views, likes, comments, scraped_at).
- [x] Penambahan kolom `source` dan `origin_url` di tabel `Sales` & `Members`.
- [x] Implementasi logic penangkap trafik di Middleware `Self-Order`.

### ✅ Phase 2: Data Acquisition
- [x] Membuat `SocialScraperService` (Automated scraper).
- [x] Mengatur Laravel Scheduler untuk update data harian jam 00:00.

### ✅ Phase 3: Dashboard & Heatmap
- [x] Membuat Custom Filament Page: **Social Insights**.
- [x] Implementasi Chart/Grid Heatmap korelasi (ApexCharts) - **Full Width Optimized**.
- [x] Penambahan tabel "Top Referrer" (IG vs TT).

### ✅ Phase 4: AI Strategic Action
- [x] Integrasi Nirmala AI (DeepSeek) untuk membaca tren di heatmap.
- [x] Fitur "AI Advice" untuk saran waktu posting terbaik berdasarkan data sales harian.

## 📊 Business Outcome
- Mengetahui platform mana yang paling banyak mendatangkan uang (bukan cuma likes).
- Mengoptimalkan budget marketing hanya pada platform yang terbukti memberikan "Conversion".
- Mengetahui "Gold Hour" untuk posting konten berdasarkan pola lapar pelanggan.

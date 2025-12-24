<x-filament-panels::page>
    <div class="flex gap-6">
        {{-- Sticky Sidebar Navigation --}}
        <aside class="hidden lg:block w-64 flex-shrink-0">
            <div
                class="sticky top-6 bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                <h3 class="font-bold text-sm uppercase text-gray-500 dark:text-gray-400 mb-3">Daftar Isi</h3>
                <nav class="space-y-1">
                    <a href="#getting-started"
                        class="block px-3 py-2 text-sm rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                        🚀 Getting Started
                    </a>
                    <a href="#pos"
                        class="block px-3 py-2 text-sm rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                        💰 POS & Kasir
                    </a>
                    <a href="#inventory"
                        class="block px-3 py-2 text-sm rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                        📦 Inventory
                    </a>
                    <a href="#reports"
                        class="block px-3 py-2 text-sm rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                        📊 Laporan & Analisis
                    </a>
                    <a href="#crm"
                        class="block px-3 py-2 text-sm rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                        🤝 CRM & Loyalty
                    </a>
                    <a href="#ai"
                        class="block px-3 py-2 text-sm rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                        🤖 AI Features
                    </a>
                    <a href="#whatsapp"
                        class="block px-3 py-2 text-sm rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                        💬 WhatsApp Center
                    </a>
                    <a href="#hrm"
                        class="block px-3 py-2 text-sm rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                        👥 HRM & Payroll
                    </a>
                    <a href="#troubleshooting"
                        class="block px-3 py-2 text-sm rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                        🔧 Troubleshooting
                    </a>
                    <a href="#support"
                        class="block px-3 py-2 text-sm rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                        💬 Support
                    </a>
                </nav>
            </div>
        </aside>

        {{-- Main Content --}}
        <div class="flex-1 min-w-0 space-y-6">
            {{-- Header Section --}}
            <div class="bg-gradient-to-r from-primary-500 to-primary-600 rounded-lg p-6 text-white">
                <h2 class="text-2xl font-bold mb-2">📚 Dokumentasi Sistem</h2>
                <p class="text-primary-100">Panduan lengkap penggunaan Intelligent Restaurant Ecosystem</p>
            </div>

            {{-- Quick Navigation Cards (Mobile) --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 lg:hidden">
                <a href="#getting-started" class="bg-white dark:bg-gray-800 rounded-lg p-4 hover:shadow-lg transition">
                    <div class="text-3xl mb-2">🚀</div>
                    <div class="font-semibold text-sm">Getting Started</div>
                </a>
                <a href="#pos" class="bg-white dark:bg-gray-800 rounded-lg p-4 hover:shadow-lg transition">
                    <div class="text-3xl mb-2">💰</div>
                    <div class="font-semibold text-sm">POS & Kasir</div>
                </a>
                <a href="#inventory" class="bg-white dark:bg-gray-800 rounded-lg p-4 hover:shadow-lg transition">
                    <div class="text-3xl mb-2">📦</div>
                    <div class="font-semibold text-sm">Inventory</div>
                </a>
                <a href="#reports" class="bg-white dark:bg-gray-800 rounded-lg p-4 hover:shadow-lg transition">
                    <div class="text-3xl mb-2">📊</div>
                    <div class="font-semibold text-sm">Laporan</div>
                </a>
            </div>

            {{-- Main Documentation Content --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 space-y-8">

                {{-- Getting Started --}}
                <section id="getting-started">
                    <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                        <span class="text-2xl">🚀</span>
                        Getting Started
                    </h3>
                    <div class="prose dark:prose-invert max-w-none">
                        <h4 class="font-semibold text-lg">Login & Dashboard</h4>
                        <p>Setelah login, Anda akan diarahkan ke Dashboard utama yang menampilkan ringkasan bisnis hari
                            ini.</p>

                        <h4 class="font-semibold text-lg mt-4">Navigasi Sistem</h4>
                        <ul class="list-disc pl-6 space-y-2">
                            <li><strong>Sidebar Kiri:</strong> Menu utama untuk akses semua fitur</li>
                            <li><strong>Top Bar:</strong> Notifikasi, profil, dan quick actions</li>
                            <li><strong>Dashboard Widgets:</strong> Metrik real-time (Omzet, Transaksi, Stok Kritis)
                            </li>
                        </ul>

                        <h4 class="font-semibold text-lg mt-4">Role & Akses</h4>
                        <p>Sistem menggunakan Role-Based Access Control (RBAC). Setiap role memiliki akses berbeda:</p>
                        <ul class="list-disc pl-6 space-y-1">
                            <li><strong>Super Admin:</strong> Akses penuh ke semua fitur</li>
                            <li><strong>Admin:</strong> Kelola produk, stok, karyawan (bisa delete)</li>
                            <li><strong>Cashier:</strong> Akses POS dan CRM (tidak bisa delete)</li>
                            <li><strong>Kitchen:</strong> Akses KDS dan lapor stok</li>
                        </ul>
                    </div>
                </section>

                <hr class="border-gray-200 dark:border-gray-700">

                {{-- POS Section --}}
                <section id="pos">
                    <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                        <span class="text-2xl">💰</span>
                        Point of Sale (POS)
                    </h3>
                    <div class="prose dark:prose-invert max-w-none">
                        <h4 class="font-semibold text-lg">Cara Transaksi</h4>
                        <ol class="list-decimal pl-6 space-y-2">
                            <li>Buka menu <strong>POS</strong> dari sidebar</li>
                            <li>Pilih produk dari katalog (klik untuk tambah ke cart)</li>
                            <li>Atur jumlah, diskon, atau catatan khusus jika perlu</li>
                            <li>Pilih tipe order: Dine In / Takeaway / Delivery</li>
                            <li>Klik <strong>Bayar</strong>, pilih metode pembayaran</li>
                            <li>Input jumlah uang diterima, sistem otomatis hitung kembalian</li>
                            <li>Klik <strong>Selesaikan Transaksi</strong></li>
                        </ol>

                        <h4 class="font-semibold text-lg mt-4">Fitur Lanjutan</h4>
                        <ul class="list-disc pl-6 space-y-2">
                            <li><strong>Quick Add Member:</strong> Daftar member baru langsung dari layar POS</li>
                            <li><strong>Split Payment:</strong> Bayar dengan kombinasi Cash + QRIS</li>
                            <li><strong>Draft Order:</strong> Simpan transaksi pending untuk dilanjutkan nanti</li>
                            <li><strong>Redeem Points:</strong> Tukar poin member menjadi diskon</li>
                        </ul>

                        <h4 class="font-semibold text-lg mt-4">Cash Session</h4>
                        <p>Setiap kasir wajib membuka Cash Session di awal shift. Sistem akan tracking:</p>
                        <ul class="list-disc pl-6 space-y-1">
                            <li><strong>Modal Awal:</strong> Uang di laci kasir saat buka</li>
                            <li><strong>Expected Cash:</strong> Dihitung otomatis (Penjualan Cash - Expenses -
                                Purchases)</li>
                            <li><strong>Actual Cash:</strong> Uang fisik saat tutup kasir</li>
                            <li><strong>Variance:</strong> Selisih antara Expected dan Actual</li>
                        </ul>
                    </div>
                </section>

                <hr class="border-gray-200 dark:border-gray-700">

                {{-- Inventory Section --}}
                <section id="inventory">
                    <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                        <span class="text-2xl">📦</span>
                        Inventory Management
                    </h3>
                    <div class="prose dark:prose-invert max-w-none">
                        <h4 class="font-semibold text-lg">Kelola Produk</h4>
                        <p>Sistem mendukung 3 tipe produk:</p>
                        <ul class="list-disc pl-6 space-y-2">
                            <li><strong>Raw Material:</strong> Bahan baku (Kopi, Gula, Tepung)</li>
                            <li><strong>Retail:</strong> Produk jadi siap jual (Snack, Minuman Kemasan)</li>
                            <li><strong>Produced:</strong> Menu hasil resep (Latte, Nasi Goreng)</li>
                        </ul>

                        <h4 class="font-semibold text-lg mt-4">Stock Opname (Adjustment)</h4>
                        <p><strong>⚠️ Penting:</strong> Selalu gunakan <strong>unit dasar</strong> saat input stock
                            opname!</p>
                        <ol class="list-decimal pl-6 space-y-2">
                            <li>Buka menu <strong>Stock Movements</strong></li>
                            <li>Klik <strong>Adjustment (Opname)</strong></li>
                            <li>Pilih produk → Unit otomatis muncul di sebelah kanan input</li>
                            <li>Input jumlah stok fisik (misal: <code>1500 g</code>)</li>
                            <li>Pilih alasan: Stock Opname / Barang Rusak / dll</li>
                            <li>Simpan</li>
                        </ol>

                        <h4 class="font-semibold text-lg mt-4">Resep (Recipe)</h4>
                        <p>Untuk menu Produced, Anda perlu membuat resep:</p>
                        <ol class="list-decimal pl-6 space-y-2">
                            <li>Edit produk menu (misal: Latte)</li>
                            <li>Tab <strong>Resep</strong> → Tambah bahan</li>
                            <li>Pilih bahan baku, input quantity + unit</li>
                            <li>Sistem otomatis hitung HPP berdasarkan harga bahan</li>
                        </ol>
                    </div>
                </section>

                <hr class="border-gray-200 dark:border-gray-700">

                {{-- Reports Section --}}
                <section id="reports">
                    <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                        <span class="text-2xl">📊</span>
                        Laporan & Analisis
                    </h3>
                    <div class="prose dark:prose-invert max-w-none">
                        <h4 class="font-semibold text-lg">Financial Report</h4>
                        <p>Laporan keuangan komprehensif dengan fitur:</p>
                        <ul class="list-disc pl-6 space-y-2">
                            <li><strong>Period Comparison:</strong> Bandingkan performa dengan periode sebelumnya</li>
                            <li><strong>Stock Valuation:</strong> Nilai total aset inventaris</li>
                            <li><strong>Trend Chart:</strong> Grafik Revenue vs Expenses</li>
                            <li><strong>PDF Export:</strong> Download laporan profesional</li>
                        </ul>

                        <h4 class="font-semibold text-lg mt-4">AI Menu Engineering</h4>
                        <p>Analisis profitabilitas menu dengan klasifikasi:</p>
                        <ul class="list-disc pl-6 space-y-2">
                            <li><strong>Stars:</strong> Populer & Profitable (pertahankan!)</li>
                            <li><strong>Plowhorses:</strong> Populer tapi margin rendah (naikkan harga)</li>
                            <li><strong>Puzzles:</strong> Margin tinggi tapi kurang laku (promosi!)</li>
                            <li><strong>Dogs:</strong> Tidak laku & tidak untung (hapus/ganti)</li>
                        </ul>

                        <h4 class="font-semibold text-lg mt-4">AI Forecasting</h4>
                        <p>Prediksi kebutuhan restock 7 hari ke depan berdasarkan tren penjualan historis.</p>
                    </div>
                </section>

                <hr class="border-gray-200 dark:border-gray-700">

                {{-- CRM Section --}}
                <section id="crm">
                    <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                        <span class="text-2xl">🤝</span>
                        CRM & Loyalty
                    </h3>
                    <div class="prose dark:prose-invert max-w-none">
                        <h4 class="font-semibold text-lg">Daftar Member</h4>
                        <p>Member otomatis dapat poin setiap transaksi. Atur nilai tukar di <strong>Settings →
                                Kemitraan</strong>.</p>

                        <h4 class="font-semibold text-lg mt-4">WhatsApp SOP</h4>
                        <p>Sistem menyarankan pesan WhatsApp berdasarkan fase pelanggan:</p>
                        <ul class="list-disc pl-6 space-y-2">
                            <li><strong>Fase 1:</strong> Pelanggan baru (sambutan hangat)</li>
                            <li><strong>Fase 2:</strong> Repeat customer (apresiasi loyalitas)</li>
                            <li><strong>Fase 3:</strong> High value (reward khusus)</li>
                        </ul>
                    </div>
                </section>

                <hr class="border-gray-200 dark:border-gray-700">

                {{-- AI Features --}}
                <section id="ai">
                    <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                        <span class="text-2xl">🤖</span>
                        AI Features
                    </h3>
                    <div class="prose dark:prose-invert max-w-none">
                        <h4 class="font-semibold text-lg">AI Business Assistant</h4>
                        <p>Tanya Nirmala tentang performa bisnis Anda. AI punya akses ke:</p>
                        <ul class="list-disc pl-6 space-y-1">
                            <li>Data penjualan real-time</li>
                            <li>Menu terlaris</li>
                            <li>Stok kritis</li>
                            <li>Promo aktif</li>
                        </ul>

                        <h4 class="font-semibold text-lg mt-4">AI Smart Message</h4>
                        <p>Generate draf pesan WhatsApp yang personal untuk pelanggan. AI tidak akan hallucinate karena:
                        </p>
                        <ul class="list-disc pl-6 space-y-1">
                            <li>Hanya menyebut menu asli dari database</li>
                            <li>Otomatis sertakan promo yang aktif</li>
                            <li>Sesuaikan dengan nama assistant di Settings</li>
                        </ul>
                    </div>
                </section>

                <hr class="border-gray-200 dark:border-gray-700">

                {{-- WhatsApp Center --}}
                <section id="whatsapp">
                    <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                        <span class="text-2xl">💬</span>
                        WhatsApp Center
                    </h3>
                    <div class="prose dark:prose-invert max-w-none">
                        <h4 class="font-semibold text-lg">Setup Awal</h4>
                        <ol class="list-decimal pl-6 space-y-2">
                            <li>Pastikan WhatsApp Gateway sudah running (PM2/Supervisor)</li>
                            <li>Buka menu <strong>WhatsApp Center</strong></li>
                            <li>Scan QR code dengan WhatsApp di HP Anda</li>
                            <li>Tunggu hingga status "Connected"</li>
                        </ol>

                        <h4 class="font-semibold text-lg mt-4">Fitur Utama</h4>
                        <ul class="list-disc pl-6 space-y-2">
                            <li><strong>AI Reply:</strong> Generate balasan cerdas dengan context bisnis</li>
                            <li><strong>Reply with Quote:</strong> Balas pesan dengan kutipan</li>
                            <li><strong>Media Support:</strong> Kirim gambar, video, voice note, dokumen</li>
                            <li><strong>Quick Actions:</strong> Convert chat ke Member atau Reservasi</li>
                        </ul>
                    </div>
                </section>

                <hr class="border-gray-200 dark:border-gray-700">

                {{-- HRM Section --}}
                <section id="hrm">
                    <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                        <span class="text-2xl">👥</span>
                        HRM & Payroll
                    </h3>
                    <div class="prose dark:prose-invert max-w-none">
                        <h4 class="font-semibold text-lg">Attendance System</h4>
                        <p>Karyawan bisa clock-in/out dengan:</p>
                        <ul class="list-disc pl-6 space-y-2">
                            <li><strong>Face Recognition:</strong> Validasi wajah</li>
                            <li><strong>GPS Geofencing:</strong> Pastikan di area outlet</li>
                            <li><strong>Late Penalty:</strong> Pemotongan otomatis untuk keterlambatan</li>
                        </ul>

                        <h4 class="font-semibold text-lg mt-4">Payroll</h4>
                        <p>Generate slip gaji otomatis dengan komponen:</p>
                        <ul class="list-disc pl-6 space-y-1">
                            <li>Gaji Pokok</li>
                            <li>Tunjangan</li>
                            <li>Overtime</li>
                            <li>Denda keterlambatan</li>
                            <li>Potongan pinjaman (auto-deduct)</li>
                        </ul>
                    </div>
                </section>

                <hr class="border-gray-200 dark:border-gray-700">

                {{-- Troubleshooting --}}
                <section id="troubleshooting">
                    <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                        <span class="text-2xl">🔧</span>
                        Troubleshooting
                    </h3>
                    <div class="prose dark:prose-invert max-w-none">
                        <h4 class="font-semibold text-lg">Printer Tidak Muncul</h4>
                        <ul class="list-disc pl-6 space-y-2">
                            <li>Pastikan Electron Agent sudah running</li>
                            <li>Cek koneksi printer (USB/LAN/Bluetooth)</li>
                            <li>Restart Electron Agent</li>
                        </ul>

                        <h4 class="font-semibold text-lg mt-4">HPP Tidak Akurat</h4>
                        <ul class="list-disc pl-6 space-y-2">
                            <li>Gunakan fitur <strong>"Hitung Ulang Semua HPP"</strong> di Menu Engineering</li>
                            <li>Pastikan resep sudah lengkap dan unit conversion benar</li>
                            <li>Update harga bahan baku dari pembelian terakhir</li>
                        </ul>

                        <h4 class="font-semibold text-lg mt-4">WhatsApp Tidak Terkirim</h4>
                        <ul class="list-disc pl-6 space-y-2">
                            <li>Pastikan WhatsApp Gateway sudah running (PM2/Supervisor)</li>
                            <li>Cek status koneksi di WhatsApp Center</li>
                            <li>Scan ulang QR code jika session expired</li>
                        </ul>

                        <h4 class="font-semibold text-lg mt-4">Stok Tidak Berkurang Otomatis</h4>
                        <ul class="list-disc pl-6 space-y-2">
                            <li>Pastikan produk tipe <strong>Retail</strong> atau <strong>Raw Material</strong></li>
                            <li>Produk <strong>Produced</strong> tidak mengurangi stok langsung, tapi mengurangi bahan
                                baku di resep</li>
                        </ul>
                    </div>
                </section>

                <hr class="border-gray-200 dark:border-gray-700">

                {{-- Support Section --}}
                <section id="support">
                    <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                        <span class="text-2xl">💬</span>
                        Butuh Bantuan?
                    </h3>
                    <div class="bg-primary-50 dark:bg-primary-900/20 rounded-lg p-6">
                        <p class="text-gray-700 dark:text-gray-300 mb-4">
                            Jika Anda mengalami kendala atau butuh bantuan lebih lanjut, silakan hubungi tim support
                            kami.
                        </p>
                        <div class="flex gap-4">
                            <a href="https://wa.me/6285155113112" target="_blank"
                                class="inline-flex items-center gap-2 bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg transition">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                                </svg>
                                WhatsApp Support
                            </a>
                        </div>
                    </div>
                </section>

            </div>

            {{-- Footer --}}
            <div class="text-center text-sm text-gray-500 dark:text-gray-400">
                <p>📍 Developed by <strong>Evan Helga</strong> — Crafting Digital Excellence for F&B Business.</p>
                <p class="mt-1">Version 2.0 • Last Updated: {{ now()->format('F Y') }}</p>
            </div>
        </div>
    </div>

    {{-- Smooth Scroll Script --}}
    <script>
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</x-filament-panels::page>
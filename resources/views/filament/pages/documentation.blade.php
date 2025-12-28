<x-filament-panels::page>
    <div class="flex gap-6">
        {{-- Sticky Sidebar Navigation --}}
        <aside class="hidden lg:block w-64 flex-shrink-0">
            <div
                class="sticky top-6 bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                <h3 class="font-bold text-sm uppercase text-gray-500 dark:text-gray-400 mb-3">Daftar Isi</h3>
                <nav class="space-y-1">
                    <a href="#fundamentals"
                        class="block px-3 py-2 text-sm rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                        ⚙️ Fundamental Sistem
                    </a>
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
                    <a href="#ordering"
                        class="block px-3 py-2 text-sm rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                        📱 Waiter & Self-Order
                    </a>
                    <a href="#reservation"
                        class="block px-3 py-2 text-sm rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                        📅 Reservation & Pre-Order
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
                <a href="#ordering" class="bg-white dark:bg-gray-800 rounded-lg p-4 hover:shadow-lg transition">
                    <div class="text-3xl mb-2">📱</div>
                    <div class="font-semibold text-sm">Self-Order</div>
                </a>
            </div>

            {{-- Main Documentation Content --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 space-y-8">

                {{-- System Fundamentals --}}
                <section id="fundamentals">
                    <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                        <span class="text-2xl">⚙️</span>
                        Fundamental Sistem
                    </h3>
                    <div class="prose dark:prose-invert max-w-none">
                        <div class="bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 p-4 mb-4">
                            <p class="font-semibold text-blue-900 dark:text-blue-100 mb-2">📘 Wajib Dibaca</p>
                            <p class="text-sm text-blue-800 dark:text-blue-200">Pahami fundamental sistem ini sebelum
                                menggunakan fitur-fitur lanjutan untuk menghindari kesalahan interpretasi data keuangan.
                            </p>
                        </div>

                        <h4 class="font-semibold text-lg mt-4">💰 Sistem Akuntansi: Accrual Method</h4>
                        <p>Sistem ini menggunakan <strong>Accrual Accounting</strong> untuk perhitungan HPP (Harga Pokok
                            Penjualan / COGS).</p>

                        <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-lg my-4">
                            <p class="font-semibold mb-2">Prinsip Dasar:</p>
                            <ul class="list-disc pl-6 space-y-2">
                                <li><strong>Pembelian Bahan Baku ≠ Expense</strong> - Pembelian dicatat sebagai <em>Aset
                                        (Stock Value)</em>, bukan biaya.</li>
                                <li><strong>COGS/HPP dicatat saat penjualan</strong> - Biaya bahan baku baru dikurangi
                                    dari profit saat menu terjual.</li>
                                <li><strong>Matching Principle</strong> - Biaya di-match dengan revenue yang dihasilkan.
                                </li>
                            </ul>
                        </div>

                        <h5 class="font-semibold mt-4">Contoh Ilustrasi:</h5>
                        <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg my-3">
                            <p class="font-semibold text-green-900 dark:text-green-100">✅ Hari 1: Beli Beras 10kg @ Rp
                                15,000/kg = Rp 150,000</p>
                            <ul class="text-sm mt-2 space-y-1">
                                <li>• Cash: <span class="text-red-600">-Rp 150,000</span></li>
                                <li>• Stock Value (Aset): <span class="text-green-600">+Rp 150,000</span></li>
                                <li>• COGS: Rp 0 <em>(belum ada penjualan)</em></li>
                                <li>• Profit: <strong>Rp 0</strong> <em>(tidak berubah)</em></li>
                            </ul>
                        </div>

                        <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg my-3">
                            <p class="font-semibold text-blue-900 dark:text-blue-100">📊 Hari 2: Jual Nasi Goreng 5
                                porsi @ Rp 25,000</p>
                            <ul class="text-sm mt-2 space-y-1">
                                <li>• Revenue: <span class="text-green-600">+Rp 125,000</span></li>
                                <li>• COGS: <span class="text-red-600">-Rp 40,000</span> <em>(5 × 200g × Rp 15/g +
                                        bumbu)</em></li>
                                <li>• Stock Value: <span class="text-red-600">-Rp 40,000</span> <em>(beras berkurang
                                        1kg)</em></li>
                                <li>• Gross Profit: <strong class="text-green-600">Rp 85,000</strong> <em>(Revenue -
                                        COGS)</em></li>
                                <li>• Stock Value tersisa: Rp 110,000 <em>(9kg beras)</em></li>
                            </ul>
                        </div>

                        <h4 class="font-semibold text-lg mt-6">📦 Sistem Inventory: Real-time Stock Tracking</h4>
                        <p>Setiap transaksi yang melibatkan produk akan otomatis membuat <code>StockMovement</code> dan
                            mengupdate stok secara real-time.</p>

                        <table class="min-w-full border mt-3">
                            <thead class="bg-gray-100 dark:bg-gray-700">
                                <tr>
                                    <th class="border px-3 py-2 text-left">Transaksi</th>
                                    <th class="border px-3 py-2 text-left">Stock Movement</th>
                                    <th class="border px-3 py-2 text-left">Dampak</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="border px-3 py-2">Purchase (Pembelian)</td>
                                    <td class="border px-3 py-2"><span class="text-green-600">+Increase</span></td>
                                    <td class="border px-3 py-2">Stock bertambah, Stock Value naik</td>
                                </tr>
                                <tr>
                                    <td class="border px-3 py-2">POS Sale (Menu terjual)</td>
                                    <td class="border px-3 py-2"><span class="text-red-600">-Decrease</span></td>
                                    <td class="border px-3 py-2">Stock berkurang, COGS dicatat</td>
                                </tr>
                                <tr>
                                    <td class="border px-3 py-2">Stock Opname</td>
                                    <td class="border px-3 py-2">+/- Adjustment</td>
                                    <td class="border px-3 py-2">Koreksi variance fisik vs sistem</td>
                                </tr>
                                <tr>
                                    <td class="border px-3 py-2">Wastage (Rusak/Basi)</td>
                                    <td class="border px-3 py-2"><span class="text-red-600">-Decrease</span></td>
                                    <td class="border px-3 py-2">Stock berkurang, masuk Expense</td>
                                </tr>
                            </tbody>
                        </table>

                        <h4 class="font-semibold text-lg mt-6">🍳 Recipe System: Automatic Ingredient Deduction</h4>
                        <p>Menu dengan recipe akan otomatis mengurangi stok bahan baku saat terjual.</p>
                        <ul class="list-disc pl-6 space-y-2 mt-2">
                            <li><strong>Unit Conversion</strong> - Sistem otomatis convert unit (misal: recipe pakai
                                gram, stock dalam kg).</li>
                            <li><strong>Multi-Channel</strong> - Berlaku untuk POS, Waiter App, dan Self-Order.</li>
                            <li><strong>HPP Calculation</strong> - HPP dihitung dari total harga bahan baku dalam
                                recipe.</li>
                        </ul>

                        <div class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-500 p-4 mt-4">
                            <p class="font-semibold text-yellow-900 dark:text-yellow-100">⚠️ Penting</p>
                            <p class="text-sm text-yellow-800 dark:text-yellow-200">Pastikan setiap menu
                                <code>produced</code> memiliki recipe yang lengkap agar HPP dan stock deduction akurat.
                            </p>
                        </div>
                    </div>
                </section>

                <hr class="border-gray-200 dark:border-gray-700">

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

                        <h4 class="font-semibold text-lg mt-6">🍳 Kitchen Production (Prepared Stock) 🆕</h4>
                        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg my-3">
                            <p class="mb-2"><strong>Konsep:</strong> Fitur untuk mencatat proses masak dari Bahan Baku
                                Mentah menjadi Stok Jadi (Prepared).</p>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-2">
                                <strong>Contoh Real:</strong><br>
                                "Masak Nasi Putih" (Output: 50 Porsi)
                            <ul class="list-disc pl-4 mt-1">
                                <li><strong>Otomatis Kurangi (Raw):</strong> Beras 5 Kg + Air Galon 2 Liter.</li>
                                <li><strong>Otomatis Tambah (Prepared):</strong> Nasi Putih +50 Porsi.</li>
                            </ul>
                            </p>
                        </div>

                        <p class="font-semibold text-sm uppercase text-gray-500 mb-2">1. Cara Mencatat Produksi (Masak)
                        </p>
                        <ul class="list-disc pl-6 space-y-2 mb-4">
                            <li>Buka Dashboard > Widget <strong>Stok Critical & Produksi Dapur</strong></li>
                            <li>Pilih Menu yang dimasak (misal: Nasi Putih) dari dropdown</li>
                            <li>Input jumlah (misal: 50 Porsi)</li>
                            <li>Klik tombol <strong>"Catat Masak"</strong></li>
                            <li>
                                <strong>Hasil:</strong>
                                <ul class="list-disc pl-4 mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    <li>Stok 'Prepared' Nasi Putih bertambah +50</li>
                                    <li>Stok 'Raw' Beras berkurang otomatis (misal -5kg) sesuai resep</li>
                                </ul>
                            </li>
                        </ul>

                        <p class="font-semibold text-sm uppercase text-gray-500 mb-2">2. Reset Stock Harian (Closing
                            Kitchen)</p>
                        <ul class="list-disc pl-6 space-y-2">
                            <li>Digunakan untuk produk yang <strong>tidak bisa disimpan</strong> untuk besok (misal:
                                Nasi sisa, Sayur matang).</li>
                            <li>Klik tombol <strong>"Reset Stock"</strong> di sebelah item.</li>
                            <li>Sistem akan menolkan stok *prepared* dan mencatatnya sebagai *waste* hari ini.</li>
                            <li>Besok pagi, stok dimulai dari 0 agar produksi baru tercatat rapi.</li>
                        </ul>

                        <h4 class="font-semibold text-lg mt-6">Recipe Stock Validation</h4>
                        <div class="bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500 p-4 mb-4">
                            <p class="font-semibold text-green-900 dark:text-green-100">✅ Prevent Negative Stock</p>
                            <p class="text-sm text-green-800 dark:text-green-200">Sistem validasi stok bahan baku yang
                                mencegah overselling dan negative stock secara real-time.</p>
                        </div>

                        <p><strong>Fitur Utama:</strong></p>
                        <ul class="list-disc pl-6 space-y-2">
                            <li><strong>Real-time Availability Check</strong> - Cek ketersediaan bahan baku sebelum item
                                ditambahkan ke cart</li>
                            <li><strong>Draft Sales Consideration</strong> - Memperhitungkan qty yang sudah di draft
                                sales hari ini (belum dibayar)</li>
                            <li><strong>Cross-Channel Sync</strong> - Auto-refresh setiap 5 detik untuk sync antar POS,
                                Waiter, dan Self-Order</li>
                            <li><strong>Visual Indicators</strong> - Badge "Tersedia: X porsi" di POS/Waiter (hanya jika
                                stock < 10), overlay "HABIS" saat stock habis</li>
                            <li><strong>Cart Increment Protection</strong> - Validasi saat user increment qty di cart
                            </li>
                            <li><strong>Toast Notifications</strong> - Notifikasi real-time via Livewire events (tanpa
                                page reload)</li>
                        </ul>

                        <p class="mt-4"><strong>Fitur per Channel:</strong></p>
                        <div class="overflow-x-auto">
                            <table class="min-w-full border border-gray-300 dark:border-gray-600">
                                <thead class="bg-gray-100 dark:bg-gray-700">
                                    <tr>
                                        <th class="border border-gray-300 dark:border-gray-600 px-4 py-2 text-left">
                                            Feature</th>
                                        <th class="border border-gray-300 dark:border-gray-600 px-4 py-2 text-center">
                                            POS</th>
                                        <th class="border border-gray-300 dark:border-gray-600 px-4 py-2 text-center">
                                            Waiter App</th>
                                        <th class="border border-gray-300 dark:border-gray-600 px-4 py-2 text-center">
                                            Self-Order</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="border border-gray-300 dark:border-gray-600 px-4 py-2">Availability
                                            Badge</td>
                                        <td class="border border-gray-300 dark:border-gray-600 px-4 py-2 text-center">✅
                                            "Tersedia: X porsi"</td>
                                        <td class="border border-gray-300 dark:border-gray-600 px-4 py-2 text-center">✅
                                            "X porsi"</td>
                                        <td class="border border-gray-300 dark:border-gray-600 px-4 py-2 text-center">❌
                                            (validation only)</td>
                                    </tr>
                                    <tr class="bg-gray-50 dark:bg-gray-800">
                                        <td class="border border-gray-300 dark:border-gray-600 px-4 py-2">Stock
                                            Validation</td>
                                        <td class="border border-gray-300 dark:border-gray-600 px-4 py-2 text-center">✅
                                        </td>
                                        <td class="border border-gray-300 dark:border-gray-600 px-4 py-2 text-center">✅
                                        </td>
                                        <td class="border border-gray-300 dark:border-gray-600 px-4 py-2 text-center">✅
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="border border-gray-300 dark:border-gray-600 px-4 py-2">Cart Increment
                                            Check</td>
                                        <td class="border border-gray-300 dark:border-gray-600 px-4 py-2 text-center">✅
                                        </td>
                                        <td class="border border-gray-300 dark:border-gray-600 px-4 py-2 text-center">✅
                                        </td>
                                        <td class="border border-gray-300 dark:border-gray-600 px-4 py-2 text-center">✅
                                        </td>
                                    </tr>
                                    <tr class="bg-gray-50 dark:bg-gray-800">
                                        <td class="border border-gray-300 dark:border-gray-600 px-4 py-2">Auto-disable
                                            when out</td>
                                        <td class="border border-gray-300 dark:border-gray-600 px-4 py-2 text-center">✅
                                        </td>
                                        <td class="border border-gray-300 dark:border-gray-600 px-4 py-2 text-center">✅
                                        </td>
                                        <td class="border border-gray-300 dark:border-gray-600 px-4 py-2 text-center">✅
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="border border-gray-300 dark:border-gray-600 px-4 py-2">"HABIS"
                                            Overlay</td>
                                        <td class="border border-gray-300 dark:border-gray-600 px-4 py-2 text-center">✅
                                        </td>
                                        <td class="border border-gray-300 dark:border-gray-600 px-4 py-2 text-center">✅
                                        </td>
                                        <td class="border border-gray-300 dark:border-gray-600 px-4 py-2 text-center">✅
                                        </td>
                                    </tr>
                                    <tr class="bg-gray-50 dark:bg-gray-800">
                                        <td class="border border-gray-300 dark:border-gray-600 px-4 py-2">Toast
                                            Notifications</td>
                                        <td class="border border-gray-300 dark:border-gray-600 px-4 py-2 text-center">✅
                                            (Filament)</td>
                                        <td class="border border-gray-300 dark:border-gray-600 px-4 py-2 text-center">✅
                                            (Alpine.js)</td>
                                        <td class="border border-gray-300 dark:border-gray-600 px-4 py-2 text-center">✅
                                            (Alpine.js)</td>
                                    </tr>
                                    <tr>
                                        <td class="border border-gray-300 dark:border-gray-600 px-4 py-2">Auto-refresh
                                            (Polling)</td>
                                        <td class="border border-gray-300 dark:border-gray-600 px-4 py-2 text-center">✅
                                            5s</td>
                                        <td class="border border-gray-300 dark:border-gray-600 px-4 py-2 text-center">✅
                                            5s</td>
                                        <td class="border border-gray-300 dark:border-gray-600 px-4 py-2 text-center">✅
                                            5s</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-500 p-4 mt-4">
                            <p class="font-semibold text-yellow-900 dark:text-yellow-100">💡 Tips</p>
                            <ul class="text-sm text-yellow-800 dark:text-yellow-200 list-disc pl-5 mt-2 space-y-1">
                                <li>Badge hanya muncul jika stock < 10 porsi (untuk alert stock terbatas)</li>
                                <li>Draft sales hanya consider yang dibuat hari ini (mencegah draft lama memblokir
                                    stock)</li>
                                <li>Polling 5 detik memastikan sync antar channel (max delay 5s)</li>
                                <li>Toast notification muncul tanpa page reload (real-time feedback)</li>
                            </ul>
                        </div>
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

                {{-- Waiter & Self-Order Section --}}
                <section id="ordering">
                    <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                        <span class="text-2xl">📱</span>
                        Waiter & Self-Order
                    </h3>
                    <div class="prose dark:prose-invert max-w-none">
                        <h4 class="font-semibold text-lg">🤵 Waiter Digital Order</h4>
                        <p>Pusat komando mobile untuk pelayan guna mempercepat pelayanan.</p>
                        <ul class="list-disc pl-6 space-y-2">
                            <li><strong>Akses & Login:</strong> Buka <code>/waiter/order</code> melalui smartphone.</li>
                            <li><strong>Input Pesanan:</strong> Pilih menu, tambahkan catatan, dan tentukan nomor meja.
                            </li>
                            <li><strong>Auto-Sync:</strong> Pesanan langsung masuk ke KDS (Dapur/Bar) dan Dashboard POS
                                kasir.</li>
                        </ul>

                        <h4 class="font-semibold text-lg mt-4">📱 QR Self-Order Menu</h4>
                        <p>Sistem pemesanan mandiri oleh pelanggan langsung dari meja.</p>
                        <ul class="list-disc pl-6 space-y-2">
                            <li><strong>Scan to Order:</strong> Pelanggan scan QR di meja → Pilih menu → Checkout.</li>
                            <li><strong>AI Broadcast:</strong> Konfirmasi pesanan dikirim otomatis via WhatsApp dengan
                                teks dari AI.</li>
                            <li><strong>Member Integration:</strong> Member terdeteksi otomatis via nomor WhatsApp saat
                                checkout.</li>
                        </ul>

                        <h4 class="font-semibold text-lg mt-4">⚙️ Konfigurasi QR Meja</h4>
                        <p>Generate dan cetak kartu QR meja melalui menu <strong>Table Management</strong> di Admin
                            Panel.</p>
                    </div>
                </section>

                <hr class="border-gray-200 dark:border-gray-700">

                {{-- Reservation & Pre-Order Section --}}
                <section id="reservation">
                    <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                        <span class="text-2xl">📅</span>
                        Reservation & Pre-Order Printing
                    </h3>
                    <div class="prose dark:prose-invert max-w-none">
                        <h4 class="font-semibold text-lg">📝 Membuat Reservasi</h4>
                        <p>Sistem reservasi yang terintegrasi dengan pre-order menu dan pembayaran DP.</p>
                        <ol class="list-decimal pl-6 space-y-2">
                            <li>Buka menu <strong>Reservations</strong> dari sidebar</li>
                            <li>Klik <strong>Create</strong> dan isi data pelanggan (Nama, Telepon, Jumlah Tamu, Tanggal
                                & Waktu)</li>
                            <li><strong>Pre-Order Menu (Opsional):</strong> Tambahkan item menu yang dipesan sebelumnya
                                dengan harga fleksibel</li>
                            <li>Sistem otomatis menghitung total estimasi pesanan</li>
                        </ol>

                        <h4 class="font-semibold text-lg mt-4">💰 Pembayaran Down Payment (DP)</h4>
                        <p>Kelola pembayaran uang muka langsung dari tabel reservasi:</p>
                        <ol class="list-decimal pl-6 space-y-2">
                            <li>Klik tombol <strong>"Bayar DP"</strong> (icon credit card biru)</li>
                            <li>Pilih metode pembayaran dan masukkan jumlah DP</li>
                            <li>Sistem otomatis membuat transaksi POS dengan prefix <code>DP-</code></li>
                            <li>DP tercatat di cash session aktif untuk tracking keuangan</li>
                        </ol>

                        <h4 class="font-semibold text-lg mt-4">🖨️ Cetak Pre-Order ke Dapur/Bar 🆕</h4>
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-500 p-4 mb-4">
                            <p class="font-semibold text-yellow-900 dark:text-yellow-100">✨ Fitur Baru!</p>
                            <p class="text-sm text-yellow-800 dark:text-yellow-200">Cetak order menu pre-order ke divisi
                                Dapur/Bar <strong>sebelum</strong> pelanggan datang untuk persiapan yang lebih matang.
                            </p>
                        </div>

                        <p><strong>Cara Menggunakan:</strong></p>
                        <ol class="list-decimal pl-6 space-y-2">
                            <li>Klik tombol <strong>"Cetak Preorder"</strong> (icon printer kuning) di tabel reservasi
                            </li>
                            <li>Tombol hanya muncul jika:
                                <ul class="list-disc pl-6 mt-2">
                                    <li>Status reservasi: <code>Pending</code> atau <code>Confirmed</code></li>
                                    <li>Ada item pre-order yang sudah ditambahkan</li>
                                </ul>
                            </li>
                            <li>Sistem otomatis mengelompokkan item berdasarkan tipe produk:
                                <ul class="list-disc pl-6 mt-2">
                                    <li><strong>Produced</strong> → Dicetak ke <span
                                            class="text-orange-600 font-semibold">Dapur</span> (Kitchen)</li>
                                    <li><strong>Bar</strong> → Dicetak ke <span
                                            class="text-blue-600 font-semibold">Bar</span></li>
                                    <li><strong>General</strong> → Dicetak ke <span
                                            class="text-gray-600 font-semibold">Kasir</span></li>
                                </ul>
                            </li>
                            <li>Struk order mencantumkan:
                                <ul class="list-disc pl-6 mt-2">
                                    <li>Invoice: <code>RSVP-{id}</code></li>
                                    <li>Nama pelanggan</li>
                                    <li>Tanggal & waktu reservasi</li>
                                    <li>Label: <strong>"PREORDER"</strong></li>
                                    <li>Daftar item dengan quantity dan notes</li>
                                </ul>
                            </li>
                        </ol>

                        <h4 class="font-semibold text-lg mt-4">🔄 Konversi ke Transaksi Penjualan</h4>
                        <p>Saat pelanggan tiba, konversi reservasi menjadi transaksi POS:</p>
                        <ol class="list-decimal pl-6 space-y-2">
                            <li>Klik tombol <strong>"Proses ke Kasir"</strong> (icon shopping cart hijau)</li>
                            <li>Sistem otomatis:
                                <ul class="list-disc pl-6 mt-2">
                                    <li>Memindahkan semua item pre-order ke transaksi POS (status <code>Draft</code>)
                                    </li>
                                    <li>Mengurangkan DP sebagai item minus</li>
                                    <li>Mengubah status reservasi menjadi <code>Seated</code></li>
                                </ul>
                            </li>
                            <li>Kasir bisa menambah item tambahan atau langsung checkout</li>
                            <li>Tombol konversi hilang setelah digunakan (mencegah duplikasi)</li>
                        </ol>

                        <div class="bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500 p-4 mt-4">
                            <p class="font-semibold text-green-900 dark:text-green-100">✅ Keuntungan Fitur</p>
                            <ul class="text-sm text-green-800 dark:text-green-200 list-disc pl-5 mt-2 space-y-1">
                                <li><strong>Persiapan Lebih Matang:</strong> Dapur/Bar bisa mempersiapkan menu sebelum
                                    pelanggan datang</li>
                                <li><strong>Efisiensi Waktu:</strong> Mengurangi waktu tunggu pelanggan saat tiba di
                                    restoran</li>
                                <li><strong>Sinkronisasi Tim:</strong> Semua divisi mendapat informasi yang sama tentang
                                    pesanan yang akan datang</li>
                                <li><strong>Fleksibilitas:</strong> Bisa cetak ulang jika ada perubahan atau penambahan
                                    item</li>
                            </ul>
                        </div>

                        <h4 class="font-semibold text-lg mt-4">⚙️ Detail Teknis</h4>
                        <ul class="list-disc pl-6 space-y-2">
                            <li><strong>Print Infrastructure:</strong> Menggunakan sistem print yang sama dengan
                                POS/Waiter/Self-Order</li>
                            <li><strong>Hosting:</strong> Webhook untuk kirim ke printer lokal</li>
                            <li><strong>Lokal:</strong> Direct print ke printer USB/LAN</li>
                            <li><strong>Environment Detection:</strong> Otomatis mendeteksi apakah sistem berjalan di
                                hosting atau lokal</li>
                            <li><strong>Item Filtering:</strong> Item "Down Payment (DP)" otomatis difilter dan tidak
                                dicetak</li>
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

                        <h4 class="font-semibold text-lg mt-6">📉 Smart Inventory Forecasting (Hybrid) 🆕</h4>
                        <p>Sistem prediksi stok cerdas dengan dua metode analisis sekaligus:</p>

                        <div class="bg-orange-50 dark:bg-orange-900/20 border-l-4 border-orange-500 p-4 my-3">
                            <p class="font-semibold text-orange-900 dark:text-orange-100">🔥 1. Fokus Besok (Daily
                                High-Precision)</p>
                            <p class="text-sm text-orange-800 dark:text-orange-200 mt-1">
                                Menggunakan metode <strong>"Apple-to-Apple Comparison"</strong>. Sistem membandingkan
                                rata-rata penggunaan spesifik pada hari yang sama (misal: Senin vs Senin-Senin
                                sebelumnya) untuk mendeteksi lonjakan harian.
                            </p>
                        </div>

                        <div class="bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 p-4 my-3">
                            <p class="font-semibold text-blue-900 dark:text-blue-100">📅 2. Rencana Mingguan (7 Days)
                            </p>
                            <p class="text-sm text-blue-800 dark:text-blue-200 mt-1">
                                Proyeksi belanja standar untuk 7 hari ke depan berdasarkan tren 30 hari terakhir.
                            </p>
                        </div>

                        <p class="font-semibold mt-3">Cara Menggunakan:</p>
                        <ol class="list-decimal pl-6 space-y-1">
                            <li>Buka menu <strong>Smart Inventory</strong></li>
                            <li>Klik <strong>Generate Analisis Sekarang</strong></li>
                            <li>Lihat kotak "Fokus Besok" untuk kebutuhan mendesak</li>
                            <li>Gunakan "Rencana Mingguan" untuk belanja ke supplier</li>
                            <li>Export ke PDF jika perlu</li>
                        </ol>
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
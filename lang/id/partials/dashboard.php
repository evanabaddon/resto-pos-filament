<?php

return [
    // Headings
    'critical_stock_title' => 'Stok Kritis (Dapur Live)',
    'low_stock_alert_title' => 'Peringatan Stok Bahan Baku',
    'peak_hours_heatmap' => 'Heatmap Jam Sibuk',
    'revenue_trend' => 'Tren Pendapatan Harian',
    'best_selling_drink' => 'Minuman Terlaris',
    'best_selling_food' => 'Makanan Terlaris',

    // Status & Labels
    'all_stock_safe' => 'Semua Stok Aman',
    'no_critical_items_desc' => 'Semua stok siap saji di atas batas minimum.',
    'all_stock_safe_title' => 'Stok Aman',
    'all_stock_safe_desc' => 'Semua stok bahan baku di atas batas peringatan.',
    'view_all_raw_materials' => 'Lihat Semua Bahan Baku',

    'level_low_suffix' => ' (Rendah)',
    'level_critical_suffix' => ' (Kritis)',

    'stock_value' => 'Nilai Stok',
    'price_per_unit' => 'Harga/Unit',
    'raw_material_name' => 'Nama Bahan Baku',
    'stock' => 'Stok',
    'unit' => 'Unit',
    'stock_level' => 'Level Stok',
    'level_critical' => 'Kritis (< 5)',
    'level_low' => 'Rendah (5-10)',
    'level_out' => 'Habis (0)',
    'out_of_stock_text' => 'Habis',
    'low_stock_count_desc' => 'Ada :count bahan baku perlu perhatian.',

    // Charts
    'revenue_and_transactions' => 'Pendapatan vs Jumlah Transaksi',
    'revenue' => 'Pendapatan',
    'transaction_count' => 'Transaksi',
    'no_data_chart' => 'Tidak Ada Data',
    'date_label' => 'Tanggal',
    'sold_quantity' => 'Terjual',
    'sold_unit_tooltip' => 'unit',

    // Peak Hours
    'peak_hours_desc' => 'Kepadatan transaksi per jam dan hari',
    'day_monday' => 'Senin',
    'day_tuesday' => 'Selasa',
    'day_wednesday' => 'Rabu',
    'day_thursday' => 'Kamis',
    'day_friday' => 'Jumat',
    'day_saturday' => 'Sabtu',
    'day_sunday' => 'Minggu',
    'operational_hours' => 'Jam Operasional',
    'day_label' => 'Hari',
    'quiet' => 'Sepi',
    'normal' => 'Normal',
    'busy' => 'Ramai',
    'very_busy' => 'Sangat Ramai',

    // Filters
    'today' => 'Hari Ini',
    'yesterday' => 'Kemarin',
    'last_7_days' => '7 Hari Terakhir',
    'last_30_days' => '30 Hari Terakhir',

    // Critical Widget Extra
    'items' => 'Item',
    'ready_stock_label' => 'Stok Siap',
    'current_stock_label' => 'Stok Saat Ini',
    'already_cooked' => 'Sudah dimasak',
    'minimum_stock_label' => 'Stok Min',
    'produced_item_badge' => 'Barang Produksi',
    'cook_more_alert' => 'Harap masak lagi!',
    'restock_recommendation' => 'Rek. Restock:',
    'for_3_days' => '(untuk 3 hari)',
    'cook_more_btn' => 'Masak Lagi',
    'record_production_modal_title' => 'Catat Produksi',
    'current_ready_stock' => 'Stok Siap Saat Ini:',
    'quantity_placeholder' => 'Masukkan jumlah dimasak/disiapkan',
    'stock_deduction_info' => 'Bahan baku akan otomatis dikurangi dari stok.',
    'save_production_btn' => 'Simpan Produksi',
    'reset_waste_btn' => 'Reset / Buang',
    'reset_stock_confirm_title' => 'Konfirmasi Reset Stok',
    'warning' => 'PERINGATAN',
    'waste_stock_warning' => 'Ini akan mereset stok siap saji',
    'reset_to_zero_warning' => 'Stok akan diubah menjadi 0.',
    'ingredients_not_returned' => 'Bahan yang digunakan TIDAK akan dikembalikan ke inventaris.',
    'action_cannot_undo' => 'Tindakan ini tidak dapat dibatalkan.',
    'confirm_reset_btn' => 'Ya, Reset Stok',
    'auto_refresh_info' => 'Data diperbarui otomatis setiap 5 menit',

    'cancel' => 'Batal',

    // Tooltips (LowStockWidget)
    'price_per_unit_tooltip' => 'Harga beli per unit',
    'stock_value_tooltip' => 'Stok × Harga/Unit',

    // Widget DB Messages
    'production_note_db' => 'Produksi berkala (Manual)',
    'production_movement_note_db' => 'Output Produksi',
    'ingredient_movement_note_db' => 'Penggunaan bahan untuk :product qty :quantity',
    'insufficient_stock_error' => 'Stok tidak cukup untuk bahan :ingredient. Butuh: :required :unit, Tersedia: :current',
    'production_recorded_title' => 'Produksi Dicatat',
    'production_success_body' => 'Menambahkan :quantity ke stok siap saji',
    'production_failed_title' => 'Produksi Gagal',
    'stock_empty_title' => 'Stok Kosong',
    'stock_empty_body' => 'Stok sudah 0',
    'reset_stock_note_db' => 'Manual Reset/Waste',
    'stock_reset_title' => 'Stok Direset',
    'stock_reset_body' => 'Stok untuk :product telah direset dari :stock menjadi 0',
    'create_purchase' => 'Buat Pembelian',
    'ai_suggestion_title' => 'Saran Harian AI',

    // Revenue Overview Widget
    'today_revenue' => 'Pendapatan Hari Ini',
    'average_value' => 'Rata-rata: :value',
    'popular_payment' => 'Metode Pembayaran Populer',
    'most_frequent' => 'Paling Sering Digunakan',
    'total_transactions' => 'Total Transaksi',
    'from_yesterday' => '% dari kemarin',
];

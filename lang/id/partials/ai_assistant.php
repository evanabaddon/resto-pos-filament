<?php

return [
    'ai_nav_label' => 'Asisten Bisnis AI',
    'ai_intelligence' => 'Kecerdasan AI',
    'ai_title' => 'Asisten Bisnis AI',
    'online_status' => 'Online',
    'thinking' => 'Sedang berpikir...',
    'type_placeholder' => 'Ketik pesan Anda (Enter untuk kirim)...',
    'clear_chat' => 'Hapus Chat',
    'ai_welcome' => 'Halo! Saya :name, asisten bisnis pribadi Anda. Saya bisa analisa penjualan, pantau stok, atau kasih ide promo. Ada yang bisa dibantu?',
    'ai_connection_error' => 'Maaf, ada gangguan koneksi ke otak AI. Coba lagi ya.',
    'ai_error_prefix' => 'Error: ',

    // Quick Actions
    'analyze_sales' => 'Analisa Omzet',
    'analyze_sales_msg' => 'Analisa tren penjualan 30 hari terakhir dan berikan insight.',
    'best_selling_menu' => 'Menu Terlaris',
    'best_selling_menu_msg' => 'Apa 5 menu terlaris saat ini dan saran untuk meningkatkannya?',
    'check_stock' => 'Cek Stok',
    'check_stock_msg' => 'Apakah ada stok kritis yang perlu saya perhatikan?',
    'promo_ideas' => 'Ide Promo',
    'promo_ideas_msg' => 'Berikan 3 ide promo kreatif untuk akhir pekan ini berdasarkan data.',

    // Widget Prompts & Context
    'ai_prompt_system' => 'Anda adalah analis bisnis restoran profesional. Berikan saran yang singkat, padat, dan dapat ditindaklanjuti berdasarkan data.',
    'ai_prompt_user' => 'Berikut status restoran hari ini: :context. Tolong berikan ringkasan singkat dan 3 rekomendasi spesifik untuk meningkatkan penjualan atau efisiensi hari ini.',
    'ai_default_advice' => 'Saat ini belum cukup data untuk memberikan saran spesifik. Fokus pada pelayanan prima dan pengecekan stok.',
    'ai_error_advice' => 'AI sedang tidak tersedia. Periksa koneksi internet atau pengaturan API.',
    'ai_daily_revenue_context' => 'Pendapatan hari ini: :amount. Item stok kritis: :count.',
    'ai_critical_retail' => 'Item Retail Kritis: :list.',
    'ai_critical_products' => 'Produk Stok Rendah: :list.',
    'ai_critical_ingredients' => 'Bahan Baku Kritis: :list.',

    // Context Strings (for System Prompt)
    'context_analysis_header' => 'DATA ANALISIS (:days HARI TERAKHIR):',
    'context_total_orders' => 'Total Order',
    'context_total_revenue' => 'Total Pendapatan',
    'context_avg_transaction' => 'Rata-rata per Transaksi',
    'context_top_menu_header' => 'TOP 5 MENU TERLARIS:',
    'context_no_sales_data' => 'Belum ada data penjualan.',
    'context_inventory_header' => 'INVENTORI & STOK:',
    'context_critical_retail_count' => 'Jumlah Item Retail Kritis (< 10): :count item',
    'context_critical_retail_list' => 'Retail Kritis',
    'context_critical_ingredient_list' => 'BAHAN BAKU KRITIS',
    'context_remaining' => 'Sisa',
];

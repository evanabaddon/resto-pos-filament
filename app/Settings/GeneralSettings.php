<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public string $app_name;
    public ?string $app_website;
    public ?string $app_instagram;
    public ?string $app_tiktok;
    public ?string $app_logo;
    public ?string $app_favicon;

    // New Company Fields
    public ?string $company_address;
    public ?string $company_phone;
    public ?string $company_email;

    // Modules
    public bool $enable_hrm;
    public ?string $hrm_license_key;

    public bool $enable_kds;
    public ?string $kds_license_key;

    public bool $enable_crm;
    public ?string $crm_license_key;

    public bool $enable_wa_center;
    public ?string $wa_license_key;
    public bool $wa_auto_download_media;

    public bool $enable_ai_forecasting;
    public ?string $ai_forecasting_license_key;

    public bool $enable_menu_engineering;
    public ?string $menu_engineering_license_key;

    public string $printer_width;
    public bool $enable_table_number;

    // Fiscal Settings
    public ?string $template_path;
    public int $start_row;
    public string $date_column;
    public string $amount_column; // Omzet
    public string $tax_column;    // Pajak
    public bool $enable_fiscal_planning;
    public ?string $fiscal_license_key;

    // Loyalty Settings
    public string $loyalty_program_name;
    public int $loyalty_point_exchange_rate; // e.g. 10000 = 1 point
    public int $loyalty_point_value; // e.g. 1 point = 1 IDR

    // WhatsApp SOP Templates
    public string $wa_template_phase_1;
    public string $wa_template_phase_2;
    public string $wa_template_phase_3;
    public string $wa_template_faq_benefit;
    public string $wa_template_faq_redemption;
    public string $wa_template_faq_use_points;

    // Reservation Template
    public string $wa_template_reservation_confirmation;

    // AI CRM Prompt
    public ?string $ai_crm_system_prompt;

    // AI Intelligence Configuration
    public string $ai_assistant_name = 'Sarah (AI Admin)';
    public string $ai_provider = 'deepseek';
    public string $ai_model = 'deepseek-chat';
    public string $ai_base_url = 'https://api.deepseek.com';
    public ?string $ai_api_key = null;

    // BMKG Weather Settings
    public ?string $bmkg_location_code;

    public static function group(): string
    {
        return 'app';
    }

    public static function defaults(): array
    {
        return [
            'app_name' => config('app.name', 'Laravel'),
            'app_website' => 'https://suralaya.id',
            'app_instagram' => '',
            'app_tiktok' => '',
            'app_logo' => null,
            'app_favicon' => null,
            'enable_hrm' => false,
            'hrm_license_key' => null,
            'enable_kds' => false,
            'kds_license_key' => null,
            'enable_crm' => false,
            'crm_license_key' => null,
            'enable_wa_center' => false,
            'wa_license_key' => '',
            'wa_auto_download_media' => true,
            'enable_ai_forecasting' => false,
            'ai_forecasting_license_key' => null,
            'enable_menu_engineering' => false,
            'menu_engineering_license_key' => null,
            'ai_provider' => 'deepseek',
            'ai_model' => 'deepseek-chat',
            'ai_base_url' => 'https://api.deepseek.com',
            'ai_api_key' => null,
            'printer_width' => '58mm',
            'enable_table_number' => false,
            'bmkg_location_code' => null, // Default null
            // Fiscal Defaults
            'template_path' => null,
            'start_row' => 2,
            'date_column' => 'A',
            'amount_column' => 'B',
            'tax_column' => 'C',
            'enable_fiscal_planning' => false,
            'fiscal_license_key' => null,
            // Loyalty Defaults
            'loyalty_program_name' => 'Sedulur Suralaya',
            'loyalty_point_exchange_rate' => 10000,
            'loyalty_point_value' => 1,
            // WA Default Templates
            'wa_template_phase_1' => "Selamat sore, Bapak/Ibu {name}.\nMatur nuwun sampun rawuh di Kahyangan Suralaya.\nSemoga suasana dan hidangannya berkenan.\n\nKami informasikan, kunjungan Bapak/Ibu sudah tercatat sebagai Sedulur Suralaya, dengan total {points} poin di sistem kami.\n\nJika di lain waktu berkenan datang kembali, Suralaya selalu terbuka menyambut.",
            'wa_template_phase_2' => "Selamat sore, Bapak/Ibu {name}.\nTotal poin Anda saat ini: {points} poin.\n\nSebagai Sedulur Suralaya, setiap kunjungan akan tercatat sebagai poin.\nPoin tersebut dapat digunakan untuk menikmati hidangan atau wedhang tertentu di kunjungan berikutnya.\n\nDi Suralaya, yang utama bukan seberapa besar belanja, tapi seberapa sering sampun rawuh.",
            'wa_template_phase_3' => "Selamat sore, Bapak/Ibu {name}.\nKami informasikan, status Sedulur panjenengan kini menjadi Sedulur Tinetes.\n\nPoin yang telah terkumpul dapat digunakan langsung di kasir atau ditukar dengan menu tertentu sesuai ketentuan.\nMatur nuwun sampun rawuh ajeg wonten Suralaya.",
            'wa_template_faq_benefit' => "Poin Sedulur kami siapkan sebagai bentuk apresiasi untuk tamu yang berkenan datang kembali.\nPoin tersebut nantinya bisa digunakan untuk menikmati hidangan atau wedhang tertentu di kunjungan berikutnya.\n\nDi Suralaya, yang utama bukan seberapa besar belanja, tetapi seberapa sering sampun rawuh.",
            'wa_template_faq_redemption' => "Untuk penukaran, kami sesuaikan dengan jumlah poin dan frekuensi kunjungan Sedulur.\nJadi semakin sering rawuh, manfaatnya akan semakin terasa.",
            'wa_template_faq_use_points' => "Untuk saat ini, poinnya masih kami simpan terlebih dahulu, dan akan bisa digunakan setelah ada kunjungan berikutnya.\nKami ingin manfaatnya benar-benar terasa untuk Sedulur yang rawuh ajeg.\nMatur nuwun atas pengertiannya.",

            // Reservation Default
            'wa_template_reservation_confirmation' => "Halo Kak {customer_name},\n\nTerima kasih sudah melakukan reservasi di *{app_name}*.\nBerikut detail reservasinya ya:\n\n📅 Tanggal: {date}\n⏰ Jam: {time}\n👥 Jumlah: {guests} Orang\n\nDitunggu kedatangannya ya Kak! 😊",

            'ai_crm_system_prompt' => "Anda adalah CRM Specialist untuk sebuah restoran bernama '{app_name}'. 
                Tugas Anda adalah merangkai pesan WhatsApp yang SANGAT PERSONAL, hangat, dan otentik.

                WAJIB GUNAKAN DATA BERIKUT UNTUK PERSONALISASI:
                1. Gunakan nama pelanggan dengan sapaan Kakak/Kak/Boss.
                2. Gunakan EMOJI yang banyak dan relevan di setiap paragraf agar pesan terlihat ceria (seperti: 👋 😊 ✨ 🍽️ 🍹 🚀).
                3. Sebutkan program loyalitas '{program_name}' dan status mereka.
                4. Tawarkan promo {available_promos} jika tersedia secara natural.
                5. Gunakan bahasa Indonesia yang santai tapi tetap sopan.
                6. Hindari format kaku. Buat kesan seolah-olah admin sedang mengetik manual dengan penuh perhatian.",
        ];
    }
}

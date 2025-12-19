<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void
    {
        $this->migrator->add('app.wa_template_phase_1', "Selamat sore, Bapak/Ibu {name}.\nMatur nuwun sampun rawuh di Kahyangan Suralaya.\nSemoga suasana dan hidangannya berkenan.\n\nKami informasikan, kunjungan Bapak/Ibu sudah tercatat sebagai Sedulur Suralaya, dengan total {points} poin di sistem kami.\n\nJika di lain waktu berkenan datang kembali, Suralaya selalu terbuka menyambut.");
        $this->migrator->add('app.wa_template_phase_2', "Sebagai Sedulur Suralaya, setiap kunjungan akan tercatat sebagai poin.\nPoin tersebut dapat digunakan untuk menikmati hidangan atau wedhang tertentu di kunjungan berikutnya.\n\nDi Suralaya, yang utama bukan seberapa besar belanja, tapi seberapa sering sampun rawuh.");
        $this->migrator->add('app.wa_template_phase_3', "Selamat sore, Bapak/Ibu {name}.\nKami informasikan, status Sedulur panjenengan kini menjadi Sedulur Tinetes.\n\nPoin yang telah terkumpul dapat digunakan langsung di kasir atau ditukar dengan menu tertentu sesuai ketentuan.\nMatur nuwun sampun rawuh ajeg wonten Suralaya.");
        $this->migrator->add('app.wa_template_faq_benefit', "Poin Sedulur kami siapkan sebagai bentuk apresiasi untuk tamu yang berkenan datang kembali.\nPoin tersebut nantinya bisa digunakan untuk menikmati hidangan atau wedhang tertentu di kunjungan berikutnya.\n\nDi Suralaya, yang utama bukan seberapa besar belanja, tetapi seberapa sering sampun rawuh.");
        $this->migrator->add('app.wa_template_faq_redemption', "Untuk penukaran, kami sesuaikan dengan jumlah poin dan frekuensi kunjungan Sedulur.\nJadi semakin sering rawuh, manfaatnya akan semakin terasa.");
        $this->migrator->add('app.wa_template_faq_use_points', "Untuk saat ini, poinnya masih kami simpan terlebih dahulu, dan akan bisa digunakan setelah ada kunjungan berikutnya.\nKami ingin manfaatnya benar-benar terasa untuk Sedulur yang rawuh ajeg.\nMatur nuwun atas pengertiannya.");
    }
};

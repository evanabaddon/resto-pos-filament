<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void
    {
        $this->migrator->add('app.wa_template_reservation_confirmation', "Halo Kak {customer_name},\n\nTerima kasih sudah melakukan reservasi di *{app_name}*.\nBerikut detail reservasinya ya:\n\n📅 Tanggal: {date}\n⏰ Jam: {time}\n👥 Jumlah: {guests} Orang\n\nDitunggu kedatangannya ya Kak! 😊");
    }
};

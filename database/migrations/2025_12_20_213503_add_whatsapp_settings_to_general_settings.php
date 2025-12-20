<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void
    {
        $this->migrator->add('app.enable_wa_center', false);
        $this->migrator->add('app.wa_license_key', null);
    }
};

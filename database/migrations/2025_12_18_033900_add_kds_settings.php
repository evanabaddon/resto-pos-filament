<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void
    {
        $this->migrator->add('app.enable_kds', false);
        $this->migrator->add('app.kds_license_key', null);
    }
};

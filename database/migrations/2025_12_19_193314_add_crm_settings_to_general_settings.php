<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void
    {
        $this->migrator->add('app.enable_crm', false);
        $this->migrator->add('app.crm_license_key', null);
    }
};

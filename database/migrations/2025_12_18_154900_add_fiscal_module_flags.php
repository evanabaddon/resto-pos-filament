<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void
    {
        $this->migrator->add('fiscal.enable_fiscal_planning', false);
        $this->migrator->add('fiscal.fiscal_license_key', null);
    }
};

<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void
    {
        $this->migrator->add('app.enable_tax', false);
        $this->migrator->add('app.tax_percentage', 0);
    }
};

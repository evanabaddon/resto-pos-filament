<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void
    {
        $this->migrator->add('app.bmkg_location_code', null);
    }

    public function down(): void
    {
        $this->migrator->delete('app.bmkg_location_code');
    }
};

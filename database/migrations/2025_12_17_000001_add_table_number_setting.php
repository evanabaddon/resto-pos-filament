<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void
    {
        $this->migrator->add('app.enable_table_number', false);
    }

    public function down(): void
    {
        $this->migrator->delete('app.enable_table_number');
    }
};

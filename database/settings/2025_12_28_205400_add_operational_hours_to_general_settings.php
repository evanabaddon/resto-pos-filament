<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void
    {
        $this->migrator->add('app.operational_start_hour', 10);
        $this->migrator->add('app.operational_end_hour', 22);
    }
};

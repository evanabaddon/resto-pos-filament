<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('app.enable_ai_forecasting', false);
        $this->migrator->add('app.ai_forecasting_license_key', null);
    }

    public function down(): void
    {
        $this->migrator->delete('app.enable_ai_forecasting');
        $this->migrator->delete('app.ai_forecasting_license_key');
    }
};

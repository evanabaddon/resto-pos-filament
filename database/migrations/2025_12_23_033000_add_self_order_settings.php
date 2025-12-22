<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('app.enable_self_order', false);
        $this->migrator->add('app.self_order_license_key', null);
    }
};

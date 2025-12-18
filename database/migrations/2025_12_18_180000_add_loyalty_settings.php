<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void
    {
        $this->migrator->add('app.loyalty_program_name', 'Sedulur Suralaya');
        $this->migrator->add('app.loyalty_point_exchange_rate', 10000);
    }
};

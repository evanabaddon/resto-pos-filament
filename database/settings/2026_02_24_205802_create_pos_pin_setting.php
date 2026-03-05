<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        try {
            $this->migrator->add('app.pos_pin', '123456');
        } catch (\Exception $e) {
            // Ignore if already exists
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\LaravelSettings\Migrations\SettingsMigrator;

return new class extends Migration
{
    public function up(): void
    {
        $migrator = app(SettingsMigrator::class);

        $migrator->add('landing.contact_image', null);
    }

    public function down(): void
    {
        $migrator = app(SettingsMigrator::class);

        $migrator->delete('landing.contact_image');
    }
};

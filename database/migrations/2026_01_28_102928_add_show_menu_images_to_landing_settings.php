<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\LaravelSettings\Migrations\SettingsMigrator;

return new class extends Migration {
    public function up(): void
    {
        $migrator = app(SettingsMigrator::class);

        $migrator->add('landing.show_menu_images', true);
    }

    public function down(): void
    {
        $migrator = app(SettingsMigrator::class);

        $migrator->delete('landing.show_menu_images');
    }
};

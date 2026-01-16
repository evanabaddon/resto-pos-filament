<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\LaravelSettings\Migrations\SettingsMigrator;

return new class extends Migration
{
    public function up(): void
    {
        $migrator = app(SettingsMigrator::class);

        $migrator->add('landing.about_image_1', null);
        $migrator->add('landing.about_image_2', null);
        $migrator->add('landing.about_image_3', null);
        $migrator->add('landing.about_image_4', null);
    }

    public function down(): void
    {
        $migrator = app(SettingsMigrator::class);

        $migrator->delete('landing.about_image_1');
        $migrator->delete('landing.about_image_2');
        $migrator->delete('landing.about_image_3');
        $migrator->delete('landing.about_image_4');
    }
};

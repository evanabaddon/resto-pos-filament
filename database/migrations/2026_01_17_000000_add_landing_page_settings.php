<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\LaravelSettings\Migrations\SettingsMigrator;

return new class extends Migration
{
    public function up(): void
    {
        $migrator = app(SettingsMigrator::class);

        $migrator->add('landing.hero_title', 'Welcome to Our Restaurant');
        $migrator->add('landing.hero_description', 'Experience the best dining in town with our curated menu and exceptional service.');
        $migrator->add('landing.hero_image', null);

        $migrator->add('landing.primary_color', '#Eab308'); // Yellow-500
        $migrator->add('landing.secondary_color', '#1f2937'); // Gray-800

        $migrator->add('landing.about_us_text', 'We serve the most delicious food prepared with love and fresh ingredients.');

        $migrator->add('landing.seo_title', 'Best Restaurant in Town');
        $migrator->add('landing.seo_description', 'Fine dining restaurant serving local and international cuisine.');
        $migrator->add('landing.seo_keywords', 'restaurant, food, dining');
    }

    public function down(): void
    {
        $migrator = app(SettingsMigrator::class);

        $migrator->delete('landing.hero_title');
        $migrator->delete('landing.hero_description');
        $migrator->delete('landing.hero_image');
        $migrator->delete('landing.primary_color');
        $migrator->delete('landing.secondary_color');
        $migrator->delete('landing.about_us_text');
        $migrator->delete('landing.seo_title');
        $migrator->delete('landing.seo_description');
        $migrator->delete('landing.seo_keywords');
    }
};

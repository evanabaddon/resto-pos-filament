<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\LaravelSettings\Migrations\SettingsMigrator;

return new class extends Migration
{
    public function up(): void
    {
        $migrator = app(SettingsMigrator::class);

        $migrator->add('landing.about_us_title', 'Authentic Tastes, Modern Twist.');
        $migrator->add('landing.stats_years', '15+');
        $migrator->add('landing.stats_customers', '10k+');
    }

    public function down(): void
    {
        $migrator = app(SettingsMigrator::class);

        $migrator->delete('landing.about_us_title');
        $migrator->delete('landing.stats_years');
        $migrator->delete('landing.stats_customers');
    }
};

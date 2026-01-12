<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $migrator = app(\Spatie\LaravelSettings\Migrations\SettingsMigrator::class);

        // Remove old BMKG setting
        $migrator->delete('app.bmkg_location_code');

        // Add new OpenWeather settings
        $migrator->add('app.latitude', null);
        $migrator->add('app.longitude', null);
        $migrator->add('app.openweather_api_key', null);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $migrator = app(\Spatie\LaravelSettings\Migrations\SettingsMigrator::class);

        // Restore BMKG setting
        $migrator->add('app.bmkg_location_code', null);

        // Remove OpenWeather settings
        $migrator->delete('app.latitude');
        $migrator->delete('app.longitude');
        $migrator->delete('app.openweather_api_key');
    }
};

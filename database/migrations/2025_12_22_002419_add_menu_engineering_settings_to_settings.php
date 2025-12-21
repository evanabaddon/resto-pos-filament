
<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('app.enable_menu_engineering', false);
        $this->migrator->add('app.menu_engineering_license_key', null);
    }

    public function down(): void
    {
        $this->migrator->delete('app.enable_menu_engineering');
        $this->migrator->delete('app.menu_engineering_license_key');
    }
};

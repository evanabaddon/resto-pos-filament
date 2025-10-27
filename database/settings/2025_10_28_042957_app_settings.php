<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('app.app_name', config('app.name', 'Laravel'));
        $this->migrator->add('app.app_website', 'https://suralaya.id');
        $this->migrator->add('app.app_instagram', '');
        $this->migrator->add('app.app_tiktok', '');
        $this->migrator->add('app.app_logo', '');
        $this->migrator->add('app.app_favicon', '');
    }
};

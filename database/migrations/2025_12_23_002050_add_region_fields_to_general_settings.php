<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('app.province_code', null);
        $this->migrator->add('app.province_name', null);
        $this->migrator->add('app.regency_code', null);
        $this->migrator->add('app.regency_name', null);
        $this->migrator->add('app.district_code', null);
        $this->migrator->add('app.district_name', null);
        $this->migrator->add('app.village_code', null);
        $this->migrator->add('app.village_name', null);
        $this->migrator->add('app.postal_code', null);
    }
};

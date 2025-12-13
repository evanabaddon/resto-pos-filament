<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void
    {
        $this->migrator->add('app.company_address', 'Jl. Raya No. 123');
        $this->migrator->add('app.company_phone', '08123456789');
        $this->migrator->add('app.company_email', 'info@example.com');
    }
};

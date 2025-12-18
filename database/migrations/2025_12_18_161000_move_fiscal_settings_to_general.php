<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void
    {
        // Add Fiscal Settings to GeneralSettings (group: 'app')
        $this->migrator->add('app.template_path', null);
        $this->migrator->add('app.start_row', 2);
        $this->migrator->add('app.date_column', 'A');
        $this->migrator->add('app.amount_column', 'B');
        $this->migrator->add('app.tax_column', 'C');
        $this->migrator->add('app.enable_fiscal_planning', false);
        $this->migrator->add('app.fiscal_license_key', null);

        // Optionally remove old 'fiscal' group settings if we want to clean up immediately
        // $this->migrator->delete('fiscal.template_path');
        // ...
    }
};

<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void
    {
        $this->migrator->add('fiscal.template_path', null);
        $this->migrator->add('fiscal.start_row', 5);
        $this->migrator->add('fiscal.date_column', 'A');
        $this->migrator->add('fiscal.amount_column', 'C');
        $this->migrator->add('fiscal.tax_column', 'D');
    }
};

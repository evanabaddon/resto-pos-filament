<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('printer.kitchen_printer_ip', '192.168.1.100');
        $this->migrator->add('printer.kitchen_printer_port', '9100');
        $this->migrator->add('printer.bar_printer_ip', '192.168.1.101');
        $this->migrator->add('printer.bar_printer_port', '9100');
        $this->migrator->add('printer.general_printer_ip', '192.168.1.102');
        $this->migrator->add('printer.general_printer_port', '9100');
    }
};

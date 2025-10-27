<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Tambahkan field-field USB baru
        $this->migrator->add('printer.printer_type', 'usb');
        $this->migrator->add('printer.usb_printer_mode', 'single');
        $this->migrator->add('printer.usb_printer_name', 'POS-58');
        $this->migrator->add('printer.usb_kitchen_printer_name', null);
        $this->migrator->add('printer.usb_bar_printer_name', null);
        $this->migrator->add('printer.usb_general_printer_name', null);
    }
};

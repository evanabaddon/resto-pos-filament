<?php

use Spatie\LaravelSettings\Settings;

class PrinterSettings extends Settings
{
    public string $kitchen_printer_ip;
    public string $kitchen_printer_port;
    public string $bar_printer_ip;
    public string $bar_printer_port;
    public string $general_printer_ip;
    public string $general_printer_port;
    
    public static function group(): string
    {
        return 'printer';
    }
}
<?php

namespace App\Settings;
use Spatie\LaravelSettings\Settings;

class PrinterSettings extends Settings
{
    public string $printer_type = 'usb';
    public string $usb_printer_mode = 'single';
    public string $usb_printer_name = 'POS-58';
    
    public ?string $usb_kitchen_printer_name = null;
    public ?string $usb_bar_printer_name = null;
    public ?string $usb_general_printer_name = null;
    
    public ?string $kitchen_printer_ip = null;
    public ?string $kitchen_printer_port = null;
    
    public ?string $bar_printer_ip = null;
    public ?string $bar_printer_port = null;
    
    public ?string $general_printer_ip = null;
    public ?string $general_printer_port = null;
    
    // Webhook / Cloud Print Settings
    public bool $use_webhook = false;
    public ?string $webhook_url = null;
    public ?string $print_secret = null;

    public static function group(): string
    {
        return 'printer';
    }
}
<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class FiscalSettings extends Settings
{
    public ?string $template_path;
    public int $start_row;
    public string $date_column;
    public string $amount_column; // Omzet
    public string $tax_column;    // Pajak

    public static function group(): string
    {
        return 'fiscal';
    }
}

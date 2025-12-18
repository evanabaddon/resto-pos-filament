<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public string $app_name;
    public ?string $app_website;
    public ?string $app_instagram;
    public ?string $app_tiktok;
    public ?string $app_logo;
    public ?string $app_favicon;

    // New Company Fields
    public ?string $company_address;
    public ?string $company_phone;
    public ?string $company_email;

    // Modules
    public bool $enable_hrm;
    public ?string $hrm_license_key;

    public bool $enable_kds;
    public ?string $kds_license_key;

    public string $printer_width;
    public bool $enable_table_number;

    // Fiscal Settings
    public ?string $template_path;
    public int $start_row;
    public string $date_column;
    public string $amount_column; // Omzet
    public string $tax_column;    // Pajak
    public bool $enable_fiscal_planning;
    public ?string $fiscal_license_key;

    // Loyalty Settings
    public string $loyalty_program_name;
    public int $loyalty_point_exchange_rate; // e.g. 10000 = 1 point
    public int $loyalty_point_value; // e.g. 1 point = 1 IDR

    public static function group(): string
    {
        return 'app';
    }

    public static function defaults(): array
    {
        return [
            'app_name' => config('app.name', 'Laravel'),
            'app_website' => 'https://suralaya.id',
            'app_instagram' => '',
            'app_tiktok' => '',
            'app_logo' => null,
            'app_favicon' => null,
            'enable_hrm' => false,
            'hrm_license_key' => null,
            'enable_kds' => false,
            'kds_license_key' => null,
            'printer_width' => '58mm',
            'enable_table_number' => false,
            // Fiscal Defaults
            'template_path' => null,
            'start_row' => 2,
            'date_column' => 'A',
            'amount_column' => 'B',
            'tax_column' => 'C',
            'enable_fiscal_planning' => false,
            'fiscal_license_key' => null,
            // Loyalty Defaults
            'loyalty_program_name' => 'Sedulur Suralaya',
            'loyalty_point_exchange_rate' => 10000,
            'loyalty_point_value' => 1,
        ];
    }
}

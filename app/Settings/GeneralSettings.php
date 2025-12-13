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

    public static function group(): string
    {
        return 'app';
    }
}

<?php

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public string $app_name;
    public string $app_website;
    public string $app_instagram;
    public string $app_tiktok;
    public ?string $app_logo;
    public ?string $app_favicon;
    
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
        ];
    }
}
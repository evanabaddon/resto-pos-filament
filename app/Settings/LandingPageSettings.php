<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class LandingPageSettings extends Settings
{
    public string $hero_title;
    public string $hero_description;
    public ?string $hero_image;

    public string $primary_color;
    public string $secondary_color;

    public string $about_us_title;
    public string $about_us_text; // Kept as main text

    public ?string $about_image_1;
    public ?string $about_image_2;
    public ?string $about_image_3;
    public ?string $about_image_4;

    public ?string $contact_image;
    public ?string $reservation_image;
    public ?string $google_maps_url;

    public string $stats_years;
    public string $stats_customers;

    public ?string $seo_title;
    public ?string $seo_description;
    public ?string $seo_keywords;

    public static function group(): string
    {
        return 'landing';
    }

    public static function defaults(): array
    {
        return [
            'hero_title' => 'Welcome to Our Restaurant',
            'hero_description' => 'Experience the best dining in town with our curated menu and exceptional service.',
            'hero_image' => null,
            'primary_color' => '#Eab308', // Yellow-500
            'secondary_color' => '#1f2937', // Gray-800
            'about_us_title' => 'Authentic Tastes, Modern Twist.',
            'about_us_text' => 'We serve the most delicious food prepared with love and fresh ingredients.',
            'about_image_1' => null,
            'about_image_2' => null,
            'about_image_3' => null,
            'about_image_4' => null,
            'contact_image' => null,
            'reservation_image' => null,
            'google_maps_url' => 'https://maps.google.com',
            'stats_years' => '15+',
            'stats_customers' => '10k+',
            'seo_title' => 'Best Restaurant in Town',
            'seo_description' => 'Fine dining restaurant serving local and international cuisine.',
            'seo_keywords' => 'restaurant, food, dining',
        ];
    }
}

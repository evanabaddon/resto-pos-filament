<?php

namespace App\Http\Controllers;

use App\Settings\GeneralSettings;
use App\Settings\LandingPageSettings;

class CustomerDisplayController extends Controller
{
    public function index()
    {
        $settings = app(GeneralSettings::class);

        // Get or initialize LandingPageSettings
        try {
            $landingSettings = app(LandingPageSettings::class);
        } catch (\Throwable $e) {
            // If settings don't exist, initialize with defaults
            $landingSettings = app(LandingPageSettings::class);
            $landingSettings->hero_title = 'Welcome';
            $landingSettings->hero_description = 'Description';
            $landingSettings->hero_image = null;
            $landingSettings->primary_color = '#667eea';
            $landingSettings->secondary_color = '#764ba2';
            $landingSettings->about_us_title = 'About Us';
            $landingSettings->about_us_text = 'About text';
            $landingSettings->about_image_1 = null;
            $landingSettings->about_image_2 = null;
            $landingSettings->about_image_3 = null;
            $landingSettings->about_image_4 = null;
            $landingSettings->contact_image = null;
            $landingSettings->reservation_image = null;
            $landingSettings->google_maps_url = '';
            $landingSettings->stats_years = 0;
            $landingSettings->stats_customers = 0;
            $landingSettings->seo_title = 'Restaurant';
            $landingSettings->seo_description = 'Description';
            $landingSettings->seo_keywords = 'restaurant';
            $landingSettings->seo_google_verification = '';
            $landingSettings->footer_description = 'Footer';
            $landingSettings->save();
        }

        return view('customer-display', [
            'settings' => $settings,
            'landingSettings' => $landingSettings
        ]);
    }
}

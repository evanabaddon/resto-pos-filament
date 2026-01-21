<?php

namespace App\Http\Controllers;

use App\Settings\GeneralSettings;
use App\Settings\LandingPageSettings;

class CustomerDisplayController extends Controller
{
    public function index()
    {
        $settings = app(GeneralSettings::class);

        // Always use default colors - don't try to load LandingPageSettings
        // This prevents any MissingSettings errors
        $landingSettings = (object) [
            'primary_color' => '#667eea',
            'secondary_color' => '#764ba2'
        ];

        return view('customer-display', [
            'settings' => $settings,
            'landingSettings' => $landingSettings
        ]);
    }
}

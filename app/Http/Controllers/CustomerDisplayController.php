<?php

namespace App\Http\Controllers;

use App\Settings\GeneralSettings;
use App\Settings\LandingPageSettings;

class CustomerDisplayController extends Controller
{
    public function index()
    {
        $settings = app(GeneralSettings::class);

        // Try to get landing page settings with proper error handling
        try {
            $landingSettings = app(LandingPageSettings::class);
        } catch (\Exception $e) {
            // If settings don't exist, create default object with default colors
            $landingSettings = new class {
                public $primary_color = '#667eea';
                public $secondary_color = '#764ba2';
            };
        }

        return view('customer-display', [
            'settings' => $settings,
            'landingSettings' => $landingSettings
        ]);
    }
}

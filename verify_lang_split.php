<?php

use Illuminate\Contracts\Console\Kernel;

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

// Test Settings keys
$keys = [
    'ai_system_prompt_helper',
    'ai_api_config',
    'auto_download_media_helper',
    'enable_ai_forecasting_desc',
    'enable_menu_engineering_desc',
    'ai_api_config_desc',
    'wa_template_reservation',
    'point_value_helper'
];

foreach (['en', 'id'] as $locale) {
    app()->setLocale($locale);
    echo "LOCALE: " . strtoupper($locale) . "\n";
    foreach ($keys as $key) {
        $val = __('messages.' . $key);
        // Truncate for display
        $display = strlen($val) > 50 ? substr($val, 0, 47) . '...' : $val;
        echo "  {$key}: " . $display . "\n";
    }
    echo "\n";
}

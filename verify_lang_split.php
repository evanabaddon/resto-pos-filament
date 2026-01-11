<?php

use Illuminate\Contracts\Console\Kernel;

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

// Test Dashboard keys
$keys = [
    'ai_nav_label',
    'context_analysis_header',
    'thinking',
    'slug',
    'track_stock_helper',
    'min_stock_helper',
    'hpp_updated_title',
    'discount_code',
    'payment_code_helper',
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

<?php

use Illuminate\Contracts\Console\Kernel;

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

// Test Dashboard keys
$keys = [
    'critical_stock_title',
    'today_revenue',
    'create_new_reservation',
    'customer_info',
    'confirm_status_change_modal',
    'no_reservation_selected',
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

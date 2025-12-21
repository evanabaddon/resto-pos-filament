<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Member;

$member = Member::where('name', 'like', '%Rose%')->first();

if ($member) {
    echo "ID: {$member->id} | Name: {$member->name}\n";
    echo "Last Visit: {$member->last_visit_at} | Raw: {$member->getRawOriginal('last_visit_at')}\n";

    // Check sales
    foreach ($member->sales as $sale) {
        echo "Sale ID: {$sale->id} | Invoice: {$sale->invoice_number} | Created At: {$sale->created_at} | Raw: {$sale->getRawOriginal('created_at')}\n";
    }
} else {
    echo "Member Rose not found.\n";
    echo "Total members: " . Member::count() . "\n";
}

echo "DB Connection Config Timezone: " . config('database.connections.mysql.timezone') . "\n";

<?php

// 1. Setup Test Data
$beras = App\Models\Product::where('name', 'LIKE', '%Beras%')->first();
if (!$beras) {
    echo "Beras not found\n";
    exit;
}
$beras->stock = 1000; // 1kg
$beras->save();

$nasi = App\Models\Product::where('name', 'LIKE', '%Sega Putih%')->first();
if (!$nasi) {
    echo "Nasi not found\n";
    exit;
}
$nasi->prepared_stock = 0;
$nasi->save();

echo "Initial State:\n";
echo " - Beras: {$beras->stock}\n";
echo " - Sega (Prepared): {$nasi->prepared_stock}\n";

// 2. Simulate Production (Cook 5 portions)
// Recipe: 80g Beras per portion. 5 * 80 = 400g needed.
$widget = new App\Filament\Widgets\CriticalStockWidget();
$widget->recordProduction($nasi->id, 5);

$beras->refresh();
$nasi->refresh();

echo "\nAfter Cooking 5 Portions:\n";
echo " - Beras: {$beras->stock} (Expected: 600)\n";
echo " - Sega: {$nasi->prepared_stock} (Expected: 5)\n";

// 3. Simulate Reset (Waste)
$widget->resetStock($nasi->id);

$nasi->refresh();
echo "\nAfter Reset (Waste):\n";
echo " - Sega: {$nasi->prepared_stock} (Expected: 0)\n";

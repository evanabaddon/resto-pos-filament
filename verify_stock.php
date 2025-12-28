<?php

$jeruk = App\Models\Product::find(34); // Jeruk (raw)
if (!$jeruk) {
    echo 'Jeruk Raw not found';
    exit;
}

$jeruk->stock = 3;
$jeruk->updated_at = now();
$jeruk->save();
echo 'Updated Jeruk (ID ' . $jeruk->id . ') to Stock: ' . $jeruk->stock . PHP_EOL;

$p1 = App\Models\Product::find(48); // ES JERUK (bar)
$p2 = App\Models\Product::find(35); // JERUK PANAS (bar)

if (!$p1 || !$p2) {
    echo 'Products not found.' . PHP_EOL;
    exit;
}

$checker = app(App\Services\RecipeStockChecker::class);

echo '--- SCENARIO: Cart has 1 ' . $p2->name . '. Check ' . $p1->name . ' (Qty 2)---' . PHP_EOL;
// Stock: 3.
// Cart: 1 JERUK PANAS (Uses 1 Jeruk).
// Remaining for Es Jeruk: 2.
// Check: 2 Es Jeruk.
// Total Needed: 1 + 2 = 3. Available: 3. Expect: AVAILABLE.

$cart = [$p2->id => ['qty' => 1]];
$avail = $checker->checkAvailability($p1, 2, $cart);
echo 'Result (Check 2 with Cart 1): ' . ($avail['available'] ? 'AVAILABLE' : 'HABIS') . ' (Max: ' . $avail['max_portions'] . ')' . PHP_EOL;

echo '--- SCENARIO: Check ' . $p1->name . ' (Qty 3) with same cart---' . PHP_EOL;
// Stock: 3.
// Cart: 1 JERUK PANAS (Uses 1 Jeruk).
// Check: 3 Es Jeruk.
// Total Needed: 1 + 3 = 4. Available: 3. Expect: HABIS.

$avail2 = $checker->checkAvailability($p1, 3, $cart);
echo 'Result (Check 3 with Cart 1): ' . ($avail2['available'] ? 'AVAILABLE' : 'HABIS') . ' (Max: ' . $avail2['max_portions'] . ')' . PHP_EOL;

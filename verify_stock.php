<?php

$jeruk = App\Models\Product::where('name', 'LIKE', '%Jeruk%')->where('type', 'raw')->first();
$jeruk->stock = 3;
$jeruk->updated_at = now();
$jeruk->save();
echo 'Updated Jeruk (ID ' . $jeruk->id . ') to Stock: ' . $jeruk->stock . PHP_EOL;

$p1 = App\Models\Product::where('name', 'LIKE', '%Es Jeruk%')->first();
$p2 = App\Models\Product::where('name', 'LIKE', '%Jeruk Anget%')->where('name', '!=', 'Jeruk Anget (Susu)')->first();

if (!$p1 || !$p2) {
    echo 'Products not found.' . PHP_EOL;
    exit;
}

$checker = app(App\Services\RecipeStockChecker::class);

echo '--- SCENARIO: Cart has 1 ' . $p2->name . '. Check ' . $p1->name . ' (Qty 3)---' . PHP_EOL;
// Cart: 1 Jeruk Anget. Check: 3 Es Jeruk. Total: 4. Stock: 3. Expect: HABIS (No available).

$cart = [$p2->id => ['qty' => 1]];
$avail = $checker->checkAvailability($p1, 3, $cart);

echo 'Result: ' . ($avail['available'] ? 'AVAILABLE' : 'HABIS') . ' (Max: ' . $avail['max_portions'] . ')' . PHP_EOL;

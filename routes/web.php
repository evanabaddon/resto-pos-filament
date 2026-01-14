<?php

use App\Models\PrintJob;
use App\Services\OrderPrintService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

Route::get('/', function () {
    return redirect('/admin');
});

// Webhook routes
require __DIR__ . '/webhook.php';

// QR Self Order Routes
Route::middleware(['web', 'self-order.enabled'])->group(function () {
    Route::get('/scan/{slug}', function ($slug) {
        $table = \App\Models\Table::where('slug', $slug)->firstOrFail();

        // Check if table is available or occupied (doesn't matter much for ordering, but good to know)
        // Store table info in session
        session(['table_id' => $table->id, 'table_slug' => $table->slug]);

        return redirect()->route('order.menu');
    })->name('order.scan');

    Route::get('/order/menu', \App\Livewire\SelfOrder\Menu::class)->name('order.menu');
    Route::get('/order/cart', \App\Livewire\SelfOrder\Cart::class)->name('order.cart');
    Route::get('/order/checkout', \App\Livewire\SelfOrder\Checkout::class)->name('order.checkout');
});

// Waiter Order Routes
Route::middleware(['web', \Filament\Http\Middleware\Authenticate::class])->prefix('waiter')->name('waiter.')->group(function () {
    Route::get('/order', \App\Livewire\WaiterOrder\WaiterMenu::class)->name('order');
    Route::get('/cart', \App\Livewire\WaiterOrder\WaiterCart::class)->name('cart');
    Route::get('/checkout', \App\Livewire\WaiterOrder\WaiterCheckout::class)->name('checkout');
});

Route::get('/kiosk', App\Livewire\AttendanceKiosk::class);

// Stock Opname Print Form
Route::middleware(['web', \Filament\Http\Middleware\Authenticate::class])->group(function () {
    Route::get('/stock-opname/print', [App\Http\Controllers\StockOpnameController::class, 'printForm'])
        ->name('stock-opname.print');
});


// routes/api.php atau routes/web.php
Route::get('/test-webhook-debug', function () {
    $printService = new OrderPrintService();

    $envTest = $printService->testEnvironment();

    // Test webhook connection dengan debug
    try {
        $webhookUrl = config('app.webhook_print_url');
        $secretKey = config('app.print_secret');

        Log::info("🔍 Testing webhook connection to: " . $webhookUrl);

        $response = Http::timeout(10)
            ->withOptions(['verify' => false])
            ->withHeaders([
                'X-Print-Secret' => $secretKey,
                'Content-Type' => 'application/json',
            ])
            ->post($webhookUrl, [
                'content' => "TEST CONNECTION\n" . now()->format('Y-m-d H:i:s'),
                'printer' => 'TEST',
                'division' => 'Test',
                'type' => 'test'
            ]);

        // Fix: If Guzzle returns a promise (async), wait for it and wrap in Laravel Response
        if ($response instanceof \GuzzleHttp\Promise\PromiseInterface) {
            $response = new \Illuminate\Http\Client\Response($response->wait());
        }

        $webhookTest = [
            'success' => $response->successful(),
            'status' => $response->status(),
            'headers' => $response->headers(),
            'body' => $response->successful() ? $response->json() : substr($response->body(), 0, 500)
        ];
    } catch (\Exception $e) {
        $webhookTest = [
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ];
    }

    return response()->json([
        'environment_test' => $envTest,
        'webhook_test' => $webhookTest,
        'config_check' => [
            'webhook_url' => config('app.webhook_print_url'),
            'app_url' => config('app.url'),
            'print_secret_set' => !empty(config('app.print_secret')),
            'use_webhook_printing' => config('app.use_webhook_printing')
        ]
    ]);
});

// Route::get('/test-api-basic', function () {
//     try {
//         $response = Http::timeout(10)
//             ->withOptions(['verify' => false])
//             ->get('https://pos.suralaya.id/api/webhook/test');

//         if ($response instanceof \GuzzleHttp\Promise\PromiseInterface) {
//             $response = new \Illuminate\Http\Client\Response($response->wait());
//         }

//         return response()->json([
//             'success' => $response->successful(),
//             'status' => $response->status(),
//             'data' => $response->json()
//         ]);
//     } catch (\Exception $e) {
//         return response()->json([
//             'success' => false,
//             'error' => $e->getMessage()
//         ]);
//     }
// });

// routes/web.php - tambahkan route debug
Route::get('/debug-printing', function () {
    $printService = new OrderPrintService();

    $envTest = $printService->testEnvironment();
    $webhookTest = $printService->testWebhookConnection();

    // Check database untuk print jobs
    $pendingJobs = PrintJob::where('status', 'pending')->count();
    $totalJobs = PrintJob::count();

    return response()->json([
        'environment' => $envTest,
        'webhook_test' => $webhookTest,
        'database' => [
            'pending_jobs' => $pendingJobs,
            'total_jobs' => $totalJobs
        ],
        'config' => [
            'webhook_print_url' => config('app.webhook_print_url'),
            'use_webhook_printing' => config('app.use_webhook_printing'),
            'print_secret_set' => !empty(config('app.print_secret')),
            'app_env' => config('app.env'),
            'app_url' => config('app.url')
        ]
    ]);
});

// routes/web.php - temporary test routes
// Route::get('/test-api-routes', function () {
//     $tests = [];

//     // Test 1: API test endpoint
//     try {
//         $response = Http::timeout(10)
//             ->withOptions(['verify' => false])
//             ->get('https://pos.suralaya.id/api/webhook/test');

//         if ($response instanceof \GuzzleHttp\Promise\PromiseInterface) {
//             $response = new \Illuminate\Http\Client\Response($response->wait());
//         }

//         $tests['api_test'] = [
//             'success' => $response->successful(),
//             'status' => $response->status(),
//             'data' => $response->json()
//         ];
//     } catch (\Exception $e) {
//         $tests['api_test'] = [
//             'success' => false,
//             'error' => $e->getMessage()
//         ];
//     }

//     // Test 2: Config check
//     $tests['config'] = [
//         'webhook_print_url' => config('app.webhook_print_url'),
//         'use_webhook_printing' => config('app.use_webhook_printing'),
//         'print_secret' => config('app.print_secret') ? '***' . substr(config('app.print_secret'), -4) : 'not set'
//     ];

//     // Test 3: Database check
//     $tests['database'] = [
//         'print_jobs_table' => Schema::hasTable('print_jobs'),
//         'pending_jobs' => PrintJob::where('status', 'pending')->count()
//     ];

//     return response()->json($tests);
// });

// HRM Payroll Print Route
Route::get('/payroll/{record}/print', function (\App\Models\Payroll $record) {
    if (!Auth::check()) {
        abort(403);
    }
    return view('payroll.print', ['record' => $record]);
})->name('payroll.print');

// Sales Receipt Print Route (HTML)
Route::get('/sales/{sale}/print', function (\App\Models\Sale $sale) {
    if (!Auth::check()) {
        abort(403);
    }

    $sale->load(['items.product', 'paymentMethod', 'user']);
    $settings = app(\App\Settings\GeneralSettings::class);

    return view('receipt.print', [
        'sale' => $sale,
        'settings' => $settings
    ]);
})->name('sales.print');

// WhatsApp Avatar Proxy (To fix local logging issue in production)
Route::get('/filament/whatsapp/avatar/{jid}', function ($jid) {
    // Auth check removed - <img> tags don't send session cookies
    // Avatar images are public profile pictures, not sensitive data

    // Session lock release not needed without auth

    // 1. Check if we have positive cache (Image Data)
    $cacheKey = "wa_avatar_{$jid}";
    $cachedData = Cache::get($cacheKey);

    if ($cachedData) {
        // If cached value is 'missing', return 404 immediately
        if ($cachedData === 'missing') {
            return response()->noContent(404)->header('Cache-Control', 'public, max-age=10');
        }

        // Load image from storage file (public disk)
        if (Storage::disk('public')->exists("avatars/{$cachedData}")) {
            return response(Storage::disk('public')->get("avatars/{$cachedData}"))
                ->header('Content-Type', 'image/jpeg')
                ->header('Cache-Control', 'public, max-age=3600');
        }
    }

    // 2. Fetch from Gateway
    $gatewayUrl = rtrim(env('WA_GATEWAY_URL', 'http://127.0.0.1:3000'), '/');
    $url = "$gatewayUrl/avatar/$jid";

    try {
        // Timeout 5s to allow slower gateway responses
        // IMPORTANT: Follow redirects because gateway returns 302 to WhatsApp CDN
        Log::info("Avatar Debug: Fetching $url");
        $response = Http::timeout(5)->withOptions(['allow_redirects' => true])->get($url);

        if ($response->successful()) {
            $body = $response->body();

            // Check if gateway returned HTML redirect text (e.g., "Found. Redirecting to https://...")
            if (str_starts_with($body, 'Found. Redirecting to ')) {
                $redirectUrl = trim(str_replace('Found. Redirecting to ', '', $body));
                Log::info("Avatar Debug: Following redirect to $redirectUrl");

                // Fetch actual image from WhatsApp CDN
                $imageResponse = Http::timeout(10)->get($redirectUrl);
                if ($imageResponse->successful()) {
                    $body = $imageResponse->body();
                } else {
                    Log::error("Avatar Debug: Failed to fetch from CDN: " . $imageResponse->status());
                    Cache::put($cacheKey, 'missing', 600);
                    return response()->noContent(404)->header('Cache-Control', 'public, max-age=10');
                }
            }

            // Check if gateway returned "No profile picture" text instead of image
            if (trim($body) === 'No profile picture' || strlen($body) < 100) {
                Log::warning("Avatar Debug: Gateway returned 'No profile picture' for $url");
                Cache::put($cacheKey, 'missing', 600);
                return response()->noContent(404)->header('Cache-Control', 'public, max-age=10');
            }

            // Store image in storage/app/public/avatars (accessible via public disk)
            $filename = str_replace(['@', '.'], '_', $jid) . '.jpg';
            Storage::disk('public')->put("avatars/{$filename}", $body);

            // Cache the filename for 1 hour
            Cache::put($cacheKey, $filename, 3600);

            return response($body)
                ->header('Content-Type', $response->header('Content-Type', 'image/jpeg'))
                ->header('Cache-Control', 'public, max-age=3600');
        } elseif ($response->status() === 404) {
            Log::warning("Avatar Debug: Gateway returned 404 for $url");
            // 3. Cache Negative Result (Only if explicit 404)
            // Mark as missing for 10 minutes
            Cache::put($cacheKey, 'missing', 600);
        } else {
            Log::error("Avatar Debug: Gateway Error " . $response->status() . " for $url");
        }
    } catch (\Exception $e) {
        Log::error("Avatar Debug: Exception " . $e->getMessage() . " for $url");
        // Gateway offline or timeout
        // Cache as missing for 60 seconds to prevent immediate retry hammering
        Cache::put($cacheKey, 'missing', 60);
    }

    return response()->noContent(404)->header('Cache-Control', 'public, max-age=10');
})->name('whatsapp.avatar')->where('jid', '.*');

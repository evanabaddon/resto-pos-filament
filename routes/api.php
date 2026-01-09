<?php

use App\Models\CashSession;
use App\Models\PrintJob;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\RecipeStockChecker;
use App\Settings\GeneralSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/*
|--------------------------------------------------------------------------
| API Routes for Print Webhook
|--------------------------------------------------------------------------
*/

// POS Connection Test
Route::get('/pos/status', function () {
    return response()->json([
        'status' => 'connected',
        'message' => 'Resto POS Server is Online',
        'time' => now()->toIso8601String(),
    ]);
});

// POS Business Settings
Route::get('/pos/settings', function () {
    // Get settings from Spatie Settings
    $settings = app(GeneralSettings::class);

    return response()->json([
        'store_name' => $settings->app_name ?? 'Resto POS Filament',
        'store_address' => $settings->company_address ?? 'Jl. Nusantara No. 10, Jakarta',
        'store_phone' => $settings->company_phone ?? '0812-3456-7890',
        'receipt_header' => 'Selamat Datang!', // Could add to GeneralSettings if needed
        'receipt_footer' => 'Terima Kasih, Datang Kembali', // Could add to GeneralSettings if needed
        'tax_rate' => $settings->tax_percentage ?? 11, // From database
        'pos_pin' => '123456', // Simple PIN for now (could be from settings too)
    ]);
});

// POS Offline Sync Endpoint
Route::get('/pos/products-sync', function () {
    // 1. Ambil kandidat produk (Sellable & Bukan Down Payment)
    $query = Product::where('is_sellable', true)
        ->where('name', 'not like', '%Down Payment%');

    // Eager load untuk optimasi perhitungan resep
    $products = $query->with(['recipes.ingredient', 'recipes.unit', 'unit'])
        ->get()
        ->map(function ($product) {
            $realStock = 0;

            if ($product->type === 'service') {
                $realStock = 9999; // Unlimited for service
            } elseif (in_array($product->type, ['produced', 'bar'])) {
                // Untuk Food/Bar: Stock adalah Prepared Stock (yang sudah jadi) + Bahan Baku yang tersedia
                $prepared = $product->prepared_stock ?? 0;

                // Hitung potensi dari bahan baku (jika ada resep)
                $potential = 0;
                if ($product->recipes->isNotEmpty()) {
                    $potential = app(RecipeStockChecker::class)->getMaxPortions($product);
                }

                $realStock = $prepared + $potential;
            } else {
                // Untuk Retail/Raw/Lainnya: Gunakan stock fisik langsung
                $realStock = $product->stock ?? 0;
            }

            // Override stock di object produk untuk dikirim ke POS
            $product->stock = $realStock;

            return $product;
        })
        // 2. Filter: Hanya yang stocknya positif
        ->filter(function ($product) {
            return $product->stock > 0;
        })
        ->values(); // Reset array keys

    // Transformasi data untuk response
    $formattedProducts = $products->map(function ($product) {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'price' => (float) $product->sell_price,
            'stock' => (float) $product->stock,
            'prepared_stock' => (float) $product->prepared_stock, // Added
            'enable_stock_alert' => (bool) $product->enable_stock_alert, // Added
            'category_id' => $product->category_id,
            'image' => $product->image,
            'type' => $product->type,
        ];
    });

    // Categories
    $categories = \App\Models\Category::select('id', 'name')->get();

    // Payment Methods
    $paymentMethods = \App\Models\PaymentMethod::where('is_active', true)
        ->select('id', 'name', 'code', 'is_active')
        ->get();

    return response()->json([
        'products' => $formattedProducts,
        'categories' => $categories,
        'payment_methods' => $paymentMethods, // Added
        'timestamp' => now()->toIso8601String()
    ]);
});

// POS Offline Sales Sync Endpoint
Route::post(
    '/pos/sync-offline-sales',
    function (Request $request) {
        try {
            $orders = $request->input('orders', []);
            $syncedCount = 0;

            foreach ($orders as $orderData) {
                // Gunakan Transaction per order agar aman
                DB::transaction(function () use ($orderData) {
                    // Recalculate Totals from Items to avoid corrupted frontend data
                    $calculatedSubtotal = 0;
                    foreach ($orderData['items'] as $item) {
                        $price = isset($item['price']) ? (float) $item['price'] : 0;
                        $qty = isset($item['quantity']) ? (float) $item['quantity'] : 1;
                        $calculatedSubtotal += ($price * $qty);
                    }

                    $tax = isset($orderData['tax']) ? (float) $orderData['tax'] : 0;
                    $finalTotal = $calculatedSubtotal + $tax;

                    // Cari Cash Session yang sedang AKTIF (Open) untuk User Default (2)
                    $activeSession = CashSession::where('user_id', 2)
                        ->where('status', 'open')
                        ->latest()
                        ->first();

                    // 1. Create Sale as DRAFT (agar bisa dibayar nanti)
                    $sale = Sale::create([
                        'invoice_number' => 'OFFLINE-' . time() . '-' . uniqid(),
                        'customer_name' => $orderData['customer_name'] ?? 'Offline Customer',
                        'order_type' => $orderData['order_type'] ?? 'offline', // Penanda ini transaksi dari offline mode
                        'user_id' => 2, // Default user (Admin ID=2, ID=1 not exists)
                        'cash_session_id' => $activeSession ? $activeSession->id : null, // Link ke sesi aktif
    
                        'subtotal' => $calculatedSubtotal,
                        'tax' => $tax,
                        'final_total' => $finalTotal,
                        'total' => $finalTotal,

                        'payment_method' => $orderData['payment_method'] ?? null,
                        'payment_method_id' => $orderData['payment_method_id'] ?? null,
                        'status' => $orderData['status'] ?? 'draft', // Use status from client
                        'created_at' => $orderData['created_at'] ?? now(),
                    ]);

                    // 2. Create Items & Deduct Stock
                    foreach ($orderData['items'] as $item) {
                        SaleItem::create([
                            'sale_id' => $sale->id,
                            'product_id' => $item['product_id'] ?? $item['id'], // Handle both cases
                            'quantity' => $item['quantity'],
                            'unit_price' => $item['price'], // Schema uses unit_price
                            'subtotal' => $item['subtotal'],
                        ]);

                        // Deduct Stock
                        $product = Product::find($item['product_id'] ?? $item['id']);
                        if ($product && $product->stock !== null) {
                            $product->decrement('stock', $item['quantity']);
                        }
                    }
                });
                $syncedCount++;
            }

            return response()->json([
                'success' => true,
                'synced_count' => $syncedCount,
                'message' => "Successfully synced {$syncedCount} offline orders"
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Sync Failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
);

// Get Current Open Shift
Route::get('/pos/current-shift', function () {
    try {
        $openSession = CashSession::where('status', 'open')
            ->orderBy('opened_at', 'desc')
            ->first();

        if (!$openSession) {
            return response()->json(['shift' => null]);
        }

        return response()->json([
            'shift' => [
                'id' => $openSession->id,
                'server_id' => $openSession->id,
                'user_name' => $openSession->user->name ?? 'Unknown',
                'cash_in_hand' => $openSession->cash_in_hand,
                'status' => $openSession->status,
                'opened_at' => $openSession->opened_at,
            ]
        ]);
    } catch (\Exception $e) {
        Log::error('Failed to get current shift: ' . $e->getMessage());
        return response()->json([
            'shift' => null,
            'error' => $e->getMessage()
        ], 500);
    }
});

// Sync Shifts (Cash Sessions)
Route::post('/pos/sync-shifts', function (Request $request) {
    try {
        $shifts = $request->input('shifts', []);
        $results = [];

        foreach ($shifts as $shift) {
            // Check if shift already exists on server (by server_id if available)
            $cashSession = null;
            if (isset($shift['server_id']) && $shift['server_id']) {
                $cashSession = CashSession::find($shift['server_id']);
            }

            if ($cashSession) {
                // Update existing session
                $cashSession->update([
                    // 'cashier_name' => $shift['cashier_name'], // Column does not exist
                    'cash_in_hand' => $shift['cash_in_hand'],
                    'cash_out' => $shift['cash_out'] ?? null,
                    'status' => $shift['status'],
                    'opened_at' => $shift['opened_at'],
                    'closed_at' => $shift['closed_at'] ?? null,
                ]);

                $results[] = [
                    'local_id' => $shift['id'],
                    'server_id' => $cashSession->id,
                    'action' => 'updated'
                ];
            } else {
                // Resolve User by Name if provided
                $userId = 1; // SAFE DEFAULT (Admin/System)

                if (isset($shift['cashier_name']) && $shift['cashier_name']) {
                    $user = \App\Models\User::where('name', $shift['cashier_name'])->first();
                    if ($user) {
                        $userId = $user->id;
                    }
                }

                $cashSession = CashSession::create([
                    'user_id' => $userId,
                    'cash_in_hand' => $shift['cash_in_hand'],
                    'cash_out' => $shift['cash_out'] ?? null,
                    'status' => $shift['status'],
                    'opened_at' => $shift['opened_at'],
                    'closed_at' => $shift['closed_at'] ?? null,
                ]);

                $results[] = [
                    'local_id' => $shift['id'],
                    'server_id' => $cashSession->id,
                    'action' => 'created'
                ];
            }
        }

        return response()->json([
            'success' => true,
            'results' => $results
        ]);
    } catch (\Exception $e) {
        Log::error('Shift sync failed: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
});

// Get Server Drafts (Today)
Route::get('/pos/drafts', function () {
    try {
        $drafts = Sale::with('items.product')
            ->where('status', 'draft')
            // ->whereDate('created_at', now()->today()) // REMOVED to avoid timezone issues
            ->latest()
            ->limit(20) // Limit to 20 latest drafts
            ->get()
            ->map(function ($sale) {
                return [
                    'server_id' => $sale->id,
                    'customer_name' => $sale->customer_name,
                    'total' => $sale->final_total,
                    'created_at' => $sale->created_at->toIso8601String(),
                    'sale_data' => [
                        'customer_name' => $sale->customer_name,
                        'total' => $sale->final_total,
                        'order_type' => $sale->order_type,
                        'discount' => 0,
                        'items' => $sale->items->map(function ($item) {
                            return [
                                'product_id' => $item->product_id,
                                'product_name' => $item->product->name ?? 'Unknown',
                                'quantity' => $item->quantity,
                                'price' => $item->unit_price,
                                'subtotal' => $item->subtotal,
                                'notes' => null
                            ];
                        })
                    ]
                ];
            });

        return response()->json([
            'success' => true,
            'drafts' => $drafts
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
});

// Get Sales History (Today - for local backup)
Route::get('/pos/sales-history', function () {
    try {
        $sales = Sale::with(['items.product', 'paymentMethod'])
            ->whereDate('created_at', now()->toDateString())
            ->latest()
            ->get()
            ->map(function ($sale) {
                return [
                    'id' => $sale->id,
                    'invoice_number' => $sale->invoice_number,
                    'customer_name' => $sale->customer_name,
                    'order_type' => $sale->order_type,
                    'table_number' => $sale->table_number,
                    'status' => $sale->status,
                    'payment_method_id' => $sale->payment_method_id,
                    'payment_method' => $sale->paymentMethod?->code,
                    'subtotal' => $sale->subtotal,
                    'tax' => $sale->tax,
                    'discount' => $sale->discount,
                    'total' => $sale->total,
                    'created_at' => $sale->created_at->toIso8601String(),
                    'items' => $sale->items->map(function ($item) {
                        return [
                            'product_id' => $item->product_id,
                            'product_name' => $item->product->name ?? 'Unknown',
                            'quantity' => $item->quantity,
                            'price' => $item->unit_price,
                            'subtotal' => $item->subtotal,
                            'notes' => null
                        ];
                    })
                ];
            });

        return response()->json([
            'success' => true,
            'sales' => $sales
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
});

// Delete Draft
Route::delete('/pos/drafts/{id}', function ($id) {
    try {
        $sale = Sale::where('id', $id)
            ->where('status', 'draft')
            ->first();

        if (!$sale) {
            return response()->json(['success' => false, 'error' => 'Draft not found'], 404);
        }

        // Restore Stock
        foreach ($sale->items as $item) {
            if ($item->product_id) {
                Product::where('id', $item->product_id)->increment('stock', $item->quantity);
            }
        }

        $sale->items()->delete();
        $sale->delete();

        return response()->json(['success' => true]);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    }
});

// Sync Shifts (Open/Close)
Route::post('/pos/sync-shifts', function (Request $request) {
    try {
        $shifts = $request->input('shifts', []);
        $results = [];

        foreach ($shifts as $shiftData) {
            DB::transaction(function () use ($shiftData, &$results) {
                // Determine if we update or create
                // Ideally we use a UUID from client. For now, let's assume we create if new.
                // But wait, if we sync "open" then later "close", we need to know the ID.
                // Client should send server_id if it has it.

                $session = null;
                if (isset($shiftData['server_id']) && $shiftData['server_id']) {
                    $session = CashSession::find($shiftData['server_id']);
                }

                // If not found by ID, try to find by similarity (user + opened_at) to avoid dupes?
                if (!$session) {
                    // Try to resolve user by name
                    $userId = 2; // SAFE DEFAULT (Admin ID=2)
                    if (isset($shiftData['cashier_name']) && $shiftData['cashier_name']) {
                        $user = \App\Models\User::where('name', $shiftData['cashier_name'])->first();
                        if ($user) {
                            $userId = $user->id;
                        }
                    }

                    $session = CashSession::where('user_id', $userId) // Resolved User
                        ->where('opened_at', $shiftData['opened_at'])
                        ->first();

                    if (!$session) {
                        $session = new CashSession();
                        $session->user_id = $userId;
                        $session->opened_at = $shiftData['opened_at'];
                    }
                }

                $session->cash_in_hand = $shiftData['cash_in_hand'];
                // $session->description = ... // Column does not exist

                if (isset($shiftData['status']) && $shiftData['status'] === 'closed') {
                    $session->status = 'closed';
                    $session->closed_at = $shiftData['closed_at'] ?? now();
                    $session->cash_out = $shiftData['cash_out'] ?? 0;
                    // $session->total_cash = ... // Calculated on server
                } else {
                    $session->status = 'open';
                }

                $session->save();

                $results[] = [
                    'local_id' => $shiftData['id'], // Client local ID
                    'server_id' => $session->id,
                    'status' => 'synced'
                ];
            });
        }

        return response()->json([
            'success' => true,
            'results' => $results
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
});

/*
|--------------------------------------------------------------------------
| WhatsApp Gateway Integration
|--------------------------------------------------------------------------
*/

// Check if logout was requested while gateway was offline
Route::get('/wa/check-logout', function () {
    $logoutRequested = Cache::get('wa_logout_requested', false);

    Log::info('WA Gateway checking logout flag', [
        'logout_requested' => $logoutRequested,
        'timestamp' => now()->toDateTimeString()
    ]);

    if ($logoutRequested) {
        // Clear the flag IMMEDIATELY after reading
        Cache::forget('wa_logout_requested');
        Log::info('WA logout flag cleared');
    }

    return response()->json([
        'logout_requested' => $logoutRequested
    ]);
});

/*
|--------------------------------------------------------------------------
*/

Route::prefix('webhook')->group(function () {

    // WhatsApp Webhook
    Route::post('/wa', [\App\Http\Controllers\Api\WhatsappWebhookController::class, 'handle']);

    // Test endpoint
    Route::get('/test', function () {
        return response()->json([
            'message' => 'Print Webhook API is running!',
            'timestamp' => now()->toIso8601String(),
            'status' => 'active'
        ]);
    });

    // Receive print job dari POS
    Route::post('/print', function (Request $request) {
        try {
            Log::info('🖨️ Print webhook received', ['data' => $request->all()]);

            // Validasi secret key
            $expectedSecret = config('app.print_secret', 'default-print-secret-123');
            $receivedSecret = $request->header('X-Print-Secret');

            if ($receivedSecret !== $expectedSecret) {
                Log::warning('❌ Unauthorized print webhook attempt', [
                    'expected' => substr($expectedSecret, 0, 10) . '...',
                    'received' => $receivedSecret ? substr($receivedSecret, 0, 10) . '...' : 'null'
                ]);
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            // Validasi input
            $validated = $request->validate([
                'content' => 'required|string',
                'printer' => 'sometimes|string',
                'division' => 'sometimes|string',
                'sale_id' => 'sometimes|nullable|integer',
                'type' => 'sometimes|string|in:receipt,order,test',
                'payload' => 'sometimes|nullable|array' // Add validation
            ]);

            // Generate job ID
            $jobId = 'job_' . uniqid() . '_' . time();

            // Simpan ke database
            $printJob = PrintJob::create([
                'job_id' => $jobId,
                'content' => $validated['content'],
                'payload' => $validated['payload'] ?? null, // Save payload
                'printer' => $validated['printer'] ?? 'BAR',
                'division' => $validated['division'] ?? 'general',
                'sale_id' => $validated['sale_id'] ?? null,
                'type' => $validated['type'] ?? 'order',
                'status' => 'pending',
                'attempts' => 0
            ]);

            Log::info("✅ Print job queued: {$jobId}", ['job_id' => $jobId]);

            return response()->json([
                'success' => true,
                'job_id' => $jobId,
                'message' => 'Print job queued successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Webhook print error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    });

    // Get pending print jobs untuk Windows client
    Route::get('/print-jobs', function (Request $request) {
        try {
            // Log::info('📋 Getting pending print jobs');

            // Validasi secret key (Prioritas Settings > Config)
            $settings = app(\App\Settings\PrinterSettings::class);
            $expectedSecret = $settings->print_secret ?? config('app.print_secret', 'default-print-secret-123');
            $receivedSecret = $request->header('X-Print-Secret');

            if ($receivedSecret !== $expectedSecret) {
                Log::warning('❌ Unauthorized print jobs attempt');
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            // Get pending jobs - TANPA created_at
            $jobs = PrintJob::where('status', 'pending')
                ->where('attempts', '<', 3)
                ->get()
                ->map(function ($job) {
                    return [
                        'id' => $job->job_id,
                        'content' => $job->content,
                        'payload' => $job->payload, // Include payload
                        'printer' => $job->printer,
                        'division' => $job->division,
                        'type' => $job->type,
                        'sale_id' => $job->sale_id
                        // HAPUS created_at karena tidak ada di database
                    ];
                })
                ->toArray();

            // Log::info("✅ Returning " . count($jobs) . " pending jobs");

            return response()->json([
                'success' => true,
                'jobs' => $jobs,
                'total' => count($jobs),
                'timestamp' => now()->toIso8601String()
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Get print jobs error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    });

    // Update job status setelah berhasil diprint
    Route::post('/print-job/{jobId}/complete', function ($jobId, Request $request) {
        try {
            // Validasi secret key (Prioritas Settings > Config)
            $settings = app(\App\Settings\PrinterSettings::class);
            $expectedSecret = $settings->print_secret ?? config('app.print_secret', 'default-print-secret-123');
            $receivedSecret = $request->header('X-Print-Secret');

            if ($receivedSecret !== $expectedSecret) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $printJob = PrintJob::where('job_id', $jobId)->first();

            if ($printJob) {
                $printJob->update([
                    'status' => 'completed',
                    'completed_at' => now()
                ]);

                Log::info("✅ Print job completed: {$jobId}");
                return response()->json([
                    'success' => true,
                    'message' => 'Job marked as completed'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Job not found'
                ], 404);
            }
        } catch (\Exception $e) {
            Log::error('❌ Complete job error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    });

    // Update job status jika gagal
    Route::post('/print-job/{jobId}/failed', function ($jobId, Request $request) {
        try {
            // Validasi secret key
            $expectedSecret = config('app.print_secret', 'default-print-secret-123');
            $receivedSecret = $request->header('X-Print-Secret');

            if ($receivedSecret !== $expectedSecret) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $validated = $request->validate([
                'error' => 'required|string'
            ]);

            $printJob = PrintJob::where('job_id', $jobId)->first();

            if ($printJob) {
                $printJob->update([
                    'status' => 'failed',
                    'error' => $validated['error'],
                    'attempts' => $printJob->attempts + 1,
                    'completed_at' => now()
                ]);

                Log::info("❌ Print job failed: {$jobId}");
                return response()->json([
                    'success' => true,
                    'message' => 'Job marked as failed'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Job not found'
                ], 404);
            }
        } catch (\Exception $e) {
            Log::error('❌ Failed job error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    });

    // Health check endpoint
    Route::get('/health', function () {
        try {
            $pendingJobs = PrintJob::where('status', 'pending')->count();

            return response()->json([
                'status' => 'ok',
                'service' => 'Print Webhook',
                'timestamp' => now()->toIso8601String(),
                'pending_jobs' => $pendingJobs,
                'version' => '1.0.0'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'error' => $e->getMessage()
            ], 500);
        }
    });
});

/*
|--------------------------------------------------------------------------
| TV Display Configuration API
|--------------------------------------------------------------------------
*/

// Get Active TV Configuration
Route::get('/tv-config', function () {
    try {
        $config = \App\Models\TvConfig::active()->first();

        if (!$config) {
            return response()->json([
                'images' => [],
                'music_url' => '',
                'slide_duration' => 10000
            ]);
        }

        // Transform images array to full URLs
        $images = [];
        if (is_array($config->images)) {
            foreach ($config->images as $imagePath) {
                // Convert storage path to full URL
                if (is_string($imagePath)) {
                    $images[] = asset('storage/' . $imagePath);
                }
            }
        }

        return response()->json([
            'images' => $images,
            'music_url' => $config->music_url ?? '',
            'slide_duration' => $config->slide_duration ?? 10000
        ]);
    } catch (\Exception $e) {
        Log::error('TV Config API Error: ' . $e->getMessage());
        return response()->json([
            'images' => [],
            'music_url' => '',
            'slide_duration' => 10000
        ], 500);
    }
});

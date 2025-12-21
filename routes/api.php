<?php

use App\Models\PrintJob;
use Illuminate\Http\Request;
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

// POS Offline Sync Endpoint
Route::get('/pos/products-sync', function () {
    $products = \App\Models\Product::where('is_sellable', true)
        ->select('id', 'name', 'sell_price as price', 'stock', 'category_id', 'image', 'type')
        ->get();

    return response()->json([
        'products' => $products,
        'timestamp' => now()->toIso8601String()
    ]);
});

// POS Offline Sales Sync Endpoint
Route::post('/pos/sync-offline-sales', function (Request $request) {
    try {
        $orders = $request->input('orders', []);
        $syncedCount = 0;

        foreach ($orders as $orderData) {
            // Gunakan Transaction per order agar aman
            \Illuminate\Support\Facades\DB::transaction(function () use ($orderData) {
                // Recalculate Totals from Items to avoid corrupted frontend data
                $calculatedSubtotal = 0;
                foreach ($orderData['items'] as $item) {
                    $price = isset($item['price']) ? (float) $item['price'] : 0;
                    $qty = isset($item['quantity']) ? (float) $item['quantity'] : 1;
                    $calculatedSubtotal += ($price * $qty);
                }

                $tax = isset($orderData['tax']) ? (float) $orderData['tax'] : 0;
                $finalTotal = $calculatedSubtotal + $tax;

                // Cari Cash Session yang sedang AKTIF (Open) untuk User 1
                $activeSession = \App\Models\CashSession::where('user_id', 1)
                    ->where('status', 'open')
                    ->latest()
                    ->first();

                // 1. Create Sale as DRAFT (agar bisa dibayar nanti)
                $sale = \App\Models\Sale::create([
                    'invoice_number' => 'OFFLINE-' . time() . '-' . uniqid(),
                    'customer_name' => $orderData['customer_name'] ?? 'Offline Customer',
                    'order_type' => 'offline', // Penanda ini transaksi dari offline mode
                    'user_id' => 1, // Default user
                    'cash_session_id' => $activeSession ? $activeSession->id : null, // Link ke sesi aktif

                    'subtotal' => $calculatedSubtotal,
                    'tax' => $tax,
                    'final_total' => $finalTotal,
                    'total' => $finalTotal,

                    'payment_method' => null, // Karena draft, belum dibayar
                    'payment_method_id' => null,
                    'status' => 'draft', // User minta draft
                    'created_at' => $orderData['created_at'] ?? now(),
                ]);

                // 2. Create Items & Deduct Stock
                foreach ($orderData['items'] as $item) {
                    \App\Models\SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => $item['product_id'] ?? $item['id'], // Handle both cases
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['price'], // Schema uses unit_price
                        'subtotal' => $item['subtotal'],
                    ]);

                    // Deduct Stock
                    $product = \App\Models\Product::find($item['product_id'] ?? $item['id']);
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
            Log::info('📋 Getting pending print jobs');

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
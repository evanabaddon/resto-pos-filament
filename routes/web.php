<?php

use App\Services\OrderPrintService;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('/welcome');
});

// Webhook routes
require __DIR__.'/webhook.php';


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

Route::get('/test-api-basic', function () {
    try {
        $response = Http::timeout(10)
            ->withOptions(['verify' => false])
            ->get('https://pos.suralaya.id/api/webhook/test');
            
        return response()->json([
            'success' => $response->successful(),
            'status' => $response->status(),
            'data' => $response->json()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
});
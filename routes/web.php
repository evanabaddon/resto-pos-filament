<?php

use App\Services\OrderPrintService;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('/welcome');
});

// Webhook routes
require __DIR__.'/webhook.php';


// routes/api.php atau routes/web.php
Route::get('/test-api-webhook', function () {
    $printService = new OrderPrintService();
    
    $envTest = $printService->testEnvironment();
    
    // Test API webhook
    try {
        $webhookUrl = 'https://pos.suralaya.id/api/webhook/test';
        $response = Http::get($webhookUrl);
        
        $apiTest = [
            'success' => $response->successful(),
            'status' => $response->status(),
            'body' => $response->json()
        ];
        
    } catch (\Exception $e) {
        $apiTest = [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
    
    return response()->json([
        'environment_test' => $envTest,
        'api_webhook_test' => $apiTest,
        'webhook_url' => config('app.webhook_print_url')
    ]);
});
<?php

use App\Services\OrderPrintService;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('/welcome');
});

// Webhook routes
require __DIR__.'/webhook.php';


Route::get('/test-printing-env', function () {
    $printService = new OrderPrintService();
    
    return response()->json([
        'environment_test' => $printService->testEnvironment(),
        'webhook_test' => $printService->testWebhookConnection()
    ]);
});
<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('/welcome');
});

// Webhook routes
require __DIR__.'/webhook.php';
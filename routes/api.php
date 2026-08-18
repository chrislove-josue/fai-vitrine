<?php

use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\WebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['status' => 'ok', 'time' => now()->toIso8601String()]));

Route::post('/webhooks/incoming', [WebhookController::class, 'incoming']);

Route::prefix('v1')
    ->middleware(['auth.api-client', 'throttle:10,1'])
    ->group(function () {
        Route::get('/customers', [CustomerController::class, 'index']);
    });

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebhookController;

// API routes are stateless and don't use CSRF protection
Route::get('/webhook', [WebhookController::class, 'verify']);
Route::post('/webhook', [WebhookController::class, 'handle']);

// Simple test route
Route::get('/test', function () {
    return response()->json(['message' => 'API is working!']);
});

Route::post('/test-post', function () {
    return response()->json(['message' => 'POST is working!', 'input' => request()->all()]);
});
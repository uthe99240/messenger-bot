<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BotManController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

Route::get('/', function () {
    return view('welcome');
});

use App\Http\Controllers\WebhookController;

Route::get('/webhook', [WebhookController::class, 'verify']); // FB verification
Route::post('/webhook', [WebhookController::class, 'handle'])->middleware('api'); // FB messages



Route::get('/test-log', function () {
    Log::info('This is a test log from Laravel!');
    return 'Log written!';
});

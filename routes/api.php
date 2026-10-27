<?php

use App\Http\Controllers\TelegramWebhookController;
use App\Http\Middleware\VerifyTelegramWebhook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('webhooks/telegram', [TelegramWebhookController::class, 'handle'])
    ->middleware(VerifyTelegramWebhook::class)
    ->name('telegram.webhook');

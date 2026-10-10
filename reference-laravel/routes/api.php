<?php
use App\Http\Controllers\PaymentWebhookController;
use Illuminate\Support\Facades\Route;

// No login or CSRF here: the HMAC signature is the authentication.
Route::post('/webhooks/payments/{provider}', PaymentWebhookController::class)
    ->middleware('throttle:120,1');

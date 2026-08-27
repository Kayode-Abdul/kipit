<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisteredUserController;

Route::group(['prefix' => 'domain'], function ()
{
    Route::post('/register', RegisteredUserController::class)
        ->name('register');
});

Route::get('/test', function ()
{
    return response()->json([
        'message' => 'Success!',
    ]);
});

Route::post('/webhooks/paystack', [\App\Http\Controllers\Api\PaystackWebhookController::class, 'handle'])
    ->name('webhooks.paystack');


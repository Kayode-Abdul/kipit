<?php

use App\Livewire\Forms\Auth\RegisterTenantForm;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

//run migration
// use Illuminate\Support\Facades\Artisan;

// Route::get('/__migrate', function () {
//     Artisan::call('migrate', ['--force' => true]);
//     return Artisan::output();
// });

Route::get('/clear-cache', function () {
    \Illuminate\Support\Facades\Artisan::call('optimize:clear');
    return "Cache cleared successfully!";
});
if (\App\Helpers\DesktopHelper::isDesktopApp()) {
    Route::get('/', function () {
        return redirect()->route('desktop.splash');
    });
} else {
    Volt::route('/', 'pages/welcome');
}
Volt::route('/download', 'pages/download')->name('download');
Volt::route('/pricing', 'pages/pricing')->name('pricing');

Route::view('/offline', 'offline');

Route::get('/serviceworker.js', function () {
    return response()->file(public_path('serviceworker.js'))
        ->header('Content-Type', 'application/javascript');
});

Route::get('/auth/register', RegisterTenantForm::class)
    ->name('auth.register');

Route::post('/paystack/webhook', [\App\Http\Controllers\PaystackController::class, 'webhook'])
    ->name('paystack.webhook');

Route::middleware([
    'web',
])
    ->prefix('admin')
    ->group(function () {
        //
    });



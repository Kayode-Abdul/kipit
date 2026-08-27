<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Desktop Routes
|--------------------------------------------------------------------------
|
| These routes are only loaded when the app is running in desktop mode
| (NativePHP/Electron). They handle the native desktop experience.
|
*/

Route::get('/desktop/splash', [\App\Http\Controllers\DesktopController::class, 'splash'])->name('desktop.splash');
Route::get('/desktop/welcome', [\App\Http\Controllers\DesktopController::class, 'welcome'])->name('desktop.welcome');
Route::get('/desktop/setup', \App\Livewire\DesktopSetup::class)->name('desktop.setup');
Route::get('/desktop/login', [\App\Http\Controllers\DesktopController::class, 'login'])->name('desktop.login');
Route::post('/desktop/login', [\App\Http\Controllers\DesktopController::class, 'loginSubmit'])->name('desktop.login.submit');

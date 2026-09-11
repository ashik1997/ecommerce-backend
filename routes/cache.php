<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\HomeController;

// Stage 26B: preserve the backend cache-clear action behind authentication and CSRF-protected POST.
Route::post('/clear/cache', [HomeController::class, 'clearCache'])
    ->middleware('auth')
    ->name('ClearCache');

// Stage 26B: the legacy multi-clear browser helper registers only when explicitly enabled locally.
if (app()->environment('local') && (bool) config('app.allow_local_unsafe_web_maintenance_routes', false)) {
    Route::get('/clear', function () {
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('view:clear');
        Artisan::call('route:clear');

        return 'Cleared!';
    });
}

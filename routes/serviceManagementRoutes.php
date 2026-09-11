<?php

use App\Http\Controllers\ServiceManagement\ServiceManagementController;
use App\Http\Controllers\ServiceManagement\ServiceController;
use App\Http\Controllers\ServiceManagement\ServiceInstanceController;
use App\Http\Controllers\ServiceManagement\ServicePaymentController;
use Illuminate\Support\Facades\Route;

Route::prefix('service-management')
    ->name('service-management.')
    ->middleware(['auth', 'CheckUserType', 'DemoMode'])
    ->group(function () {
        Route::get('/dashboard', [ServiceManagementController::class, 'dashboard'])->name('dashboard');
        Route::resource('/services', ServiceController::class)
            ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
        Route::post('/services/{service}/products', [ServiceController::class, 'storeProduct'])->name('services.products.store');
        Route::put('/services/{service}/products/{serviceProduct}', [ServiceController::class, 'updateProduct'])->name('services.products.update');
        Route::delete('/services/{service}/products/{serviceProduct}', [ServiceController::class, 'destroyProduct'])->name('services.products.destroy');
        Route::get('/instances/service-products/{service}', [ServiceInstanceController::class, 'serviceProducts'])->name('instances.service-products');
        Route::get('/instances/{instance}/invoice', [ServiceInstanceController::class, 'invoice'])->name('instances.invoice');
        Route::post('/instances/{instance}/confirm', [ServiceInstanceController::class, 'confirm'])->name('instances.confirm');
        Route::post('/instances/{instance}/bill', [ServiceInstanceController::class, 'bill'])->name('instances.bill');
        Route::post('/instances/{instance}/close', [ServiceInstanceController::class, 'close'])->name('instances.close');
        Route::post('/instances/{instance}/cancel', [ServiceInstanceController::class, 'cancel'])->name('instances.cancel');
        Route::resource('/instances', ServiceInstanceController::class)
            ->only(['index', 'create', 'store']);
        Route::get('/billing', [ServiceManagementController::class, 'billing'])->name('billing.index');
        Route::get('/payments', [ServicePaymentController::class, 'index'])->name('payments.index');
        Route::post('/payments', [ServicePaymentController::class, 'store'])->name('payments.store');
        Route::get('/payments/{payment}/receipt', [ServicePaymentController::class, 'receipt'])->name('payments.receipt');
        Route::get('/reports', [ServiceManagementController::class, 'reports'])->name('reports.index');
    });

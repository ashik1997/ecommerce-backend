<?php

use App\Http\Middleware\DemoMode;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\CheckUserType;
use App\Http\Controllers\Analytics\HomePageAnalytics;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Crm\CrmDashboardController;
use App\Http\Controllers\CkeditorController;
use App\Http\Controllers\ProductController;

Route::middleware([CheckUserType::class, DemoMode::class])->group(function(){
   
   //Dashboard routes
    Route::get('/', [HomePageAnalytics::class, 'index'])->name('home');
    Route::get('/home', [HomePageAnalytics::class, 'index']);
    Route::get('/home/analytics/data', [HomePageAnalytics::class, 'summary'])->name('home.analytics');
    Route::get('/crm-home', [CrmDashboardController::class, 'index'])
        ->middleware('crm.sidebar.permission:crm.dashboard,read')
        ->name('crm.home');
    Route::get('/accounts-home', [HomeController::class, 'accounts_index'])->name('accounts.home');
    Route::get('/inventory-home', [HomeController::class, 'inventory_dashboard'])->name('inventory.home');
    Route::get('/inventory-home/api/overview', [HomeController::class, 'inventoryDashboardOverview'])->name('inventory.home.api.overview');
    Route::get('/inventory-home/api/relationships', [HomeController::class, 'inventoryDashboardRelationships'])->name('inventory.home.api.relationships');
    Route::get('/inventory-home/api/trends', [HomeController::class, 'inventoryDashboardTrends'])->name('inventory.home.api.trends');
    Route::get('/inventory-home/api/recent', [HomeController::class, 'inventoryDashboardRecent'])->name('inventory.home.api.recent');
    Route::get('/inventory/products/{identifier}/barcode-data', [ProductController::class, 'getBarcodeData'])->name('inventory.products.barcode-data');

    // api
    Route::get('/api/pending-high-value-orders', [HomePageAnalytics::class, 'pendingHighValueOrders']);
    Route::get('/api/trending-products', [HomePageAnalytics::class, 'trendingProducts']);
    Route::get('/api/dashboard/overview', [HomePageAnalytics::class, 'dashboardOverview']);

});

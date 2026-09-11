<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Analytics\AnalyticsController;
use App\Http\Controllers\Analytics\DashboardAnalyticsv2Controller;
use App\Http\Controllers\Analytics\EcommerceAnalyticsController;

/*
|--------------------------------------------------------------------------
| Analytics Routes
|--------------------------------------------------------------------------
|
| Here are all analytics and dashboard related routes
|
*/

Route::middleware(['auth'])->group(function () {

    // Analytics Dashboard
    Route::get('/analytics/dashboard', [AnalyticsController::class, 'index'])->name('analytics.dashboard');

    // API Endpoints for AJAX data loading
    Route::prefix('api/analytics')->group(function () {
        Route::get('/overview', [AnalyticsController::class, 'getOverview'])->name('api.analytics.overview');
        Route::get('/top-rated-products', [AnalyticsController::class, 'getTopRatedProducts'])->name('api.analytics.topRated');
        Route::get('/top-viewed-products', [AnalyticsController::class, 'getTopViewedProducts'])->name('api.analytics.topViewed');
        Route::get('/top-categories', [AnalyticsController::class, 'getTopCategories'])->name('api.analytics.topCategories');
        Route::get('/sales-chart', [AnalyticsController::class, 'getSalesChart'])->name('api.analytics.salesChart');
        Route::get('/category-distribution', [AnalyticsController::class, 'getCategoryDistribution'])->name('api.analytics.categoryDistribution');
        Route::get('/top-selling-products', [AnalyticsController::class, 'getTopSellingProducts'])->name('api.analytics.topSelling');
    });

    // API Endpoints for AJAX data loading
    Route::prefix('api/analytics-v2')->group(function () {
        Route::get('/at-a-glance', [DashboardAnalyticsv2Controller::class, 'atAGlance'])->name('api.analytics-v2.at-a-glance');
        Route::get('/courier-breakdown', [DashboardAnalyticsv2Controller::class, 'courierBreakdown'])->name('api.analytics-v2.courier-breakdown');
        Route::get('/quotation-funnel', [DashboardAnalyticsv2Controller::class, 'quotationFunnel'])->name('api.analytics-v2.quotation-funnel');
        Route::get('/revenue-trend', [DashboardAnalyticsv2Controller::class, 'revenueTrend'])->name('api.analytics-v2.revenue-trend');
        Route::get('/rev-exp-profit-trend', [DashboardAnalyticsv2Controller::class, 'revExpProfitTrend'])->name('api.analytics-v2.rev-exp-profit-trend');
        Route::get('/top-products-by-sales', [DashboardAnalyticsv2Controller::class, 'topProductsBySales'])->name('api.analytics-v2.top-products-by-sales');
        Route::get('/payment-methods', [DashboardAnalyticsv2Controller::class, 'paymentMethods'])->name('api.analytics-v2.payment-methods');
        Route::get('/top-categories-by-orders', [DashboardAnalyticsv2Controller::class, 'topCategoriesByOrders'])->name('api.analytics-v2.top-categories-by-orders');
        Route::get('/order-count-vs-profit', [DashboardAnalyticsv2Controller::class, 'orderCountVsProfit'])->name('api.analytics-v2.order-count-vs-profit');
        Route::get('/top-categories-by-profit', [DashboardAnalyticsv2Controller::class, 'topCategoriesByProfit'])->name('api.analytics-v2.top-categories-by-profit');
        Route::get('/orders-table', [DashboardAnalyticsv2Controller::class, 'ordersTable'])->name('api.analytics-v2.orders-table');
        Route::get('/trending-products', [DashboardAnalyticsv2Controller::class, 'trendingProducts'])->name('api.analytics-v2.trending-products');
        Route::get('/category-profit-share', [DashboardAnalyticsv2Controller::class, 'categoryProfitShare'])->name('api.analytics-v2.category-profit-share');
        Route::get('/expenses-by-category', [DashboardAnalyticsv2Controller::class, 'expensesByCategory'])->name('api.analytics-v2.expenses-by-category');
        Route::get('/customer-breakdown', [DashboardAnalyticsv2Controller::class, 'customerBreakdown'])->name('api.analytics-v2.customer-breakdown');
        Route::get('/website-visitors', [DashboardAnalyticsv2Controller::class, 'websiteVisitors'])->name('api.analytics-v2.website-visitors');
        Route::get('/return-rate-insight', [DashboardAnalyticsv2Controller::class, 'returnRateInsight'])->name('api.analytics-v2.return-rate-insight');
        Route::get('/monthly-profit-loss', [DashboardAnalyticsv2Controller::class, 'monthlyProfitLoss'])->name('api.analytics-v2.monthly-profit-loss');
        Route::get('/ecommerce-overview', [EcommerceAnalyticsController::class, 'overview'])->name('api.analytics-v2.ecommerce-overview');
    });
});

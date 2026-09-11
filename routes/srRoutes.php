<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SrManagement\SalesTargetController;

use App\Http\Controllers\SrManagement\AffiliateController;
use App\Http\Controllers\SrManagement\CommissionRuleController;
use App\Http\Controllers\SrManagement\CommissionEntryController;
use App\Http\Controllers\SrManagement\CommissionSettlementController;
use App\Http\Controllers\SrManagement\CommissionLedgerController;
use App\Http\Controllers\SrManagement\CommissionReportController;
use App\Http\Controllers\SrManagement\CommissionManualController;

/*
|--------------------------------------------------------------------------
| SR Management - Sales Targets
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->prefix('sales-targets')->name('sales_targets.')->group(function () {
    Route::get('/', [SalesTargetController::class, 'index'])->name('index');
    Route::get('/analytics', [SalesTargetController::class, 'analytics'])->name('analytics');
    Route::get('/users-list', [SalesTargetController::class, 'usersList'])->name('users_list');
    Route::get('/create', [SalesTargetController::class, 'create'])->name('create');
    Route::post('/store', [SalesTargetController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [SalesTargetController::class, 'edit'])->name('edit');
    Route::get('/{id}', [SalesTargetController::class, 'show'])->name('show');
    Route::post('/update', [SalesTargetController::class, 'update'])->name('update');
});

/*
|--------------------------------------------------------------------------
| SR Management - Sales Commission & Affiliate
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->prefix('sr-management')->name('sr.')->group(function () {
    Route::post('affiliates/validate-code', [AffiliateController::class, 'validateCode'])->name('affiliates.validate-code');
    Route::resource('affiliates', AffiliateController::class)->except(['show']);
    Route::post('commission-rules/conflict-check', [CommissionRuleController::class, 'conflictCheck'])->name('commission-rules.conflict-check');
    Route::resource('commission-rules', CommissionRuleController::class)->except(['show']);

    Route::get('commission-entries', [CommissionEntryController::class, 'index'])->name('commission-entries.index');
    Route::post('commission-entries/bulk-approve', [CommissionEntryController::class, 'bulkApprove'])->name('commission-entries.bulk-approve');
    Route::post('commission-entries/{id}/approve', [CommissionEntryController::class, 'approve'])->name('commission-entries.approve');
    Route::post('commission-entries/{id}/reverse', [CommissionEntryController::class, 'reverse'])->name('commission-entries.reverse');
    Route::post('commission-entries/order/{orderId}/recalculate', [CommissionEntryController::class, 'recalculateOrder'])->name('commission-entries.order-recalculate');

    Route::get('commission-settlements', [CommissionSettlementController::class, 'index'])->name('commission-settlements.index');
    Route::get('commission-settlements/create', [CommissionSettlementController::class, 'create'])->name('commission-settlements.create');
    Route::post('commission-settlements', [CommissionSettlementController::class, 'store'])->name('commission-settlements.store');
    Route::get('commission-settlements/{id}', [CommissionSettlementController::class, 'show'])->name('commission-settlements.show');
    Route::post('commission-settlements/{id}/approve', [CommissionSettlementController::class, 'approve'])->name('commission-settlements.approve');
    Route::post('commission-settlements/{id}/mark-paid', [CommissionSettlementController::class, 'markPaid'])->name('commission-settlements.mark-paid');
    Route::post('commission-settlements/{id}/cancel', [CommissionSettlementController::class, 'cancel'])->name('commission-settlements.cancel');

    Route::get('commission-ledger', [CommissionLedgerController::class, 'index'])->name('commission-ledger.index');
    Route::get('commission-reports', [CommissionReportController::class, 'index'])->name('commission-reports.index');
    Route::get('commission-reports/export', [CommissionReportController::class, 'export'])->name('commission-reports.export');

    Route::get('commission-manual', [CommissionManualController::class, 'bn'])->name('commission-manual.index');
    Route::get('commission-manual/bn', [CommissionManualController::class, 'bn'])->name('commission-manual.bn');
    Route::get('commission-manual/en', [CommissionManualController::class, 'en'])->name('commission-manual.en');
});

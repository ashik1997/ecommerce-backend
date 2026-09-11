<?php

use App\Http\Controllers\FixedAsset\FixedAssetAccountingController;
use App\Http\Controllers\FixedAsset\FixedAssetAssignmentController;
use App\Http\Controllers\FixedAsset\FixedAssetController;
use App\Http\Controllers\FixedAsset\FixedAssetDashboardController;
use App\Http\Controllers\FixedAsset\FixedAssetDepreciationController;
use App\Http\Controllers\FixedAsset\FixedAssetDisposalController;
use App\Http\Controllers\FixedAsset\FixedAssetImportExportController;
use App\Http\Controllers\FixedAsset\FixedAssetMaintenanceController;
use App\Http\Controllers\FixedAsset\FixedAssetQrController;
use App\Http\Controllers\FixedAsset\FixedAssetReportController;
use App\Http\Controllers\FixedAsset\FixedAssetSettingController;
use App\Http\Controllers\FixedAsset\FixedAssetTransferController;
use App\Http\Controllers\FixedAsset\FixedAssetVerificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('fixed-assets')
    ->name('fixed-assets.')
    ->middleware(['auth', 'CheckUserType', 'DemoMode'])
    ->group(function () {
        Route::get('/dashboard', [FixedAssetDashboardController::class, 'index'])->name('dashboard');
        Route::get('/', [FixedAssetController::class, 'index'])->name('assets.index');
        Route::get('/assets/create', [FixedAssetController::class, 'create'])->name('assets.create');
        Route::post('/assets', [FixedAssetController::class, 'store'])->name('assets.store');
        Route::get('/assets/{asset}', [FixedAssetController::class, 'show'])->name('assets.show');
        Route::get('/assets/{asset}/edit', [FixedAssetController::class, 'edit'])->name('assets.edit');
        Route::put('/assets/{asset}', [FixedAssetController::class, 'update'])->name('assets.update');
        Route::post('/assets/{asset}/archive', [FixedAssetController::class, 'archive'])->name('assets.archive');
        Route::get('/assets/{asset}/tag', [FixedAssetController::class, 'tag'])->name('assets.tag');
        Route::get('/assets/{asset}/qr.svg', [FixedAssetQrController::class, 'svg'])->name('assets.qr.svg');

        Route::get('/assignments', [FixedAssetAssignmentController::class, 'index'])->name('assignments.index');
        Route::get('/assets/{asset}/assign', [FixedAssetAssignmentController::class, 'create'])->name('assignments.create');
        Route::post('/assets/{asset}/assign', [FixedAssetAssignmentController::class, 'store'])->name('assignments.store');
        Route::post('/assignments/{assignment}/return', [FixedAssetAssignmentController::class, 'returnAsset'])->name('assignments.return');
        Route::post('/assignments/{assignment}/cancel', [FixedAssetAssignmentController::class, 'cancel'])->name('assignments.cancel');

        Route::get('/transfers', [FixedAssetTransferController::class, 'index'])->name('transfers.index');
        Route::get('/assets/{asset}/transfer', [FixedAssetTransferController::class, 'create'])->name('transfers.create');
        Route::post('/assets/{asset}/transfer', [FixedAssetTransferController::class, 'store'])->name('transfers.store');
        Route::post('/transfers/{transfer}/receive', [FixedAssetTransferController::class, 'receive'])->name('transfers.receive');
        Route::post('/transfers/{transfer}/cancel', [FixedAssetTransferController::class, 'cancel'])->name('transfers.cancel');

        Route::get('/maintenance', [FixedAssetMaintenanceController::class, 'index'])->name('maintenance.index');
        Route::get('/assets/{asset}/maintenance/create', [FixedAssetMaintenanceController::class, 'create'])->name('maintenance.create');
        Route::post('/assets/{asset}/maintenance', [FixedAssetMaintenanceController::class, 'store'])->name('maintenance.store');
        Route::post('/maintenance/{job}/start', [FixedAssetMaintenanceController::class, 'start'])->name('maintenance.start');
        Route::post('/maintenance/{job}/complete', [FixedAssetMaintenanceController::class, 'complete'])->name('maintenance.complete');
        Route::post('/maintenance/{job}/cancel', [FixedAssetMaintenanceController::class, 'cancel'])->name('maintenance.cancel');

        Route::get('/depreciation', [FixedAssetDepreciationController::class, 'index'])->name('depreciation.index');
        Route::post('/depreciation/preview', [FixedAssetDepreciationController::class, 'preview'])->name('depreciation.preview');
        Route::post('/depreciation/post', [FixedAssetDepreciationController::class, 'post'])->name('depreciation.post');
        Route::post('/depreciation/{run}/reverse', [FixedAssetDepreciationController::class, 'reverse'])->name('depreciation.reverse');

        Route::get('/disposals', [FixedAssetDisposalController::class, 'index'])->name('disposals.index');
        Route::get('/assets/{asset}/dispose', [FixedAssetDisposalController::class, 'create'])->name('disposals.create');
        Route::post('/assets/{asset}/dispose', [FixedAssetDisposalController::class, 'store'])->name('disposals.store');
        Route::post('/disposals/{disposal}/approve', [FixedAssetDisposalController::class, 'approve'])->name('disposals.approve');
        Route::post('/disposals/{disposal}/cancel', [FixedAssetDisposalController::class, 'cancel'])->name('disposals.cancel');

        Route::get('/verification', [FixedAssetVerificationController::class, 'index'])->name('verification.index');
        Route::get('/verification/create', [FixedAssetVerificationController::class, 'create'])->name('verification.create');
        Route::post('/verification', [FixedAssetVerificationController::class, 'store'])->name('verification.store');
        Route::get('/verification/{session}', [FixedAssetVerificationController::class, 'show'])->name('verification.show');
        Route::post('/verification/{session}/scan', [FixedAssetVerificationController::class, 'scan'])->name('verification.scan');
        Route::post('/verification/items/{item}', [FixedAssetVerificationController::class, 'updateItem'])->name('verification.items.update');
        Route::post('/verification/{session}/complete', [FixedAssetVerificationController::class, 'complete'])->name('verification.complete');

        Route::get('/reports', [FixedAssetReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export', [FixedAssetReportController::class, 'export'])->name('reports.export');

        Route::get('/accounting/ledger', [FixedAssetAccountingController::class, 'ledger'])->name('accounting.ledger');
        Route::get('/accounting/ledger/export', [FixedAssetAccountingController::class, 'export'])->name('accounting.ledger.export');

        Route::get('/import-export', [FixedAssetImportExportController::class, 'index'])->name('import-export.index');
        Route::get('/import/template', [FixedAssetImportExportController::class, 'template'])->name('import.template');
        Route::post('/import', [FixedAssetImportExportController::class, 'import'])->name('import.store');
        Route::get('/export', [FixedAssetImportExportController::class, 'export'])->name('export');

        Route::get('/settings', [FixedAssetSettingController::class, 'index'])->name('settings.index');
        Route::post('/settings/categories', [FixedAssetSettingController::class, 'storeCategory'])->name('settings.categories.store');
        Route::post('/settings/locations', [FixedAssetSettingController::class, 'storeLocation'])->name('settings.locations.store');
        Route::post('/settings/depreciation-profiles', [FixedAssetSettingController::class, 'storeProfile'])->name('settings.profiles.store');
        Route::post('/settings/ensure-accounts', [FixedAssetSettingController::class, 'ensureAccounts'])->name('settings.ensure-accounts');

        Route::get('/manual/bn', function () {
            return view('backend.fixed_asset.manual_bn');
        })->name('manual.bn');

        Route::get('/manual/en', function () {
            return view('backend.fixed_asset.manual_en');
        })->name('manual.en');
    });

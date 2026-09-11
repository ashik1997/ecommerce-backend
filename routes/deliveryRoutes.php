<?php

use App\Http\Controllers\Delivery\DeliveryDashboardController;
use App\Http\Controllers\Delivery\DeliveryCodCollectionController;
use App\Http\Controllers\Delivery\DeliveryEmployeeController;
use App\Http\Controllers\Delivery\DeliveryProviderController;
use App\Http\Controllers\Delivery\DeliveryRateCardController;
use App\Http\Controllers\Delivery\DeliveryReportController;
use App\Http\Controllers\Delivery\DeliverySettlementController;
use App\Http\Controllers\Delivery\DeliveryShipmentController;
use App\Http\Controllers\Delivery\DeliveryShipmentWorkflowController;
use App\Http\Controllers\Delivery\DeliveryUserManualController;
use App\Http\Controllers\Delivery\DeliveryZoneAreaController;
use App\Http\Controllers\Delivery\DeliveryZoneController;
use App\Http\Controllers\Inventory\ProductOrderController;
use App\Http\Controllers\Courier\CourierManagementController;
use App\Http\Controllers\Courier\Settlement\CourierSettlementController;
use Illuminate\Support\Facades\Route;

Route::prefix('delivery-management')
    ->name('delivery-management.')
    ->middleware(['auth', 'CheckUserType', 'DemoMode'])
    ->group(function () {
        Route::get('/dashboard', [DeliveryDashboardController::class, 'index'])->name('dashboard');
        Route::resource('/providers', DeliveryProviderController::class)
            ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
        Route::resource('/employees', DeliveryEmployeeController::class)
            ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
        Route::resource('/zones', DeliveryZoneController::class)
            ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
        Route::post('/zones/{zone}/areas', [DeliveryZoneAreaController::class, 'store'])->name('zones.areas.store');
        Route::delete('/zones/{zone}/areas/{area}', [DeliveryZoneAreaController::class, 'destroy'])->name('zones.areas.destroy');
        Route::resource('/rate-cards', DeliveryRateCardController::class)
            ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
        Route::resource('/shipments', DeliveryShipmentController::class)
            ->only(['index', 'show']);
        Route::post('/shipments/{shipment}/assign', [DeliveryShipmentWorkflowController::class, 'assign'])->name('shipments.assign');
        Route::post('/shipments/{shipment}/status', [DeliveryShipmentWorkflowController::class, 'updateStatus'])->name('shipments.status.update');
        Route::post('/shipments/{shipment}/assignments/{assignment}/status', [DeliveryShipmentWorkflowController::class, 'assignmentStatus'])->name('shipments.assignments.status');
        Route::get('/cod-collections', [DeliveryCodCollectionController::class, 'index'])->name('cod-collections.index');
        Route::post('/shipments/{shipment}/cod-collections', [DeliveryCodCollectionController::class, 'store'])->name('shipments.cod-collections.store');
        Route::put('/cod-collections/{codCollection}', [DeliveryCodCollectionController::class, 'update'])->name('cod-collections.update');
        Route::post('/cod-collections/{codCollection}/verify', [DeliveryCodCollectionController::class, 'verify'])->name('cod-collections.verify');
        Route::resource('/settlements', DeliverySettlementController::class)
            ->only(['index', 'create', 'store', 'show']);
        Route::post('/settlements/{settlement}/approve', [DeliverySettlementController::class, 'approve'])->name('settlements.approve');
        Route::get('/reports', [DeliveryReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export', [DeliveryReportController::class, 'export'])->name('reports.export');
        Route::get('/user-manual', [DeliveryUserManualController::class, 'index'])->name('user-manual.index');
        Route::get('/user-manual/{locale}', [DeliveryUserManualController::class, 'index'])
            ->where('locale', 'bn|en')
            ->name('user-manual.locale');
        Route::get('/courier-config', [CourierManagementController::class, 'index'])->name('courier-config.index');
        Route::get('/courier-config/methods', [CourierManagementController::class, 'getMethods'])->name('courier-config.methods');
        Route::put('/courier-config/methods/{id}', [CourierManagementController::class, 'update'])->name('courier-config.methods.update');
        Route::get('/courier-wise-orders/{courier}', [ProductOrderController::class, 'courierWiseOrders'])->name('courier-wise-orders');
        Route::get('/courier-settlements', [CourierSettlementController::class, 'index'])->name('courier-settlements.index');
        Route::get('/courier-settlements/orders', [CourierSettlementController::class, 'orders'])->name('courier-settlements.orders');
        Route::post('/courier-settlements/sync', [CourierSettlementController::class, 'sync'])->name('courier-settlements.sync');
        Route::post('/courier-settlements', [CourierSettlementController::class, 'store'])->name('courier-settlements.store');
        Route::get('/courier-settlements/{settlement}', [CourierSettlementController::class, 'show'])->name('courier-settlements.show');
        Route::get('/courier-settlements/{settlement}/print', [CourierSettlementController::class, 'print'])->name('courier-settlements.print');
    });

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GeneralInfoController;
use App\Http\Controllers\BreakingNewsController;
use App\Http\Controllers\Courier\CarryBeeController;
use App\Http\Controllers\Courier\CourierManagementController;
use App\Http\Controllers\Courier\PathaoController;
use App\Http\Controllers\Courier\Settlement\CourierSettlementController;
use App\Http\Controllers\Courier\SteadfastController;
use Illuminate\Support\Facades\Log;

Route::group([
        'middleware' => [
            'auth', 
            'CheckUserType', 
            'DemoMode'
        ]
    ], function () {

    // general info routes
  
    Route::get('/general/info', [GeneralInfoController::class, 'generalInfo'])->name('GeneralInfo');
    Route::post('/update/general/info', [GeneralInfoController::class, 'updateGeneralInfo'])->name('UpdateGeneralInfo');
    Route::get('/website/theme/page', [GeneralInfoController::class, 'websiteThemePage'])->name('WebsiteThemePage');
    Route::post('/update/website/theme/color', [GeneralInfoController::class, 'updateWebsiteThemeColor'])->name('UpdateWebsiteThemeColor');
    Route::get('/social/media/page', [GeneralInfoController::class, 'socialMediaPage'])->name('SocialMediaPage');
    Route::post('/update/social/media/link', [GeneralInfoController::class, 'updateSocialMediaLinks'])->name('UpdateSocialMediaLinks');
    Route::get('/seo/homepage', [GeneralInfoController::class, 'seoHomePage'])->name('SeoHomePage');
    Route::post('/update/seo/homepage', [GeneralInfoController::class, 'updateSeoHomePage'])->name('UpdateSeoHomePage');
    Route::get('/custom/css/js', [GeneralInfoController::class, 'customCssJs'])->name('CustomCssJs');
    Route::post('/update/custom/css/js', [GeneralInfoController::class, 'updateCustomCssJs'])->name('UpdateCustomCssJs');
    Route::get('/social/api-scripts/page', [GeneralInfoController::class, 'socialChatScriptPage'])->name('SocialChatScriptPage');
    Route::post('/update/google/recaptcha', [GeneralInfoController::class, 'updateGoogleRecaptcha'])->name('UpdateGoogleRecaptcha');
    Route::post('/update/google/analytic', [GeneralInfoController::class, 'updateGoogleAnalytic'])->name('UpdateGoogleAnalytic');
    Route::post('/update/google/tag/manager', [GeneralInfoController::class, 'updateGoogleTagManager'])->name('updateGoogleTagManager');
    Route::post('/update/social/login/info', [GeneralInfoController::class, 'updateSocialLogin'])->name('UpdateSocialLogin');
    Route::post('/update/facebook/pixel', [GeneralInfoController::class, 'updateFacebookPixel'])->name('UpdateFacebookPixel');
    Route::post('/update/tiktok/pixel', [GeneralInfoController::class, 'updateTikTokPixel'])->name('UpdateTikTokPixel');
    Route::post('/update/messenger/chat/info', [GeneralInfoController::class, 'updateMessengerChat'])->name('UpdateMessengerChat');
    Route::post('/update/tawk/chat/info', [GeneralInfoController::class, 'updateTawkChat'])->name('UpdateTawkChat');
    Route::post('/update/crisp/chat/info', [GeneralInfoController::class, 'updateCrispChat'])->name('UpdateCrispChat');
    Route::get('/change/guest/checkout/status', [GeneralInfoController::class, 'changeGuestCheckoutStatus'])->name('ChangeGuestCheckoutStatus');
    Route::get('/checkout-config', [GeneralInfoController::class, 'checkoutConfigPage'])->name('CheckoutConfigPage');
    Route::post('/save-checkout-config', [GeneralInfoController::class, 'saveCheckoutConfig'])->name('SaveCheckoutConfig');
        
    Route::get('/template-choice', [GeneralInfoController::class, 'templateChoicePage'])->name('TemplateChoicePage');
    Route::post('/save-template-choice', [GeneralInfoController::class, 'saveTemplateChoice'])->name('SaveTemplateChoice');

    // breaking news routes
    Route::get('/add/new/breaking-news', [BreakingNewsController::class, 'create'])->name('AddNewBreakingNews');
    Route::post('/save/new/breaking-news', [BreakingNewsController::class, 'store'])->name('SaveNewBreakingNews');
    Route::get('/view/all/breaking-news', [BreakingNewsController::class, 'index'])->name('ViewAllBreakingNews');
    Route::get('/edit/breaking-news/{id}', [BreakingNewsController::class, 'edit'])->name('EditBreakingNews');
    Route::post('/update/breaking-news', [BreakingNewsController::class, 'update'])->name('UpdateBreakingNews');
    Route::get('/delete/breaking-news/{id}', [BreakingNewsController::class, 'destroy'])->name('DeleteBreakingNews');

    // Courier management (view + API for Vue)
    Route::get('/courier-management', [CourierManagementController::class, 'index'])->name('courier-management.index');
    Route::get('/courier-management/methods', [CourierManagementController::class, 'getMethods'])->name('courier-management.methods');
    Route::put('/courier-management/methods/{id}', [CourierManagementController::class, 'update'])->name('courier-management.methods.update');

    // Steadfast API proxy routes (auth required)
    // Route::get('/get-steadfast-order-status/{stead_fast_id}', [SteadfastController::class, 'getOrderStatus'])->name('steadfast.order-status');
    Route::get('/steadfast/get_balance', [SteadfastController::class, 'getBalance'])->name('steadfast.balance');
    Route::post('/steadfast/create_return_request', [SteadfastController::class, 'createReturnRequest'])->name('steadfast.create-return-request');
    Route::get('/steadfast/get_return_request/{id}', [SteadfastController::class, 'getReturnRequest'])->name('steadfast.return-request');
    Route::get('/steadfast/get_return_requests', [SteadfastController::class, 'getReturnRequests'])->name('steadfast.return-requests');
    Route::get('/steadfast/payments', [SteadfastController::class, 'getPayments'])->name('steadfast.payments');
    Route::get('/steadfast/payments/{payment_id}', [SteadfastController::class, 'getPayment'])->name('steadfast.payment');
    Route::get('/steadfast/police_stations', [SteadfastController::class, 'getPoliceStations'])->name('steadfast.police-stations');

    // Pathao bulk orders (from order management view)
    Route::post('/pathao/set-bulk-orders', [PathaoController::class, 'setBulkOrders'])->name('pathao.set-bulk-orders');

    // Steadfast bulk orders (from order management view)
    Route::post('/steadfast/set-bulk-orders', [SteadfastController::class, 'setBulkOrders'])->name('steadfast.set-bulk-orders');

    // CarryBee bulk orders (from order management view)
    Route::post('/carrybee/set-bulk-orders', [CarryBeeController::class, 'setBulkOrders'])->name('carrybee.set-bulk-orders');

    // Courier settlement and audit reports
    Route::get('/courier-settlements', [CourierSettlementController::class, 'index'])->name('courier-settlements.index');
    Route::get('/courier-settlements/orders', [CourierSettlementController::class, 'orders'])->name('courier-settlements.orders');
    Route::post('/courier-settlements/sync', [CourierSettlementController::class, 'sync'])->name('courier-settlements.sync');
    Route::post('/courier-settlements', [CourierSettlementController::class, 'store'])->name('courier-settlements.store');
    Route::get('/courier-settlements/{settlement}', [CourierSettlementController::class, 'show'])->name('courier-settlements.show');
    Route::get('/courier-settlements/{settlement}/print', [CourierSettlementController::class, 'print'])->name('courier-settlements.print');

});

// Public courier status routes (no auth)
Route::get('/get-steadfast-order-status/{stead_fast_id}', [SteadfastController::class, 'getOrderStatus'])->name('steadfast.order-status');
Route::get('/get-pathao-order-status/{pathao_id}', [PathaoController::class, 'getOrderStatus'])->name('pathao.order-status');
Route::get('/get-carrybee-order-status/{carrybee_id}', [CarryBeeController::class, 'getOrderStatus'])->name('carrybee.order-status');


// Pathao webhook callback (must be public, no auth, no CSRF). Pathao sends POST with body: { "event": "webhook_integration" } for integration test.
Route::any('/pathao-callbacks', [PathaoController::class, 'handlePathaoCallback'])->name('pathao.callback');

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductManagement\ProductManagementController;
use App\Http\Controllers\ProductManagement\ProductOfferManagementController;

/*
|--------------------------------------------------------------------------
| Product Management Routes
|--------------------------------------------------------------------------
*/

Route::group(['middleware' => ['auth']], function () {
    
    // Product Management Routes
    Route::prefix('product-management')->name('product-management.')->group(function () {
        
        // Main product routes
        Route::get('/', [ProductManagementController::class, 'index'])->name('index');
        Route::get('/create', [ProductManagementController::class, 'create'])->name('create');
        Route::post('/store', [ProductManagementController::class, 'store'])->name('store');
        Route::get('/show/{id}', [ProductManagementController::class, 'show'])->name('show');
        Route::get('/pdf/{id}', [ProductManagementController::class, 'generatePDF'])->name('pdf');
        Route::get('/edit/{id}', [ProductManagementController::class, 'edit'])->name('edit');
        Route::put('/update/{id}', [ProductManagementController::class, 'update'])->name('update');
        Route::delete('/delete/{id}', [ProductManagementController::class, 'destroy'])->name('destroy');
        
         // Filter routes
        Route::post('/apply-filters', [ProductManagementController::class, 'applyFilters'])->name('apply-filters');
        Route::post('/clear-filters', [ProductManagementController::class, 'clearFilters'])->name('clear-filters');
        
        // Product details routes
        Route::get('/{productId}/unit-prices', [ProductManagementController::class, 'getUnitPrices'])->name('unit-prices');
        Route::get('/{productId}/variant-stocks', [ProductManagementController::class, 'getVariantStocks'])->name('variant-stocks');
        
        // AJAX routes for dynamic data
        Route::get('/get-subcategories/{categoryId}', [ProductManagementController::class, 'getSubcategories'])->name('get-subcategories');
        Route::get('/get-child-categories/{subcategoryId}', [ProductManagementController::class, 'getChildCategories'])->name('get-child-categories');
        Route::get('/get-models/{brandId}', [ProductManagementController::class, 'getModelsByBrand'])->name('get-models');
        Route::get('/get-variant-groups', [ProductManagementController::class, 'getVariantGroups'])->name('get-variant-groups');
        Route::get('/get-variant-group-keys/{groupId}', [ProductManagementController::class, 'getVariantGroupKeys'])->name('get-variant-group-keys');
        
        // Product details AJAX
        Route::get('/{productId}/data', [ProductManagementController::class, 'getProductData'])->name('get-product-data');
        Route::get('/{productId}/unit-prices', [ProductManagementController::class, 'getUnitPrices'])->name('unit-prices');
        Route::get('/{productId}/variant-stocks', [ProductManagementController::class, 'getVariantStocks'])->name('variant-stocks');
        
        // Bulk actions
        Route::get('/search-products', [ProductManagementController::class, 'searchProducts'])->name('search-products');
        Route::post('/bulk-delete', [ProductManagementController::class, 'bulkDelete'])->name('bulk-delete');
        Route::post('/bulk-status-update', [ProductManagementController::class, 'bulkStatusUpdate'])->name('bulk-status-update');
        Route::post('/check-slug', [ProductManagementController::class, 'checkSlug'])->name('check-slug');

        // Category management
        Route::post('/categories/store', [ProductManagementController::class, 'storeCategory'])->name('categories.store');
        Route::post('/subcategories/store', [ProductManagementController::class, 'storeSubcategory'])->name('subcategories.store');
        Route::post('/child-categories/store', [ProductManagementController::class, 'storeChildCategory'])->name('child-categories.store');
        
        // Brand management
        Route::post('/brands/store', [ProductManagementController::class, 'storeBrand'])->name('brands.store');
        
        // Model management
        Route::post('/models/store', [ProductManagementController::class, 'storeModel'])->name('models.store');
        
        // Unit management
        Route::post('/units/store', [ProductManagementController::class, 'storeUnit'])->name('units.store');

        // Product offer management
        Route::prefix('product-offers')->name('product-offers.')->group(function () {
            Route::get('/', [ProductOfferManagementController::class, 'index'])->name('index');
            Route::get('/create', [ProductOfferManagementController::class, 'create'])->name('create');
            Route::post('/store', [ProductOfferManagementController::class, 'store'])->name('store');
            Route::get('/{productOffer}', [ProductOfferManagementController::class, 'show'])->name('show');
            Route::get('/{productOffer}/edit', [ProductOfferManagementController::class, 'edit'])->name('edit');
            Route::put('/{productOffer}', [ProductOfferManagementController::class, 'update'])->name('update');
            Route::post('/{productOffer}/items', [ProductOfferManagementController::class, 'addProduct'])->name('items.store');
            Route::delete('/{productOffer}/items/{item}', [ProductOfferManagementController::class, 'removeProduct'])->name('items.destroy');
            Route::patch('/{productOffer}/items/{item}/discount', [ProductOfferManagementController::class, 'updateItemDiscount'])->name('items.discount');
        });

        // product wise purchase history
        Route::get('product-wise-purchase-history', [ProductManagementController::class, 'productWisePurchaseHistory'])
        ->name('product.purchase.history');
    });
    
    
});
// wardah products sync
// Route::get('/sync-categories-from-api', [ProductManagementController::class, 'syncCategoriesFromApi'])->name('sync-categories-from-api');
// Route::get('/sync-brands-from-api', [ProductManagementController::class, 'syncBrandsFromApi'])->name('sync-brands-from-api');
// Route::get('/sync-customers-from-api', [ProductManagementController::class, 'syncCustomersFromApi'])->name('sync-customers-from-api');
// Route::get('/sync-from-api', [ProductManagementController::class, 'syncProductsFromApi'])->name('sync-from-api');


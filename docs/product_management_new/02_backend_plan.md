# 02 Backend Plan

Last updated: 2026-06-07

## Hard Boundary

Do not use:

```text
App\Http\Controllers\ProductManagement\ProductManagementController
```

Do not add v3 actions to the existing controller.

Do not change existing stable route behavior.

## Route File

Preferred option:

```text
routes/productManagementV3Routes.php
```

Then include it from `routes/web.php` near the existing product route include.

Alternative option:

```text
Add a separate v3 route group inside routes/productManagementRoutes.php.
```

If this alternative is used, keep it clearly separated and do not modify old route definitions.

## Proposed Routes

```php
Route::middleware(['auth'])
    ->prefix('product-management/v3')
    ->name('product-management.v3.')
    ->group(function () {
        Route::get('/create', CreatePageController::class)->name('create');
        Route::get('/products/{product}/edit', EditPageController::class)->name('edit');

        Route::get('/bootstrap', BootstrapController::class)->name('bootstrap');
        Route::get('/products/{product}', ShowProductController::class)->name('products.show');

        Route::post('/products/basic-info', StoreBasicInfoController::class)->name('basic-info.store');
        Route::put('/products/{product}/basic-info', UpdateBasicInfoController::class)->name('basic-info.update');

        Route::put('/products/{product}/images', UpdateImagesController::class)->name('images.update');
        Route::put('/products/{product}/content', UpdateContentController::class)->name('content.update');
        Route::put('/products/{product}/pricing', UpdatePricingController::class)->name('pricing.update');
        Route::put('/products/{product}/variants', UpdateVariantsController::class)->name('variants.update');
        Route::put('/products/{product}/filters', UpdateFiltersController::class)->name('filters.update');
        Route::put('/products/{product}/attributes', UpdateAttributesController::class)->name('attributes.update');
        Route::put('/products/{product}/shipping', UpdateShippingController::class)->name('shipping.update');
        Route::put('/products/{product}/related', UpdateRelatedController::class)->name('related.update');
        Route::put('/products/{product}/notification', UpdateNotificationController::class)->name('notification.update');
        Route::put('/products/{product}/seo', UpdateSeoController::class)->name('seo.update');
        Route::put('/products/{product}/faq', UpdateFaqController::class)->name('faq.update');

        Route::get('/options/subcategories', SubcategoryOptionsController::class)->name('options.subcategories');
        Route::get('/options/child-categories', ChildCategoryOptionsController::class)->name('options.child-categories');
        Route::get('/options/models', ModelOptionsController::class)->name('options.models');
        Route::get('/options/products', ProductSearchOptionsController::class)->name('options.products');

        Route::post('/slug/check', CheckProductSlugController::class)->name('slug.check');
    });
```

## Controller File Layout

Use single-action controllers.

```text
app/Http/Controllers/ProductManagement/V3/CreatePageController.php
app/Http/Controllers/ProductManagement/V3/EditPageController.php
app/Http/Controllers/ProductManagement/V3/BootstrapController.php
app/Http/Controllers/ProductManagement/V3/ShowProductController.php

app/Http/Controllers/ProductManagement/V3/BasicInfo/StoreBasicInfoController.php
app/Http/Controllers/ProductManagement/V3/BasicInfo/UpdateBasicInfoController.php
app/Http/Controllers/ProductManagement/V3/Images/UpdateImagesController.php
app/Http/Controllers/ProductManagement/V3/Content/UpdateContentController.php
app/Http/Controllers/ProductManagement/V3/Pricing/UpdatePricingController.php
app/Http/Controllers/ProductManagement/V3/Variants/UpdateVariantsController.php
app/Http/Controllers/ProductManagement/V3/Filters/UpdateFiltersController.php
app/Http/Controllers/ProductManagement/V3/Attributes/UpdateAttributesController.php
app/Http/Controllers/ProductManagement/V3/Shipping/UpdateShippingController.php
app/Http/Controllers/ProductManagement/V3/Related/UpdateRelatedController.php
app/Http/Controllers/ProductManagement/V3/Notification/UpdateNotificationController.php
app/Http/Controllers/ProductManagement/V3/Seo/UpdateSeoController.php
app/Http/Controllers/ProductManagement/V3/Faq/UpdateFaqController.php
app/Http/Controllers/ProductManagement/V3/Options/SubcategoryOptionsController.php
app/Http/Controllers/ProductManagement/V3/Options/ChildCategoryOptionsController.php
app/Http/Controllers/ProductManagement/V3/Options/ModelOptionsController.php
app/Http/Controllers/ProductManagement/V3/Options/ProductSearchOptionsController.php
app/Http/Controllers/ProductManagement/V3/Slug/CheckProductSlugController.php
```

## Request Objects

Each tab gets its own request class.

```text
app/Http/Requests/ProductManagement/V3/StoreBasicInfoRequest.php
app/Http/Requests/ProductManagement/V3/UpdateBasicInfoRequest.php
app/Http/Requests/ProductManagement/V3/UpdateImagesRequest.php
app/Http/Requests/ProductManagement/V3/UpdateContentRequest.php
app/Http/Requests/ProductManagement/V3/UpdatePricingRequest.php
app/Http/Requests/ProductManagement/V3/UpdateVariantsRequest.php
app/Http/Requests/ProductManagement/V3/UpdateFiltersRequest.php
app/Http/Requests/ProductManagement/V3/UpdateAttributesRequest.php
app/Http/Requests/ProductManagement/V3/UpdateShippingRequest.php
app/Http/Requests/ProductManagement/V3/UpdateRelatedRequest.php
app/Http/Requests/ProductManagement/V3/UpdateNotificationRequest.php
app/Http/Requests/ProductManagement/V3/UpdateSeoRequest.php
app/Http/Requests/ProductManagement/V3/UpdateFaqRequest.php
```

## Service Layer

Recommended service files:

```text
app/Services/ProductManagement/V3/ProductV3BootstrapService.php
app/Services/ProductManagement/V3/ProductV3ReadService.php
app/Services/ProductManagement/V3/ProductV3BasicInfoService.php
app/Services/ProductManagement/V3/ProductV3ImageService.php
app/Services/ProductManagement/V3/ProductV3ContentService.php
app/Services/ProductManagement/V3/ProductV3PricingService.php
app/Services/ProductManagement/V3/ProductV3VariantService.php
app/Services/ProductManagement/V3/ProductV3FilterService.php
app/Services/ProductManagement/V3/ProductV3AttributeService.php
app/Services/ProductManagement/V3/ProductV3ShippingService.php
app/Services/ProductManagement/V3/ProductV3RelatedService.php
app/Services/ProductManagement/V3/ProductV3NotificationService.php
app/Services/ProductManagement/V3/ProductV3SeoService.php
app/Services/ProductManagement/V3/ProductV3FaqService.php
app/Services/ProductManagement/V3/ProductV3MediaUsageService.php
app/Services/ProductManagement/V3/ProductV3CompletionService.php
```

## Response Format

All tab save endpoints should return a consistent shape:

```json
{
  "success": true,
  "message": "Saved successfully.",
  "product_id": 123,
  "data": {},
  "completion": {
    "setup_status": "draft",
    "completed_tabs": ["basic_info", "images"],
    "missing_tabs": ["pricing"]
  }
}
```

Validation errors use Laravel 422:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "name": ["The name field is required."]
  }
}
```

## Transaction Rules

Use DB transactions per tab save.

Do not wrap unrelated tabs in the same transaction.

Examples:

```text
Basic Info transaction only writes the products root fields.
Pricing transaction writes products pricing fields and product_unit_pricings.
Variants transaction writes product_variant_combinations and related media usage.
Images transaction writes product image fields and media usage.
```

## Authorization

Initial v3 can reuse current auth middleware.

If sidebar permission keys are needed, reserve new keys instead of reusing old keys implicitly:

```text
product-management.v3.list
product-management.v3.create
product-management.v3.edit
product-management.v3.read
product-management.v3.media
```

## Slug Rules

Slug should be normalized server-side before validation.

Recommended rules:

```text
required
string
max:255
regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/
unique:products,slug except current product on update
```

## Stock Rules

v3 must preserve current stock source-of-truth:

```text
Do not create stock entries from product builder tabs.
Do not set products.stock from pricing input.
Recalculate stock from product_stocks when needed.
```

## Media Rules

Selected existing media:

```text
Do not mark as temp.
Do not delete on field clear.
Track usage if usage service is enabled.
```

New uploaded media:

```text
Created as temp by upload endpoint.
When tab save succeeds, mark selected IDs permanent.
If abandoned, cleanup command can remove old temp files.
```


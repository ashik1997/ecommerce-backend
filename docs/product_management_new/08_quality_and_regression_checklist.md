# 08 Quality and Regression Checklist

Last updated: 2026-06-07

## No-Touch Regression Checklist

Before and after each v3 stage, confirm no accidental changes to:

```text
routes/productManagementRoutes.php old route definitions
app/Http/Controllers/ProductManagement/ProductManagementController.php
resources/views/backend/product_management/create.blade.php
resources/views/backend/product_management/edit.blade.php
public/assets/js/product_create_vue.js
public/assets/js/product_edit_vue.js
```

These files may be read for reference. They should not be edited for v3 unless a separate explicit migration/cutover task approves it.

## Static Checks

Run targeted PHP syntax checks for new PHP files:

```text
php -l app/Http/Controllers/ProductManagement/V3/...
php -l app/Http/Requests/ProductManagement/V3/...
php -l app/Services/ProductManagement/V3/...
```

Run JavaScript syntax checks where possible:

```text
node --check public/assets/js/product-management/v3/app.js
node --check public/assets/js/product-management/v3/bootstrap.js
node --check public/assets/js/product-management/v3/stores/*.js
node --check public/assets/js/product-management/v3/components/**/*.js
```

## Route Checks

Expected v3 route prefix:

```text
/product-management/v3
```

Expected route name prefix:

```text
product-management.v3.
```

There must be no v3 route that points to:

```text
ProductManagementController
```

## Create Mode QA

Checklist:

```text
Open /product-management/v3/create.
Only Basic Info tab is enabled.
Click Images tab before save.
Warning appears.
No API call is made for locked tab save.
Fill name, slug, category.
Save Basic Info.
Product row is created.
Response returns product_id.
Tabs unlock.
Browser URL changes to edit URL if implemented.
Reload page opens edit mode for that product.
```

## Edit Mode QA

Checklist:

```text
Open /product-management/v3/products/{product}/edit.
All tabs enabled.
Product data hydrates.
Save each tab independently.
Check unrelated fields are unchanged after each tab save.
```

## Tab Isolation QA

For every tab save:

```text
Capture product before save.
Save one tab.
Capture product after save.
Confirm only owned fields/tables changed.
```

Critical examples:

```text
Pricing save must not touch product image.
Images save must not touch price.
Filters save must not touch variant combinations.
Variants save must not touch filter attributes.
Notification save must not touch SEO.
FAQ save must not touch content.
```

## Media Manager QA

Checklist:

```text
Open two different image picker fields on one page.
Select image for first picker.
Select different image for second picker.
Confirm callbacks do not conflict.
Open modal in single mode.
Only one item can be selected.
Open modal in multiple mode.
Multiple items can be selected up to max.
Upload new image.
Uploaded image appears and is selected.
Clear selected field.
Media file remains in library.
Save product images tab.
Selected temp media becomes permanent.
```

## Stock QA

Checklist:

```text
Create product through Basic Info.
Save pricing.
Save variants.
Confirm no product_stocks row was created by product builder.
Confirm products.stock remains derived from existing stock tables.
```

## Failure Isolation QA

Simulate failure in one tab:

```text
Force validation error in Pricing.
Confirm Basic Info data remains saved.
Confirm Images data remains saved.
Confirm other tabs are still usable.
```

## Browser Compatibility

Minimum manual browser checks:

```text
Chrome desktop
Chrome mobile viewport
Safari or WebKit if available
```

## Deployment Safety

First deployment should keep old product management links live.

Recommended v3 exposure:

```text
direct URL only
or hidden sidebar link for admins
or feature flag
```

Do not replace old create/edit links until v3 passes create/edit/media/variant QA.


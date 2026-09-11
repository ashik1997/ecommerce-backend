# 01 Product Management v3 Architecture

Last updated: 2026-06-07

## Goal

Build a new product builder that is modular, predictable, and easy to change.

The v3 product builder must support both:

```text
Create product
Edit product
```

with one shared frontend app and one shared backend domain layer.

## Route Prefix

Use:

```text
/product-management/v3
```

Route name prefix:

```text
product-management.v3.
```

## High-Level User Flow

Create mode:

```text
1. User opens /product-management/v3/create.
2. Only Basic Info tab is enabled.
3. User saves Basic Info.
4. Backend creates products row.
5. Response returns product_id and edit URL.
6. Frontend unlocks all tabs.
7. URL may be replaced with /product-management/v3/products/{product}/edit.
8. User saves each tab independently.
```

Edit mode:

```text
1. User opens /product-management/v3/products/{product}/edit.
2. All tabs are enabled.
3. Product data is loaded by API.
4. User can switch to any tab and save only that tab.
```

## Core Principle

v3 is not one giant product form.

v3 is a product shell plus independent tab forms.

```text
Basic Info creates or updates the root product row.
Images updates only image fields and media usage.
Content updates only content fields.
Pricing updates only pricing and unit pricing.
Variants updates only variant combinations.
Filters updates only filter attributes and mappings.
Attributes updates only product attributes JSON.
Shipping updates only shipping and tax JSON.
Related updates only related product fields.
Notification updates only notification fields.
SEO updates only meta fields.
FAQ updates only FAQ JSON.
```

## Recommended Backend Pattern

Use thin invokable controllers or single-action controllers.

Example:

```text
app/Http/Controllers/ProductManagement/V3/CreatePageController.php
app/Http/Controllers/ProductManagement/V3/EditPageController.php
app/Http/Controllers/ProductManagement/V3/BootstrapController.php
app/Http/Controllers/ProductManagement/V3/ShowProductController.php
app/Http/Controllers/ProductManagement/V3/BasicInfo/StoreBasicInfoController.php
app/Http/Controllers/ProductManagement/V3/BasicInfo/UpdateBasicInfoController.php
app/Http/Controllers/ProductManagement/V3/Images/UpdateImagesController.php
```

Each controller should usually have only:

```php
public function __invoke(...)
```

Business logic should live in:

```text
app/Services/ProductManagement/V3/*
app/Actions/ProductManagement/V3/*
```

## Recommended Frontend Pattern

Use POS v3 style:

```text
Blade page only injects config and script tags.
Vue 3 owns the UI.
Pinia owns module state.
Components are registered under one namespace.
```

Root mount point:

```html
<div id="product-v3-app" class="product-v3-page"></div>
```

Global config:

```js
window.PRODUCT_V3_CONFIG = {
  configVersion: 1,
  mode: 'create',
  productId: null,
  routes: {}
}
```

## File Ownership

Backend page shell:

```text
resources/views/backend/product_management/v3/form.blade.php
```

Frontend app:

```text
public/assets/js/product-management/v3
```

Styles:

```text
public/assets/css/product_management_v3/product_management_v3.css
```

Global reusable media manager:

```text
resources/views/backend/components/media-manager/modal.blade.php
public/assets/js/media-manager
public/assets/css/media-manager.css
```

## Data Loading Strategy

Use two API layers:

```text
Bootstrap data: static/reference options needed by the form.
Product data: current product values for edit mode or after basic create.
```

Bootstrap examples:

```text
websites
categories
brands
units
flags
variant groups
filter groups
availability options
routes
feature flags
```

Product data examples:

```text
basic info
images
pricing
unit pricing
variants
filters
attributes
shipping
related
notification
seo
faq
completion status
```

## Completion State

Because create mode is split, v3 should track product setup progress.

Recommended model concept:

```text
setup_status = draft | ready
```

If no schema change is desired in first stage, use existing `status = 0` for newly created products and keep setup progress in frontend/API response only.

Preferred long-term approach:

```text
products.status = publish/inactive state
products.setup_status = builder completion state
```

## Tab Locking Rule

Frontend must enforce:

```text
if mode is create and productId is null:
    only basic_info is enabled
else:
    all authorized tabs are enabled
```

Backend must also enforce:

```text
Every non-basic tab route requires a valid product route model.
```


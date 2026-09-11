# 00 Existing Product Management Audit

Last updated: 2026-06-07

## Scope

This audit records the current stable product creation/editing flow so v3 can preserve business behavior without inheriting the messy implementation.

## Existing Route Layer

Current product routes live in:

```text
routes/productManagementRoutes.php
```

The current route prefix is:

```text
/product-management
```

Current create/edit endpoints:

```text
GET  /product-management/create
POST /product-management/store
GET  /product-management/edit/{id}
PUT  /product-management/update/{id}
```

Current controller:

```text
App\Http\Controllers\ProductManagement\ProductManagementController
```

v3 must not use this controller.

## Existing Frontend Layer

Current create Blade:

```text
resources/views/backend/product_management/create.blade.php
```

Current edit Blade:

```text
resources/views/backend/product_management/edit.blade.php
```

Current tab partials:

```text
resources/views/backend/product_management/tabs/basic_info.blade.php
resources/views/backend/product_management/tabs/images.blade.php
resources/views/backend/product_management/tabs/content.blade.php
resources/views/backend/product_management/tabs/pricing.blade.php
resources/views/backend/product_management/tabs/variants.blade.php
resources/views/backend/product_management/tabs/filter_attributes.blade.php
resources/views/backend/product_management/tabs/attributes.blade.php
resources/views/backend/product_management/tabs/shipping.blade.php
resources/views/backend/product_management/tabs/related_products.blade.php
resources/views/backend/product_management/tabs/notification.blade.php
resources/views/backend/product_management/tabs/seo.blade.php
resources/views/backend/product_management/tabs/faq.blade.php
```

Current JavaScript:

```text
public/assets/js/product_create_vue.js
public/assets/js/product_edit_vue.js
```

The current JS is Vue 2 style and large. It mixes:

```text
form state
tab state
validation
slug generation
AJAX calls
media upload
variant matrix generation
localStorage backup
payload building
submit and resend behavior
inline master-data creation
```

v3 should not copy this structure.

## Current Product Create Behavior

The current create page:

```text
Loads all dropdown/reference data in ProductManagementController@create.
Injects data into window.productData.
Mounts one large Vue instance.
Lets the user fill all tabs before first save.
POSTs one large JSON payload to /product-management/store.
Creates the product and child records in one DB transaction.
Redirects to edit page after success.
```

## Current Save Payload Areas

Current single payload includes:

```text
product
pricing
unit_pricing
has_variants
variant_combinations
filter_attributes
attributes
shipping_info
tax_info
meta_info
special_offer
related
notification
faq
_metadata
```

## Current Tables Touched

The existing save flow writes or updates:

```text
products
product_unit_pricings
product_variant_combinations
product_filter_attributes
product_filter_attribute_mappings
media_files
product_stocks only indirectly through stock recalculation, not initial stock creation
```

## Current Stock Behavior

Important business behavior:

```text
Product creation does not create actual stock.
products.stock is set or recalculated from product_stocks.
Variant combination stock is also derived from product_stocks.
Initial create form stock input should not be treated as source of truth.
Stock should remain inventory/stock-adjustment owned.
```

v3 must preserve this rule unless a separate inventory decision changes it.

## Current Media Behavior

Current product image upload:

```text
POST /media/upload
stores media_files row as is_temp = true
product save marks image/galleries/variant images permanent
remove usually calls /media/revert for temp files
```

Problem for v3:

```text
This behavior is upload-first, not reuse-first.
Remove can imply delete, which is dangerous for reusable media.
```

v3 must distinguish:

```text
detach from field
delete from media library
```

## Current Pain Points

```text
Create and edit logic duplicated.
One huge Vue file is hard to reason about.
One backend controller owns too many responsibilities.
One giant submit means one tab can break the entire product save.
Media upload code is repeated across product image, gallery, variants, notification, and color images.
Filter attributes and sale variants are separate concepts but live in same large state object.
Basic product row does not exist until the final full submit, so later tabs cannot save independently.
```

## v3 Audit Conclusion

Build v3 as a new module.

Use the existing module only as a behavior reference.


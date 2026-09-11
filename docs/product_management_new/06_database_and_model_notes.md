# 06 Database and Model Notes

Last updated: 2026-06-07

## Existing Product Storage

v3 should initially reuse existing product-related tables.

Known tables from current flow:

```text
products
product_unit_pricings
product_variant_combinations
product_filter_attributes
product_filter_attribute_mappings
product_stocks
product_stock_logs
media_files
media_in_uses
```

## Product Model Casts

Existing `App\Models\Product` already casts:

```text
related_similar_products => array
related_recommended_products => array
related_addon_products => array
faq => array
notification_is_show => boolean
shipping_info => array
tax_info => array
attributes => array
dimensions => array
measurements => array
```

v3 services should use these casts instead of manual JSON string manipulation where possible.

## Setup Status

Recommended future migration:

```text
add setup_status to products
```

Suggested values:

```text
draft
ready
```

Suggested behavior:

```text
Basic Info create sets setup_status = draft.
Completion service marks ready when required tabs/fields are satisfied.
Publishing can require setup_status = ready.
```

If migration is deferred:

```text
Use status = 0 for new Basic Info products.
Expose completion status from service without storing it.
```

## Product Image Storage Compatibility

Current product image storage:

```text
products.image stores file path
products.multiple_images stores JSON array of paths
products.notification_image_id stores media ID
products.notification_image_path stores file path
product_variant_combinations.image stores file path
```

v3 should accept media IDs from frontend and resolve to file paths on save for compatibility.

Example:

```text
product_image_id 10 -> products.image = media_files.file_path
gallery_image_ids [11, 12] -> products.multiple_images = [file_path_11, file_path_12]
```

## Media File Rules

`media_files.is_temp` means:

```text
Uploaded but not attached to any saved record yet.
Eligible for cleanup after 24 hours.
```

When selected media is saved to a product tab:

```text
set is_temp = false
set temp_token = null
```

Do this for new uploads and harmlessly for existing permanent files.

## Media Usage

`media_in_uses` exists but is underused.

v3 should use a service to sync usage:

```text
ProductV3MediaUsageService
```

Service responsibilities:

```text
sync one media usage for a model column
sync many media usages for a model column
remove inactive usages for detached media
return usage count for delete safety
```

Do not physically delete media when syncing usage.

## Stock Compatibility

Do not introduce stock writes in product v3 builder.

Current product stock behavior:

```text
products.stock is derived from product_stocks.
variant stock is derived from product_stocks for each combination.
```

Allowed in v3:

```text
read present stock
display present stock
set low stock thresholds
```

Not allowed in v3 product builder:

```text
create product_stocks entry
create product_stock_logs entry
decrement stock
increment stock
```

## Migration Safety

First v3 build should avoid destructive migrations.

Allowed:

```text
nullable additive columns
new indexes
new permission keys
new route file
new controllers/services/views/assets
```

Not allowed:

```text
drop existing product columns
rename existing product columns
modify existing product route behavior
hard-delete existing media records as part of detach
```


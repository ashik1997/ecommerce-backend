# FBM-05 — Existing Pixel and Feed Consolidation

Completed: 2026-06-09

## Outcome

FB MARKETING configuration remains isolated from the existing stable General Information Pixel flow. The module uses its existing encrypted `fbm_connections` vault plus a new tenant-local singleton `fbm_module_settings` row for product-feed enablement and cache TTL. No legacy Pixel credential is imported, overwritten or deleted.

## Product-feed consolidation

The canonical product opt-in field is `products.is_facebook_product_feed`. The guarded tenant migration adds it when missing and backfills true rows from the legacy `products.is_facebook_feed` field without dropping the legacy column. Rollback deliberately preserves the canonical field so operator selections are not silently lost.

`GET /api/facebook-product-feed.xml` now resolves through `App\Http\Controllers\Api\FbMarketingProductFeedController` and `App\Services\FbMarketing\FbmProductFeedService`. It uses canonical selections when available, falls back to the legacy field only before migration, and safely emits zero products when neither field exists. The former arbitrary latest-100-products fallback has been removed.

Product save, delete and restore events invalidate a tenant-scoped cache key through `FbmProductFeedCacheObserver`. Cache namespaces include a hash of tenant host and active database name.

## Safe diagnostics

The module Configuration page shows feed URL, selection mode, feed status, TTL, opted-in products, feed-ready products, selected local Pixel and catalog counts, cache invalidation timestamp and warning counts. It exposes no provider asset IDs or secrets.

## Permissions

```text
fb_marketing_setup_wizard_view.read
fb_marketing_configuration_manage.update
```

Configuration is the final FB MARKETING sidebar link. Existing `general_infos.fb_pixel_*` fields and the stable storefront/API behavior remain untouched.

## Explicitly absent

```text
Meta catalog upload
Meta write requests
automatic legacy credential migration
campaign sync
queue jobs
scheduler
CAPI sending
webhooks
attribution changes
```

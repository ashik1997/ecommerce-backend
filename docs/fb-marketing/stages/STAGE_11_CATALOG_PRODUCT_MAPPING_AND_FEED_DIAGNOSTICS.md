# FBM-11 — Catalog, Product Mapping and Feed Diagnostics

Completed in the compact patch on 2026-06-10.

## Scope

FBM-11 adds tenant-local read-only catalog product and product-set mirrors, deterministic ERP mapping, local mapping overrides, one shared XML-feed projection and a Products & Catalog reporting surface. It adds no Meta mutation endpoint.

## Tenant migration

The guarded non-destructive tenant migration adds:

```text
fbm_catalog_product_mappings
fbm_product_sets
fbm_catalog_sync_runs
```

Rollback intentionally preserves mapping overrides and operational ledgers. Explicit removal is required if a deployment later retires the module.

## Shared ERP feed projection

`FbmFeedProductProjectionService` is the single validity boundary used by both XML generation and UI diagnostics. An opted-in ERP product is emitted only when it is active, has a title, has an image and has a positive effective price. Existing `/api/facebook-product-feed.xml`, canonical `products.is_facebook_product_feed`, legacy fallback behavior and the stable ERP product form remain in place.

Variant products remain parent-level feed items and emit `variant_parent_level_export` warnings. FBM-11 deliberately does not expand variants because that could silently change catalog identities.

## Read-only catalog mirror

For selected and available locally discovered catalogs only:

```text
GET /{version}/{catalog-id}/products
GET /{version}/{catalog-id}/product_sets
```

Traversal uses the existing trusted Graph client with a fixed field allow-list, trusted-host enforcement, disabled redirects, bounded pages, bounded rows and safe API ledgers. Raw payloads remain in memory only long enough to normalize allow-listed fields.

Provider catalog-item IDs and product-set IDs are internal synchronization keys. They are hidden from browser-safe summaries and excluded from operational logs.

## ERP product mapping

Automatic mapping is deterministic:

```text
Meta retailer_id == ERP product.id
```

Duplicate retailer IDs remain `ambiguous`. Missing deterministic matches remain `unmatched`. Operators holding `fb_marketing_catalog_mapping_manage.update` may save or clear a tenant-local manual override. A local override sends no provider request.

## Incomplete-family rule

If Graph pagination fails, reaches a bound or product sets exceed the local per-catalog cap, existing unseen rows remain preserved. They are marked unavailable only after a complete family result. This prevents a partial provider response from erasing previously known tenant-local mirror state.

## Queued and manual execution

Queued full read-only sync now runs:

```text
asset discovery
catalog products and product sets mirror
campaign hierarchy mirror
Ads Insights snapshot coordination
```

Manual direct full sync includes the same catalog phase with a smaller selected-catalog bound. Manual drilldown-only refresh skips catalog traversal.

A separate bounded mapped-domain fallback is available:

```text
POST /fb-marketing/configuration/connections/{connection}/refresh-catalog-now
```

It uses the existing full-read-only tenant-connection lock, dispatches no worker job and performs no provider write. It requires an active connection, configured access token, latest healthy-or-warning read-only health check, Configuration read permission and `fb_marketing_catalog_sync_run.read`.

## Products & Catalog UI

```text
GET /fb-marketing/products-catalog
```

Tabs:

```text
Feed Products
Product Mapping
Catalog Sync
Feed Diagnostics
```

The page exposes safe counts, local ERP IDs, retailer IDs, names, local mapping state, bounded diagnostic flags, product-set summaries and redacted catalog-sync ledger rows. Stock and variant warning totals use a configurable bounded scan and display a truncation notice when the scan is not exhaustive. It does not expose credentials, raw payloads, raw Graph URLs, query strings, authorization headers, provider item IDs or provider set IDs.

## Permissions

```text
fb_marketing_catalog_view.read
fb_marketing_catalog_sync_run.read
fb_marketing_catalog_mapping_manage.update
```

## Explicitly deferred

```text
catalog feed upload
catalog item create/update/delete
product-set mutation
campaign-product assignment
variant-level feed expansion
browser Pixel events
Conversions API events
ERP order attribution
product profitability
campaign publish or operational actions
```

## Validation performed in the partial source checkout

```text
PHP syntax lint for changed and newly added PHP files
Route and permission wiring inspection
Safe-projection inspection for provider-key exclusion
Public diagnostic artifact absence check
Source diff review and compact-patch manifest generation
```

The uploaded source is a partial checkout without `artisan`, Composer `vendor`, `scripts/fbm-security-gate.sh` or `PATCH_DELETE_MANIFEST.txt`. Framework boot, route-list, migration execution and repository security-gate execution must run in a complete deployment checkout. The intentionally retained local unsafe-maintenance-route override must be restored to the fail-closed expression manually before release.

# FBM-17 - Product-level Sales, Profit and Stock Risk

Completed in the packaged source on 2026-06-11.

## Goal

FBM-17 adds a local product-level reporting page over FBM-15 attributed order item snapshots. The page explains attributed product quantity, ERP revenue, estimated purchase cost, gross profit, margin, returns/cancellations and stock risk without calling Meta during report render.

## User-facing page

```text
GET /fb-marketing/product-performance
```

The page includes:

```text
Product count
Attributed order count
Confirmed attributed quantity sold
Confirmed attributed revenue
Estimated purchase cost
Gross profit
Gross margin
Risk product count
Stock risk breakdown
Product-level performance table
Date, Product, Stock risk and Catalog status filters
```

## Reporting boundary

The report uses tenant-local tables only:

```text
fbm_order_attributions
fbm_order_attribution_items
products
product_order_products        # optional cost source
fbm_catalog_product_mappings  # optional catalog status source
```

No external Meta API call is made while rendering the report. Raw order references, landing evidence, browser identifiers, customer values, Pixel IDs, provider IDs, event IDs, encrypted ciphertext and HMAC values are not rendered.

## Calculation sources

Confirmed product sales are counted only when:

```text
fbm_order_attributions.evidence_state = attributed
fbm_order_attributions.lifecycle_state_current = confirmed
```

Product quantity and revenue come from `fbm_order_attribution_items.quantity_snapshot` and `line_total_snapshot`.

Estimated purchase cost is resolved in this order:

```text
product_order_products.purchase_price * attributed quantity
products.purchase_price * attributed quantity
missing cost -> 0 with warning
```

Gross profit and margin:

```text
gross profit = confirmed attributed revenue - estimated purchase cost
gross margin percent = gross profit / confirmed attributed revenue * 100
```

Returns and cancellations are shown from attributed item snapshots whose latest normalized lifecycle state is `returned` or `cancelled`.

## Stock risk labels

```text
healthy
low_stock
out_of_stock
oversold_risk
catalog_unmapped
missing_cost
no_sales
```

Parent product stock is read from `products.current_stock` when available, otherwise `products.stock`. Low-stock threshold is read from `products.low_stock` when available, otherwise `fb_marketing.product_performance.low_stock_threshold`.

Variant-level stock allocation remains deferred; this stage does not silently claim variant-granular stock risk.

## Permission

```text
fb_marketing_product_performance_view.read
```

The sidebar link and backend route both require this permission.

## Manual Verification Checklist

Use this checklist after deploying FBM-17:

```text
[ ] Apply all tenant migrations through FBM-15 before opening the report.
[ ] Run php artisan route:list --path=fb-marketing/product-performance in a complete deployment checkout.
[ ] Grant fb_marketing_product_performance_view.read to one test role.
[ ] Confirm the FB MARKETING sidebar shows Product Performance for that role.
[ ] Remove fb_marketing_product_performance_view.read and confirm the page is forbidden and hidden from navigation.
[ ] Open /fb-marketing/product-performance with no filters and confirm the page renders without Meta API activity.
[ ] Set Date from and Date to, submit, and confirm the query string preserves the selected range.
[ ] Filter by Product using the local product dropdown and confirm rows narrow safely.
[ ] Try a URL with an arbitrary product_id from another tenant or a non-existing ID and confirm no data is exposed.
[ ] Filter by stock risk: healthy, low_stock, out_of_stock, oversold_risk, catalog_unmapped, missing_cost and no_sales.
[ ] Filter by catalog status: mapped, unmapped, ambiguous and unavailable.
[ ] Confirm product revenue equals summed confirmed attributed item line_total_snapshot for the selected range.
[ ] Confirm purchase cost uses product_order_products.purchase_price when available, otherwise products.purchase_price.
[ ] Confirm gross profit = revenue - purchase cost.
[ ] Confirm gross margin = gross profit / revenue * 100 and zero revenue does not crash.
[ ] Confirm returned and cancelled quantities/revenue appear separately and do not inflate confirmed sales.
[ ] Confirm out-of-stock or low-stock products with attributed sales are flagged.
[ ] Confirm products without catalog mapping are flagged as catalog_unmapped.
[ ] Confirm products without cost data are flagged/warned as missing_cost.
[ ] Confirm raw order references, URLs, browser identifiers, event IDs, customer values and provider IDs are not visible in page HTML.
[ ] Confirm schema-missing or empty-data tenants show warnings instead of a crash.
```

## Notes and limitations

This stage reports gross product economics only. Campaign cost allocation, ad-adjusted contribution profit and finance-grade profitability remain deferred to FBM-18. Product-level export generation remains deferred to FBM-20.

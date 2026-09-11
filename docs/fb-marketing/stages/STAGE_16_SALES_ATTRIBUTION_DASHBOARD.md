# FBM-16 - Sales Attribution Dashboard

Completed in the packaged source on 2026-06-11.

## Goal

FBM-16 adds a local reporting layer over FBM-15 order-attribution snapshots and FBM-08 Ads Insights snapshots. The page compares ad spend with ERP-attributed sales without calling Meta during page render and without adding any provider write operation.

## User-facing page

```text
GET /fb-marketing/attribution-reports
POST /fb-marketing/attribution-reports/reconcile
```

The page includes:

```text
Ad spend
Attributed ERP order count
Attributed sales revenue
Average order value
Cost per attributed order
ROAS
Revenue minus ad spend
Reconciliation status
Data freshness
Campaign, ad set and ad performance rows
Attribution status breakdown
Attribution method breakdown
Date, Campaign, Ad Set, Ad, status and method filters
```

## Reporting boundary

The report uses tenant-local tables only:

```text
fbm_insight_daily_snapshots
fbm_campaigns
fbm_ad_sets
fbm_ads
fbm_order_attributions
fbm_attribution_reconciliations
```

No external Meta API call is made while rendering the report. Spend is read from stored ad-level Insights snapshots. ERP revenue is read from immutable FBM-15 order snapshots plus append-only amount-adjustment reconciliation rows.

The report does not expose raw order references, landing URLs, UTM values, browser identifiers, Pixel IDs, event IDs, customer values, provider sync keys, encrypted ciphertext or HMAC values.

## Metrics

```text
ROAS = attributed revenue / ad spend
Cost per attributed order = ad spend / attributed order count
Average order value = attributed revenue / attributed order count
Revenue minus spend = attributed revenue - ad spend
```

All divisions are zero-safe. Money values are rounded for reporting display.

## Permissions

```text
fb_marketing_attribution_reports_view.read
fb_marketing_attribution_reports_reconcile.update
```

The sidebar link is permission-aware. The manual reconciliation button is hidden without the contextual reconcile permission and the backend route is protected by the same permission.

## Manual reconciliation

The report page reuses the existing FBM-15 bounded recent-order reconciliation service. It remains idempotent through the tenant-local `(source_order_type, source_order_id)` unique boundary and does not create duplicate attribution snapshots.

## Manual Verification Checklist

Use this checklist after deploying FBM-16:

```text
[ ] Apply all tenant migrations through FBM-15 before opening the report.
[ ] Run php artisan route:list --path=fb-marketing/attribution-reports in a complete deployment checkout.
[ ] Grant fb_marketing_attribution_reports_view.read to one test role.
[ ] Confirm the FB MARKETING sidebar shows Attribution Reports for that role.
[ ] Remove fb_marketing_attribution_reports_view.read and confirm the page is forbidden and hidden from navigation.
[ ] Grant fb_marketing_attribution_reports_reconcile.update only to the operator role.
[ ] Confirm the Reconcile recent button is visible only to the operator role.
[ ] Open /fb-marketing/attribution-reports with no filters and confirm the page renders without Meta API activity.
[ ] Set Date from and Date to, submit, and confirm the query string preserves the selected range.
[ ] Filter by Campaign, Ad set and Ad using locally mirrored options and confirm rows narrow safely.
[ ] Try a URL with an arbitrary campaign_id from another tenant or a non-existing ID and confirm no data is exposed.
[ ] Filter by attribution status: attributed, missing_session, invalid_session and expired_session.
[ ] Filter by attribution method and confirm breakdown/table totals update.
[ ] Confirm formulas: ROAS = revenue / spend, CPA = spend / orders, AOV = revenue / orders.
[ ] Confirm zero spend or zero order ranges render 0 instead of an error.
[ ] Confirm Reconciliation summary shows total considered, attributed, unattributed, unresolved and latest reconciliation.
[ ] Click Reconcile recent once and confirm a success flash with bridged/warning counts.
[ ] Click Reconcile recent again and confirm duplicate attribution rows are not created.
[ ] Confirm raw URLs, UTM values, fbclid/fbc/fbp, event IDs, customer values, Pixel IDs and provider IDs are not visible in page HTML.
[ ] Confirm Data freshness shows latest stored snapshot, fetched time and watermark values when Insights rows exist.
[ ] Confirm schema-missing or empty-data tenants show warnings instead of a crash.
```

## Notes and limitations

Attribution-to-campaign mapping is derived from safe server-side decoding of the encrypted FBM-15 evidence snapshot and matched against local campaign hierarchy names or local IDs. Raw evidence values are never rendered. Orders that cannot be matched to a local campaign/ad set/ad remain visible as unmatched attribution rows with zero spend.

Product-level sales, profit and stock risk remain deferred to FBM-17.

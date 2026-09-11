# FBM-18 - Campaign Cost and Profitability Analysis

Completed in the packaged source on 2026-06-11.

## Goal

FBM-18 adds a local profitability page over stored FBM-08 ad-level Insights snapshots, FBM-15 attributed ERP sales and FBM-17-style ERP product cost resolution. It also adds approved local campaign cost adjustments for finance-only costs such as agency fees, creative costs, VAT, tax and service charges.

## User-facing page

```text
GET /fb-marketing/profitability
POST /fb-marketing/profitability/cost-adjustments
```

The page includes:

```text
Meta spend
Local cost adjustments
Total marketing cost
Attributed revenue
Purchase cost
ERP contribution profit
Ad-adjusted contribution profit
Break-even revenue and gap
ROAS and cost per attributed order
Profit state breakdown
Campaign, ad set and ad profitability table
Approved local adjustment table
Date, Campaign, Ad set, Ad and Profit-state filters
```

## Reporting boundary

The report uses tenant-local tables only:

```text
fbm_insight_daily_snapshots
fbm_order_attributions
fbm_order_attribution_items
fbm_attribution_reconciliations
fbm_campaigns
fbm_ad_sets
fbm_ads
products
product_order_products          # optional cost source
fbm_campaign_cost_adjustments   # FBM-18 local finance adjustments
```

No external Meta API call is made while rendering the report or saving local adjustments. Raw order references, landing evidence, browser identifiers, customer values, Pixel IDs, provider IDs, event IDs, encrypted ciphertext and HMAC values are not rendered.

## Formula contract

```text
Meta Ad Spend = SUM stored ad-level Insights spend
ERP Attributed Sales = confirmed attributed order total plus amount_adjusted reconciliations
ERP Purchase Cost = attributed item cost resolved from ERP source rows
ERP Contribution Profit = ERP Attributed Sales - ERP Purchase Cost
Local Campaign Cost Adjustments = SUM approved local adjustment total_amount
Ad-adjusted Contribution Profit = ERP Contribution Profit - Meta Ad Spend - Local Campaign Cost Adjustments
Break-even Revenue = ERP Purchase Cost + Meta Ad Spend + Local Campaign Cost Adjustments
Break-even Gap = ERP Attributed Sales - Break-even Revenue
```

Local adjustment totals include:

```text
base_amount
vat_amount
tax_amount
service_charge_amount
```

Only `approved` adjustments affect report totals. Pending and void rows are retained locally but excluded from profitability totals.

## Permission

```text
fb_marketing_profitability_view.read
fb_marketing_profitability_adjustment_manage.create
```

The sidebar link and report route require the view permission. Adjustment creation additionally requires the manage permission.

## Manual Verification Checklist

Use this checklist after deploying FBM-18:

```text
[ ] Apply all tenant migrations through FBM-18 before opening the report.
[ ] Run php artisan route:list --path=fb-marketing/profitability in a complete deployment checkout.
[ ] Grant fb_marketing_profitability_view.read to one test role.
[ ] Confirm the FB MARKETING sidebar shows Profitability for that role.
[ ] Remove fb_marketing_profitability_view.read and confirm the page is forbidden and hidden from navigation.
[ ] Grant fb_marketing_profitability_adjustment_manage.create only to a trusted finance test role.
[ ] Open /fb-marketing/profitability with no filters and confirm the page renders without Meta API activity.
[ ] Set Date from and Date to, submit, and confirm the query string preserves the selected range.
[ ] Filter by Campaign, Ad set and Ad using local dropdowns and confirm rows narrow safely.
[ ] Try arbitrary campaign_id, ad_set_id or ad_id values from the URL and confirm no cross-tenant data is exposed.
[ ] Add an approved adjustment with base, VAT, tax and service-charge amounts and confirm Local adjustments and Marketing cost update.
[ ] Add a pending adjustment and confirm it is retained but excluded from report totals.
[ ] Confirm Meta spend equals summed ad-level fbm_insight_daily_snapshots.spend for the selected range.
[ ] Confirm ERP attributed sales equals confirmed attributed order snapshots plus amount_adjusted reconciliations.
[ ] Confirm purchase cost uses product_order_products.purchase_price when available, otherwise products.purchase_price.
[ ] Confirm ERP contribution profit = attributed revenue - purchase cost.
[ ] Confirm ad-adjusted contribution profit = ERP contribution profit - Meta spend - approved local adjustments.
[ ] Confirm break-even gap = attributed revenue - break-even revenue.
[ ] Confirm missing product cost creates a warning and does not crash.
[ ] Confirm raw provider IDs, URLs, browser identifiers, event IDs, customer values and ciphertext are not visible in page HTML.
[ ] Confirm schema-missing or empty-data tenants show warnings instead of a crash.
```

## Notes and limitations

This stage reports finance-grade local profitability projections from stored source rows. It does not export reports, mutate Meta campaigns, change budgets, create boosting jobs or allocate product-level spend. Exports remain deferred to FBM-20, and controlled operational actions remain deferred to later stages.

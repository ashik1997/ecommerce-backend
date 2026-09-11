# FBM-09 — Executive Dashboard V1

Completed: 2026-06-09

## Scope

FBM-09 adds a tenant-local executive dashboard over FBM-08 stored Ads Insights snapshots. Dashboard rendering remains read-only and does not issue Meta Graph requests. The stage adds no tenant migration, queue job or provider write path.

## Implemented contract

```text
GET /fb-marketing/dashboard
→ validate bounded date, account and comparison filters
→ resolve selected and available tenant-local Ad Accounts only
→ read insight_level=account daily snapshots only
→ aggregate delivery totals and currency-safe financial groups
→ derive CTR, CPC and CPM from aggregate numerators and denominators
→ show previous equal-length period comparisons only when both ranges have complete selected account-day coverage
→ show account-day coverage, freshness watermark and latest safe report state
```

## Dashboard sections

```text
Executive filter bar
Delivery KPI cards
Currency-separated financial KPI cards
Summed-daily-reach explanation
Freshness and selected account-day coverage panel
Dependency-free daily trend bars
Daily stored-snapshot table
Existing operational snapshot, asset and hierarchy readiness panels
```

## Safety decisions

### No hierarchy-level double counting

FBM-08 persists account, campaign, adset and ad rows. FBM-09 executive totals deliberately query only:

```text
insight_level = account
```

Campaign, adset and ad totals remain available for FBM-10 drilldowns but are not mixed into executive totals.

### Currency separation

Financial totals are grouped by stored account currency. Different currencies are never silently summed. When multiple currency groups exist, the dashboard displays a warning and separate cards.

### Reach label

Daily reach snapshots cannot reconstruct exact unique multi-day reach. The dashboard labels the metric:

```text
Summed daily reach
```

and explains that repeat users may be counted across days.

### Tenant-local account filters

The browser submits a local Ad Account ID only. The service intersects the requested ID with tenant-local rows where:

```text
is_selected = true
is_available = true
```

A foreign, stale or unselected local ID yields no reporting rows and a safe warning.

### Safe projections only

Dashboard view data excludes credentials, encrypted vault values, provider IDs, async report keys, raw Graph payloads, raw URLs and query strings.

## Files

```text
app/Http/Requests/Backend/FbMarketing/FbMarketingDashboardFilterRequest.php
app/Services/FbMarketing/FbmExecutiveDashboardService.php
app/Http/Controllers/Backend/FbMarketing/FbMarketingDashboardController.php
resources/views/backend/fb-marketing/_executive-dashboard.blade.php
resources/views/backend/fb-marketing/dashboard.blade.php
resources/views/backend/fb-marketing/_status-card.blade.php
resources/views/backend/fb-marketing/setup-wizard.blade.php
resources/views/backend/fb-marketing/user-manual.blade.php
docs/fb-marketing/REPORTING_DEFINITIONS.md
FB_MARKETING_STAGE_TRACKER.md
```

## Deployment-side smoke test

The uploaded compact package does not include `artisan` or Composer dependencies, and tenant activation is domain-based. Apply the patch to a deployment checkout and run tenant-domain smoke tests:

```text
Open /fb-marketing/dashboard through a mapped tenant domain.
Confirm a user without fb_marketing_dashboard_view.read is denied.
Confirm no-snapshot tenants receive a safe empty state.
Select one Ad Account and compare a 7-day dashboard total to account-level snapshot rows.
Enable previous-period comparison and verify the immediately preceding equal-length window.
Select same-currency accounts and verify combined financial totals.
Select different-currency accounts and verify separate cards plus the visible warning.
Remove one account-day row in a controlled test tenant and verify incomplete coverage warning.
Repeat Insights sync and verify executive totals remain idempotent.
Submit another tenant's local-looking account ID and verify no data is exposed.
```

## Deferred to FBM-10+

```text
Campaign, adset and ad drilldowns
Performance worklists
ERP order attribution
Actual ecommerce sales and profit
Meta ROAS reconciliation with ERP-attributed ROAS
Exports
Campaign write actions
```

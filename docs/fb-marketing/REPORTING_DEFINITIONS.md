# FB MARKETING — Reporting Definitions

Last updated: 2026-06-10

## Revenue meanings

| Metric | Meaning |
| --- | --- |
| Meta Reported Revenue | Revenue reported by Meta for the selected insight window and attribution setting. |
| ERP Attributed Sales | ERP order revenue linked through captured attribution evidence and the immutable order-attribution bridge. |
| Campaign Window Sales | ERP sales during the campaign window, whether attributed to Meta or not. |
| Comparable-period Sales Uplift | Campaign-window sales minus sales from the comparable previous window. |

## Core formulas

```text
ROAS = ERP Attributed Sales / Meta Ad Spend

Meta ROAS = Meta Reported Revenue / Meta Ad Spend

Campaign-window ROAS = Campaign Window Sales / Meta Ad Spend

Ad-adjusted Contribution Profit = ERP Contribution Profit - Meta Ad Spend - Local Campaign Cost Adjustments

Cost per ERP Attributed Order = Meta Ad Spend / ERP Attributed Order Count

Sales Uplift = Campaign Window Sales - Comparable Previous Window Sales
```

## Product-level allocation rule

When Meta does not return product-level spend for a campaign type, a product report must label allocated spend as an estimate and persist the allocation rule used. Estimated cost must not be silently presented as directly reported spend.

## Reconciliation rule

Dashboard totals, drilldown totals and export totals must reconcile to stored snapshot rows for the same tenant, filter set, date range and freshness watermark.

## FBM-08 stored snapshot contract

FBM-08 stores daily Ads Insights snapshots at four report levels: account, campaign, adset and ad. Recent rows may refresh repeatedly because Meta attribution results can change after the first observation. Reports must reconcile to stored rows for the same tenant, date range, level and freshness watermark.

Stored allow-listed delivery metrics include spend, impressions, reach, clicks, inline-link clicks, frequency, CTR, CPC and CPM. Stored allow-listed action maps are limited by centralized configuration. Purchase-oriented metrics are persisted separately as Meta-reported values; they are not ERP-attributed sales.

`Meta Reported Revenue` and `ERP Attributed Sales` remain separate meanings. FBM-09 may visualize snapshot totals, but ERP revenue attribution remains deferred until the attribution bridge stages.


## FBM-09 executive dashboard contract

FBM-09 executive totals read stored rows where `insight_level = account` only. Account, campaign, adset and ad snapshot levels must never be added together in an executive total because that would double count the same delivery activity.

```text
CTR = aggregate clicks / aggregate impressions × 100
CPC = aggregate spend / aggregate clicks
CPM = aggregate spend / aggregate impressions × 1000
```

Derived rates are calculated from aggregate numerators and denominators rather than averaging daily rates.

Financial metrics remain grouped by `account_currency`. Different currencies are never silently combined or converted by FBM-09.

The reach card is labeled `Summed daily reach`. It is the sum of daily snapshots and may include the same person on multiple days. It must not be labeled exact unique range reach.

Freshness coverage uses selected account-days:

```text
Expected account-days = selected available Ad Accounts × selected calendar days
Coverage percent = stored account-level account-days / expected account-days × 100
```

This prevents one account's snapshot row from hiding another selected account's missing day. Previous-period comparison labels remain unavailable unless both the current and previous selected ranges have complete selected account-day coverage.

Meta-reported purchases and purchase value remain Meta-reported snapshot metrics. They are not ERP-attributed orders, ERP-attributed sales or profit.

## FBM-09A manual no-queue refresh boundary

The Configuration page exposes a bounded mapped-tenant-domain fallback for testing. Manual mode performs read-only discovery, selected-account hierarchy refresh and recent `account`-level Insights ingestion inside the authenticated request. It does not dispatch queue jobs or historical async backfill reports.

Default manual Insights scope:

```text
recent window: 7 completed calendar days
selected accounts: maximum 3
Insights levels: account only
maximum direct reports per request: 6
```

Dashboard totals remain governed by the FBM-09 reporting rules. Manual mode changes how recent snapshots are refreshed; it does not change aggregation formulas, currency grouping or selected account-day coverage checks.


## FBM-10 performance drilldown definitions

### Level-safe aggregation

```text
Performance Campaign totals = SUM stored rows WHERE insight_level=campaign
Campaign detail total = SUM stored rows WHERE insight_level=campaign AND local campaign ID matches
Campaign child table = stored rows WHERE insight_level=adset AND local parent campaign ID matches
Ad Set detail total = SUM stored rows WHERE insight_level=adset AND local ad-set ID matches
Ad Set child table = stored rows WHERE insight_level=ad AND local parent ad-set ID matches
Ad detail and daily table = stored rows WHERE insight_level=ad AND local ad ID matches
```

Never add account, campaign, ad-set and ad snapshot rows together. They are alternate reporting grains for the same delivery activity.

### Derived rate metrics

```text
CTR = total clicks / total impressions × 100
CPC = total spend / total clicks
CPM = total spend / total impressions × 1000
```

Rates derive from aggregate numerators and denominators. Do not average per-day or per-entity rates. Performance entity tables are bounded; a truncation warning means filters must be narrowed before the rendered rows are treated as exhaustive.

### Currency boundary

Financial values group by stored account currency. Different currencies remain separate and must not be silently summed or converted.

### Review indicators

FBM-10 worklists are dynamic review prompts derived from stored rows. They are not confirmed optimization conclusions and do not trigger automatic Meta writes. `Meta-reported results` remain provider metrics; they are not ERP-attributed sales, revenue, profit or ROAS.



## FBM-11 feed and catalog diagnostic definitions

### XML feed validity

A selected ERP product is emitted by `/api/facebook-product-feed.xml` only when it is active, has a non-empty title, has a non-empty image and has a positive effective price. The Products & Catalog page uses the same shared projection, so the UI `Feed valid products` total and XML emission rules remain aligned.

### Catalog mapping meanings

| Metric | Meaning |
| --- | --- |
| Automatic mapping | Meta catalog `retailer_id` deterministically equals a local ERP product ID. |
| Manual mapping | A trusted operator saved a tenant-local ERP product override. No Meta write occurred. |
| Unmatched item | The local mirror has no resolved ERP product. |
| Ambiguous item | Duplicate retailer IDs prevent a deterministic automatic match. |
| Unavailable item | A previously mirrored item was absent from a later complete catalog-products family result. |

### Diagnostic warning boundary

Warnings are safe review indicators, not automatic remediation actions:

```text
inactive
missing_title
missing_image
non_positive_price
zero_stock_but_feed_in_stock
positive_stock_but_feed_out_of_stock
variant_parent_level_export
erp_product_missing
erp_product_inactive
duplicate_retailer_id
provider_availability_mismatch
provider_price_mismatch
```

Variant-level feed expansion is deferred. The current parent-level warning prevents the reporting UI from implying variant-granular catalog fidelity. Stock and variant warning totals use a configurable bounded scan; the UI must display a truncation warning whenever the scan is not exhaustive.

## FBM-15 ERP attribution reporting boundary

### Immutable attributed-order grain

```text
one attribution snapshot = one tenant-local (source_order_type, source_order_id)
```

The immutable snapshot captures the order amount, line items, evidence state and normalized lifecycle state at bridge creation. Later ERP amount and lifecycle observations append reconciliation rows. Reports must not silently rewrite the origin snapshot.

### Evidence meanings

| Evidence state | Meaning |
| --- | --- |
| `attributed` | A valid, non-expired FBM-12 visitor attribution session existed at immutable capture time. |
| `missing_session` | Checkout contained no attribution-session UUID. |
| `invalid_session` | The submitted UUID was malformed or not found tenant-locally. |
| `expired_session` | A matching tenant-local session existed but was expired. |

Only `attributed` snapshots can automatically create a production CAPI Purchase candidate.

### Lifecycle meanings

| Normalized lifecycle | Meaning |
| --- | --- |
| `pending` | ERP order exists but has not reached a recognized confirmed state. |
| `confirmed` | ERP status reached a recognized accepted, processing, invoiced, shipped or delivered family state. |
| `cancelled` | ERP order reached a recognized cancel or reject state. |
| `returned` | ERP return evidence or recognized return status exists. |
| `unknown` | Deployed ERP status value is not recognized and requires operator review. |

The existing source contains a pre-existing product-order status-schema drift. FBM-15 records `unknown` safely instead of making a destructive schema assumption. FBM-16 and later profitability reports must distinguish origin snapshot values, current lifecycle state and append-only amount adjustments.

## FBM-18 profitability definitions

Profitability reports read tenant-local rows only:

```text
Meta Ad Spend = SUM stored ad-level fbm_insight_daily_snapshots.spend
ERP Attributed Sales = SUM confirmed attributed order snapshot total plus append-only amount adjustments
ERP Purchase Cost = SUM attributed item cost from product_order_products purchase_price, then products purchase_price, else zero with warning
Local Campaign Cost Adjustments = SUM approved fbm_campaign_cost_adjustments.total_amount
ERP Contribution Profit = ERP Attributed Sales - ERP Purchase Cost
Ad-adjusted Contribution Profit = ERP Contribution Profit - Meta Ad Spend - Local Campaign Cost Adjustments
Break-even Revenue = ERP Purchase Cost + Meta Ad Spend + Local Campaign Cost Adjustments
Break-even Gap = ERP Attributed Sales - Break-even Revenue
```

Manual cost adjustments are tenant-local finance rows. They are not Meta spend, do not mutate campaigns, and must not be silently mixed with provider-reported delivery metrics without the `Local adjustments` label.

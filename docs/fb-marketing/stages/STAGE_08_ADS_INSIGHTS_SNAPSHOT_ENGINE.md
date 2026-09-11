# FBM-08 — Ads Insights Snapshot Engine

Completed: 2026-06-09

## Scope

FBM-08 extends the dedicated tenant-safe FB MARKETING worker with daily Ads Insights snapshots for selected and available Ad Accounts. It preserves the existing read-only boundary and defers KPI visualization to FBM-09.

## Implemented

```text
Guarded tenant migration: fbm_insight_report_runs and fbm_insight_daily_snapshots
Idempotent daily upsert key: connection + Ad Account + level + internal entity key + snapshot date
Levels: account, campaign, adset and ad
Recent rolling refresh: bounded direct GET pagination
Historical initial backfill: queued Meta async report creation and bounded polling
Queue payload: tenant registry reference plus local report UUID only
Operational UI: safe snapshot count, report count, latest date and freshness watermark
Baseline repair: public diagnostics removed, deletion manifest restored, security gate restored
FBM-07 repair: Graph edge-result operation key propagated safely
```

## Security rules

```text
No Meta Ads mutation endpoint is introduced.
No raw Graph response is persisted.
No raw Graph URL, query string or authorization header is logged.
No access token, app secret or tenant password enters a queue payload.
No provider entity ID or provider async report key enters browser-safe summaries.
Action metrics are allow-listed centrally before persistence.
Every recent report is page- and row-bounded.
Every historical report chunk and poll lifecycle is bounded.
```

## Deferred

```text
Executive KPI cards and charts
Performance drilldowns and worklists
ERP order attribution
ERP-based ROAS and profitability
Product-level spend allocation
Campaign publish or operational mutation
Pixel, CAPI and webhook writes
```

## Operator actions

```text
Apply PATCH_DELETE_MANIFEST.txt removals to deployment checkouts.
Run the approved tenant-aware migration process.
Run bash scripts/fbm-security-gate.sh.
Start the dedicated fb-marketing worker.
Run one controlled full read-only sync with one selected Ad Account.
Verify repeated recent refreshes update rows without duplicates.
Review async backfill warnings before expanding scope.
```

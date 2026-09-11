# FBM-10 — Performance Drilldowns and Worklists

Completed in the compact patch on 2026-06-10.

## Scope

FBM-10 adds a tenant-local stored-snapshot reporting surface for Campaign → Ad Set → Ad analysis. It does not add a provider write path, a tenant migration or a browser-time Meta request.

## Reporting boundary

```text
Performance overview totals use insight_level=campaign only.
Campaign detail totals use insight_level=campaign only.
Campaign child rows use insight_level=adset only.
Ad Set detail totals use insight_level=adset only.
Ad Set child rows use insight_level=ad only.
Ad detail totals and daily rows use insight_level=ad only.
```

Account, campaign, ad-set and ad snapshot rows are never summed together. Financial totals remain grouped by stored account currency; no silent currency conversion or cross-currency sum is allowed. Local mirror tables are bounded to a configurable maximum of 250 matching entities per table by default; the UI warns operators to narrow filters when that cap is reached.

## New reporting surface

```text
GET /fb-marketing/performance
GET /fb-marketing/performance/campaigns/{campaign}
GET /fb-marketing/performance/ad-sets/{adSet}
GET /fb-marketing/performance/ads/{ad}
```

Every route requires `fb_marketing_performance_view.read` in addition to the existing module-access middleware. Page rendering reads tenant-local mirrors and snapshots only.

## Review worklists

Dynamic operator-review indicators are derived from the currently filtered stored-snapshot rows:

```text
missing snapshots
incomplete coverage
stale snapshots
spend without clicks
spend without Meta-reported results
impressions without inline-link clicks
CTR below the configured review threshold
```

These indicators are review prompts only. They do not publish, pause, resume, change budget or otherwise mutate Meta assets.

## Separate bounded manual drilldown refresh

```text
POST /fb-marketing/configuration/connections/{connection}/refresh-drilldowns-now
```

This request-bound testing fallback is separate from the existing account-level manual sync. By default it:

```text
skips asset discovery
refreshes the hierarchy for at most one deterministically selected local Ad Account
requests campaign, adset and ad Insights levels only
stores a maximum recent three completed days
runs a maximum of three direct report windows
uses the shared tenant-connection full-read-only lock
dispatches no Laravel worker job
dispatches no historical asynchronous report
performs no Meta write request
```

Production-scale refresh and historical backfill remain on the dedicated FB MARKETING queue worker path.

## Safe diagnostics

The Performance health panel exposes safe projections only:

```text
latest safe sync state
latest safe Insights-report state
operation key
HTTP status
provider error code and subcode
duration
redacted message
```

It excludes tokens, app secrets, provider IDs, internal fingerprints, raw URLs, query strings, authorization headers, async provider report keys and raw Graph payloads.

## No migration

FBM-10 reuses:

```text
fbm_campaigns
fbm_ad_sets
fbm_ads
fbm_insight_daily_snapshots
fbm_insight_report_runs
fbm_sync_runs
fbm_api_request_logs
```

## Packaging repair

The uploaded source ZIP omitted `scripts/fbm-security-gate.sh` and `PATCH_DELETE_MANIFEST.txt`. FBM-10 restores both. The target checkout still intentionally contains a temporary hardcoded unsafe local maintenance-route override requested for manual testing. The restored gate fails by default until that override is manually reverted to the fail-closed local-environment plus explicit-config expression before release.

For non-release verification while the temporary override is intentionally present:

```bash
FBM_ACKNOWLEDGE_LOCAL_UNSAFE_OVERRIDE=1 bash scripts/fbm-security-gate.sh
```

Do not use the acknowledgement flag as a production-release bypass.

## Operator verification

```text
Apply PATCH_DELETE_MANIFEST.txt deletions on the target checkout.
Apply this compact patch through a mapped tenant domain deployment.
Grant fb_marketing_performance_view.read only to intended reporting users.
Run the existing account-level manual action for dashboard snapshots where needed.
Run Refresh drilldown snapshots now (no queue) for one selected Ad Account.
Open Performance and verify Campaign → Ad Set → Ad navigation.
Confirm each report shows stored-snapshot freshness and coverage warnings.
Confirm mixed currencies render as separate groups.
Confirm worklist items are read-only prompts.
Restore routes/web.php fail-closed maintenance-route expression before release.
Run bash scripts/fbm-security-gate.sh without acknowledgement before release.
```

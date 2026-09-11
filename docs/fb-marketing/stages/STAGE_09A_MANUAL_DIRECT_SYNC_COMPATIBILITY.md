# FBM-09A — Manual Direct Sync Compatibility

## Purpose

FBM-09A adds an explicit tenant-domain testing fallback for environments where the dedicated Laravel queue storage, landlord-registry worker boot or Supervisor process is not yet available.

The fallback is intentionally separate from the existing background queue path. It does not replace the production queue architecture.

## Operator action

From a mapped tenant domain:

```text
FB MARKETING → Configuration
→ Run a read-only connection health test
→ Run manual sync now (no queue)
```

The first run may perform asset discovery only when no Ad Account has been selected yet. After selecting an intended Ad Account, run the same action again to refresh campaign hierarchy mirrors and recent dashboard snapshots.

## Request-bound execution boundary

The manual fallback runs synchronously inside the authenticated tenant-domain HTTP request:

```text
Tenant-local connection
→ bounded asset discovery
→ selected-account campaign hierarchy refresh
→ bounded recent account-level Ads Insights refresh
→ tenant-local ledgers and dashboard snapshots
```

It deliberately does **not**:

```text
read central queue readiness
insert Laravel queue jobs
dispatch RunFbmSyncJob
create or poll historical async Insights backfill jobs
serialize tenant credentials or tenant database passwords
perform campaign writes, budget changes or publish actions
```

## Bounded defaults

The fallback is configured in `config/fb_marketing.php`:

```php
'manual_sync' => [
    'enabled' => (bool) env('FBM_MANUAL_SYNC_ENABLED', true),
    'rate_limit_per_minute' => 1,
    'recent_days' => 7,
    'max_selected_ad_accounts' => 3,
    'insights_levels' => ['account'],
    'max_direct_reports_per_run' => 6,
],
```

These limits keep the browser request suitable for testing and small controlled refreshes. Production-scale history still belongs to the dedicated worker path.

## Concurrency and tenant safety

Manual and queued sync executions share the same tenant-connection cache lock. A manual request skips safely when another read-only sync currently owns that lock.

The manual ledger uses:

```text
sync_scope   = manual_direct_read_only
trigger_type = manual_direct
```

Credentials stay in the encrypted tenant vault. Browser output includes only redacted ledgers and allow-listed snapshot metrics.

## Schema impact

No migration is required. Existing FBM-06 through FBM-08 tenant tables are reused.

## Acceptance checklist

```text
Manual button is visible without queue readiness when the connection is active and health-tested
Manual execution does not call dispatch(...)
Manual execution creates a tenant-local sync ledger row
First run can discover assets before Ad Account selection
Second run can refresh selected-account hierarchy and recent dashboard snapshots
Recent manual Insights use account level only
No historical async report job is queued
Queued full sync remains available when FBM-06 readiness passes
Manual and queued provider calls cannot overlap for the same tenant connection lock
No credential, raw payload, provider URL or async report key appears in browser-safe output
```

# FBM-06 — Queue, Scheduler and Sync Run Infrastructure

Completed: 2026-06-09

## Scope

FBM-06 introduces the tenant-safe asynchronous execution boundary used by later FB MARKETING sync stages. It moves FBM-04 read-only Meta asset discovery out of the web request and into a dedicated central queue while keeping existing stable ERP queue behavior unchanged.

## Central queue contract

```text
Queue connection: fb-marketing
Queue name: fb-marketing
Default queue storage DB connection: landlord
Existing QUEUE_CONNECTION default: unchanged
Scheduler provider calls: forbidden
Job payload secrets: forbidden
```

The job payload stores only a landlord tenant registry reference and a sync-run UUID. It does not serialize Meta tokens, app secrets, CAPI secrets, webhook verification secrets, database credentials, FTP credentials, raw URLs, query strings or raw Graph payloads.

## Tenant-local schema

The tenant-aware migration `2026_06_09_000005_create_fbm_sync_infrastructure_tables.php` adds:

```text
fbm_sync_runs
fbm_api_request_logs
fbm_module_settings.scheduled_sync_enabled
fbm_module_settings.scheduled_sync_interval_minutes
fbm_module_settings.last_scheduled_sync_dispatched_at
```

Scheduled sync defaults to disabled.

## Worker context boundary

`FbmTenantContextService` resolves active tenant rows from the landlord registry, applies the runtime tenant `mysql` configuration, reconnects for the queued work and restores the previous runtime database configuration in a `finally` block. Queue jobs fail closed when registry resolution or schema readiness fails.

## Read-only dispatch behavior

Manual route:

```text
POST /fb-marketing/configuration/connections/{connection}/sync
```

Backward-compatible route:

```text
POST /fb-marketing/configuration/connections/{connection}/discover-assets
```

Both routes enqueue the same `RunFbmSyncJob`; neither performs Meta Graph traversal in the browser request. The initial queue scope is `asset_discovery` only.

## Scheduler behavior

Deployment-level scheduler registration is disabled by default through `FBM_SCHEDULER_ENABLED=false`. When privately enabled, Laravel schedules `fb-marketing:dispatch-scheduled-sync` every minute. The command checks tenant-local settings and enqueues due work only. Provider calls remain worker-only.

## Safe diagnostics

`FbmGraphClient` writes allow-listed Graph request summaries through `FbmApiRequestLogService`. Safe rows may include operation key, Graph version, GET method, page count, HTTP status, duration, provider error code/subcode, HMAC request fingerprint and redacted message. Raw URLs, query strings, authorization headers, provider IDs and raw provider payloads are excluded.

## Operator deployment steps

```bash
# Apply PATCH_DELETE_MANIFEST.txt deletions.
# Apply tenant-aware migrations to every tenant DB.
# Ensure jobs and failed_jobs exist on the configured central queue DB.
php artisan fb-marketing:queue-readiness --tenant=<landlord-registry-id>
php artisan queue:work fb-marketing --queue=fb-marketing
php artisan schedule:list
```

Enable `FBM_SCHEDULER_ENABLED=true` only after readiness passes and the dedicated worker is supervised. Tenant scheduled dispatch remains disabled until enabled in FB MARKETING Configuration.

## Deferred

FBM-06 does not add campaign/ad-set/ad/creative mirror tables, campaign hierarchy sync, insights snapshots, reporting metrics, catalog upload, CAPI sends, webhooks or Meta write actions. FBM-07 extends this queue foundation with campaign hierarchy read-only sync.

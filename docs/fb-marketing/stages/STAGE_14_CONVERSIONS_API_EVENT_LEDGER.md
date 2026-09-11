# FBM-14 — Conversions API Event Ledger

Completed: 2026-06-10

## Goal

Add a production-oriented, tenant-safe server Conversions API boundary without modifying existing checkout flows. The stage establishes an immutable encrypted Purchase snapshot, append-only redacted delivery attempts, explicit dry-run/test/live controls, a tenant-safe queue job, and a manual no-queue testing path.

## Implemented boundary

```text
Tenant-local server mode: disabled | dry_run | test | live
Stable destination: general_infos.fb_pixel_app_id
Encrypted server token: fbm_connections.capi_access_token_ciphertext
Encrypted optional test code: fbm_connections.capi_test_event_code_ciphertext
Durable ledger: fbm_conversion_events
Append-only attempts: fbm_conversion_event_attempts
Queue payload: tenant registry reference + event UUID only
Visible UI: FB MARKETING → Tracking & Attribution
```

Initial allow-listed server event support is `Purchase`. Same tenant destination, event name and event ID deduplicate locally before a provider request.

## Mode guarantees

| Mode | Snapshot | Meta request | Test Events code |
| --- | --- | --- | --- |
| `disabled` | Diagnostic blocked | Never | Never |
| `dry_run` | Yes | Never | Never |
| `test` | Yes | Explicit synthetic test only | Required and attached |
| `live` | Yes | Production snapshot delivery only | Never attached |

Synthetic live diagnostics are intentionally blocked. Test-mode delivery requires an explicit confirmation checkbox and a separately stored encrypted Test Events code.

## Security guarantees

```text
No token, test code or Authorization header in browser output
No destination Pixel ID or raw event ID in ERP screens
No raw provider request or response body in operational ledgers
No raw source URL, customer matching data, _fbc or _fbp in browser-safe projections
No token, Pixel ID, customer data or event body in queued serialization
No import or overwrite of legacy General Information secret fields
```

## Deferred to FBM-15

```text
Confirmed-order hook
guest checkout hook
cart checkout hook
immutable order-attribution snapshot
automatic live Purchase event creation
ERP attributed-sales reporting
order cancellation and return linkage
```

## Deployment verification

```text
Back up tenant databases and APP_KEY.
Apply the guarded tenant-aware FBM-14 migration.
Run php artisan route:list --path=fb-marketing in a full checkout.
Grant the new Tracking & Attribution permissions only to intended operators.
Save a rotated encrypted CAPI token; add a Test Events code only during explicit provider verification.
Keep server mode disabled initially, then run dry_run and verify DRY_RUN_VALIDATED without a Meta request.
Use test mode only intentionally and return to disabled or dry_run afterward.
Use Retry now for mapped-domain no-queue testing; use Queue only after FBM queue readiness passes.
Do not claim ERP-attributed sales before FBM-15 and later reporting stages.
```

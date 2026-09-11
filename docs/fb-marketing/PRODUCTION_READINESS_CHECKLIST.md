# FB MARKETING — Production Readiness Checklist

Last updated: 2026-06-12

## Application database

```text
[ ] Confirm every queued FB MARKETING job serializes its local UUID only.
[ ] Confirm workers use the configured primary application database.
[ ] Confirm application migrations have run for FBM-00 through FBM-29 tables.
[ ] Confirm no FB MARKETING controller changes the configured database connection.
```

## Permissions audit

```text
[ ] Grant fb_marketing_access.read only to intended operators.
[ ] Grant credential, health-test, sync, publish, operational-action, reconcile and export permissions separately.
[ ] Confirm hidden sidebar links are not the authorization boundary; route middleware remains authoritative.
[ ] Confirm demo-mode and auth middleware remain active on backend FB MARKETING routes.
```

## Secret scan

```text
[ ] Run bash scripts/fbm-security-gate.sh.
[ ] Run bash scripts/fbm-production-readiness.sh.
[ ] Confirm public/info.php and public/error_log are absent after deployment.
[ ] Rotate any credential that was previously committed or exposed.
[ ] Keep production APP_DEBUG=false.
[ ] Keep ALLOW_LOCAL_UNSAFE_WEB_MAINTENANCE_ROUTES=false in production.
```

## Migration safety

```text
[ ] Back up tenant databases before applying FB MARKETING migrations.
[ ] Review non-destructive down() behavior for append-only ledgers.
[ ] Apply PATCH_DELETE_MANIFEST.txt to remove old public artifacts from existing hosts.
[ ] Verify setup wizard advanced checklist shows required schema as ready.
```

## Rate limits and provider writes

```text
[ ] Review throttle values in config/fb_marketing.php for mapped tenant domains.
[ ] Confirm campaign_publish.provider_writes_enabled=false before initial release.
[ ] Confirm operational_actions.provider_writes_enabled=false before initial release.
[ ] Confirm provider-writer readiness shows ads_management scope coverage before any later enablement.
[ ] Confirm daily and lifetime safety budget caps match the approved Meta Ads operating policy.
[ ] Confirm default_targeting_countries remains empty unless a written targeting policy is approved.
[ ] Confirm first campaign publish UAT creates Meta campaign hierarchy in PAUSED status only.
[ ] Confirm operational action UAT pauses/resumes only the intended campaign/ad set/ad from hidden local mirrors.
[ ] Confirm ad-set budget/schedule UAT respects approved caps and audit reason policy.
[ ] Confirm FBM-34 rollback and reconciliation summaries are present after publish/action UAT.
[ ] Confirm FBM-35 final provider-writer launch gate is clean before any signed live-write enablement.
[ ] Enable provider writes only through a separate signed-off change and rollback plan.
```

## UAT checklist

```text
[ ] Save a connection without secrets echoing back to the browser.
[ ] Run connection health test and confirm safe ledger row.
[ ] Discover/select assets and run bounded read-only sync.
[ ] Confirm dashboards render stored snapshots without provider calls.
[ ] Create and approve a campaign draft without provider writes.
[ ] Run Lead Ads webhook verification in a Meta test app.
[ ] Run Health & Alerts reconciliation and review open alerts.
[ ] Open Setup Wizard and BN/EN User Manual.
```

## Rollback notes

```text
[ ] Disable FB MARKETING sidebar access by revoking fb_marketing_access.read.
[ ] Disable scheduled sync and stop the dedicated fb-marketing queue worker.
[ ] Keep append-only ledgers for audit unless an explicit data-retention procedure is approved.
[ ] Restore previous config/fb_marketing.php values if a rate-limit or mode change causes operational issues.
```

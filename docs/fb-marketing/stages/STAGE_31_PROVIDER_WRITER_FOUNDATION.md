# FBM-31 — Provider Writer Foundation

## Scope

FBM-31 adds the production gate for future live Meta campaign writes. It evaluates whether encrypted credentials, token health, writer scopes, selected Ad Accounts and publish/action ledgers are ready before FBM-32/33 workers are allowed to execute provider mutations.

This stage does not execute live Meta writes.

## Implemented artifacts

```text
app/Services/FbMarketing/FbmProviderWriterReadinessService.php
config/fb_marketing.php provider_writer policy
app/Services/FbMarketing/FbmCampaignPublishEngineService.php readiness guard
app/Services/FbMarketing/FbmCampaignOperationalActionService.php readiness guard
resources/views/backend/fb-marketing/configuration.blade.php readiness panel
resources/views/backend/fb-marketing/setup-wizard.blade.php FBM-31 checklist status
```

## Readiness rules

The provider writer foundation requires:

```text
encrypted active FB MARKETING connection
configured hidden Meta access token
latest read-only connection health status of healthy or warning
ads_read and ads_management scopes in the latest token-debug metadata
at least one selected and available Ad Account on the same connection
FBM-24 publish ledgers and FBM-25 operational action ledgers
```

## Safety policy

```text
campaign_publish.provider_writes_enabled=false by default
operational_actions.provider_writes_enabled=false by default
max_single_daily_budget_amount=5000
max_single_lifetime_budget_amount=50000
idempotency_ttl_hours=72
max_retry_attempts=3
allowed publish edges are campaign, ad set, creative and ad creation edges only
allowed operational actions are pause, resume, update_budget, update_schedule and safe_edit
```

## Acceptance checks

```text
[x] Dashboard exposes provider-writer readiness without showing secrets or provider IDs.
[x] Campaign publish attempts cannot become ready for worker when writer readiness fails.
[x] Operational actions cannot become ready for worker when writer readiness fails.
[x] Readiness requires ads_management in addition to baseline ads_read.
[x] Provider writes remain disabled by default.
[x] This stage does not execute live Meta writes.
```

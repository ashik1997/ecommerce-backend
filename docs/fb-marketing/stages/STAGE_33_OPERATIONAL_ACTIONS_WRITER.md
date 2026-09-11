# FBM-33 — Operational Actions Writer

## Scope

FBM-33 adds the queue-backed writer for controlled operational actions after a campaign has already been published by FBM-32. It supports pause, resume, budget and schedule updates through the Meta Marketing API when `operational_actions.provider_writes_enabled=true`, FBM-31 readiness passes and the dedicated FB MARKETING queue is ready.

Provider writes remain disabled by default.

## Implemented artifacts

```text
app/Jobs/FbMarketing/RunFbmOperationalActionJob.php
app/Services/FbMarketing/FbmCampaignOperationalActionDispatchService.php
app/Services/FbMarketing/FbmCampaignOperationalActionWorkerService.php
app/Services/FbMarketing/FbmCampaignOperationalProviderClient.php
app/Exceptions/FbMarketing/FbmRetryableOperationalActionException.php
```

## Execution behavior

```text
approved draft -> completed FBM-32 publish attempt -> operational action ledger -> queued provider writer
worker rehydrates tenant context from the landlord tenant registry
target provider object is read from hidden local mirror columns only
pause/resume can target campaign, ad set or ad
budget and schedule updates are limited to ad set targets
successful provider responses update the local campaign/ad set/ad mirror
browser rows show safe statuses, before/after summaries and redacted messages only
```

## Safety rules

```text
operational_actions.provider_writes_enabled=false by default
FBM-31 provider writer readiness must pass
completed FBM-32 publish attempt is required
budget updates enforce max_single_daily_budget_amount and max_single_lifetime_budget_amount
safe_edit is intentionally blocked until a field-level allow-list is approved
raw provider object IDs are never rendered
```

## Acceptance checks

```text
[x] Operational action dispatch happens only after provider writes and FBM-31 readiness pass.
[x] Worker requires a completed publish attempt and local provider mirror IDs.
[x] Pause/resume/status mutations update local mirror status after provider success.
[x] Budget updates are ad-set-only and enforce configured caps.
[x] Schedule updates are ad-set-only.
[x] Provider writes remain disabled by default.
```

# FBM-32 — Campaign Publish Worker

## Scope

FBM-32 adds the queue-backed worker that can publish an approved FBM-23/24 campaign snapshot through the Meta Marketing API after FBM-31 readiness passes and `campaign_publish.provider_writes_enabled=true`.

Provider writes remain disabled by default.

## Implemented artifacts

```text
app/Jobs/FbMarketing/PublishFbmCampaignAttemptJob.php
app/Services/FbMarketing/FbmCampaignPublishDispatchService.php
app/Services/FbMarketing/FbmCampaignPublishWorkerService.php
app/Services/FbMarketing/FbmCampaignPublishProviderClient.php
app/Exceptions/FbMarketing/FbmRetryableCampaignPublishException.php
```

## Execution behavior

```text
approved draft -> immutable publish snapshot -> idempotent attempt -> queued provider worker
worker rehydrates tenant context from the landlord tenant registry
worker executes campaign -> ad set -> creative -> ad
each provider object is created in PAUSED status
raw provider IDs are stored only in hidden local mirror columns
browser rows show local IDs, safe statuses, response HMAC refs and redacted messages only
```

The worker creates provider hierarchy in PAUSED status so an operator can review it in Meta before activation. Retryable provider failures keep a retryable attempt state for the bounded queue retry policy; permanent failures stop safely with a redacted diagnostic.

## Preflight gates

```text
campaign_publish.provider_writes_enabled must be true
FBM-31 writer readiness must pass
dedicated FB MARKETING queue readiness must pass before dispatch
connection, selected Ad Account and selected Page are required
ready creative asset and valid destination URL are required
selected provider audience or configured default targeting countries are required
daily/lifetime budget must stay inside configured safety caps
```

`default_targeting_countries` is empty by default, so broad targeting cannot happen accidentally.

## Acceptance checks

```text
[x] Publish dispatch happens only after provider writes and FBM-31 readiness pass.
[x] Worker payloads use allow-listed campaign/ad set/creative/ad edges.
[x] Campaign hierarchy is created in PAUSED status.
[x] Step ledgers store safe request summaries and redacted responses.
[x] API request ledger accepts campaign publish operation keys.
[x] Provider writes remain disabled by default.
```

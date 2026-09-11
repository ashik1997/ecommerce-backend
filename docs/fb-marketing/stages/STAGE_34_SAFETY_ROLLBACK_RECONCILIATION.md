# FBM-34 - Safety, Rollback and Reconciliation

FBM-34 adds a shared provider-write safety layer for the live write stages. It does not expand the Meta write surface. Instead, it records rollback plans and post-write reconciliation summaries for campaign publish and operational action workers.

## Implemented files

```text
app/Services/FbMarketing/FbmProviderWriteSafetyService.php
app/Services/FbMarketing/FbmCampaignPublishWorkerService.php
app/Services/FbMarketing/FbmCampaignOperationalActionWorkerService.php
config/fb_marketing.php
```

## Safety behavior

Campaign publish rollback is `pause_and_reconcile`. Published campaign hierarchy objects are already created in PAUSED status, so the safe recovery path keeps provider objects paused, retains the immutable attempt/step ledgers and asks operators to run read-only hierarchy sync before retrying or closing a partial attempt.

Operational rollback is `inverse_allow_listed_mutation`. The worker records the inverse payload keys that would restore the audited before-state, but does not execute rollback automatically. An operator must review provider state through read-only sync and submit an approved inverse action if needed.

## Reconciliation behavior

Post-write summaries are stored only in safe JSON ledgers:

```text
fbm_campaign_publish_attempts.safe_response_summary
fbm_campaign_operational_actions.after_state
```

The summaries include local mirror IDs, statuses, last-seen timestamps, payload keys and drift flags. Raw provider IDs remain hidden in model fields and are not rendered to the dashboard.

## Completion gate

```text
[x] Campaign publish completion stores rollback and reconciliation summaries.
[x] Partial publish failure stores a pause-and-reconcile rollback plan.
[x] Operational success stores inverse rollback payload keys and local mirror reconciliation.
[x] Operational failure records that no provider-success rollback is required.
[x] Provider writes remain disabled by default.
```

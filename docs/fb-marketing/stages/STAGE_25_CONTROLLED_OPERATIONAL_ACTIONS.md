# FBM-25 - Controlled Operational Actions

Completed in the packaged source on 2026-06-11.

## Goal

FBM-25 adds a controlled operational action ledger for approved campaign drafts and publish attempts. Operators can request pause, resume, budget update, schedule update or a supported safe edit, while every request is permissioned, idempotent and audited.

## User-facing integration

```text
POST /fb-marketing/campaign-drafts/{draft}/operational-actions
```

The existing Campaign Drafts page now shows:

```text
Operational action form for approved drafts
Action type and target type controls
Budget and schedule change fields
Reason field
Recent operational action history
```

## Local table

```text
fbm_campaign_operational_actions
```

## Permission

```text
fb_marketing_campaign_operational_action_create.create
```

## Supported local action types

```text
pause
resume
update_budget
update_schedule
safe_edit
```

Targets:

```text
campaign
ad_set
ad
```

## Idempotency and audit

Each action stores:

```text
draft and approval version
latest publish attempt reference when available
target type and local target reference when available
safe before_state
safe after_state
actor, reason and timestamps
idempotency key
```

Repeated identical actions reuse the existing ledger row.

## Security and provider-write boundary

Provider writes are disabled by default:

```text
config('fb_marketing.operational_actions.provider_writes_enabled') === false
```

With the default setting, actions are recorded as `blocked_preflight`, and no Meta mutation request is sent. Raw provider IDs, provider sync keys, tokens, raw URLs with query strings, request fingerprints, credentials, ciphertext and HMACs are not exposed.

## Manual Verification Checklist

```text
[ ] Apply all tenant migrations through FBM-25 before using operational actions.
[ ] Run php artisan route:list --path=fb-marketing/campaign-drafts in a complete deployment checkout.
[ ] Grant fb_marketing_campaign_operational_action_create.create only to trusted operators.
[ ] Approve a campaign draft.
[ ] Create a Publish attempt or use an approved draft directly.
[ ] Log pause, resume, budget update and schedule update actions.
[ ] Confirm each row stores safe before_state and after_state.
[ ] Repeat the same action and confirm idempotency reuses the existing action.
[ ] Confirm actions are blocked_preflight while provider_writes_enabled is false.
[ ] Confirm no Meta pause, resume, budget or schedule mutation request occurs.
```

## Notes and limitations

This stage creates the operational action ledger and confirmation boundary. Live provider mutations remain behind an explicit disabled-by-default configuration switch and require a later production-readiness pass before use.

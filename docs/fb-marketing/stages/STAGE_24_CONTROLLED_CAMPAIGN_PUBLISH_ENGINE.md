# FBM-24 - Controlled Campaign Publish Engine

Completed in the packaged source on 2026-06-11.

## Goal

FBM-24 adds the idempotent campaign publish attempt ledger for an approved FBM-23 draft snapshot. Duplicate publish clicks reuse the same attempt instead of creating duplicate publish chains.

## User-facing integration

```text
POST /fb-marketing/campaign-drafts/{draft}/publish
```

The existing Campaign Drafts page now shows:

```text
Publish attempt action for approved drafts
Provider-write safety status
Recent publish attempts
Recent publish steps
```

## Local tables

```text
fbm_campaign_publish_attempts
fbm_campaign_publish_steps
```

## Permission

```text
fb_marketing_campaign_publish_create.create
```

## Idempotency

The publish attempt idempotency key is derived from:

```text
draft ID
approval version
immutable publish snapshot payload hash
application key HMAC
```

Re-clicking Publish attempt for the same approved snapshot reuses the existing attempt and does not create a second campaign chain.

## Security and provider-write boundary

Provider writes are disabled by default:

```text
config('fb_marketing.campaign_publish.provider_writes_enabled') === false
```

With the default setting, the engine creates the attempt and four ordered steps only:

```text
campaign
ad_set
creative
ad
```

All steps are marked `blocked_preflight`, and no Meta request is sent. The step ledgers store safe request summaries only. Raw provider IDs, provider sync keys, tokens, raw URLs with query strings, request fingerprints, credentials, ciphertext and HMACs are not exposed.

## Manual Verification Checklist

```text
[ ] Apply all tenant migrations through FBM-24 before using Publish attempt.
[ ] Run php artisan route:list --path=fb-marketing/campaign-drafts in a complete deployment checkout.
[ ] Grant fb_marketing_campaign_publish_create.create only to trusted operators.
[ ] Approve a campaign draft so it has an immutable publish snapshot.
[ ] Click Publish attempt once and confirm one fbm_campaign_publish_attempts row and four fbm_campaign_publish_steps rows are created.
[ ] Click Publish attempt again and confirm the existing attempt is reused by idempotency key.
[ ] Confirm the steps are blocked_preflight while provider_writes_enabled is false.
[ ] Confirm no Meta campaign, ad set, creative, ad, budget or audience write request occurs.
```

## Notes and limitations

This stage creates the controlled publish ledger and duplicate-click boundary. Live provider POST execution remains behind an explicit disabled-by-default configuration switch and must preserve the same step ledger, response capture and retry semantics when enabled in a later production readiness pass.

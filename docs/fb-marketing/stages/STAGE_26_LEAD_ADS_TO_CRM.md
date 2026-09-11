# FBM-26 - Lead Ads to CRM

Completed in the packaged source on 2026-06-12.

## Goal

FBM-26 adds tenant-local Lead Ads webhook verification, callback ingestion, CRM lead mapping and dedupe.

## User-facing page

```text
GET /fb-marketing/lead-ads
```

The page includes:

```text
Webhook endpoint display
Lead event totals
Mapped-to-CRM count
Pending retrieval count
Duplicate count
Recent lead events
Recent webhook logs
```

## Public API endpoint

```text
GET  /api/fb-marketing/webhooks/lead-ads
POST /api/fb-marketing/webhooks/lead-ads
```

The GET endpoint verifies `hub.mode=subscribe`, `hub.verify_token` and `hub.challenge` against encrypted active FB MARKETING connection webhook verify tokens.

The POST endpoint accepts Lead Ads `leadgen` changes. Payloads containing `field_data` can create one CRM lead. Payloads containing only `leadgen_id` are recorded as `pending_retrieval`.

## Local tables

```text
fbm_lead_ad_events
fbm_lead_ad_webhook_logs
```

CRM target:

```text
crm_leads
```

## Permission

```text
fb_marketing_lead_ads_view.read
```

## Dedupe and CRM mapping

`provider_leadgen_id` is unique in the tenant-local Lead Ads event ledger. A repeated callback for the same leadgen ID does not create another CRM lead.

Mapped CRM leads use:

```text
source = facebook
status = new
priority = normal
```

## Security boundary

Raw Lead Ads payloads are not stored or rendered. The FBM event page exposes safe field keys, mapping flags, statuses and CRM lead IDs only. Request fingerprints are HMACs and are hidden from browser-safe projections.

This stage does not subscribe pages, mutate webhook subscriptions, create Meta forms or call provider write endpoints.

## Manual Verification Checklist

```text
[ ] Apply all tenant migrations through FBM-26 before using Lead Ads.
[ ] Run php artisan route:list --path=fb-marketing/lead-ads in a complete deployment checkout.
[ ] Run php artisan route:list --path=fb-marketing/webhooks/lead-ads in a complete deployment checkout.
[ ] Grant fb_marketing_lead_ads_view.read to intended operators.
[ ] Store a webhook verify token in an active encrypted FB MARKETING connection.
[ ] Call the GET webhook endpoint with hub.mode=subscribe, the matching verify token and a challenge.
[ ] Confirm the challenge is returned only for the matching token.
[ ] POST a test lead payload containing field_data and a stable leadgen_id.
[ ] Confirm exactly one crm_leads row is created with source facebook.
[ ] POST the same leadgen_id again and confirm no second CRM lead is created.
[ ] POST a leadgen_id-only callback and confirm it is marked pending_retrieval.
[ ] Confirm raw payload values, provider IDs and request fingerprints are absent from page HTML.
```

## Notes and limitations

This stage supports inbound webhook verification and test/local field-data mapping. Graph retrieval for leadgen-ID-only callbacks remains a controlled read path for a later production-readiness pass.

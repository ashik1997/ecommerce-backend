# FBM-21 - Creative Library and Publish Preflight Assets

Completed in the packaged source on 2026-06-11.

## Goal

FBM-21 adds a tenant-local creative library for draft assets and deterministic preflight checks before any future publish workflow exists.

## User-facing page

```text
GET /fb-marketing/creative-library
POST /fb-marketing/creative-library/assets
POST /fb-marketing/creative-library/assets/{asset}/preflight
```

The page includes:

```text
Asset type and status filters
Local creative asset entry
Media file ID or external asset URL reference
Landing URL and UTM fields
Preflight status and issue counts
Ready/draft summaries
```

## Local tables

```text
fbm_creative_assets
fbm_creative_asset_variants
fbm_creative_preflight_checks
```

## Preflight checks

```text
title_present
primary_text_present
headline_present
media_or_text_ready
landing_url_valid
primary_text_length
utm_ready
```

Preflight passing marks the asset `ready`. Failed preflight leaves the asset in `draft` and records safe issue metadata.

## Permission

```text
fb_marketing_creative_library_view.read
fb_marketing_creative_asset_manage.create
fb_marketing_creative_preflight_run.create
```

## Security and publishing boundary

This stage does not upload media to Meta, create creatives in Meta, publish ads, mutate campaigns, change budgets, create audiences or call any provider write endpoint. Existing `fbm_creatives` remains the read-only mirrored provider creative table from FBM-07; FBM-21 uses new local draft tables.

Raw provider IDs, tokens, raw URLs with query strings, browser identifiers, event IDs, customer values, ciphertext and HMACs are not exposed.

## Manual Verification Checklist

```text
[ ] Apply all tenant migrations through FBM-21 before opening Creative Library.
[ ] Run php artisan route:list --path=fb-marketing/creative-library in a complete deployment checkout.
[ ] Grant fb_marketing_creative_library_view.read to one test role.
[ ] Confirm the FB MARKETING sidebar shows Creative Library for that role.
[ ] Remove fb_marketing_creative_library_view.read and confirm the page is forbidden and hidden from navigation.
[ ] Grant fb_marketing_creative_asset_manage.create only to intended creative operators.
[ ] Grant fb_marketing_creative_preflight_run.create only to intended operators.
[ ] Create an image creative with title, headline, primary text, media file ID or external asset URL, landing URL and UTM fields.
[ ] Run preflight and confirm passing assets become ready.
[ ] Create an incomplete asset and confirm preflight records safe failed checks.
[ ] Confirm no Meta API request, upload, publish, campaign mutation or budget mutation happens.
[ ] Confirm raw provider IDs, query strings, browser identifiers, event IDs, customer private values and ciphertext are absent from page HTML.
```

## Notes and limitations

This is a local draft and validation layer only. Audience management remains deferred to FBM-22, campaign draft governance to FBM-23 and controlled publishing to FBM-24.

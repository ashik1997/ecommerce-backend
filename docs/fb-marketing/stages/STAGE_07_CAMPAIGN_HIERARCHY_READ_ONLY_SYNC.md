# FBM-07 — Campaign Hierarchy Read-only Sync

Completed: 2026-06-09

## Scope

FBM-07 adds a tenant-local, read-only Campaign → Ad Set → Ad → Creative mirror for locally selected and currently available Ad Accounts.

## Security rules

- Meta provider operations are bounded GET requests only.
- No campaign, ad set, ad, creative, budget, status or audience mutation is introduced.
- Provider IDs are retained only as internal tenant sync keys and hidden from browser-safe projections.
- Raw Graph payloads, query strings, next URLs, tokens and secrets are not persisted.
- Incomplete or truncated edge walks preserve existing unseen rows instead of marking them unavailable.
- Job payloads remain limited to landlord tenant reference and sync-run UUID.

## Added tables

- `fbm_campaigns`
- `fbm_ad_sets`
- `fbm_ads`
- `fbm_creatives`
- `fbm_sync_run_items`

## Sync behavior

The primary queued sync now runs asset discovery first, then campaign hierarchy sync for selected Ad Accounts. The legacy asset-discovery route remains discovery-only.

## Deferred

Insights snapshots, spend, ROAS, attribution, Pixel/CAPI writes, campaign publishing, status changes and budget mutation are deferred to later stages.

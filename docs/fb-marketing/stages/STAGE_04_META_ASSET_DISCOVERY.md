# FBM-04 — Meta Asset Discovery

Completed: 2026-06-09

## Scope

FBM-04 adds an explicit, permission-controlled and read-only tenant asset discovery slice on top of the FBM-02 encrypted vault and FBM-03 connection-health boundary.

Implemented asset families:

```text
Business accounts
Ad accounts
Pages
Pixels
Datasets when the token and provider edge support them
Product catalogs
Page-linked Instagram business accounts
```

## Baseline package repair

Startup verification found a packaged-source regression again:

```text
public/info.php
public/error_log
```

The uploaded package also omitted:

```text
scripts/fbm-security-gate.sh
PATCH_DELETE_MANIFEST.txt
```

FBM-04 removes the two public diagnostic artifacts, restores the executable gate, restores the explicit deletion manifest and adds a `.gitignore` exception so the manifest remains packageable despite the broad `*.txt` rule.

## Tenant schema

The guarded tenant-aware migration adds:

```text
fbm_business_accounts
fbm_ad_accounts
fbm_pages
fbm_pixels
fbm_datasets
fbm_catalogs
fbm_instagram_accounts
fbm_asset_discovery_runs
fbm_asset_selection_audits
```

Provider asset IDs are internal tenant sync keys. Browser projections omit them. Raw Meta payloads are never persisted.

`fbm_asset_discovery_runs` is an append-only safe run ledger. It stores safe counts, successful and warning families, sanitized warning summaries, duration, actor, graph version and hidden non-reversible request fingerprint / IP hash fields.

`fbm_asset_selection_audits` is an append-only local selection ledger. It stores the asset type, internal local record ID, hidden provider-ID HMAC, before/after selected state, actor, optional redacted reason and hidden request-IP HMAC.

## Graph boundary

`App\Services\FbMarketing\FbmGraphClient::paginateEdge()` adds bounded read-only Graph GET traversal:

```text
HTTPS trusted host only
centralized permitted Graph version only
redirects disabled
bounded connection and request timeouts
bounded pages per edge
bounded records per edge
server-side Bearer access token only
cursor reconstruction instead of blindly following provider URLs
explicit allow-listed field projections
```

Raw provider rows exist in memory only long enough for `FbmAssetDiscoveryService` to normalize allow-listed fields.

## Discovery behavior

`App\Services\FbMarketing\FbmAssetDiscoveryService` requires:

```text
active connection
allowed Graph API version
configured encrypted access token
latest FBM-03 health status of healthy or warning
```

Discovery reads direct assets plus business-owned and client/shared edges. Dataset discovery is capability-first. A missing permission or unsupported edge creates a safe partial-result warning without discarding successful families.

Unseen local assets are marked unavailable only after the matching family completes successfully. Failed, skipped or truncated families preserve existing unseen asset availability state.

## Local active-asset selection

`App\Services\FbMarketing\FbmAssetSelectionService` updates local tenant selections only. It does not send Meta writes. Selecting an unavailable asset fails closed. Deselecting a stale asset remains allowed.

Contextual permissions:

```text
fb_marketing_asset_discovery_run.read
fb_marketing_asset_selection_manage.update
```

Routes:

```text
POST  /fb-marketing/configuration/connections/{connection}/discover-assets
PATCH /fb-marketing/assets/{assetType}/{asset}/selection
```

Both remain behind the existing authenticated tenant-aware FB MARKETING route group and contextual permission middleware. Discovery is additionally throttled.

## UI

Configuration now shows:

```text
asset-schema readiness
latest per-connection discovery status
safe discovery summaries
explicit read-only discovery action
append-only discovery ledger
```

Dashboard now shows tenant-safe active-asset readiness and local select/deselect controls. Setup Wizard Step 4 now reports real schema, run, availability and selection readiness.

## Explicitly deferred

```text
Legacy pixel and Facebook-feed consolidation
General queue, scheduler and sync-run infrastructure
Campaign, ad-set, ad and creative sync
Ads Insights snapshots
Attribution and reporting dashboards
Conversions API writes
Webhooks
Campaign create, update, pause or publish operations
```

## Validation

Run:

```bash
bash scripts/fbm-security-gate.sh
find app config database/migrations routes -name '*.php' -print0 | xargs -0 -n1 php -l
```

Deployment checkout operator checks after restoring `artisan` and Composer dependencies:

```bash
php artisan route:list --path=fb-marketing
# Run the approved tenant-aware migration process for every tenant database.
# Run a controlled read-only health test.
# Run a controlled read-only asset discovery attempt.
# Verify local active-asset selection and deselection.
```

## Next exact command

```text
FBM-05 implement করুন: Existing Pixel and Feed Consolidation
```

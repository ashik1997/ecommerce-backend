# FB MARKETING — Security Gate

Last updated: 2026-06-09

## Why this gate exists

FBM credentials and provider calls must not be enabled unless the host ERP has a fail-closed baseline for unsafe browser routes, source secrets and diagnostic leakage.

## Packaged-source verification repairs

During FBM-01 startup verification, the uploaded ZIP contained an unsafe-route regression and two public diagnostic artifacts even though the tracker described them as closed. FBM-01 restored the fail-closed local-only route switch, removed `public/info.php`, removed `public/error_log` and restored the missing `scripts/fbm-security-gate.sh` executable check.

During FBM-02 startup verification, the next uploaded ZIP again contained `public/info.php`, `public/error_log` and omitted `scripts/fbm-security-gate.sh`. FBM-02 removed the artifacts, restored the executable script and corrected `.gitignore` rules that prevented the fail-closed `TenantSeeder`, tracker and stage documentation from being reliably tracked as source files.

During FBM-03 startup verification, the uploaded ZIP again contained:

```text
public/info.php
public/error_log
```

It also omitted `scripts/fbm-security-gate.sh` and reintroduced the `.gitignore` `docs` rule. FBM-03 removes the artifacts, restores the executable gate and removes the `docs` ignore regression again. The patch package includes an explicit deletion manifest for deployment application.

## Packaged-source checks

Run:

```bash
bash scripts/fbm-security-gate.sh
```

The script verifies:

```text
public/info.php is absent
public/error_log is absent
legacy browser maintenance routes require local environment plus explicit opt-in
schema-mutating `/product_website_id` is inside the local-only maintenance block
legacy external API and Pathao webhook credentials remain server-configured
shared log redaction processor is installed
TenantSeeder reads an ignored local-only source after explicit opt-in
FBM vault migration, encrypted cast and safe model projection are present
FBM connection-health migration, model, services and centralized config are present
FBM secret inputs do not echo stored values into HTML
FB MARKETING Meta credentials are not read from `.env`
Graph `input_token` and derived `app_access_token` material are covered by the redactor
connection tests require contextual permission and rate limiting
health-ledger request fingerprints and IP hashes stay hidden from serialization
docs remain packageable
```

## Closed source-level exposures

```text
routes/web.php hardcoded unsafe-route flag
public `/product_website_id` route that executed schema-wide `ALTER TABLE`
public/info.php phpinfo exposure
public/error_log artifact under web root
config/services.php hardcoded Google OAuth secret
Legacy external API authorization token committed in PHP controllers and ERP JavaScript bundles
Pathao webhook handshake secret committed in Courier/PathaoController
TenantSeeder committed tenant database and FTP values
UserVerificationEmail SMTP configuration logging
TenantDbMiddleware raw tenant-registry exception response
SocialLoginResource raw app-secret payload
Saved secret values echoed back into existing provider settings HTML
Tenant database and FTP passwords echoed back into tenant edit HTML
```

## FBM-02 vault contract

```text
Meta app secrets, access tokens, CAPI tokens and webhook verify tokens live in encrypted tenant-database columns.
APP_KEY remains server-side and is never copied into the vault.
Secret HTML inputs are blank replacement fields.
Blank update input preserves the existing encrypted value.
Saved secret values, ciphertext, suffix fragments and reusable tokens never return to browser views.
Credential audits store safe projections and HMAC fingerprints only.
Credential mutation requires fb_marketing_credentials_manage create/update permission in addition to Configuration read access.
```

## FBM-03 Graph health contract

```text
Graph version policy is centralized in config/fb_marketing.php.
Default Graph API version is v25.0; permitted versions remain centrally replaceable.
Only GET /{version}/debug_token is called in FBM-03.
Graph base URL must use HTTPS and a configured trusted host.
App secret and access token are decrypted server-side only.
Derived app_id|app_secret request credential exists in memory only.
Redirects are disabled and request timeouts are bounded.
Every authorized test attempt creates an append-only fbm_connection_health_checks row.
The ledger stores allow-listed metadata only.
Provider user IDs, granular target IDs, raw provider responses, raw query strings and reusable secrets are excluded.
Connection test requires fb_marketing_connection_health_test.read and route throttling.
The shared redactor covers input_token and app_access_token key patterns.
```

## Required operator action

Previously committed values must be treated as compromised. Rotate affected credentials outside source control before deployment. This package removes values from source but cannot rotate external provider, database or FTP credentials.

Set production `APP_DEBUG=false`. Keep `ALLOW_LOCAL_UNSAFE_WEB_MAINTENANCE_ROUTES=false` in production. The maintenance switch may be enabled only on a developer workstation running `APP_ENV=local`.

Use `docs/fb-marketing/ENV_SECURITY_KEYS.example` as a placeholder checklist. Set rotated values privately for `LEGACY_API_AUTHORIZATION_TOKEN` and `PATHAO_WEBHOOK_INTEGRATION_SECRET` where those integrations are used. Do not commit real values.

Back up the application key securely. Changing `APP_KEY` requires a controlled secret re-encryption procedure before the old key is retired.

Apply the tenant-aware FBM-03 migration before exposing the connection-test action. Review Meta's official Graph lifecycle documentation before changing the centralized version allow-list.

## Existing legacy storage limitation

FBM-03 does not migrate existing legacy social-login, pixel, reCAPTCHA or TikTok secret columns to encrypted storage. FBM-05 handles legacy Facebook pixel/feed consolidation. The new FBM vault and health ledger remain isolated from those legacy settings.

## FBM-04 packaged-source repair and discovery contract

During FBM-04 startup verification, the uploaded ZIP again contained `public/info.php` and `public/error_log`, while omitting `scripts/fbm-security-gate.sh` and `PATCH_DELETE_MANIFEST.txt`. FBM-04 removes the public artifacts, restores the executable gate, restores the explicit deployment deletion manifest and adds `!/PATCH_DELETE_MANIFEST.txt` so the manifest survives the broad `*.txt` ignore rule.

FBM-04 security rules:

```text
Asset discovery requires an active encrypted connection and a latest healthy or warning FBM-03 health result.
Only bounded read-only Graph GET edge traversal is added.
Graph base URL remains HTTPS and trusted-host restricted.
Redirects remain disabled.
Access tokens remain server-side Bearer credentials only.
Cursor pagination is reconstructed from a bounded cursor value; provider next URLs are not blindly followed.
Every edge request has page and record caps.
Raw Graph payloads are normalized in memory only and never persisted.
Provider asset IDs are internal tenant sync keys and are omitted from browser projections and logs.
A failed, skipped or truncated family cannot mark unseen assets unavailable.
Discovery ledger rows contain safe counts, safe warnings and hidden HMAC fields only.
Local asset selection never writes to Meta.
Selection ledger rows store a hidden provider-ID HMAC rather than a raw provider ID.
Discovery requires fb_marketing_asset_discovery_run.read and route throttling.
Selection mutation requires fb_marketing_asset_selection_manage.update.
```

Apply the tenant-aware FBM-04 migration before exposing discovery or selection actions. Apply the paths in `PATCH_DELETE_MANIFEST.txt` to deployment checkouts because a changed-files-only ZIP cannot delete pre-existing host files.

## FBM-05 isolated feed contract

```text
FB MARKETING settings remain separate from legacy General Information Pixel settings.
No automatic migration of legacy Pixel credentials occurs.
The public feed reads products only from explicit canonical opt-in, with legacy fallback before migration.
If neither opt-in field exists, the public feed emits zero products rather than selecting arbitrary rows.
Product mutations invalidate tenant-scoped XML cache through a central observer.
The Configuration diagnostics card exposes counts and readiness only; no secrets or provider asset IDs.
Configuration is the final FB MARKETING sidebar link.
public/info.php and public/error_log remain absent and are listed in PATCH_DELETE_MANIFEST.txt.
```

## FBM-06 queue and scheduler security gate

```text
Existing default QUEUE_CONNECTION remains unchanged.
FB MARKETING uses a dedicated central database queue connection and queue name.
Job payloads contain tenant registry reference and run UUID only.
Job payloads exclude Meta secrets and tenant database / FTP credentials.
Worker tenant context is resolved from the landlord registry at execution time.
Worker tenant context is restored in a finally block after each job.
Scheduled dispatch defaults disabled at deployment and tenant levels.
Scheduler enqueues only and never performs Meta Graph calls.
Graph diagnostics store allow-listed operation metadata and HMAC fingerprints only.
Raw URLs, query strings, authorization headers, provider IDs and raw Graph payloads are excluded.
The backward-compatible discovery route uses the same queued dispatcher.
public/info.php and public/error_log remain absent and listed in PATCH_DELETE_MANIFEST.txt.
```

Run `bash scripts/fbm-security-gate.sh` before packaging and deployment.

## FBM-29 production readiness gate

Before release, run the base security gate and the production-readiness wrapper:

```bash
bash scripts/fbm-security-gate.sh
bash scripts/fbm-production-readiness.sh
```

The wrapper verifies that the unsafe local maintenance-route override is absent, provider writes remain disabled by default, release runbook/checklist documents are present, FB MARKETING browser views omit hidden secret/provider/fingerprint/raw-payload fields and relevant action routes carry explicit throttles.

## FBM-31 provider writer foundation gate

```text
Provider writer readiness requires the dedicated service boundary.
Writer scope readiness requires ads_read and ads_management in the latest health metadata.
Campaign publish and operational actions must call the readiness guard before ready-for-worker state.
Configuration views may show readiness counts and missing scope names only.
Provider IDs, encrypted values, request fingerprints and raw payloads remain hidden.
Provider writes remain disabled by default until a separate signed-off enablement.
```

## FBM-32 campaign publish worker gate

```text
Campaign publish dispatch requires dedicated queue readiness.
Worker rehydrates tenant context from the landlord tenant registry.
Provider client posts only to allow-listed campaign/ad set/creative/ad edges.
All created provider campaign hierarchy objects start PAUSED.
Raw provider IDs stay in hidden mirror columns and never in browser views.
Default broad country targeting is empty; publish requires selected provider audience or explicit country config.
Budget amount must remain inside configured daily/lifetime caps.
```

## FBM-33 operational actions writer gate

```text
Operational writer dispatch requires dedicated queue readiness.

FBM-34 requires every provider-write worker result to carry a safe rollback plan and local reconciliation summary. Rollback plans must not expose raw provider IDs or execute destructive provider calls automatically.
Worker requires a completed FBM-32 publish attempt.
Target provider IDs are loaded only from hidden local mirror columns.
Pause/resume can target campaign, ad set or ad.
Budget and schedule mutations are ad-set-only.
Budget changes must remain inside configured daily/lifetime caps.
Safe edit remains blocked until a field-level allow-list is approved.
```

## FBM-35 final launch gate

```text
Final provider-writer launch gate is read-only.
Dashboard may show pass/blocked launch gate rows only.
Source must keep provider writes disabled by default.
Signed production enablement is a separate configuration release.
```

## FBM-08 Insights snapshot security gate

```text
public/info.php and public/error_log remain absent and are listed in PATCH_DELETE_MANIFEST.txt.
FBM-07 Graph edge-result operation keys are explicitly propagated into the safe API request ledger.
Insights levels, fields, date formats, page caps, row caps and queue caps are centralized and bounded.
Recent snapshots use read-only GET reporting requests only.
Historical async creation creates reporting work only and cannot mutate Ads assets.
Async report polling is capped and backoff-controlled.
Queue payloads contain tenant registry reference plus local report UUID only.
Queue payloads exclude Meta credentials, tenant passwords, provider report keys, provider asset IDs and raw payloads.
Daily snapshots persist allow-listed normalized metrics only.
Internal provider entity keys and provider async report keys stay hidden from browser projections.
Raw provider payloads, URLs, query strings and authorization headers are never written to logs or snapshot ledgers.
```

Apply the tenant-aware FBM-08 migration before enabling snapshot refreshes. Start with one selected Ad Account and review safe warnings before widening scope.


## FBM-10 performance drilldown security gate

```text
Performance routes require fb_marketing_performance_view.read.
Performance rendering reads tenant-local mirrors and stored snapshots only.
Each aggregate filters exactly one insight_level.
Mixed currencies remain separated.
Performance entity tables have a configurable bounded row cap and show a truncation warning.
Review worklists are informational and do not invoke provider writes.
The manual drilldown route is independently throttled.
The manual drilldown profile skips asset discovery, uses a deterministic local-account bound and is limited to a small recent campaign/adset/ad window.
Manual drilldown execution shares the full-read-only connection lock.
Manual drilldown execution dispatches no worker job and no historical async report.
Performance browser views omit tokens, provider identifiers, raw URLs, query strings, internal fingerprints, report keys and raw payloads.
```

The compact FBM-10 package restores `scripts/fbm-security-gate.sh` and `PATCH_DELETE_MANIFEST.txt`. If a developer intentionally keeps `$allowLocalUnsafeWebMaintenanceRoutes = true;` temporarily for mapped-domain manual testing, the gate fails by default. For non-release verification only, run:

```bash
FBM_ACKNOWLEDGE_LOCAL_UNSAFE_OVERRIDE=1 bash scripts/fbm-security-gate.sh
```

Before any release, restore the fail-closed local-environment plus explicit-config expression and run the gate again without the acknowledgement flag.

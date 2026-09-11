# FBM-00 — Baseline, Security Gate and Tracker Foundation

Implemented: 2026-06-08

## Scope

FBM-00 establishes the compact stage tracker, stable architecture documents and fail-closed security baseline required before Meta credential storage begins.

## Baseline findings

The uploaded Laravel ERP already contained useful tenant, CRM, ecommerce, export, queue and scheduler foundations. It also contained blockers that made Meta secret storage unsafe:

```text
routes/web.php set $allowLocalUnsafeWebMaintenanceRoutes = true
public /product_website_id route executed schema-wide ALTER TABLE operations
public/info.php exposed phpinfo()
public/error_log was packaged under the web root
config/services.php contained a hardcoded Google OAuth client secret
Legacy API authorization token was committed in API controllers and ERP JavaScript bundles
Pathao webhook handshake returned a committed integration secret
TenantSeeder contained committed tenant database and FTP values
UserVerificationEmail logged the complete SMTP configuration array
TenantDbMiddleware returned raw tenant-registry connection exception text to the browser
SocialLoginResource returned Facebook and Google secret values
Existing provider settings forms rendered stored secret values into HTML
Tenant edit form rendered stored database and FTP passwords into HTML
```

## Implemented changes

### Unsafe route gate

`routes/web.php` now registers the legacy maintenance block only when both conditions are true:

```php
app()->environment('local')
config('app.allow_local_unsafe_web_maintenance_routes', false)
```

This protects the existing destructive seeder, migration, append-column, source-unzip, tenant-update and profit-recalculation browser routes from production registration. The schema-wide `/product_website_id` ALTER route was moved into the same local-only block. The empty public `/ttt` debug placeholder was removed.

### Public artifact removal

Removed:

```text
public/info.php
public/error_log
```

### Source-secret removal

`config/services.php` now reads Google OAuth values from:

```text
GOOGLE_CLIENT_ID
GOOGLE_CLIENT_SECRET
GOOGLE_REDIRECT_URI
```

`TenantSeeder` no longer embeds tenant credentials. It requires local mode, `ALLOW_LOCAL_TENANT_SEEDER=true` and an ignored `database/seeders/tenant-seeder.local.php` input file. A placeholder-only example file is included.

The external legacy ecommerce/mobile API now reads `LEGACY_API_AUTHORIZATION_TOKEN` server-side through `App\Support\Security\LegacyApiAuthorization` and deliberately fails closed when no token is configured. ERP purchase, product-order and quotation browser bundles no longer receive a static token: they call an authenticated web-session route at `/internal-api/search/products`.

The Pathao webhook handshake now reads `PATHAO_WEBHOOK_INTEGRATION_SECRET` from server-side configuration only when configured. Placeholder deployment keys are documented in `docs/fb-marketing/ENV_SECURITY_KEYS.example`.

### Redaction baseline

Added:

```text
app/Support/Security/SecretRedactor.php
app/Logging/RedactSensitiveContext.php
```

Configured log channels now apply the shared redaction processor. The exception handler's no-flash list includes current and future provider-secret field names.

### Existing secret UI hardening

Existing provider-secret HTML inputs are blank replacement fields. Blank submissions preserve the currently configured value. `SocialLoginResource` returns boolean configured indicators instead of raw secrets. Relevant models hide secret attributes during serialization.

Tenant edit HTML similarly stops returning database and FTP passwords. Blank tenant secret inputs preserve stored values during edit.

### Documentation foundation

Added:

```text
FB_MARKETING_STAGE_TRACKER.md
docs/fb-marketing/FB_MARKETING_MASTER_IMPLEMENTATION_PLAN.md
docs/fb-marketing/ARCHITECTURE.md
docs/fb-marketing/DB_MAP.md
docs/fb-marketing/API_MATRIX.md
docs/fb-marketing/REPORTING_DEFINITIONS.md
docs/fb-marketing/SECURITY_GATE.md
scripts/fbm-security-gate.sh
```

## Database changes

None.

## Route changes

No FBM module route is introduced in FBM-00.

Production route behavior changes:

```text
Legacy browser maintenance block no longer registers outside explicitly enabled local mode.
Public /ttt debug placeholder removed.
Schema-mutating /product_website_id route moved into the local-only maintenance block.
Authenticated POST /internal-api/search/products added for ERP browser bundles.
External /api/search/products remains available to configured legacy API clients only.
```

## Validation checklist

```text
PHP syntax lint for changed PHP files
Targeted route review
Migration safety review: no migration added
Namespace and import review
Permission boundary review: no FBM permission added yet
Tenant-context review
Changed Blade syntax extraction lint
Known secret leakage grep
Maintenance/debug route sweep
Shared redactor behavior smoke test
bash scripts/fbm-security-gate.sh
```

## Known limitations

```text
Previously exposed credentials still require external rotation.
External legacy ecommerce/mobile API clients must send the rotated server-configured token after deployment.
Pathao webhook handshake requires its rotated server-configured secret where enabled.
Existing legacy pixel/social/reCAPTCHA/TikTok secret columns remain plaintext database fields.
FBM encrypted credential tables and UI begin in FBM-02.
FBM shell, sidebar and permission keys begin in FBM-01.
Legacy Facebook product-feed flag mismatch remains scheduled for FBM-05.
The uploaded source package omits `artisan` and Composer `vendor/autoload.php`; framework boot and `route:list` smoke tests must run in the deployment checkout after dependencies are restored.
```

## Next exact command

```text
FBM-01 implement করুন: Module Shell, Sidebar and Permissions
```

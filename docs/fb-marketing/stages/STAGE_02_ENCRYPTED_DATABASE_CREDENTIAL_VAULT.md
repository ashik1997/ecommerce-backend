# FBM-02 — Encrypted Database Credential Vault

Implemented: 2026-06-09

## Scope implemented

FBM-02 adds the first tenant-database FB MARKETING schema and the configuration workflow required to save Meta connection secrets without editing code or adding Meta-specific `.env` values. It implements encrypted at-rest storage, replacement-only secret inputs, safe browser projections, an append-only credential-change audit and a setup-wizard shell.

This stage deliberately performs no Meta Graph API request. Connection tests, token debug, scope metadata and expiry checks begin in FBM-03.

## Startup blocker repair

Startup verification found that the uploaded ZIP had reintroduced:

```text
public/info.php
public/error_log
app/Http/.DS_Store
docs/.DS_Store
```

The package also omitted the documented executable:

```text
scripts/fbm-security-gate.sh
```

FBM-02 removed both public artifacts, restored the security gate and corrected `.gitignore` rules that prevented the fail-closed `TenantSeeder`, Markdown tracker and docs from being reliably tracked. Inherited `.DS_Store` artifacts were also removed and ignored as package hygiene.

## Tenant-database migration

Added:

```text
database/migrations/2026_06_09_000001_create_fbm_connection_vault_tables.php
```

The guarded migration introduces:

```text
fbm_connections
fbm_connection_secret_audits
```

The migration is intentionally non-destructive on rollback. Credential configuration and audit history require an explicit operator-approved removal process.

## Encryption boundary

Added:

```text
app/Casts/EncryptedNullableString.php
app/Models/FbMarketing/FbmConnection.php
app/Models/FbMarketing/FbmConnectionSecretAudit.php
app/Services/FbMarketing/FbmCredentialVaultService.php
```

Encrypted tenant columns:

```text
app_secret_ciphertext
access_token_ciphertext
capi_access_token_ciphertext
webhook_verify_token_ciphertext
```

The custom cast uses Laravel `Crypt::encryptString()` on persistence and `Crypt::decryptString()` only for future server-side consumers. The connection model hides encrypted columns from serialization and exposes an allow-listed `toSafeSummary()` projection for browser views.

Saved browser-visible state is limited to:

```text
configured
not configured
```

The UI does not render token suffix fragments, stored ciphertext or decrypted values.

## Credential-change audit

Every vault mutation creates an append-only `fbm_connection_secret_audits` row.

Stored audit metadata:

```text
connection id
action
actor user id
safe changed-field names
configured secret-field names
safe before/after projections
HMAC fingerprints for newly supplied replacements
HMAC request-IP hash
redacted optional reason
timestamp
```

The audit does not store raw secrets. HMAC fingerprints and request-IP hash are hidden from browser output.

## Permission boundary

Existing shell permissions remain:

```text
fb_marketing_access
fb_marketing_dashboard_view
fb_marketing_configuration_view
fb_marketing_user_manual_view
```

Added contextual permission:

```text
fb_marketing_credentials_manage
```

Route requirements:

| Method | URL | Route name | Required permissions |
| --- | --- | --- | --- |
| GET | `/fb-marketing/configuration` | `fbMarketing.configuration.index` | module access + configuration read |
| GET | `/fb-marketing/configuration/setup-wizard` | `fbMarketing.configuration.setup-wizard` | module access + configuration read |
| POST | `/fb-marketing/configuration/connections` | `fbMarketing.configuration.connections.store` | module access + configuration read + credentials create |
| PUT | `/fb-marketing/configuration/connections/{connection}` | `fbMarketing.configuration.connections.update` | module access + configuration read + credentials update |
| PATCH | `/fb-marketing/configuration/connections/{connection}/status` | `fbMarketing.configuration.connections.status` | module access + configuration read + credentials update |

Admins retain the existing `user_type = 1` bypass. Non-admin roles must be explicitly granted the new contextual credential permission through the existing role-sidebar permission editor.

## Masked configuration UI

Updated:

```text
resources/views/backend/fb-marketing/configuration.blade.php
resources/views/backend/fb-marketing/_status-card.blade.php
resources/views/backend/fb-marketing/dashboard.blade.php
resources/views/backend/fb-marketing/user-manual.blade.php
```

Added:

```text
resources/views/backend/fb-marketing/setup-wizard.blade.php
```

The configuration page supports:

```text
Create an encrypted tenant-local connection
Inspect safe configured/not-configured status
Replace secrets through blank-preserving password inputs
Update safe fields
Enable or disable a connection
Read append-only safe credential audit rows
Open the setup-wizard shell
```

If migrations have not run, the configuration page fails safely into a read-only migration-required notice.

## Validation hardening

Added Form Requests:

```text
app/Http/Requests/Backend/FbMarketing/StoreFbmConnectionRequest.php
app/Http/Requests/Backend/FbMarketing/UpdateFbmConnectionRequest.php
app/Http/Requests/Backend/FbMarketing/UpdateFbmConnectionStatusRequest.php
```

Validation rules include:

```text
tenant-local unique connection name
numeric Meta App ID
credential-mode allow-list
optional Graph version format vN.N
bounded secret lengths
bounded notes and audit reason
```

`app/Exceptions/Handler.php` now excludes FBM CAPI and webhook token fields from validation-flash data.

## Changed files

Added:

```text
app/Casts/EncryptedNullableString.php
app/Models/FbMarketing/FbmConnection.php
app/Models/FbMarketing/FbmConnectionSecretAudit.php
app/Services/FbMarketing/FbmCredentialVaultService.php
app/Http/Requests/Backend/FbMarketing/StoreFbmConnectionRequest.php
app/Http/Requests/Backend/FbMarketing/UpdateFbmConnectionRequest.php
app/Http/Requests/Backend/FbMarketing/UpdateFbmConnectionStatusRequest.php
database/migrations/2026_06_09_000001_create_fbm_connection_vault_tables.php
resources/views/backend/fb-marketing/setup-wizard.blade.php
scripts/fbm-security-gate.sh
docs/fb-marketing/stages/STAGE_02_ENCRYPTED_DATABASE_CREDENTIAL_VAULT.md
```

Updated:

```text
.gitignore
routes/fbMarketingRoutes.php
app/Http/Controllers/Backend/FbMarketing/FbMarketingConfigurationController.php
app/Http/Helpers/BackendSidebarHelper.php
app/Services/RoleSidebarPermissionService.php
app/Exceptions/Handler.php
app/Support/Security/SecretRedactor.php
resources/views/backend/fb-marketing/_status-card.blade.php
resources/views/backend/fb-marketing/dashboard.blade.php
resources/views/backend/fb-marketing/configuration.blade.php
resources/views/backend/fb-marketing/user-manual.blade.php
FB_MARKETING_STAGE_TRACKER.md
docs/fb-marketing/ARCHITECTURE.md
docs/fb-marketing/DB_MAP.md
docs/fb-marketing/SECURITY_GATE.md
```

Removed as startup blocker repair:

```text
public/info.php
public/error_log
app/Http/.DS_Store
docs/.DS_Store
```

## Validation results

Passed in the packaged source:

```text
Changed PHP file syntax lint
Restored bash scripts/fbm-security-gate.sh
Public artifact absence check
Unsafe maintenance-route gate check
FBM encrypted-cast static review
Safe projection and hidden-column review
Replacement-only secret HTML grep
Meta-secret environment-variable grep
Permission middleware route review
Tenant-context review
Blade directive balance review
ZIP integrity test
```

Not runnable in the uploaded source package:

```text
php artisan migrate
php artisan route:list --path=fb-marketing
Framework boot smoke tests
Database-backed encrypted round-trip test
```

Reason: the uploaded source package omits `artisan` and Composer `vendor/autoload.php`. Run these in the deployment checkout after restoring dependencies and backing up the tenant databases.

## Operator action after deployment

```text
Back up tenant databases and APP_KEY securely.
Run the approved tenant-aware migration process for every tenant database.
Run php artisan route:list --path=fb-marketing after restoring artisan and Composer dependencies.
Grant fb_marketing_credentials_manage create/update only to trusted operators.
Keep production APP_DEBUG=false and ALLOW_LOCAL_UNSAFE_WEB_MAINTENANCE_ROUTES=false.
Enter rotated Meta values through the Configuration UI; do not add Meta secrets to .env.
```

## Known limitations

```text
No Graph API request exists yet.
No token debug, scope check, expiry metadata or API-version validation exists yet.
No Meta asset discovery exists yet.
Existing legacy pixel/feed settings remain scheduled for FBM-05 consolidation.
APP_KEY rotation requires a controlled re-encryption procedure.
```

## Next exact command

```text
FBM-03 implement করুন: Graph Client and Connection Health
```

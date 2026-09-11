# FBM-03 — Graph Client and Connection Health

Completed: 2026-06-09

## Scope delivered

FBM-03 adds the first controlled Meta provider request while keeping the integration read-only. An authorized operator can test one encrypted tenant connection from Configuration. The operation validates safe token metadata and writes an append-only tenant-database ledger row.

## Added application components

```text
config/fb_marketing.php
App\Models\FbMarketing\FbmConnectionHealthCheck
App\Services\FbMarketing\FbmGraphApiVersionPolicy
App\Services\FbMarketing\FbmGraphClient
App\Services\FbMarketing\FbmConnectionHealthService
database/migrations/2026_06_09_000002_create_fbm_connection_health_checks_table.php
```

## Graph request boundary

```text
Endpoint: GET https://graph.facebook.com/{version}/debug_token
Purpose: token metadata only
Mode: read-only
Redirects: disabled
Timeouts: bounded through centralized config
Graph host: HTTPS trusted-host allow-list
```

The request uses the encrypted tenant vault. The Meta access token and app secret are decrypted only inside the server process. The derived `app_id|app_secret` request credential exists in memory only and is never stored or logged.

## Health classification

```text
failed
    connection disabled
    required app ID or encrypted secret missing
    Graph version outside centralized application policy
    provider request failure or non-2xx response
    token invalid
    provider app ID absent or mismatched
    token expired
    data-access authorization expired

warning
    token valid but required baseline scope missing
    token valid but expiry boundary is approaching

healthy
    token valid
    provider app ID matches
    no required scope gap
    no approaching expiry boundary
```

The default required baseline scope is `ads_read`. It is configurable in `config/fb_marketing.php`.

## Stored allow-listed metadata

```text
status
graph_api_version
token_is_valid
token_type
provider_app_id
issued_at
expires_at
data_access_expires_at
scopes
missing_required_scopes
http_status
provider_error_code
provider_error_subcode
redacted_message
duration_ms
request_fingerprint HMAC
request_ip_hash HMAC
checked_at
```

Excluded:

```text
plaintext secret
ciphertext
derived app access token
raw query string
raw provider response
provider user ID
granular target ID
token suffix
raw request IP
```

## Route and permission boundary

```text
POST /fb-marketing/configuration/connections/{connection}/test

required middleware:
auth
CheckUserType
DemoMode
fb_marketing_access.read
fb_marketing_configuration_view.read
fb_marketing_connection_health_test.read
throttle
CSRF protection
```

Admin retains the existing bypass. Staff must receive the contextual `fb_marketing_connection_health_test` read permission through the existing role editor.

## UI delivered

```text
Configuration connection cards show never-tested, healthy, warning or failed state.
Authorized users receive an explicit Run read-only connection test button.
Latest safe expiry, scope and diagnostic details display per connection.
A safe append-only health-ledger history table displays recent attempts.
Setup Wizard displays migration, tested-connection and healthy-connection counts.
```

## Packaged-source regression repair

The approved input ZIP again contained `public/info.php`, `public/error_log`, omitted `scripts/fbm-security-gate.sh` and ignored `docs`. FBM-03 removes the public artifacts, restores the executable gate, corrects `.gitignore` and records deleted paths in the patch deletion manifest.

## Validation completed in packaged source

```bash
bash scripts/fbm-security-gate.sh
php -l on changed and new PHP files
```

## Operator steps after patch application

```text
Delete paths listed in PATCH_DELETE_MANIFEST.txt.
Back up tenant databases and APP_KEY securely.
Run the approved tenant-aware migration process for every tenant database.
Run php artisan route:list --path=fb-marketing after restoring artisan and Composer dependencies.
Grant fb_marketing_connection_health_test.read only to intended operators.
Enter rotated Meta credentials through Configuration.
Run a controlled read-only connection test from Configuration.
Review warning rows for missing scopes or approaching expiry.
Keep production APP_DEBUG=false and ALLOW_LOCAL_UNSAFE_WEB_MAINTENANCE_ROUTES=false.
```

## Explicitly deferred

```text
Business Manager asset discovery
Ad-account import
Page, Pixel, Dataset or Catalog sync
Campaign sync
Ads Insights
Dashboard analytics
Conversions API event delivery
Webhook subscriptions
Meta create, update, pause or publish actions
```

Next stage: `FBM-04 — Meta Asset Discovery`.

# FBM-12 — Landing Attribution Capture

Completed: 2026-06-10

## Scope

FBM-12 introduces a tenant-local, privacy-bounded landing-attribution capture boundary. A storefront may submit allow-listed marketing evidence to the mapped tenant API after its own consent decision. The API returns only an opaque attribution-session UUID and an expiry timestamp for later checkout handoff.

This stage does **not** fire Meta Pixel events, send Conversions API events or associate attribution with an ERP order. Browser event contracts belong to FBM-13, the server-side event ledger belongs to FBM-14 and immutable order attribution belongs to FBM-15.

## Added tenant schema

```text
fbm_visitor_attribution_sessions
    session_uuid                         # opaque browser handoff UUID
    first_seen_at
    last_seen_at
    expires_at
    capture_count

    first_landing_url                    # sanitized; allow-listed UTM query only
    latest_landing_url                   # sanitized; allow-listed UTM query only
    first_referrer_url                   # sanitized; query removed
    latest_referrer_url                  # sanitized; query removed

    first_utm_source
    first_utm_medium
    first_utm_campaign
    first_utm_content
    first_utm_term
    first_utm_id

    latest_utm_source
    latest_utm_medium
    latest_utm_campaign
    latest_utm_content
    latest_utm_term
    latest_utm_id

    fbclid_ciphertext                    # encrypted at rest
    fbc_ciphertext                       # encrypted at rest
    fbp_ciphertext                       # encrypted at rest

    request_ip_hash                      # HMAC only; no raw IP
    user_agent_hash                      # HMAC only; no raw UA
    created_at
    updated_at
```

The guarded migration also adds opt-in settings to `fbm_module_settings`:

```text
landing_attribution_enabled              # default false
landing_attribution_retention_days       # default 90; bounded 1..365
```

## Public tenant-domain capture API

```text
POST /api/fb-marketing/attribution/landing
```

The route is public because it is called from the storefront browser, but it still runs after the existing global `TenantDbMiddleware` and has a dedicated throttle. It performs no Meta request and requires no browser-visible reusable API credential.

Accepted fields are allow-listed and bounded:

```text
session_uuid
landing_url                           # required HTTP(S) URL
referrer_url
utm_source
utm_medium
utm_campaign
utm_content
utm_term
utm_id
fbclid
fbc
fbp
```

Safe stored response:

```json
{
  "success": true,
  "data": {
    "attribution_session_uuid": "opaque-uuid",
    "expires_at": "ISO-8601 timestamp",
    "capture_state": "stored"
  }
}
```

When tenant capture is disabled, the endpoint returns `capture_state=disabled` without creating a row or returning a UUID. When the FBM-12 tenant migration is missing, it returns a safe `capture_state=unavailable` response with HTTP 503.

## Capture rules

```text
First-touch fields are immutable after session creation.
Latest-touch fields update only when a new bounded value exists.
Expired tokens create a new opaque attribution session instead of reviving an old row.
fbclid is encrypted and removed from stored landing URLs.
_fbc and _fbp are encrypted at rest.
When fbclid exists but _fbc is absent, the backend derives a bounded fb.1.<timestamp-ms>.<fbclid> value before encryption.
URL fragments are discarded.
Landing URL query storage is restricted to UTM allow-list fields.
Referrer query storage is discarded.
Raw IP and raw User-Agent values are never stored.
Expired-row cleanup is opportunistic and capped per capture request.
```

## Browser helper

Added:

```text
public/assets/js/fb-marketing/fbm-landing-attribution.js
```

The helper does nothing until the storefront explicitly calls it with `consentGranted: true`. It reads allow-listed UTM values and `fbclid` from the current landing URL, reads existing `_fbc` and `_fbp` cookies where available, calls the tenant-domain API and persists only the returned opaque UUID.

Example integration:

```html
<script src="/assets/js/fb-marketing/fbm-landing-attribution.js"></script>
<script>
    window.FbmLandingAttribution.capture({
        apiBaseUrl: '',
        consentGranted: true
    });
</script>
```

Checkout payload augmentation for a later approved bridge:

```javascript
const payload = window.FbmLandingAttribution.withCheckoutAttribution(existingCheckoutPayload);
```

The only added checkout field is:

```text
fbm_attribution_session_uuid
```

Existing checkout controllers intentionally remain unchanged in FBM-12. FBM-15 will validate the opaque UUID and persist an immutable order-attribution snapshot across the approved order paths.

## Configuration and Setup Wizard

The existing Configuration page now shows:

```text
Landing attribution opt-in switch
Retention window
Capture endpoint
Active captured-session count
Latest capture timestamp
Storefront helper path
```

The Setup Wizard adds Step 12 with tenant-safe readiness only. Browser identifiers, UTM values, landing URLs and referrers are not rendered in ERP views.

## Deferred explicitly

```text
Meta Pixel initialization
ViewContent, AddToCart, InitiateCheckout or Purchase browser events
event_id deduplication contract
Conversions API event delivery
CAPI retries and dead-letter handling
ERP order-attribution snapshot
Legacy orders linkage
product_orders linkage
Sales attribution dashboard
Campaign revenue or profit claims
Meta provider writes
```

## Deployment checklist

```text
Back up every tenant database and APP_KEY securely.
Apply the guarded FBM-12 migration through the approved tenant-aware migration process.
Run php artisan route:list --path=fb-marketing/attribution after restoring artisan and Composer dependencies.
Open FB MARKETING → Configuration through a mapped tenant domain.
Review the retention window and enable landing attribution only after storefront consent handling is ready.
Include the standalone browser helper from the storefront and call capture only after explicit consent.
Verify disabled mode returns capture_state=disabled and creates no row.
Verify enabled mode returns only an opaque attribution_session_uuid plus expiry.
Verify stored fbclid, fbc and fbp columns contain ciphertext rather than plaintext.
Verify stored landing URLs exclude fbclid and non-UTM query parameters.
Verify raw IP and raw User-Agent values are absent from the tenant table.
Keep the routes/web.php local-maintenance override fail-closed before production release.
Run the repository security gate in a complete deployment checkout before release.
```

## Uploaded-package limitation

The supplied source remains a partial checkout without `artisan`, `vendor`, `scripts/fbm-security-gate.sh` or `PATCH_DELETE_MANIFEST.txt`. PHP syntax lint and source-level targeted checks can run on the compact patch, but framework boot, migration execution, route-list smoke testing and the repository gate must run in the complete deployment checkout.

# FBM-13 — Browser Pixel Event Contract

Completed: 2026-06-10

## Scope

FBM-13 introduces a tenant-safe, consent-gated browser Pixel event contract. It deliberately stops before server-side Conversions API delivery, durable conversion-event ledgers, checkout persistence and ERP order attribution.

## Added files

```text
database/migrations/2026_06_10_000010_add_fbm_browser_pixel_event_contract_settings.php
app/Services/FbMarketing/FbmBrowserPixelContractService.php
app/Http/Controllers/Api/FbmBrowserPixelConfigurationController.php
public/assets/js/fb-marketing/fbm-browser-pixel.js
docs/fb-marketing/stages/STAGE_13_BROWSER_PIXEL_EVENT_CONTRACT.md
```

## Tenant setting

The guarded migration adds one tenant-local singleton setting:

```text
fbm_module_settings.browser_pixel_mode
```

Allowed values:

```text
disabled
dry_run
live
```

Default:

```text
disabled
```

Applying the migration alone cannot load the Meta script or send a browser event.

## Stable Pixel configuration reuse

FBM-13 does not create a second Pixel credential store. It reads the existing stable General Information fields:

```text
general_infos.fb_pixel_status
general_infos.fb_pixel_app_id
```

It never exposes or reads into the browser contract:

```text
general_infos.fb_pixel_api_key
general_infos.fb_test_event_code
fbm_connections access token
fbm_connections CAPI token
Meta app secret
provider asset IDs
landing attribution identifiers
```

Live readiness requires:

```text
browser_pixel_mode = live
general_infos.fb_pixel_status = 1
general_infos.fb_pixel_app_id matches the bounded numeric Pixel-ID contract
```

## Public tenant-domain runtime endpoint

```text
GET /api/fb-marketing/pixel/browser-config
```

The endpoint:

```text
runs through existing global tenant switching
uses a dedicated throttle
returns Cache-Control: no-store, private
returns Pragma: no-cache
performs no Meta request
returns the Pixel ID only when live delivery readiness passes
```

Safe example response in dry-run mode:

```json
{
  "success": true,
  "data": {
    "contract_version": "v1",
    "mode": "dry_run",
    "enabled": true,
    "live_delivery_ready": false,
    "pixel_id": null,
    "supported_events": [
      "PageView",
      "ViewContent",
      "AddToCart",
      "InitiateCheckout",
      "Purchase"
    ]
  }
}
```

## Storefront helper

Load:

```html
<script src="/assets/js/fb-marketing/fbm-browser-pixel.js"></script>
```

Initialize only after explicit consent:

```javascript
window.FbmBrowserPixel.initialize({
    apiBaseUrl: '',
    consentGranted: true
});
```

Available calls:

```javascript
window.FbmBrowserPixel.trackPageView();
window.FbmBrowserPixel.trackViewContent(payload);
window.FbmBrowserPixel.trackAddToCart(payload);
window.FbmBrowserPixel.trackInitiateCheckout(payload);
window.FbmBrowserPixel.trackPurchase(payload);
```

The helper does not scan the DOM, bind automatic cart listeners or fire Purchase automatically. Storefront code chooses the correct business moment.

## Dry-run verification

Set mode to:

```text
dry_run
```

Listen locally:

```javascript
window.addEventListener('fbm:pixel-contract-event', function (event) {
    console.log(event.detail);
});
```

Example:

```javascript
window.FbmBrowserPixel.trackAddToCart({
    eventId: 'cart-add-opaque-uuid',
    content_ids: ['123'],
    content_type: 'product',
    contents: [
        { id: '123', quantity: 1, item_price: 1250 }
    ],
    currency: 'BDT',
    value: 1250
});
```

Dry-run mode:

```text
loads no connect.facebook.net script
sends no Meta browser event
emits normalized local diagnostics
shows dropped unsupported keys
shows bounded validation errors
```

## Event payload boundary

Allow-listed fields:

```text
eventId
content_ids
contents[{id, quantity, item_price}]
content_type
content_name
content_category
currency
value
num_items
```

Validation boundary:

```text
bounded arrays
bounded strings
non-negative bounded numeric value
positive bounded integer quantities
three-letter uppercase currency normalization
product or product_group content type only
Purchase requires currency and value
unknown keys dropped
```

Initial catalog alignment:

```text
content_ids use ERP product ID strings, matching the FBM-11 XML g:id projection.
```

## Future CAPI deduplication boundary

Each method accepts `eventId`. Live mode sends:

```javascript
fbq('track', 'Purchase', payload, {
    eventID: eventId
});
```

The helper can generate an opaque bounded browser event ID when a caller omits one. Production Purchase integration must instead create one order-confirmed identifier and reuse the same value for the future FBM-14 server-side event ledger request.

## Configuration and Setup Wizard

The backend shows only:

```text
schema readiness
delivery mode
legacy stable Pixel enabled state
safe Pixel ID configured state
live readiness
public config endpoint
storefront helper path
supported event names
```

Raw Pixel ID display, provider asset IDs and secrets are not added to diagnostics.

## Explicitly deferred

```text
Conversions API HTTP delivery
server-side test_event_code use
fbm_conversion_events ledger
fbm_conversion_event_attempts ledger
retry and dead-letter processing
checkout controller modification
ERP order attribution snapshot
sales dashboard
profitability claims
automatic DOM event hooks
automatic Purchase firing
campaign, catalog or audience writes
queue dependency
```

## Verification performed in the partial source snapshot

```text
PHP syntax lint for all changed PHP files
JavaScript syntax check for fbm-browser-pixel.js
source-level endpoint, secret-exclusion and mode-contract checks
changed-file manifest review
```

Framework boot, tenant migration execution, route-list smoke testing and the repository security gate remain deployment-checkout actions because the uploaded snapshot does not contain `artisan`, `vendor`, `scripts/fbm-security-gate.sh` or `PATCH_DELETE_MANIFEST.txt`.

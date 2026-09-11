# FB MARKETING — Meta API Matrix

Last updated: 2026-06-10

## Version policy

Meta's official Graph API changelog lists `v25.0` as the latest version reviewed during FBM-03 implementation on 2026-06-09. The application default and permitted-version list are centralized in `config/fb_marketing.php`; controllers, services and views must not scatter hardcoded Graph versions.

The configured tenant connection may choose an application-permitted version. Operators must review Meta's official lifecycle documentation before removing or adding versions in the centralized allow-list.

Official references:

```text
https://developers.facebook.com/docs/graph-api/changelog/
https://developers.facebook.com/docs/graph-api/changelog/versions/
https://developers.facebook.com/docs/graph-api/changelog/version25.0/
https://developers.facebook.com/docs/graph-api/reference/debug_token/
https://developers.facebook.com/documentation/ads-commerce/marketing-api
https://developers.facebook.com/documentation/ads-commerce/marketing-api/get-started/authorization
https://developers.facebook.com/documentation/ads-commerce/marketing-api/get-started/authentication
```

## Implemented endpoint groups

| Stage | Purpose | Endpoint family | Mode | Status |
| --- | --- | --- | --- | --- |
| FBM-03 | Token health, app ownership, scope and expiry metadata | `GET /{version}/debug_token` | Read-only | Implemented |
| FBM-04 | Businesses, ad accounts, Pages, pixels, datasets, catalogs and Instagram mappings | Bounded Graph asset discovery edges | Read-only | Implemented |
| FBM-07 | Campaign, ad-set, ad and creative mirrors | Marketing API hierarchy edges | Read-only | Implemented |
| FBM-08 | Daily performance snapshots and bounded historical reporting lifecycle | Ads Insights API | Read-only snapshots plus reporting-work creation | Implemented |
| FBM-11 | Catalog products, product sets and ERP mapping diagnostics | Catalog product and product-set edges | Read-only | Implemented |
| FBM-12 | Tenant-local landing attribution evidence | `/api/fb-marketing/attribution/landing` | Local bounded write; no Meta request | Implemented |
| FBM-13 | Consent-gated browser Pixel runtime contract | `/api/fb-marketing/pixel/browser-config` plus storefront `fbq('track')` calls | Local config read; browser delivery only in explicit live mode | Implemented |

## Planned endpoint groups

| Stage | Purpose | Endpoint family | Mode |
| --- | --- | --- | --- |
| FBM-14 | Server-side conversion delivery | Conversions API events | Controlled write |
| FBM-22 | Audience and product-set preparation | Selected ad-account audience edges and local product-set selections | Implemented read-only sync; no provider writes |
| FBM-23 | Campaign draft approval governance | Local draft and immutable publish snapshot records | Implemented local-only; no provider writes |
| FBM-24 | Campaign hierarchy publish | Campaign → ad set → creative → ad step ledger | Implemented with provider writes disabled by default |
| FBM-25 | Pause, resume, budget and supported edits | Local operational action ledger | Implemented with provider writes disabled by default |
| FBM-26 | Lead Ads ingestion | Public Lead Ads webhook verify/callback plus CRM mapping ledger | Implemented inbound; no provider writes |
| FBM-27 | Ad-account webhooks and reconciliation | Public ad-account webhook verify/callback plus local alert refresh | Implemented inbound + local read; no provider writes |
| FBM-28 | Setup Wizard and embedded user manual | Backend setup checklist and BN/EN manual content | Implemented local read; no provider request |
| FBM-29 | Production readiness | Source gates, release runbook and UAT checklist | Implemented local verification only |
| FBM-30 | Controlled rules and recommendations | Local alert-driven recommendation rules and operator decisions | Implemented local-only; autonomous writes disabled |

## FBM-03 request contract

```text
App\Services\FbMarketing\FbmGraphApiVersionPolicy
    resolves the centralized default and permitted version list

App\Services\FbMarketing\FbmGraphClient
    permits HTTPS requests only to configured trusted Graph hosts
    performs GET /{version}/debug_token only
    reads app secret and access token from encrypted tenant-database casts
    derives app_id|app_secret in memory for the token-debug request
    disables redirects
    applies bounded connection and request timeouts
    returns an allow-listed internal projection only

App\Services\FbMarketing\FbmConnectionHealthService
    fails closed before provider calls for inactive, incomplete or unsupported connections
    checks provider app-ID ownership, validity, expiry and baseline scopes
    writes an append-only safe ledger row for every authorized test attempt
```

## Allow-listed token metadata

```text
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
```

Excluded from storage and browser output:

```text
access token
app secret
derived app access token
authorization header
raw request query string
raw provider response
provider user ID
granular target IDs
request IP value
```

## Logging rules

```text
Never log access_token, input_token, app_access_token, app_secret, client_secret, authorization headers or raw request bodies containing secrets.
Store non-reversible request fingerprints and allow-listed diagnostic fields only.
Pass diagnostic text through App\Support\Security\SecretRedactor.
Persist safe HTTP status, provider error codes, redacted message and timestamps where useful.
```


## FBM-04 read-only asset discovery contract

```text
App\Services\FbMarketing\FbmGraphClient::paginateEdge
    validates the centralized Graph version and HTTPS trusted host
    disables redirects and uses bounded timeouts
    sends the decrypted access token as a server-side Bearer credential only
    requests explicit allow-listed fields only
    bounds pages and rows per edge
    reconstructs pagination from a bounded cursor instead of blindly following next URLs
    returns raw rows in memory only for immediate normalization

App\Services\FbMarketing\FbmAssetDiscoveryService
    blocks disabled, unsupported, untested and failed-health connections
    accepts healthy or warning latest health state
    discovers direct plus owned and client/shared assets
    treats dataset edges as capability-first and warning-safe
    stores allow-listed asset columns only
    marks unseen assets unavailable only after a complete family result
    records an append-only safe discovery summary
```

Implemented read-only edge families:

```text
GET /{version}/me/businesses
GET /{version}/me/adaccounts
GET /{version}/me/accounts
GET /{version}/{business-id}/owned_ad_accounts
GET /{version}/{business-id}/client_ad_accounts
GET /{version}/{business-id}/owned_pages
GET /{version}/{business-id}/client_pages
GET /{version}/{business-id}/owned_pixels
GET /{version}/{business-id}/client_pixels
GET /{version}/{business-id}/owned_product_catalogs
GET /{version}/{business-id}/client_product_catalogs
GET /{version}/{business-id}/owned_datasets        # capability-first
GET /{version}/{business-id}/client_datasets       # capability-first
```

Page field projection includes the linked Instagram business account where Meta returns it. Provider asset IDs remain internal tenant sync keys and are excluded from browser projections and operational logs.

## FBM-05 local public feed contract

| Endpoint | Service boundary | Mode | Notes |
| --- | --- | --- | --- |
| `GET /api/facebook-product-feed.xml` | `FbmProductFeedService` | Public read-only XML | Canonical opt-in flag, legacy pre-migration fallback, tenant-scoped cache, no Meta request |
| `PATCH /fb-marketing/configuration/module-settings` | `FbmConfigurationService` | Authenticated tenant-local write | Updates isolated feed switch and TTL only; requires `fb_marketing_configuration_manage.update` |

FBM-05 performs no Meta provider call and no Meta write operation.

## FBM-06 queued execution matrix

| Operation | Trigger | Provider method | Queue behavior | Persisted diagnostics |
| --- | --- | --- | --- | --- |
| Connection health | Explicit browser action | `GET /{version}/debug_token` | Synchronous read-only test retained | Safe allow-listed `fbm_api_request_logs` row plus health ledger |
| Asset discovery sync | Manual browser or scheduled dispatch | Bounded read-only Graph edge `GET` calls | Dedicated `fb-marketing` worker only | `fbm_sync_runs`, existing discovery ledger and safe API-log rows |
| Scheduler tick | Laravel scheduler | None | Enqueue due jobs only | Tenant last-dispatched timestamp |

No Meta write endpoint is introduced in FBM-06.


## FBM-07 update — Campaign hierarchy read-only sync

Selected Ad Accounts now have a tenant-local Campaign → Ad Set → Ad → Creative mirror populated only by bounded Meta Graph GET calls. Provider IDs remain internal sync keys; browser projections and logs expose only safe counts, statuses and redacted diagnostics.

## FBM-08 read-only Ads Insights matrix

| Operation | Provider endpoint shape | Method | Worker behavior | Persisted output |
| --- | --- | --- | --- | --- |
| Recent daily refresh | `/{version}/{ad-account}/insights` | `GET` | Bounded cursor pagination, fixed field allow-list, `time_increment=1` | Daily normalized snapshot upserts |
| Historical report creation | `/{version}/{ad-account}/insights` | `POST` | Queued report creation with fixed allow-list and bounded date chunk | Internal report lifecycle row only |
| Historical report status | `/{version}/{report-run-key}` | `GET` | Bounded polling with capped attempts and configured backoff | Safe status summary only |
| Historical report result | `/{version}/{report-run-key}/insights` | `GET` | Bounded cursor pagination | Daily normalized snapshot upserts |

The historical report-creation POST creates reporting work only. FBM-08 introduces no campaign, budget, creative, audience, Pixel, CAPI or webhook mutation endpoint.


## FBM-10 stored-snapshot performance and manual drilldown contract

```text
GET /fb-marketing/performance
GET /fb-marketing/performance/campaigns/{campaign}
GET /fb-marketing/performance/ad-sets/{adSet}
GET /fb-marketing/performance/ads/{ad}
    tenant-local stored-snapshot reads only
    requires fb_marketing_performance_view.read
    no Meta request during page rendering
    one matching insight level per aggregate
    currency groups remain separate
    table rows are capped by configuration and warn when truncated

POST /fb-marketing/configuration/connections/{connection}/refresh-drilldowns-now
    requires Configuration read plus fb_marketing_sync_run.read
    throttled independently
    skips asset discovery
    refreshes hierarchy for at most one deterministically selected local Ad Account by default
    requests a bounded recent campaign/adset/ad direct window only
    shares the full-read-only connection lock
    dispatches no worker job and no historical async report
    sends no provider write request
```

Performance safe health projections may include operation key, safe status, HTTP status, provider error code/subcode, request duration and redacted message. They exclude provider IDs, request fingerprints, report keys, raw URLs, query strings, authorization headers, tokens and raw payloads.



## FBM-11 read-only catalog mapping contract

| Operation | Provider endpoint shape | Method | Boundary | Persisted output |
| --- | --- | --- | --- | --- |
| Catalog products mirror | `/{version}/{catalog-id}/products` | `GET` | Selected available local catalogs only; fixed allow-list; bounded pages and rows | Safe tenant-local mapping rows |
| Product sets mirror | `/{version}/{catalog-id}/product_sets` | `GET` | Selected available local catalogs only; fixed allow-list; bounded pages, rows and local per-catalog set count | Safe tenant-local product-set summaries |
| Public ERP XML feed | `/api/facebook-product-feed.xml` | local `GET` | Shared local feed projection; no Meta request | Cached tenant-scoped XML only |
| Local ERP mapping override | `/fb-marketing/products-catalog/mappings/{mapping}` | local `PATCH` | Tenant DB update only; requires contextual mapping permission | Local product relation, actor, timestamp and bounded note |
| Manual catalog refresh | `/fb-marketing/configuration/connections/{connection}/refresh-catalog-now` | local `POST` | No queue; shared full-read-only lock; requires active tested connection and contextual permission | Catalog mapping ledger plus safe API-log rows |

Catalog Graph calls are read-only. FBM-11 introduces no catalog-item create/update/delete, feed upload, product-set mutation, campaign-product assignment, Pixel event, CAPI event or campaign write endpoint.

Browser-safe catalog projections exclude provider catalog-item IDs, provider product-set IDs, request fingerprints, raw URLs, query strings, authorization headers, tokens and raw payloads.

## FBM-12 local landing attribution capture contract

| Endpoint | Service boundary | Mode | Notes |
| --- | --- | --- | --- |
| `POST /api/fb-marketing/attribution/landing` | `FbmLandingAttributionService` | Public tenant-local bounded write | Existing global tenant middleware, dedicated throttle, allow-listed input, safe opaque UUID response, no Meta request |
| `PATCH /fb-marketing/configuration/module-settings` | `FbmConfigurationService` | Authenticated tenant-local write | Adds opt-in landing-capture switch and bounded retention window; requires `fb_marketing_configuration_manage.update` |

The public capture endpoint accepts only bounded landing evidence and existing Meta browser identifiers. It stores `fbclid`, `_fbc` and `_fbp` encrypted at rest, stores raw-IP and raw-User-Agent HMACs only, strips URL fragments, removes `fbclid` and non-UTM query parameters from stored landing URLs and removes referrer query strings.

FBM-12 performs no Graph API request, Pixel initialization, browser event firing, Conversions API request, order linkage or provider write operation.

## FBM-13 consent-gated browser Pixel contract

| Endpoint / helper | Service boundary | Mode | Notes |
| --- | --- | --- | --- |
| `GET /api/fb-marketing/pixel/browser-config` | `FbmBrowserPixelContractService` | Public tenant-local read | Dedicated throttle, `Cache-Control: no-store`, allow-listed secret-free response |
| `/assets/js/fb-marketing/fbm-browser-pixel.js` | Standalone storefront helper | Browser local or browser-to-Meta | No config fetch before explicit consent; no embedded credential |
| `PATCH /fb-marketing/configuration/module-settings` | `FbmConfigurationService` | Authenticated tenant-local write | Updates `browser_pixel_mode`; requires `fb_marketing_configuration_manage.update` |

Public runtime response:

```text
contract_version
mode                         # disabled | dry_run | live
enabled
live_delivery_ready
pixel_id                     # present only when live delivery is ready
supported_events
```

Excluded from the public response and helper bundle:

```text
fb_pixel_api_key
fb_test_event_code
CAPI token
Meta access token
Meta app secret
provider asset IDs
landing attribution session UUID
raw landing evidence
```

Supported storefront calls:

```text
FbmBrowserPixel.trackPageView(payload)
FbmBrowserPixel.trackViewContent(payload)
FbmBrowserPixel.trackAddToCart(payload)
FbmBrowserPixel.trackInitiateCheckout(payload)
FbmBrowserPixel.trackPurchase(payload)
```

Allow-listed payload fields:

```text
eventId
content_ids
contents[{id, quantity, item_price}]
content_type                 # product | product_group
content_name
content_category
currency
value
num_items
```

`Purchase` requires `currency` and `value`. Unknown keys are dropped. Bounded invalid inputs produce local validation errors and are not sent. Disabled and dry-run modes load no Meta browser script and send no browser event. In live mode the helper passes the event identifier through the Meta browser call as `{ eventID: eventId }`, reserving the same identifier for FBM-14 server-side deduplication.

FBM-13 introduces no Conversions API HTTP request, durable conversion-event ledger, `test_event_code` browser use, checkout mutation, order attribution write or reporting claim.


## FBM-14 server Conversions API event-ledger contract

| Endpoint / operation | Service boundary | Mode | Notes |
| --- | --- | --- | --- |
| `GET /fb-marketing/tracking-attribution` | `FbMarketingTrackingAttributionController` | Authenticated tenant-local read | Safe readiness, conversion-event rows and redacted attempt rows only; requires `fb_marketing_tracking_view.read` |
| `POST /fb-marketing/tracking-attribution/diagnostics` | `FbmConversionEventService` | Authenticated local write; optional provider test | Creates a synthetic Purchase snapshot; dry-run sends no Meta request; test requires explicit confirmation and encrypted Test Events code |
| `POST /fb-marketing/tracking-attribution/events/{eventUuid}/retry-now` | `FbmConversionEventService` | Authenticated request-bound delivery | Bounded no-queue retry for mapped-domain testing; requires `fb_marketing_capi_event_retry.update` |
| `POST /fb-marketing/tracking-attribution/events/{eventUuid}/dispatch` | `FbmConversionEventDispatchService` | Authenticated queue dispatch | Serializes tenant registry reference plus event UUID only; requires queue readiness and `fb_marketing_capi_event_dispatch.create` |
| `POST /{version}/{pixel-id}/events` | `FbmConversionsApiClient` | Meta Graph provider write | Trusted configured Graph host only; test code attaches only in explicit test mode; live mode never attaches it |
| `PATCH /fb-marketing/configuration/module-settings` | `FbmConfigurationService` | Authenticated tenant-local write | Updates fail-closed `server_capi_mode`; requires `fb_marketing_configuration_manage.update` |

FBM-14 server modes:

```text
disabled      # no event creation through diagnostic; no Meta request
dry_run       # immutable ledger plus append-only local validation attempt; no Meta request
test          # one explicit synthetic Purchase tagged with encrypted test_event_code
live          # production snapshots only; synthetic live diagnostics blocked
```

Provider payload construction is allow-listed. Initial event support is `Purchase` with `event_name`, `event_time`, `event_id`, `action_source=website`, `event_source_url`, normalized `user_data`, and bounded `custom_data` currency/value/contents. Raw tokens, Test Events codes, raw event IDs, destination IDs, source URLs, customer matching fields, browser identifiers, provider bodies and request fingerprints stay out of browser projections and operational logs.

FBM-14 changes no checkout controller and claims no ERP-attributed sales. FBM-15 will bridge confirmed ERP orders into immutable production Purchase snapshots and reuse the FBM-13 browser/server event identifier.

## FBM-15 ERP order-attribution bridge contract

| Endpoint / operation | Service boundary | Mode | Notes |
| --- | --- | --- | --- |
| Legacy API/cart checkout completion hooks | `FbmOrderAttributionBridgeService` | Tenant-local best-effort write | Captures one immutable local snapshot after order totals exist; failures never block ERP order placement |
| Legacy shipping, COD, online payment and admin lifecycle hooks | `FbmOrderAttributionBridgeService` | Tenant-local best-effort reconcile | Appends lifecycle or amount observations without overwriting origin evidence |
| Eligible ecommerce product-order status, edit and return hooks | `FbmOrderAttributionBridgeService` | Tenant-local best-effort reconcile | Processes only `order_source=ecommerce` or `website`; POS-only orders are ignored |
| `POST /fb-marketing/tracking-attribution/orders/reconcile-recent` | `FbMarketingTrackingAttributionController` | Authenticated bounded tenant-local reconcile | Requires `fb_marketing_order_attribution_reconcile.update`, tracking read permission and throttle |
| `FbmLandingAttribution.withCheckoutAttribution(payload, { newPurchase: true })` | Standalone storefront helper | Browser local checkout handoff | Rotates and attaches opaque Purchase event ID for a newly initiated checkout |
| `FbmLandingAttribution.getPurchaseEventId()` | Standalone storefront helper | Browser local order confirmation | Reuse as FBM-13 `trackPurchase({ eventId: ... })` value, then clear after confirmation |

FBM-15 does not expose an attribution-evidence read API. ERP views show safe aggregate counts and bounded safe rows only. Raw order references, URLs, UTMs, `_fbc`, `_fbp`, customer values, destination IDs and Purchase event IDs remain hidden.

## FBM-31 provider writer foundation contract

| Endpoint / operation | Service boundary | Mode | Notes |
| --- | --- | --- | --- |
| `GET /fb-marketing/configuration` | `FbmProviderWriterReadinessService` | Authenticated tenant-local read | Shows writer readiness, required scopes, selected Ad Account count and safety policy only |
| `GET /fb-marketing/configuration/setup-wizard` | `FbmProviderWriterReadinessService` | Authenticated tenant-local read | Includes FBM-31 readiness in the advanced setup checklist |
| Campaign publish preflight | `FbmCampaignPublishEngineService` | Local guard | Blocks ready-for-worker state if provider writer readiness fails |
| Operational action preflight | `FbmCampaignOperationalActionService` | Local guard | Blocks ready-for-worker state if provider writer readiness fails |

FBM-31 requires `ads_read` and `ads_management` in the latest read-only token-debug metadata. It does not add a public API endpoint, does not expose provider IDs, and does not execute live Meta writes.

## FBM-32 campaign publish worker contract

| Endpoint / operation | Service boundary | Mode | Notes |
| --- | --- | --- | --- |
| `POST /fb-marketing/campaign-drafts/{draft}/publish` | `FbmCampaignPublishEngineService` | Authenticated local write and optional queue dispatch | Creates/reuses an idempotent publish attempt; dispatches worker only when provider writes and readiness pass |
| `PublishFbmCampaignAttemptJob` | `FbmCampaignPublishWorkerService` | Dedicated FB MARKETING queue | Rehydrates tenant context and executes allow-listed provider writes |
| `POST /{version}/act_{ad_account_id}/campaigns` | `FbmCampaignPublishProviderClient` | Meta provider write | Creates campaign in PAUSED status |
| `POST /{version}/act_{ad_account_id}/adsets` | `FbmCampaignPublishProviderClient` | Meta provider write | Creates ad set in PAUSED status and enforces budget/targeting preflight |
| `POST /{version}/act_{ad_account_id}/adcreatives` | `FbmCampaignPublishProviderClient` | Meta provider write | Creates link creative from local approved creative asset |
| `POST /{version}/act_{ad_account_id}/ads` | `FbmCampaignPublishProviderClient` | Meta provider write | Creates ad in PAUSED status |

Raw provider object IDs are never rendered. They are stored only in hidden local mirror columns needed for later sync/reconciliation and operational actions.

## FBM-33 operational actions writer contract

| Endpoint / operation | Service boundary | Mode | Notes |
| --- | --- | --- | --- |
| `POST /fb-marketing/campaign-drafts/{draft}/operational-actions` | `FbmCampaignOperationalActionService` | Authenticated local write and optional queue dispatch | Creates/reuses an idempotent operational action; dispatches worker only when provider writes and readiness pass |
| `RunFbmOperationalActionJob` | `FbmCampaignOperationalActionWorkerService` | Dedicated FB MARKETING queue | Rehydrates tenant context and executes allow-listed provider mutation |
| `POST /{version}/{campaign-id}` | `FbmCampaignOperationalProviderClient` | Meta provider write | Pause/resume campaign status |
| `POST /{version}/{ad-set-id}` | `FbmCampaignOperationalProviderClient` | Meta provider write | Pause/resume, budget or schedule update |
| `POST /{version}/{ad-id}` | `FbmCampaignOperationalProviderClient` | Meta provider write | Pause/resume ad status |

Budget and schedule mutations are ad-set-only. Safe edit is blocked until a field-level provider allow-list is approved.

## FBM-34 safety, rollback and reconciliation contract

| Operation | Service boundary | Mode | Notes |
| --- | --- | --- | --- |
| Publish rollback plan | `FbmProviderWriteSafetyService::publishRollbackPlan` | Local ledger only | Records pause-and-reconcile recovery for completed or partial publish attempts |
| Publish reconciliation | `FbmProviderWriteSafetyService::publishReconciliationSummary` | Local mirror check | Stores safe local mirror IDs/statuses and missing mirror flags, never raw provider IDs |
| Operational rollback plan | `FbmProviderWriteSafetyService::operationalRollbackPlan` | Local ledger only | Records inverse payload keys from before-state; automatic rollback is disabled |
| Operational reconciliation | `FbmProviderWriteSafetyService::operationalReconciliationSummary` | Local mirror check | Stores payload keys, target type and drift flags after the local mirror update |

## FBM-35 final provider-writer launch gate contract

| Operation | Service boundary | Mode | Notes |
| --- | --- | --- | --- |
| Launch checklist | `FbmProviderWriterLaunchChecklistService::build` | Local dashboard read | Combines writer readiness, queue readiness, default-disabled writes, scopes, caps, rollback, targeting and safe-edit gates |
| Configuration dashboard | `FbMarketingConfigurationController@index` | Authenticated tenant-local read | Shows pass/blocked launch gate rows without enabling provider writes |
| Setup Wizard | `FbMarketingConfigurationController@setupWizard` | Authenticated tenant-local read | Adds FBM-35 to the advanced readiness checklist |

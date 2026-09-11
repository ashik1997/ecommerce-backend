# FB MARKETING — Architecture Decisions

Last updated: 2026-06-10

## Purpose

FB MARKETING will be a tenant-safe ERP module for Meta advertising operations, ecommerce attribution, profitability analysis, boosting-job finance, controlled campaign actions and embedded operator guidance.

## Non-negotiable decisions

### Tenant boundary

The existing global `App\Http\Middleware\TenantDbMiddleware` resolves the active tenant by domain and switches the runtime `mysql` connection before application work continues. FBM tables introduced from FBM-02 onward belong to the selected tenant database unless a later stage explicitly documents a landlord-level exception.

No FBM query, queue job, scheduler command, export or webhook handler may assume a global default tenant. Every background or inbound execution boundary must resolve and record its tenant context before reading or writing FBM data.

### Backend shell and permission boundary

FBM-01 registers the `fb-marketing` backend route shell through the existing web route flow. The global `TenantDbMiddleware` remains the tenant-resolution boundary. Staff access requires both the top-level `fb_marketing_access` read permission and the matching page read permission. Admin behavior continues to follow the existing `user_type = 1` bypass.

FBM route authorization is enforced at middleware boundaries, not only through hidden sidebar items. The existing role-sidebar JSON storage and tenant-keyed cache are reused.

FBM-02 adds the contextual `fb_marketing_credentials_manage` permission. Reading Configuration still requires `fb_marketing_configuration_view.read`. Credential creation additionally requires `fb_marketing_credentials_manage.create`; replacement and enable/disable actions require `fb_marketing_credentials_manage.update`.

FBM-03 adds the contextual `fb_marketing_connection_health_test` permission. The explicit read-only test route additionally requires `fb_marketing_connection_health_test.read`, CSRF protection and route throttling. Hiding the button is not the authorization boundary; route middleware remains authoritative.

### Credential boundary

Meta credential values are managed from the ERP UI and stored per tenant in encrypted database columns beginning in FBM-02. Meta access tokens, app secrets, CAPI tokens and webhook verify tokens must not be added to `.env` or committed source files.

The tenant vault foundation is:

```text
fbm_connections
fbm_connection_secret_audits
App\Casts\EncryptedNullableString
App\Services\FbMarketing\FbmCredentialVaultService
```

The server-side `APP_KEY` remains an application invariant because Laravel encryption depends on it. It is not a Meta credential and remains outside the tenant credential vault. Changing `APP_KEY` without a controlled re-encryption procedure will make existing vault secrets unreadable.

Raw credentials must never be returned after save. UI screens show configured/not-configured state and accept blank-preserving replacement input only. Safe browser projections must be allow-listed. Secret ciphertext columns remain hidden on Eloquent serialization.

Credential-change audits are append-only. They record safe before/after projections, configured-field names, actor, timestamp, optional redacted reason, a non-reversible request-IP HMAC and HMAC fingerprints for newly supplied secret values. They do not store plaintext secrets.

### Read-only before write operations

FBM-01 through FBM-22 build the shell, secret vault, health checks, discovery, sync, insights, reporting, tracking, attribution and draft-preparation layers before publishing is enabled. Provider writes begin only after draft governance and preflight boundaries exist.

FBM-02 stores encrypted credentials only. It does not call Meta. FBM-03 introduces read-only server-side Graph connection health checks through `GET /{version}/debug_token`. The Graph base URL must be HTTPS and match a configured trusted host. Redirects are disabled and timeouts are bounded. The app secret and Meta access token are decrypted server-side only; the derived `app_id|app_secret` request credential exists in memory only.

FBM-03 writes one append-only `fbm_connection_health_checks` row for every authorized test attempt, including fail-fast outcomes that do not call Meta. Ledger fields are allow-listed. Raw provider responses, query strings, provider user IDs, granular target IDs and reusable secrets are excluded.

### Secret redaction contract

`App\Support\Security\SecretRedactor` is the shared baseline redactor. `App\Logging\RedactSensitiveContext` attaches it to configured diagnostic channels. Future FBM logging, exports, audit payloads, exception responses and API diagnostic views must pass nested metadata through this redactor or store an explicitly allow-listed projection.

Non-Meta integration secrets that remain application-level invariants, such as the rotated legacy external API token and Pathao webhook handshake secret, are server-configured through private environment values. Browser bundles must use authenticated web-session routes rather than embedding reusable API credentials.

### Reporting semantics

Reports must keep these meanings separate:

```text
Meta Reported Revenue
ERP Attributed Sales
Campaign Window Sales
Comparable-period Sales Uplift
```

No campaign-window sale may be presented as a confirmed Meta-attributed sale.

### Write governance

Campaign publishing, budget updates and other operational writes require permission checks, preflight validation, approval where defined, idempotency keys and durable before/after audit records.

## Existing ERP foundations to reuse

```text
Global TenantDbMiddleware tenant switching
BackendSidebarHelper sidebar definitions and role filtering
CRM staged-delivery patterns, audit history and immutable snapshot patterns
product_orders and product_order_products ecommerce foundations
net_profit, contribution profit, returns, refunds and courier settlement foundations
barryvdh/laravel-dompdf and maatwebsite/excel export dependencies
Laravel queue migrations and scheduler infrastructure
```

## Existing Facebook-related foundations to consolidate later

```text
general_infos Facebook pixel fields
/api/facebook-product-feed.xml public product-feed endpoint
Product Facebook-feed selection field
Legacy feed flag mismatch: is_facebook_feed vs is_facebook_product_feed
```

The consolidation repair belongs to FBM-05 so FBM-03 remains limited to encrypted-vault-backed connection health.

## FBM-04 read-only asset discovery boundary

FBM-04 extends the centralized Graph client with bounded read-only edge traversal. It permits HTTPS requests only to configured trusted Graph hosts, disables redirects, applies bounded timeouts, limits pages and rows per edge, reconstructs cursor pagination without blindly following provider URLs and submits the decrypted Meta access token as a server-side Bearer credential only.

`App\Services\FbMarketing\FbmAssetDiscoveryService` normalizes allow-listed fields into tenant asset mirrors. Raw provider payloads exist in memory only during normalization. Provider asset IDs are retained as internal tenant sync keys but are omitted from browser-safe projections and logs.

Discovery is explicit and fail-closed. It requires an active connection with a latest FBM-03 health status of `healthy` or `warning`. Missing permissions, unsupported dataset capability and truncated bounded traversal are represented as safe partial-result warnings. A family marks unseen local rows unavailable only after that family completes successfully; incomplete families preserve unseen state.

FBM-04 adds two contextual permissions:

```text
fb_marketing_asset_discovery_run.read
fb_marketing_asset_selection_manage.update
```

Local asset selection is tenant-database state only. Selecting or deselecting a discovered asset never writes to Meta. Selection changes append a safe local audit row with a hidden provider-ID HMAC and hidden request-IP HMAC.

## FBM-05 isolated module configuration and feed boundary

FBM-05 keeps FB MARKETING configuration separate from the stable General Information Pixel flow. `fbm_connections` remains the encrypted credential vault. `fbm_module_settings` stores tenant-local product-feed enablement, bounded cache TTL and the latest cache invalidation timestamp. Legacy `general_infos.fb_pixel_*` values are neither imported nor overwritten.

The public feed is generated by `FbmProductFeedService`, not the large Product Management controller. `products.is_facebook_product_feed` is canonical. A legacy `products.is_facebook_feed` fallback exists only for pre-migration compatibility. When neither flag exists, the service emits a valid empty feed instead of silently including arbitrary products. Product mutations invalidate a tenant-scoped cache through an observer.

## FBM-06 tenant-safe queue boundary

FB MARKETING asynchronous work uses the dedicated `fb-marketing` database queue connection and `fb-marketing` queue name. Existing ERP queue defaults remain unchanged. Multi-tenant deployments configure central queue storage and the tenant registry through the `landlord` connection. A single-tenant deployment may explicitly opt into a `mysql` queue override only through private environment configuration.

`RunFbmSyncJob` serializes only a tenant registry reference and a sync-run UUID. `FbmTenantContextService` rehydrates the tenant database at worker runtime and restores the previous `mysql` connection configuration in a `finally` block so a long-running worker cannot leak Tenant A context into Tenant B work.

The scheduler dispatches due jobs only. It never calls Meta. The initial FBM-06 worker scope wraps existing bounded read-only asset discovery. Later read-only mirror and insights jobs extend the same boundary.


## FBM-07 update — Campaign hierarchy read-only sync

Selected Ad Accounts now have a tenant-local Campaign → Ad Set → Ad → Creative mirror populated only by bounded Meta Graph GET calls. Provider IDs remain internal sync keys; browser projections and logs expose only safe counts, statuses and redacted diagnostics.

## FBM-08 update — Ads Insights snapshot engine

The existing dedicated FB MARKETING worker now extends its read-only sequence to asset discovery → hierarchy mirror → Insights snapshot coordination. Recent daily windows use bounded direct Graph GET pagination. Older historical windows create queued Meta-hosted asynchronous reporting jobs and poll them with capped attempts and bounded backoff.

`FbmInsightsSnapshotService` persists tenant-local daily metrics only after allow-listed normalization. Raw Graph payloads, provider next URLs, query strings, authorization headers and tokens remain transient in memory. Internal provider entity sync keys and asynchronous report keys are hidden from browser projections.

`RunFbmInsightReportJob` and `PollFbmInsightReportJob` serialize only the landlord tenant reference and local report UUID. Tenant database credentials and Meta credentials are rehydrated server-side at execution time through the existing tenant-context boundary.


## FBM-10 update — Stored-snapshot performance drilldowns and worklists

`FbmPerformanceDrilldownService` adds tenant-local Campaign → Ad Set → Ad reporting without browser-time provider calls. Every aggregate selects exactly one matching `insight_level`, preventing account, campaign, ad-set and ad rows from double counting the same delivery activity. Financial metrics remain separated by stored account currency. Matching local Campaign, Ad Set and Ad tables are capped by configuration, and UI warnings make a truncated bounded view explicit.

`FbmPerformanceWorklistService` derives bounded operator-review prompts from currently filtered snapshot rows. These prompts are informational only and never mutate Meta assets. `FbmPerformanceHealthService` exposes allow-listed redacted sync, report and API-error metadata only.

A separate request-bound `manual_drilldown_read_only` path supports controlled tenant-domain testing when a queue worker is unavailable. It skips asset discovery, refreshes hierarchy for at most one selected Ad Account and stores a bounded recent campaign/adset/ad window. The shared full-read-only tenant-connection lock prevents overlap with queued production sync. Historical async report dispatch and provider writes remain disabled in this path.



## FBM-11 update — Catalog product mapping and feed diagnostics

FBM-11 adds a tenant-local read-only catalog mirror and one shared ERP feed projection. The public XML feed and Products & Catalog diagnostics now evaluate the same validity boundary:

```text
selected for FB product feed
active ERP product
non-empty title
non-empty image
positive effective price
stable ERP product ID
normalized availability
```

Invalid selected products remain visible as safe diagnostic rows but are excluded from XML. Existing ERP product selection fields and the existing public feed URL remain stable. Variant products continue to export at parent-product level and surface an explicit warning; variant-level feed expansion is deferred so existing catalog identities are not silently changed.

Selected and available local catalogs may refresh through bounded Graph `GET` requests only. Provider catalog-item IDs and provider product-set IDs remain internal tenant synchronization keys. Browser projections expose safe display fields, retailer IDs, local ERP IDs, statuses, counts and redacted diagnostics only. Raw payloads, raw URLs, query strings, credentials and authorization headers are excluded.

FBM-22 adds a tenant-local audience preparation layer. Selected ad-account saved/custom audiences may be mirrored through bounded read-only Graph `GET` edge walks, while local audience plans and product-set selections remain ERP-only records. Provider audience IDs and product-set IDs are internal synchronization keys and are excluded from browser projections. Audience creation, customer-list upload, product-set mutation and campaign assignment remain deferred until later governed write stages.

FBM-23 adds campaign draft governance before provider publishing. Drafts combine selected account/page, objective, special-ad-category declaration, budget, schedule, ready local creatives, selected audiences and selected product sets into a local approval workflow. Approval creates an immutable publish snapshot with a payload hash. No Meta campaign, ad set, creative, ad, budget or audience write endpoint is called in this stage.

FBM-24 adds the controlled publish engine ledger. Approved draft snapshots can create one idempotent publish attempt with ordered campaign, ad set, creative and ad steps. Provider writes are disabled by default through `fb_marketing.campaign_publish.provider_writes_enabled=false`; in that mode no Meta request is sent, but duplicate clicks reuse the same attempt and all steps are recorded as blocked preflight. Enabling a future provider writer must preserve the same idempotency key, paused initial statuses and per-step response capture.

FBM-25 adds controlled operational action ledgers for pause, resume, budget update, schedule update and supported safe edits. Each action stores safe before/after state, actor, reason and an idempotency key. Provider operational writes are disabled by default through `fb_marketing.operational_actions.provider_writes_enabled=false`; in that mode actions are blocked preflight and no Meta mutation is sent.

FBM-26 adds Lead Ads inbound ingestion. The public tenant-domain API endpoint verifies Meta webhook challenges against encrypted per-tenant webhook verify tokens and accepts leadgen callbacks into tenant-local ledgers. Payloads with field data create one deduplicated CRM lead; leadgen-ID-only callbacks are stored as pending retrieval. FBM Lead Ads pages expose only safe field keys, mapping flags, statuses and CRM lead references, not raw lead payloads or request fingerprints.

FBM-27 adds ad-account webhook recovery and local alerting. The public tenant-domain API endpoint verifies Meta ad-account webhook challenges with the same encrypted per-tenant verify-token boundary and records callback changes in a safe webhook ledger. Provider object IDs and request fingerprints remain hidden model fields. The Health & Alerts page can run a bounded local reconciliation that refreshes alerts for token expiry, read-only sync failure, opt-in overspend thresholds, poor CTR and catalog stock risk. The reconciliation path does not call provider write endpoints; webhook loss is recoverable through existing bounded read-only sync and local alert review.

FBM-28 completes the operator onboarding surface. The Setup Wizard now includes an advanced readiness checklist for FBM-15 through FBM-28 schema surfaces, and the embedded User Manual can read tenant-local BN/EN sections from `fbm_user_manual_sections` with built-in fallback content. Manual content is local documentation only and never stores provider credentials, raw payloads or provider identifiers.

FBM-29 adds the production-readiness signoff layer. `scripts/fbm-production-readiness.sh` chains the base security gate, blocks hardcoded unsafe maintenance overrides, confirms provider writes remain disabled by default, scans FB MARKETING views for hidden secret/provider/fingerprint fields and verifies release checklist/runbook coverage. The release checklist documents tenant isolation, permissions, migration safety, secret scan, queue/cron, rate limits, UAT and rollback notes.

FBM-30 adds controlled rules and recommendations. The engine seeds local approval-required rules for overspend, poor performance, stock risk, sync failure and token health, then creates deduplicated recommendations from open `fbm_alerts`. Operators may approve or dismiss recommendations, but approval is an auditable local decision only. It does not call Meta, does not enqueue provider writers and does not bypass the FBM-25 controlled operational action boundary.

FBM-31 adds the provider writer foundation. `FbmProviderWriterReadinessService` evaluates encrypted active connections, latest read-only token health metadata, `ads_read` plus `ads_management` scopes, selected available Ad Accounts and FBM-24/25 ledgers before any publish or operational action can reach the future provider worker state. Configuration and Setup Wizard expose a secret-free readiness summary. Provider writes remain disabled by default and this foundation performs no live Meta mutation.

FBM-32 adds the controlled campaign publish worker. When `campaign_publish.provider_writes_enabled=true`, queue readiness passes and FBM-31 readiness is clean, an approved publish attempt is queued to `PublishFbmCampaignAttemptJob`. The worker rehydrates tenant context, creates campaign, ad set, creative and ad provider objects in PAUSED status, writes hidden provider IDs into the existing local mirror tables and stores only safe response refs plus redacted diagnostics in the attempt/step ledgers. Broad default targeting is empty by default, so a selected provider audience or an explicitly configured country list is required.

FBM-33 adds the controlled operational action writer. When `operational_actions.provider_writes_enabled=true`, FBM-31 readiness passes and a completed FBM-32 publish attempt exists, `RunFbmOperationalActionJob` can apply pause, resume, ad-set budget and ad-set schedule mutations. The worker reads provider IDs only from hidden local mirror columns, updates the local mirror after provider success and keeps safe before/after state plus redacted diagnostics in the operational action ledger. Field-level safe edit remains blocked until a future allow-list is approved.

FBM-34 adds the shared provider-write safety layer. `FbmProviderWriteSafetyService` records rollback plans and post-write reconciliation summaries for FBM-32 and FBM-33 without adding any new Meta write endpoint. Campaign publish rollback is pause-and-reconcile because all created objects start PAUSED. Operational rollback records inverse allow-listed payload keys from the audited before-state, but automatic rollback is disabled until an operator confirms provider state through read-only sync. Raw provider IDs remain hidden.

FBM-35 adds the final provider-writer launch gate. `FbmProviderWriterLaunchChecklistService` combines provider-writer readiness, dedicated queue readiness, default-disabled write config, required scopes, budget caps, rollback/reconciliation requirements, fail-closed broad targeting and the blocked `safe_edit` boundary into one dashboard-visible production checklist. It is read-only and does not enable live writes; production enablement remains a separate signed configuration release.

Automatic mapping is deterministic:

```text
Meta retailer_id == ERP product.id
```

Trusted operators may save a tenant-local manual override. The override does not send a Meta request. Duplicate retailer IDs remain ambiguous, and incomplete or bounded families preserve unseen local rows instead of silently marking them unavailable.

The dedicated no-queue catalog action shares the existing full-read-only tenant-connection lock. It requires an active connection, configured access token and a latest read-only health result in `healthy` or `warning` state. Production-scale refresh remains part of the dedicated queued full-read-only synchronization path. Drilldown-only refresh deliberately skips catalog traversal.

## FBM-12 update — Privacy-bounded landing attribution capture

FBM-12 introduces a local browser-to-tenant API boundary before any Pixel or Conversions API event contract exists. `POST /api/fb-marketing/attribution/landing` runs through the existing global tenant middleware, uses a dedicated throttle and performs no Meta request. The storefront must make its own consent decision before calling the standalone helper.

`FbmLandingAttributionService` stores an opaque UUID for later checkout handoff. First-touch evidence remains immutable, latest-touch evidence updates only when a new bounded value exists and expired tokens are replaced rather than revived. Stored landing URLs keep only allow-listed UTM query parameters; stored referrers drop query strings. URL fragments, credentials, arbitrary query parameters and raw browser request data are excluded.

Raw `fbclid`, `_fbc` and `_fbp` identifiers are encrypted at rest through the existing application encryption cast. Request IP and User-Agent values are HMAC-hashed only. Configuration and Setup Wizard projections expose readiness, retention, row count and latest capture time only. No UTM value, landing URL, referrer or browser identifier is rendered in ERP views.

The browser helper persists only the opaque attribution UUID and may attach it to a future checkout payload as `fbm_attribution_session_uuid`. Existing checkout controllers intentionally ignore that field until FBM-15 adds the immutable ERP order-attribution bridge. Pixel events, `event_id` deduplication and Conversions API delivery remain deferred to FBM-13 and FBM-14.

## FBM-13 update — Consent-gated browser Pixel event contract

FBM-13 adds a secret-free browser event contract without changing the stable General Information Pixel form or introducing server-side conversion delivery. The tenant-local `fbm_module_settings.browser_pixel_mode` switch is fail-closed and permits `disabled`, `dry_run` or `live` only. The stable `general_infos.fb_pixel_status` and validated `general_infos.fb_pixel_app_id` values remain the browser initialization source; FB MARKETING reads them without importing or overwriting them.

`GET /api/fb-marketing/pixel/browser-config` runs through the existing tenant-domain middleware boundary and returns a no-store allow-listed contract. The Pixel ID is returned only when live delivery is ready. Reusable credentials, CAPI tokens, `fb_test_event_code`, provider asset IDs, raw landing evidence and attribution-session identifiers are excluded.

The standalone storefront helper performs no config fetch before explicit consent. `disabled` and `dry_run` modes never load the Meta browser script and never send a Meta browser event. Dry-run calls emit a local `fbm:pixel-contract-event` diagnostic containing bounded normalized fields, dropped keys and validation errors so storefront wiring can be tested without provider traffic.

Live mode initializes the official browser Pixel loader once per helper-managed Pixel ID and avoids adding a second `fbevents.js` script when one is already present. The helper does not auto-scan the DOM and does not fire automatic Purchase events. Storefront code calls bounded methods at the correct business moment:

```text
trackPageView
trackViewContent
trackAddToCart
trackInitiateCheckout
trackPurchase
```

Every event accepts an explicit `eventId` and passes it as browser `eventID`. This establishes the future FBM-14 deduplication boundary without persisting an event ledger yet. For production Purchase flow, one order-confirmed event ID must be reused later by the FBM-14 server-side CAPI event. Checkout persistence and ERP attribution remain deferred to FBM-15.


## FBM-14 update — Durable server Conversions API event ledger

FBM-14 introduces a tenant-local immutable Purchase-event snapshot and append-only delivery-attempt ledger. It deliberately does not change checkout controllers. FBM-15 will create production Purchase snapshots only after an ERP order is confirmed and will reuse the same order-confirmed event identifier used by the FBM-13 browser Pixel contract.

Server delivery modes are tenant-local and fail closed:

```text
disabled                     # default; no Meta request
dry_run                      # encrypted snapshot plus local validation only; no Meta request
test                         # explicit synthetic Purchase tagged with the configured Meta Test Events code only
live                         # real server delivery only; synthetic diagnostic blocked
```

The stable validated destination remains `general_infos.fb_pixel_app_id`. The FB MARKETING vault stores the server token and optional Test Events code encrypted at rest. Legacy General Information secret fields are neither imported nor overwritten.

A queued delivery job serializes only the landlord tenant-registry reference and conversion-event UUID. Tenant DB context, immutable encrypted snapshot and encrypted vault token are rehydrated inside the worker. A separate permission-controlled manual retry path supports bounded mapped-domain testing without queue storage or a worker.

Browser-safe projections expose event UUID, event name, delivery mode, safe status, attempt count, timestamps and redacted diagnostics only. They exclude destination IDs, raw event IDs, source URLs, customer matching data, browser identifiers, tokens, Test Events codes, provider request bodies and hidden fingerprints.

## FBM-15 update — Immutable ERP order-attribution bridge

FBM-15 connects local landing evidence, browser Purchase `eventID` and the encrypted CAPI ledger to ERP orders without making order placement depend on provider availability. `FbmOrderAttributionBridgeService` normalizes legacy `orders` plus `order_details` and newer eligible `product_orders` plus `product_order_products` into one tenant-local immutable snapshot boundary.

The snapshot row is unique by local source type and ERP order ID. Later amount and lifecycle observations are append-only `fbm_attribution_reconciliations`; the initial evidence, encrypted Purchase event ID and item snapshots are not overwritten. Recognized lifecycle values normalize to `pending`, `confirmed`, `cancelled`, `returned` or `unknown`. Unknown product-order status values remain visible as safe operator warnings because the existing ERP source contains a pre-existing status-schema drift.

The production CAPI candidate is created only for attributed, normalized-confirmed orders while server CAPI mode is `live`. Queue readiness uses the existing tenant-safe worker path; otherwise the existing no-queue manual retry remains available. Guarded controller entrypoints catch dependency, schema, provider and queue errors so ERP writes remain authoritative.

The storefront landing helper now attaches an opaque `fbm_purchase_event_id` to checkout payloads. A newly initiated checkout should rotate that value once, retries reuse it, the order-confirmation browser Pixel Purchase call reuses it as `eventId`, and the storefront clears it afterwards. Older clients remain compatible but cannot achieve browser/server deduplication until they send the field.

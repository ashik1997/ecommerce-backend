# FB MARKETING — Database Map

Last updated: 2026-06-10

## Current FBM schema state

FBM-03 adds a tenant-database connection-health ledger on top of the encrypted vault introduced in FBM-02. The existing global `TenantDbMiddleware` switches the runtime `mysql` connection before the vault or ledger is queried, so no landlord-level tenant identifier is stored in these tables.

### `fbm_connections`

Purpose: encrypted per-tenant Meta connection configuration.

```text
id
connection_name                     # tenant-local unique label
app_id                              # non-secret Meta App ID
credential_mode                     # system_user | user_access_token | other
graph_api_version                   # application-permitted version; defaults through centralized policy
app_secret_ciphertext               # encrypted cast; never rendered
access_token_ciphertext             # encrypted cast; never rendered
capi_access_token_ciphertext        # encrypted cast; never rendered
webhook_verify_token_ciphertext     # encrypted cast; never rendered
is_active
notes                               # redacted safe notes only
secret_version
credential_updated_at
created_by
updated_by
disabled_by
disabled_at
created_at
updated_at
```

### `fbm_connection_secret_audits`

Purpose: append-only safe change audit for credential creation, replacement, enable and disable events.

```text
id
fbm_connection_id
action                              # created | updated | rotated | enabled | disabled
actor_user_id
changed_fields                      # safe field names only
configured_secret_fields            # configured/not-configured field names only
secret_fingerprints                 # HMAC fingerprints for supplied replacements; hidden
before_state                        # allow-listed safe projection
after_state                         # allow-listed safe projection
request_ip_hash                     # non-reversible HMAC; hidden
change_reason                       # redacted optional audit note
created_at
```

### `fbm_connection_health_checks`

Purpose: append-only safe connection-health ledger for explicit read-only Graph token-debug attempts.

```text
id
fbm_connection_id
actor_user_id
status                              # healthy | warning | failed
graph_api_version
token_is_valid
token_type
provider_app_id                     # non-secret app ownership comparison
issued_at
expires_at
data_access_expires_at
scopes                              # safe scope strings only
missing_required_scopes             # baseline scope gaps only
http_status
provider_error_code
provider_error_subcode
redacted_message                    # sanitized safe diagnostic only
duration_ms
request_fingerprint                 # non-reversible HMAC; hidden
request_ip_hash                     # non-reversible HMAC; hidden
checked_at
```

No plaintext secret, decrypted secret, derived app access token, raw query string, raw provider response, provider user ID, granular target ID, token suffix or ciphertext is returned to browser views or stored in the health ledger.

## Planned tenant-database groups

### Configuration and connections

```text
fbm_connections                     # implemented FBM-02
fbm_connection_secret_audits        # implemented FBM-02
fbm_connection_health_checks        # implemented FBM-03
fbm_business_accounts
fbm_ad_accounts
fbm_pages
fbm_pixels
fbm_datasets
fbm_catalogs
fbm_instagram_accounts
fbm_webhook_subscriptions
```

### Meta hierarchy and assets

```text
fbm_campaigns
fbm_ad_sets
fbm_ads
fbm_creatives
fbm_audiences                       # implemented FBM-22
fbm_product_sets                    # implemented FBM-11; selection planning extended FBM-22
fbm_audience_sync_runs              # implemented FBM-22
fbm_audience_selection_audits       # implemented FBM-22
fbm_campaign_products
fbm_campaign_drafts                 # implemented FBM-23
fbm_campaign_draft_assets           # implemented FBM-23
fbm_campaign_draft_approvals        # implemented FBM-23
fbm_campaign_publish_snapshots      # implemented FBM-23
fbm_campaign_publish_attempts       # implemented FBM-24
fbm_campaign_publish_steps          # implemented FBM-24
fbm_campaign_operational_actions    # implemented FBM-25
fbm_lead_ad_events                  # implemented FBM-26
fbm_lead_ad_webhook_logs            # implemented FBM-26
fbm_ad_account_webhook_logs         # implemented FBM-27
fbm_ad_account_reconciliation_runs  # implemented FBM-27
fbm_alerts                          # implemented FBM-27
fbm_user_manual_sections            # implemented FBM-28
fbm_recommendation_rules            # implemented FBM-30
fbm_recommendations                 # implemented FBM-30
fbm_catalog_product_mappings        # implemented FBM-11
```

### Sync and API operations

```text
fbm_sync_runs
fbm_sync_run_items
fbm_api_request_logs
fbm_ad_account_webhook_logs
fbm_lead_ad_webhook_logs
fbm_action_audits
fbm_alerts
```

`fbm_api_request_logs` was implemented in FBM-06. FBM-03 intentionally retains the narrowly scoped `fbm_connection_health_checks` ledger for explicit token-health attempts.

### Insights and report snapshots

```text
fbm_insight_daily_snapshots
fbm_product_insight_daily_snapshots
fbm_report_exports
```

### Tracking and ecommerce attribution

```text
fbm_visitor_attribution_sessions
fbm_order_attributions
fbm_conversion_events
fbm_conversion_event_attempts
fbm_attribution_reconciliations
```

### Boosting-business finance

```text
fbm_boosting_jobs
fbm_boosting_job_cost_adjustments
fbm_boosting_job_payments
fbm_boosting_job_campaigns
```

### Embedded manual

```text
fbm_manual_categories
fbm_manual_articles
fbm_manual_faqs
```

## Existing tables relevant to consolidation

```text
general_infos
products
product_orders
product_order_products
orders
order_details
product_order_returns
product_order_refunds
product_order_product_allocations
```

## Migration rules

```text
Guard every table and column addition.
Avoid destructive down() behavior for operational ledgers and credential audits.
Preserve tenant isolation.
Do not store APP_KEY in a tenant table.
Store Meta secrets only in encrypted columns introduced by FBM-02.
Never expose ciphertext, decrypted values or reusable tokens in browser payloads, logs or exports.
Store health diagnostics through an explicit allow-list rather than raw provider responses.
Document source columns and reconciliation behavior per stage.
```

## FBM-04 implemented tenant asset discovery tables

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

### Shared discovered-asset contract

```text
fbm_connection_id                 # tenant-local encrypted connection owner
fbm_business_account_id           # nullable tenant-local parent where relevant
provider_asset_id                 # internal sync key; excluded from browser projections
asset_name                        # safe display name where provider supplies one
asset_relationship                # direct | owned | client
provider_status                   # safe status where relevant
is_available                      # changed to false only after complete family discovery
is_selected                       # local tenant readiness selection only
last_seen_at
created_at
updated_at
```

Type-specific safe fields remain separate columns:

```text
fbm_business_accounts.verification_status
fbm_ad_accounts.account_status
fbm_ad_accounts.currency
fbm_ad_accounts.timezone_name
fbm_pages.category
fbm_pixels.data_use_setting
fbm_datasets.dataset_type
fbm_catalogs.vertical
fbm_instagram_accounts.fbm_page_id
fbm_instagram_accounts.username
```

### Append-only discovery run ledger

```text
fbm_asset_discovery_runs
    fbm_connection_id
    actor_user_id
    status                          # success | partial_success | failed
    graph_api_version
    asset_counts                    # safe available-count summary
    successful_families
    failed_families
    warning_details                 # sanitized safe summaries only
    redacted_message
    duration_ms
    request_fingerprint             # hidden HMAC
    request_ip_hash                 # hidden HMAC
    started_at
    completed_at
```

### Append-only local selection audit ledger

```text
fbm_asset_selection_audits
    fbm_connection_id
    actor_user_id
    asset_type
    asset_record_id                 # local tenant row ID
    provider_asset_hash             # hidden HMAC; never raw provider ID
    before_selected
    after_selected
    change_reason                   # redacted optional operator reason
    request_ip_hash                 # hidden HMAC
    created_at
```

Raw Graph payloads, access tokens, app secrets, cursor URLs and provider asset IDs must not be written to either ledger or returned to browser views.

## FBM-05 implemented tenant module settings and product-feed flag

```text
fbm_module_settings
  id                                 # singleton row: 1
  feed_enabled                       # module-owned public feed switch
  feed_cache_ttl_minutes             # bounded 5..1440 at UI/service boundary
  last_feed_cache_invalidated_at     # safe operational timestamp
  updated_by                         # nullable ERP actor
  timestamps

products.is_facebook_product_feed    # canonical operator opt-in flag
products.is_facebook_feed            # legacy compatibility source when present; not dropped in FBM-05
```

FBM-05 backfills true legacy rows into the canonical product flag without overwriting existing canonical selections. Down migration does not drop the canonical flag because that would destroy operator data.

## FBM-06 tenant-local queue and diagnostics tables

| Table / column | Database | Purpose | Sensitive-data rule |
| --- | --- | --- | --- |
| `fbm_sync_runs` | Tenant DB | Durable queued/running/completed sync lifecycle | Stores safe summaries and HMAC fingerprints only |
| `fbm_api_request_logs` | Tenant DB | Allow-listed Graph request diagnostics | No raw URL, query string, header, payload, token or provider ID |
| `fbm_module_settings.scheduled_sync_enabled` | Tenant DB | Tenant opt-in for scheduled dispatch | Defaults to `false` |
| `fbm_module_settings.scheduled_sync_interval_minutes` | Tenant DB | Due-dispatch interval | Bounded to 15–1440 minutes |
| `fbm_module_settings.last_scheduled_sync_dispatched_at` | Tenant DB | Scheduler due-state | Timestamp only |
| `jobs` | Central configured queue DB | Dedicated FB MARKETING queued payload storage | Payload contains tenant registry reference and run UUID only |
| `failed_jobs` | Central configured queue DB | Central queue readiness prerequisite | Keep framework failed-job storage policy under operator control |


## FBM-07 update — Campaign hierarchy read-only sync

Selected Ad Accounts now have a tenant-local Campaign → Ad Set → Ad → Creative mirror populated only by bounded Meta Graph GET calls. Provider IDs remain internal sync keys; browser projections and logs expose only safe counts, statuses and redacted diagnostics.

## FBM-08 tenant-local Ads Insights tables

| Table / column | Purpose | Sensitive-data rule |
| --- | --- | --- |
| `fbm_insight_report_runs` | Direct refresh and asynchronous historical report lifecycle ledger | Provider async report key is internal-only and hidden from browser projections |
| `fbm_insight_daily_snapshots` | Idempotent daily account, campaign, adset and ad metrics | Stores allow-listed normalized metrics only; raw provider payloads are excluded |
| `fbm_insight_daily_snapshots.entity_provider_sync_key` | Internal entity upsert key | Internal-only; never returned to browser-safe summaries or operational logs |
| `fbm_insight_daily_snapshots.metrics_hash` | Change-detection fingerprint for refreshed daily rows | Non-secret digest only |
| `fbm_insight_daily_snapshots.freshness_watermark` | Latest source window date applied to the row | Used for reporting reconciliation |

Daily row uniqueness is tenant connection + selected Ad Account + insight level + internal entity key + snapshot date. Re-running the same recent window refreshes the stored row rather than creating a duplicate.



## FBM-11 tenant-local catalog mapping tables

### `fbm_catalog_product_mappings`

| Column group | Purpose |
| --- | --- |
| `fbm_catalog_id`, `provider_product_item_id` | Tenant-local catalog relation plus internal provider synchronization key; provider item ID is hidden from browser-safe projections |
| `product_id` | Nullable local ERP product relation |
| `retailer_id`, `retailer_product_group_id`, `item_name` | Safe catalog identity and display projection used for mapping diagnostics |
| `provider_availability`, `provider_price_amount`, `provider_currency` | Safe comparison fields for local feed diagnostics |
| `mapping_status`, `mapping_source` | `automatic`, `manual`, `unmatched` or `ambiguous`; deterministic source is retailer ID or local manual override |
| `diagnostic_flags` | Sanitized allow-listed local warning names only |
| `is_available`, `last_seen_at` | Local mirror lifecycle; unseen rows become unavailable only after a complete family result |
| `mapped_by`, `mapped_at`, `mapping_note` | Tenant-local manual override traceability |

### `fbm_product_sets`

| Column group | Purpose |
| --- | --- |
| `fbm_catalog_id`, `provider_product_set_id` | Tenant-local catalog relation plus hidden internal provider synchronization key |
| `set_name`, `filter_summary`, `item_count` | Safe read-only product-set summary; raw filter payload is not stored |
| `is_available`, `last_seen_at` | Local mirror lifecycle with complete-family preservation rule |

### `fbm_catalog_sync_runs`

Append-only safe catalog-refresh ledger. It stores local connection/catalog relations, execution mode, status, Graph version, bounded counts, sanitized warnings, redacted message, duration and timestamps. It does not store tokens, raw responses, provider item IDs, provider set IDs, raw URLs or query strings.

The migration rollback is intentionally non-destructive. Mapping overrides and operational ledgers require an explicit removal procedure.

## FBM-12 tenant-local landing attribution table

### `fbm_visitor_attribution_sessions`

```text
session_uuid                         # opaque browser-visible handoff token only
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
request_ip_hash                      # hidden HMAC; no raw IP
user_agent_hash                      # hidden HMAC; no raw browser UA
created_at
updated_at
```

`fbm_module_settings` gains:

```text
landing_attribution_enabled          # tenant opt-in; default false
landing_attribution_retention_days   # bounded 1..365; default 90
```

The table intentionally has no order foreign key. FBM-15 will define an immutable order-attribution snapshot without mutating this browser-session ledger.

## FBM-13 tenant-local browser Pixel mode

`fbm_module_settings` gains:

```text
browser_pixel_mode                    # disabled | dry_run | live; default disabled
```

The column is tenant-local configuration only. It stores no Pixel ID, credential, browser event payload or event identifier. The stable browser Pixel ID remains in `general_infos.fb_pixel_app_id`, and the public browser config endpoint returns that ID only when live delivery readiness passes. Durable conversion-event rows and delivery-attempt rows remain deferred to FBM-14.


## FBM-14 tenant-local Conversions API ledgers

`fbm_connections` gains:

```text
capi_test_event_code_ciphertext       # optional encrypted Test Events code; replacement-only; test mode only
```

`fbm_module_settings` gains:

```text
server_capi_mode                      # disabled | dry_run | test | live; default disabled
```

### `fbm_conversion_events`

```text
event_uuid                            # browser-safe local row reference
fbm_connection_id                    # tenant-local encrypted-vault relation
event_name                            # FBM-14 initial allow-list: Purchase
event_id_ciphertext                   # immutable encrypted browser/server deduplication value
event_id_hash                         # hidden local HMAC for idempotency
destination_id_ciphertext             # immutable encrypted Pixel destination snapshot
destination_id_hash                   # hidden local HMAC for idempotency
action_source                         # website
event_time
event_source_url_ciphertext           # immutable encrypted source URL
user_data_ciphertext                  # encrypted allow-listed normalized matching data
custom_data_ciphertext                # encrypted allow-listed Purchase value, currency and contents
delivery_mode_snapshot                # dry_run | test | live
status                                # pending | queued | dry_run_validated | delivered | retryable_failed | permanent_failed | rejected
attempt_count
next_attempt_at
last_attempt_at
delivered_at
created_at
updated_at
```

The migration enforces a tenant-local unique boundary across destination hash, event name and event-ID hash. Raw event IDs, destination IDs, customer matching data and browser identifiers are never exposed by the browser-safe model projection.

### `fbm_conversion_event_attempts`

```text
fbm_conversion_event_id
attempt_number
origin                                # diagnostic | manual | queue
delivery_mode
status
http_status
provider_error_code
provider_error_subcode
redacted_message
request_fingerprint                   # hidden HMAC only
duration_ms
attempted_at
created_at
```

Attempt rows are append-only safe diagnostics. Raw provider response bodies, authorization headers, tokens, raw URLs, query strings, customer information and browser identifiers are not stored.

## FBM-15 ERP order-attribution bridge tables

### `fbm_order_attributions`

```text
attribution_uuid                         # opaque local snapshot UUID
source_order_type                        # legacy_order | product_order
source_order_id                          # tenant-local ERP order ID
source_order_reference_ciphertext        # encrypted ERP reference snapshot
source_order_reference_hash              # hidden HMAC
fbm_visitor_attribution_session_id        # nullable FBM-12 session link
evidence_state                           # attributed | missing_session | invalid_session | expired_session
evidence_snapshot_ciphertext             # encrypted immutable landing/handoff evidence JSON
evidence_snapshot_hash                   # hidden HMAC
purchase_event_id_ciphertext             # encrypted browser/server dedupe identifier
purchase_event_id_hash                   # hidden HMAC
currency
order_subtotal_snapshot
discount_snapshot
delivery_fee_snapshot
order_total_snapshot
item_quantity_snapshot
lifecycle_state_snapshot                 # immutable initial normalized state
lifecycle_state_current                  # latest normalized state
snapshot_created_at
confirmed_at
cancelled_at
returned_at
created_at
updated_at
```

A tenant-local unique index on `(source_order_type, source_order_id)` prevents duplicate attribution snapshots for the same ERP order.

### `fbm_order_attribution_items`

```text
fbm_order_attribution_id
source_order_item_id
product_id
product_reference_snapshot
quantity_snapshot
unit_price_snapshot
line_total_snapshot
created_at
updated_at
```

### `fbm_attribution_reconciliations`

```text
fbm_order_attribution_id
event_type                               # captured | confirmed | cancelled | returned | status_changed | amount_adjusted
previous_state
current_state
amount_delta
safe_reason
origin
created_at
```

### FBM-14 linkage update

`fbm_conversion_events` gains nullable indexed `fbm_order_attribution_id`. The provider-safe event projection exposes only a boolean linked state; raw ERP evidence remains hidden.

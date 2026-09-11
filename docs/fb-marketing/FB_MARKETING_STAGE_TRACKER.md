# FB MARKETING — Stage Tracker

Last updated: 2026-06-12

## Current status

| Stage | Scope | Status |
| --- | --- | --- |
| FBM-00 | Baseline, Security Gate and Tracker Foundation | ✅ Complete |
| FBM-01 | Module Shell, Sidebar and Permissions | ✅ Complete |
| FBM-02 | Encrypted Database Credential Vault | ✅ Complete |
| FBM-03 | Graph Client and Connection Health | ✅ Complete |
| FBM-04 | Meta Asset Discovery | ✅ Complete |
| FBM-05 | Existing Pixel and Feed Consolidation | ✅ Complete |
| FBM-06 | Queue, Scheduler and Sync Run Infrastructure | ✅ Complete |
| FBM-07 | Campaign Hierarchy Read-only Sync | ✅ Complete |
| FBM-08 | Ads Insights Snapshot Engine | ✅ Complete |
| FBM-09 | Executive Dashboard V1 | ✅ Complete |
| FBM-10 | Performance Drilldowns and Worklists | ✅ Complete |
| FBM-11 | Catalog, Product Mapping and Feed Diagnostics | ✅ Complete |
| FBM-12 | Landing Attribution Capture | ✅ Complete |
| FBM-13 | Browser Pixel Event Contract | ✅ Complete |
| FBM-14 | Conversions API Event Ledger | ✅ Complete |
| FBM-15 | ERP Order Attribution Bridge | ✅ Complete |
| FBM-16 | Sales Attribution Dashboard | ✅ Complete |
| FBM-17 | Product-level Sales, Profit and Stock Risk | ✅ Complete |
| FBM-18 | Campaign Cost and Profitability Analysis | ✅ Complete |
| FBM-19 | Boosting Jobs and Client Ledger | ✅ Complete |
| FBM-20 | Reporting Center and Exports | ✅ Complete |
| FBM-21 | Creative Library and Publish Preflight Assets | ✅ Complete |
| FBM-22 | Audience and Product-set Management | ✅ Complete |
| FBM-23 | Campaign Draft Planner and Approval Governance | ✅ Complete |
| FBM-24 | Controlled Campaign Publish Engine | ✅ Complete |
| FBM-25 | Controlled Operational Actions | ✅ Complete |
| FBM-26 | Lead Ads to CRM | ✅ Complete |
| FBM-27 | Ad-account Webhooks, Reconciliation and Alerts | ✅ Complete |
| FBM-28 | Setup Wizard and Embedded User Manual | ✅ Complete |
| FBM-29 | Security, Performance, Regression and Production Readiness | ✅ Complete |
| FBM-30 | Controlled Rules and Recommendations | ✅ Complete |
| FBM-31 | Provider Writer Foundation | ✅ Complete |
| FBM-32 | Campaign Publish Worker | ✅ Complete |
| FBM-33 | Operational Actions Writer | ✅ Complete |
| FBM-34 | Safety, Rollback and Reconciliation | ✅ Complete |
| FBM-35 | Final Provider Writer Launch Gate | ✅ Complete |

## FBM-00 completion gate

Passed in the packaged source:

```text
Unsafe browser maintenance routes fail closed outside explicitly enabled local mode
Schema-mutating /product_website_id is inside the local-only maintenance block
public/info.php and public/error_log are absent
Hardcoded Google OAuth, tenant-registry, legacy API and Pathao webhook credentials are absent from source
TenantSeeder requires local mode, explicit opt-in and an ignored local credential file
External legacy API authorization reads LEGACY_API_AUTHORIZATION_TOKEN server-side and fails closed when unset
Authenticated ERP product-search bundles no longer ship an API token in JavaScript
Pathao webhook handshake reads PATHAO_WEBHOOK_INTEGRATION_SECRET server-side
Configured log channels apply the shared SecretRedactor processor
SMTP configuration is no longer written to logs by UserVerificationEmail
Tenant-registry connection failures no longer echo raw exception text to the browser
Existing social-login API output exposes configured flags instead of raw secrets
Existing secret settings forms do not echo stored secrets into HTML
Existing tenant edit form does not echo database or FTP passwords into HTML
```

Operator action still required outside source control:

```text
Rotate every credential that was previously committed or exposed.
Populate GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET where Google OAuth is used.
Set a rotated LEGACY_API_AUTHORIZATION_TOKEN for external legacy ecommerce/mobile API clients.
Set a rotated PATHAO_WEBHOOK_INTEGRATION_SECRET where the Pathao webhook handshake is used.
Use database/seeders/tenant-seeder.local.php only on a developer machine when local seed data is required.
Review docs/fb-marketing/ENV_SECURITY_KEYS.example without committing real values.
Keep production APP_DEBUG=false and ALLOW_LOCAL_UNSAFE_WEB_MAINTENANCE_ROUTES=false.
Run framework boot and route-list smoke tests in the deployment checkout after restoring artisan and Composer dependencies.
```

## Stable docs

```text
docs/fb-marketing/FB_MARKETING_MASTER_IMPLEMENTATION_PLAN.md
docs/fb-marketing/ARCHITECTURE.md
docs/fb-marketing/DB_MAP.md
docs/fb-marketing/API_MATRIX.md
docs/fb-marketing/ENV_SECURITY_KEYS.example
docs/fb-marketing/REPORTING_DEFINITIONS.md
docs/fb-marketing/SECURITY_GATE.md
docs/fb-marketing/stages/STAGE_00_BASELINE_SECURITY_GATE_AND_TRACKER_FOUNDATION.md
docs/fb-marketing/stages/STAGE_01_MODULE_SHELL_SIDEBAR_AND_PERMISSIONS.md
docs/fb-marketing/stages/STAGE_02_ENCRYPTED_DATABASE_CREDENTIAL_VAULT.md
docs/fb-marketing/stages/STAGE_03_GRAPH_CLIENT_AND_CONNECTION_HEALTH.md
docs/fb-marketing/stages/STAGE_04_META_ASSET_DISCOVERY.md
docs/fb-marketing/stages/STAGE_05_EXISTING_PIXEL_AND_FEED_CONSOLIDATION.md
docs/fb-marketing/stages/STAGE_06_QUEUE_SCHEDULER_AND_SYNC_RUN_INFRASTRUCTURE.md
docs/fb-marketing/stages/STAGE_07_CAMPAIGN_HIERARCHY_READ_ONLY_SYNC.md
docs/fb-marketing/stages/STAGE_08_ADS_INSIGHTS_SNAPSHOT_ENGINE.md
docs/fb-marketing/stages/STAGE_09_EXECUTIVE_DASHBOARD_V1.md
docs/fb-marketing/stages/STAGE_09A_MANUAL_DIRECT_SYNC_COMPATIBILITY.md
docs/fb-marketing/stages/STAGE_10_PERFORMANCE_DRILLDOWNS_AND_WORKLISTS.md
docs/fb-marketing/stages/STAGE_11_CATALOG_PRODUCT_MAPPING_AND_FEED_DIAGNOSTICS.md
docs/fb-marketing/stages/STAGE_12_LANDING_ATTRIBUTION_CAPTURE.md
docs/fb-marketing/stages/STAGE_13_BROWSER_PIXEL_EVENT_CONTRACT.md
docs/fb-marketing/stages/STAGE_14_CONVERSIONS_API_EVENT_LEDGER.md
docs/fb-marketing/stages/STAGE_15_ERP_ORDER_ATTRIBUTION_BRIDGE.md
docs/fb-marketing/stages/STAGE_16_SALES_ATTRIBUTION_DASHBOARD.md
docs/fb-marketing/stages/STAGE_17_PRODUCT_LEVEL_SALES_PROFIT_AND_STOCK_RISK.md
docs/fb-marketing/stages/STAGE_18_CAMPAIGN_COST_AND_PROFITABILITY_ANALYSIS.md
docs/fb-marketing/stages/STAGE_19_BOOSTING_JOBS_AND_CLIENT_LEDGER.md
docs/fb-marketing/stages/STAGE_20_REPORTING_CENTER_AND_EXPORTS.md
docs/fb-marketing/stages/STAGE_21_CREATIVE_LIBRARY_AND_PUBLISH_PREFLIGHT_ASSETS.md
docs/fb-marketing/stages/STAGE_22_AUDIENCE_AND_PRODUCT_SET_MANAGEMENT.md
docs/fb-marketing/stages/STAGE_23_CAMPAIGN_DRAFT_PLANNER_AND_APPROVAL_GOVERNANCE.md
docs/fb-marketing/stages/STAGE_24_CONTROLLED_CAMPAIGN_PUBLISH_ENGINE.md
docs/fb-marketing/stages/STAGE_25_CONTROLLED_OPERATIONAL_ACTIONS.md
docs/fb-marketing/stages/STAGE_26_LEAD_ADS_TO_CRM.md
docs/fb-marketing/stages/STAGE_27_AD_ACCOUNT_WEBHOOKS_RECONCILIATION_AND_ALERTS.md
docs/fb-marketing/stages/STAGE_28_SETUP_WIZARD_AND_EMBEDDED_USER_MANUAL.md
docs/fb-marketing/stages/STAGE_29_SECURITY_PERFORMANCE_REGRESSION_PRODUCTION_READINESS.md
docs/fb-marketing/stages/STAGE_30_CONTROLLED_RULES_AND_RECOMMENDATIONS.md
docs/fb-marketing/PRODUCTION_READINESS_CHECKLIST.md
docs/fb-marketing/RELEASE_RUNBOOK.md
```

## FBM-01 completion summary

Completed in the packaged source on 2026-06-09:

```text
FB MARKETING backend route shell registered through routes/fbMarketingRoutes.php
Dedicated dashboard, configuration and user-manual placeholder controllers and views added
Existing global TenantDbMiddleware context preserved
Existing role-sidebar JSON storage and cache architecture reused
fb_marketing_access plus three page-specific read permission keys added
Staff sidebar visibility requires module access plus matching submenu read permission
Direct URL access requires module access plus matching page permission middleware
FBM-00 packaged-source regressions repaired: local-only maintenance gate restored, public/info.php removed, public/error_log removed
scripts/fbm-security-gate.sh restored and passed
No migration added
No Meta credential, token, Graph request, sync or reporting logic added
```

Operator action after deployment:

```text
Run php artisan route:list --path=fb-marketing after restoring artisan and Composer dependencies.
Grant FB MARKETING read permissions to intended non-admin roles through the existing role-sidebar permission editor.
Keep production APP_DEBUG=false and ALLOW_LOCAL_UNSAFE_WEB_MAINTENANCE_ROUTES=false.
```

## FBM-02 completion summary

Completed in the packaged source on 2026-06-09:

```text
Per-tenant fbm_connections and append-only fbm_connection_secret_audits migration added
Meta app secret, access token, CAPI token and webhook verify token persist only in encrypted tenant-database columns
APP_KEY remains server-side and is not stored in the vault
Replacement-only secret inputs added: blank updates preserve the currently stored ciphertext
Browser-safe projections expose configured/not-configured state only; no token suffix, ciphertext or decrypted value returns to HTML
Credential mutations create safe append-only audit rows with actor, redacted reason, configured-field names and HMAC fingerprints
fb_marketing_credentials_manage contextual permission added for trusted credential operators
Configuration page, setup-wizard shell and FBM-02 operator notes added
No Meta Graph request, token debug call, sync or reporting logic added
FBM-00 packaged-source regressions repaired again: public/info.php removed, public/error_log removed, scripts/fbm-security-gate.sh restored
.gitignore corrected so fail-closed TenantSeeder, tracker and docs can be reliably tracked
bash scripts/fbm-security-gate.sh passed
```

Operator action after deployment:

```text
Back up tenant databases and APP_KEY securely.
Run the approved tenant-aware migration process for every tenant database.
Run php artisan route:list --path=fb-marketing after restoring artisan and Composer dependencies.
Grant fb_marketing_credentials_manage create/update only to trusted operators.
Enter rotated Meta values through the Configuration UI; do not add Meta secrets to .env.
Keep production APP_DEBUG=false and ALLOW_LOCAL_UNSAFE_WEB_MAINTENANCE_ROUTES=false.
```

## FBM-03 completion summary

Completed in the packaged source on 2026-06-09:

```text
Centralized FB MARKETING Graph policy added in config/fb_marketing.php with default v25.0, trusted-host validation, bounded timeouts and replaceable permitted-version list
Read-only FbmGraphClient added for GET /{version}/debug_token only
Encrypted app secret and access token are decrypted server-side only; derived app_id|app_secret credential exists in memory only
FbmConnectionHealthService added with fail-closed preflight checks, app-ID ownership validation, validity, expiry and configurable baseline-scope checks
Append-only tenant-database fbm_connection_health_checks ledger migration added
Health ledger stores safe allow-listed metadata, HMAC request fingerprint and HMAC request-IP hash only
Provider user IDs, granular target IDs, raw responses, raw query strings, plaintext secrets, ciphertext and token suffixes are excluded
fb_marketing_connection_health_test contextual read permission added for explicit test actions
POST /fb-marketing/configuration/connections/{connection}/test is protected by auth, CheckUserType, DemoMode, module read, Configuration read, health-test read, CSRF and throttling
Configuration cards, safe ledger history and Setup Wizard health readiness state added
SecretRedactor hardened for input_token and app_access_token patterns
FBM-00 packaged-source regressions repaired again: public/info.php removed, public/error_log removed, scripts/fbm-security-gate.sh restored and docs ignore rule removed
PATCH_DELETE_MANIFEST.txt added so deployment removes public diagnostic artifacts explicitly
bash scripts/fbm-security-gate.sh passed with 38 checks
```

Operator action after deployment:

```text
Apply deletions listed in PATCH_DELETE_MANIFEST.txt.
Back up tenant databases and APP_KEY securely.
Run the approved tenant-aware migration process for every tenant database.
Run php artisan route:list --path=fb-marketing after restoring artisan and Composer dependencies.
Grant fb_marketing_connection_health_test.read only to intended operators.
Enter rotated Meta credentials through Configuration and run a controlled read-only test.
Review warning rows for missing scopes or approaching expiry.
Keep production APP_DEBUG=false and ALLOW_LOCAL_UNSAFE_WEB_MAINTENANCE_ROUTES=false.
```


## FBM-04 completion summary

Completed in the packaged source on 2026-06-09:

```text
Baseline packaged-source regression repaired again: public/info.php and public/error_log removed
scripts/fbm-security-gate.sh restored and expanded for FBM-04
PATCH_DELETE_MANIFEST.txt restored and .gitignore exception added so the manifest remains packageable
Guarded tenant migration adds seven discovered-asset mirrors plus append-only discovery-run and local-selection ledgers
FbmGraphClient gains bounded read-only Graph edge pagination with trusted HTTPS host validation, disabled redirects, bounded cursors, bounded pages and bounded row counts
FbmAssetDiscoveryService discovers direct plus Business-owned and client/shared assets and normalizes allow-listed fields only
Dataset discovery is capability-first; unsupported or permission-limited families produce safe partial-result warnings
Failed, skipped or truncated families do not mark unseen local assets unavailable
Provider asset IDs remain internal tenant sync keys and stay out of browser-safe projections and operational logs
FbmAssetSelectionService updates local tenant readiness selections only and appends safe audits with hidden provider-ID HMACs
fb_marketing_asset_discovery_run.read and fb_marketing_asset_selection_manage.update contextual permissions added
Configuration, Dashboard and Setup Wizard expose real safe discovery and active-asset readiness state
No queue job, scheduler, campaign hierarchy sync, insights snapshot, attribution, CAPI write, webhook or campaign mutation added
```

Operator action after deployment:

```text
Apply deletions listed in PATCH_DELETE_MANIFEST.txt.
Back up tenant databases and APP_KEY securely.
Run the approved tenant-aware migration process for every tenant database.
Run php artisan route:list --path=fb-marketing after restoring artisan and Composer dependencies.
Grant fb_marketing_asset_discovery_run.read only to intended discovery operators.
Grant fb_marketing_asset_selection_manage.update only to intended asset-selection operators.
Run a controlled read-only connection health test, then a controlled read-only asset discovery attempt.
Verify local selection and deselection on the Dashboard; confirm that no Meta write request occurs.
Review partial-success warnings for missing permissions, unsupported dataset edges or bounded traversal limits.
Keep production APP_DEBUG=false and ALLOW_LOCAL_UNSAFE_WEB_MAINTENANCE_ROUTES=false.
```

## FBM-05 completion summary

Completed in the packaged source on 2026-06-09:

```text
Existing stable General Information Pixel fields, form and storefront behavior remain untouched
FB MARKETING configuration remains separate: encrypted fbm_connections vault plus tenant-local fbm_module_settings singleton
Configuration is the final FB MARKETING sidebar link; Setup Wizard is exposed as its own earlier link
fb_marketing_setup_wizard_view.read and fb_marketing_configuration_manage.update permissions added
Guarded tenant migration adds canonical products.is_facebook_product_feed when missing
Legacy products.is_facebook_feed true rows backfill safely without dropping the legacy column
Rollback preserves canonical product selections rather than silently deleting operator data
/api/facebook-product-feed.xml now resolves through a dedicated API controller and FbmProductFeedService
Canonical selection is used first; legacy fallback remains only for pre-migration compatibility
Unsafe arbitrary latest-100-products fallback removed; missing flags safely emit an empty valid XML feed
Tenant host plus database scoped feed cache added with bounded module-owned TTL
Product save, delete and restore invalidate the XML cache centrally through an observer
Configuration diagnostics show safe feed readiness, warnings and selected local Pixel/catalog counts only
No provider asset IDs, Pixel secrets, access tokens, automatic legacy imports or Meta write requests added
Baseline packaged-source regressions repaired again: public/info.php and public/error_log removed
scripts/fbm-security-gate.sh and PATCH_DELETE_MANIFEST.txt restored
```

Operator action after deployment:

```text
Apply deletions listed in PATCH_DELETE_MANIFEST.txt.
Back up tenant databases and APP_KEY securely.
Run the approved tenant-aware migration process for every tenant database.
Run php artisan route:list --path=fb-marketing after restoring artisan and Composer dependencies.
Run php artisan route:list --path=facebook-product-feed after restoring artisan and Composer dependencies.
Grant fb_marketing_setup_wizard_view.read to intended wizard users.
Grant fb_marketing_configuration_manage.update only to trusted operators who may change module feed settings.
Open the XML feed and verify that checked active products appear while unchecked products remain absent.
Keep existing General Information Pixel settings unchanged until a separately approved migration stage exists.
Keep production APP_DEBUG=false and ALLOW_LOCAL_UNSAFE_WEB_MAINTENANCE_ROUTES=false.
```

## FBM-06 completion summary

Completed in the packaged source on 2026-06-09:

```text
Dedicated FB MARKETING database queue connection and queue name added without changing existing QUEUE_CONNECTION default behavior
Tenant-local fbm_sync_runs lifecycle ledger and fbm_api_request_logs safe diagnostics ledger migration added
Scheduled tenant sync controls added with fail-closed default scheduled_sync_enabled=false
RunFbmSyncJob payload limited to landlord tenant registry reference plus safe run UUID
FbmTenantContextService added for worker-time tenant DB rehydration and finally-block connection restoration
Manual POST /fb-marketing/configuration/connections/{connection}/sync added behind contextual fb_marketing_sync_run.read permission and throttling
Backward-compatible /discover-assets route now queues the same worker job instead of performing Meta calls during the browser request
Scheduler command queues due tenant work only; deployment-level FBM_SCHEDULER_ENABLED defaults false
Graph client safe ledger integration added without storing raw URL, query string, headers, payload, token or provider IDs
Configuration UI, setup wizard step 6, status card and embedded operator notes updated
Packaged-source regression repaired again: public/info.php removed, public/error_log removed, scripts/fbm-security-gate.sh restored and PATCH_DELETE_MANIFEST.txt restored
bash scripts/fbm-security-gate.sh passed with 28 checks
```

Operator action after deployment:

```text
Apply deletions listed in PATCH_DELETE_MANIFEST.txt.
Back up tenant databases and APP_KEY securely.
Apply the tenant-aware FBM-06 migration to every tenant database.
Ensure jobs and failed_jobs tables exist on the configured central FBM queue storage database.
Run php artisan fb-marketing:queue-readiness --tenant=<landlord-registry-id>.
Start a supervised dedicated worker: php artisan queue:work fb-marketing --queue=fb-marketing.
Run php artisan schedule:list after restoring artisan and Composer dependencies.
Grant fb_marketing_sync_run.read only to intended operators.
Keep FBM_SCHEDULER_ENABLED=false until readiness passes and the dedicated worker is supervised.
Enable tenant scheduled sync only where required.
```


## FBM-07 completion summary

Completed in the packaged source on 2026-06-09:

```text
Baseline packaged-source regression repaired again: public/info.php and public/error_log removed
scripts/fbm-security-gate.sh and PATCH_DELETE_MANIFEST.txt restored
Tenant migration adds campaign, ad set, ad, creative and sync-run-item mirror tables
Primary queued sync now runs read-only asset discovery and selected-Ad-Account campaign hierarchy sync
Legacy asset discovery route remains discovery-only and backward compatible
Graph edge pagination supports campaign-hierarchy operation keys and bounded limits from centralized config
Campaign hierarchy service stores allow-listed fields only and hides provider IDs from safe projections
Incomplete or truncated hierarchy families preserve existing unseen rows instead of marking them unavailable
Dashboard, Setup Wizard and status card expose FBM-07 readiness and local hierarchy counts
No insights, attribution, campaign publish, budget mutation or provider write request added
```

Operator action after deployment:

```text
Apply deletions listed in PATCH_DELETE_MANIFEST.txt.
Back up tenant databases and APP_KEY securely.
Run the approved tenant-aware migration process for every tenant database.
Run php artisan route:list --path=fb-marketing after restoring artisan and Composer dependencies.
Select intended Ad Account(s), then run the queued full read-only sync from Configuration.
Review partial-result warnings for missing ads_read permissions, unsupported edges or bounded traversal limits.
Keep production APP_DEBUG=false and ALLOW_LOCAL_UNSAFE_WEB_MAINTENANCE_ROUTES=false.
```

## FBM-08 completion summary

Completed in the packaged source on 2026-06-09:

```text
Baseline packaged-source regressions repaired again: public/info.php and public/error_log removed
scripts/fbm-security-gate.sh and PATCH_DELETE_MANIFEST.txt restored
FBM-07 FbmGraphClient edge-result operation-key propagation runtime bug repaired
Guarded tenant migration adds fbm_insight_report_runs and fbm_insight_daily_snapshots
Selected and available Ad Accounts refresh account, campaign, adset and ad level daily Insights snapshots
Recent daily windows use bounded read-only GET cursor pagination inside the dedicated FB MARKETING worker
Older historical windows create bounded queued Meta async Insights reports and poll with capped backoff attempts
RunFbmInsightReportJob and PollFbmInsightReportJob serialize tenant registry reference plus local report UUID only
Async provider report keys remain internal hidden columns and never return in browser-safe summaries
Daily rows use idempotent tenant, account, level, entity and date upserts so repeated refreshes do not create duplicates
Only allow-listed action metrics persist; raw Graph payloads, URLs, query strings, authorization headers and tokens are excluded
FbmSyncExecutionService now runs discovery → hierarchy → Insights coordination under the existing full read-only worker boundary
Dashboard, Configuration and Setup Wizard expose safe snapshot count, report count, latest snapshot date and freshness watermark only
FBM-09 Executive Dashboard V1 remains deferred
```

Operator action after deployment:

```text
Apply deletions listed in PATCH_DELETE_MANIFEST.txt.
Back up tenant databases and APP_KEY securely.
Run the approved tenant-aware migration process for every tenant database.
Run bash scripts/fbm-security-gate.sh before release.
Run php artisan route:list --path=fb-marketing after restoring artisan and Composer dependencies.
Run a controlled full read-only sync for one selected Ad Account first.
Verify recent daily snapshot upserts and confirm repeated refreshes do not create duplicate rows.
Start the dedicated fb-marketing queue worker so async historical backfill and bounded poll jobs can execute.
Review partial-success warnings before expanding selected Ad Accounts.
Keep production APP_DEBUG=false and ALLOW_LOCAL_UNSAFE_WEB_MAINTENANCE_ROUTES=false.
```

## FBM-18 completion summary

Completed in the packaged source on 2026-06-11:

```text
Profitability sidebar page added at /fb-marketing/profitability
Dedicated FbmProfitabilityReportService combines stored ad-level Insights spend, confirmed attributed ERP revenue and local product cost
Approved tenant-local campaign cost adjustments table added with base, VAT, tax and service-charge fields
Profitability summary exposes Meta spend, local adjustments, marketing cost, attributed revenue, purchase cost, ERP contribution profit, ad-adjusted contribution profit and break-even gap
Campaign/ad-set/ad rows expose ROAS, cost per attributed order, contribution margin, adjusted margin and profit state
New fb_marketing_profitability_view permission and fb_marketing_profitability_adjustment_manage contextual permission added
Adjustment creation is permission-gated, CSRF-protected, throttled and stores only local safe labels/notes
Raw provider IDs, URLs, browser identifiers, event IDs, customer values, ciphertext and HMACs remain hidden
No Meta API call, provider write request, campaign mutation, budget mutation, webhook, export or new queue requirement added
FBM-18 stage documentation includes a manual verification checklist for operator testing
```

Operator action after deployment:

```text
Apply all FBM-08, FBM-15 and FBM-18 tenant migrations before relying on profitability reports.
Run php artisan route:list --path=fb-marketing/profitability after restoring artisan, Composer dependencies and database connectivity.
Grant fb_marketing_profitability_view.read to intended reporting users.
Grant fb_marketing_profitability_adjustment_manage.create only to trusted finance operators.
Open FB MARKETING -> Profitability through a mapped tenant domain and verify totals against fbm_insight_daily_snapshots, fbm_order_attribution_items and fbm_campaign_cost_adjustments.
Follow the manual checklist in docs/fb-marketing/stages/STAGE_18_CAMPAIGN_COST_AND_PROFITABILITY_ANALYSIS.md.
Keep production APP_DEBUG=false and ALLOW_LOCAL_UNSAFE_WEB_MAINTENANCE_ROUTES=false.
```

## FBM-09 completion summary

Completed in the packaged source on 2026-06-09:

```text
No tenant migration, queue job or provider write path added
GET dashboard filter request added with bounded date range, optional local Ad Account filter and equal-length previous-period comparison toggle
Executive dashboard service resolves selected and available tenant-local Ad Accounts only
Executive totals query stored insight_level=account rows only so hierarchy levels cannot double count delivery activity
CTR, CPC and CPM derive from aggregate numerators and denominators rather than averaging daily rates
Financial totals group by stored account currency and show separate cards plus warning for mixed currencies
Reach is deliberately labeled Summed daily reach with repeat-user explanation
Freshness panel checks selected account-day coverage, not merely distinct dates
Previous-period comparison labels remain unavailable unless current and previous selected ranges both have complete account-day coverage
Latest selected-account report exposes safe report status only
Dependency-free trend bars and a daily account-level snapshot table added above existing operational readiness panels
Setup Wizard, status card, embedded user manual and reporting definitions updated
ERP attribution, actual ecommerce sales, profit, exports and Meta write actions remain deferred
```

Operator action after deployment:

```text
Apply deletions listed in PATCH_DELETE_MANIFEST.txt.
Apply this compact patch to the deployment checkout.
Open /fb-marketing/dashboard through a mapped tenant domain.
Grant fb_marketing_dashboard_view.read only to intended reporting users.
Run one controlled queued read-only sync for a selected Ad Account before validating totals.
Compare a 7-day dashboard total to stored account-level snapshot rows.
Verify mixed-currency accounts render separate financial totals and a warning.
Verify a deliberately incomplete account-day range renders the coverage warning.
Verify repeated Insights refreshes remain idempotent.
Keep production APP_DEBUG=false and manually restore the local-only maintenance-route gate before release.
```


## FBM-09A completion summary

Completed in the packaged source on 2026-06-10:

```text
Separate mapped-tenant-domain manual sync route and button added without removing the queued production path
Manual no-queue execution bypasses FbmQueueReadinessService, Laravel queue storage and dedicated workers
Manual and queued executions share the same tenant-connection provider-call lock
Manual ledger rows use sync_scope=manual_direct_read_only and trigger_type=manual_direct
Manual request performs bounded read-only discovery, selected-account campaign hierarchy refresh and recent account-level Insights refresh
First manual run may discover assets only; a second run after Ad Account selection populates hierarchy and dashboard snapshots
Manual Insights defaults to 7 completed days, account level only, maximum 3 selected accounts and maximum 6 direct report windows
Historical async Insights report creation and poll-job dispatch remain disabled in manual mode
Queued full sync remains available for production background refresh and historical backfill after FBM-06 readiness passes
No migration, provider write request, credential exposure or raw payload persistence added
```

Operator action after deployment:

```text
Apply the superseding FBM-09 + FBM-09A compact patch to the deployment checkout.
Open FB MARKETING → Configuration through a mapped tenant domain.
Save encrypted credentials and run the read-only connection health test.
Choose Run manual sync now (no queue) to perform initial asset discovery.
Select the intended Ad Account, then run the manual action again.
Open FB MARKETING → Dashboard and verify recent account-level stored snapshots.
Use Queue full read-only Meta sync later when central queue readiness and the dedicated worker are available.
Keep production APP_DEBUG=false and manually restore the local-only maintenance-route gate before release.
```

## FBM-10 completion summary

Completed in the compact patch on 2026-06-10:

```text
No tenant migration or provider write path added
New fb_marketing_performance_view.read permission registered for sidebar filtering and direct-route protection
Stored-snapshot Performance overview plus Campaign → Ad Set → Ad drilldown pages added
Every aggregate filters exactly one matching insight level so alternate grains cannot double count delivery activity
CTR, CPC and CPM derive from aggregate numerators and denominators
Different currencies remain separate and mixed-currency state stays explicit
Performance entity tables default to a bounded 250-row local limit and warn when filters must be narrowed
Dynamic bounded needs-attention worklists added as operator-review prompts only
Safe operational health panel exposes redacted allow-listed sync, report and API-error metadata only
Separate throttled Refresh drilldown snapshots now (no queue) Configuration action added
Manual drilldown scope skips asset discovery, deterministically refreshes at most one selected Ad Account and stores a bounded recent three-day campaign/adset/ad window
Manual drilldown and queued full sync share the existing full-read-only tenant-connection lock
Manual drilldown mode dispatches no worker job and no historical asynchronous report
Existing account-level manual fallback and queued production path remain available
scripts/fbm-security-gate.sh and PATCH_DELETE_MANIFEST.txt restored after uploaded-package regression
```

Known release blocker retained by operator instruction:

```text
routes/web.php currently hardcodes $allowLocalUnsafeWebMaintenanceRoutes = true for temporary mapped-domain testing.
FBM-10 does not silently revert that requested temporary override.
The restored security gate fails by default until the fail-closed local-environment plus explicit-config expression is manually restored before release.
Use FBM_ACKNOWLEDGE_LOCAL_UNSAFE_OVERRIDE=1 only for non-release source verification while the temporary override remains intentional.
```

Operator action after deployment:

```text
Apply deletions listed in PATCH_DELETE_MANIFEST.txt.
Apply this compact patch to the mapped tenant-domain checkout.
Grant fb_marketing_performance_view.read only to intended reporting users.
Run Refresh drilldown snapshots now (no queue) for one selected Ad Account.
Open Performance and verify Campaign → Ad Set → Ad stored-snapshot navigation.
Confirm currency separation, level-safe totals, freshness warnings and review worklists.
Restore the routes/web.php fail-closed maintenance-route expression before release.
Run bash scripts/fbm-security-gate.sh without acknowledgement before release.
Run php artisan route:list --path=fb-marketing after restoring artisan and Composer dependencies.
```



## FBM-11 completion summary

Completed in the compact patch on 2026-06-10:

```text
Guarded tenant migration adds fbm_catalog_product_mappings, fbm_product_sets and fbm_catalog_sync_runs
Products & Catalog sidebar page adds Feed Products, Product Mapping, Catalog Sync and Feed Diagnostics tabs
Shared FbmFeedProductProjectionService makes XML emission and UI diagnostics use the same valid-product rules
Opted-in products with inactive status, blank title, blank image or non-positive price are excluded from XML safely
Existing stable ERP product feed opt-in checkbox and public /api/facebook-product-feed.xml URL remain unchanged
Variant products remain explicit parent-level exports and surface a diagnostic warning instead of silently changing catalog IDs
Selected available catalogs refresh bounded read-only /products and /product_sets Graph edges only
Meta retailer_id == ERP product.id performs automatic local matching; trusted operators can save tenant-local manual overrides
Duplicate retailer IDs remain ambiguous instead of silently selecting an ERP product
Incomplete, truncated or locally capped catalog families preserve unseen local rows as availability-unknown
Full queued and manual-direct sync paths include catalog mapping except the drilldown-only path
Dedicated Refresh catalog mappings now (no queue) action shares the tenant-connection full-read-only lock
Catalog refresh requires an active connection, configured access token and latest healthy-or-warning read-only health test
New fb_marketing_catalog_view.read, fb_marketing_catalog_sync_run.read and fb_marketing_catalog_mapping_manage.update permissions added
Browser-safe projections exclude provider catalog-item IDs, provider product-set IDs, raw payloads, URLs, query strings and credentials
No catalog write, feed upload, product-set mutation, campaign-product assignment, Pixel event, CAPI event or campaign mutation added
Uploaded source remains a partial checkout without artisan, vendor, scripts/fbm-security-gate.sh or PATCH_DELETE_MANIFEST.txt
```

Operator action after deployment:

```text
Back up every tenant database and APP_KEY securely.
Apply the guarded FBM-11 migration through the approved tenant-aware migration process.
Run php artisan route:list --path=fb-marketing after restoring artisan and Composer dependencies.
Grant fb_marketing_catalog_view.read to intended reporting users.
Grant fb_marketing_catalog_sync_run.read only to operators allowed to run bounded read-only catalog refreshes.
Grant fb_marketing_catalog_mapping_manage.update only to trusted operators allowed to maintain local ERP mapping overrides.
Run a read-only connection health test, select an available catalog, then use Refresh catalog mappings now (no queue) for mapped-domain testing.
Open Products & Catalog and verify XML emitted count, exclusions, local mapping state, product sets and catalog-sync ledger.
Verify that missing image and non-positive-price opted-in products remain excluded from XML.
Keep the queued full read-only sync for production-scale catalog refreshes.
Restore the routes/web.php fail-closed local-maintenance expression manually before production release.
Run the repository security gate in a complete deployment checkout before release; this uploaded partial source does not include the gate script.
```


## FBM-12 completion summary

Completed in the compact patch on 2026-06-10:

```text
Guarded tenant migration adds fbm_visitor_attribution_sessions plus opt-in landing-attribution settings
Landing attribution capture is disabled by default and retention is bounded from 1 to 365 days
Public POST /api/fb-marketing/attribution/landing runs through existing global tenant switching and a dedicated throttle
Endpoint accepts allow-listed bounded landing evidence only and performs no Meta request
First-touch evidence remains immutable while latest-touch fields update only when a new bounded value exists
Expired opaque tokens create new attribution sessions instead of reviving old rows
Stored landing URLs keep allow-listed UTM query fields only; fbclid, arbitrary query parameters and fragments are removed
Stored referrers drop query strings
fbclid, _fbc and _fbp persist only in encrypted tenant-database columns
Raw request IP and raw User-Agent values are excluded; hidden HMAC values are stored instead
Bounded opportunistic retention cleanup deletes at most the configured cap per capture request
Standalone storefront helper added with explicit consent gate and no embedded reusable API token
Browser helper persists only the returned opaque UUID and exposes future checkout payload augmentation as fbm_attribution_session_uuid
Configuration and Setup Wizard expose readiness, opt-in state, retention, count and latest-capture timestamp only
Existing checkout controllers remain unchanged; immutable ERP order attribution stays deferred to FBM-15
Pixel initialization, browser events, event_id deduplication and Conversions API delivery remain deferred to FBM-13 and FBM-14
Uploaded source remains a partial checkout without artisan, vendor, scripts/fbm-security-gate.sh or PATCH_DELETE_MANIFEST.txt
```

Operator action after deployment:

```text
Back up every tenant database and APP_KEY securely.
Apply the guarded FBM-12 migration through the approved tenant-aware migration process.
Run php artisan route:list --path=fb-marketing/attribution after restoring artisan and Composer dependencies.
Open FB MARKETING → Configuration through a mapped tenant domain.
Keep landing attribution disabled until storefront consent handling is ready.
Load /assets/js/fb-marketing/fbm-landing-attribution.js from the storefront and call capture only after explicit consent.
Verify disabled mode stores no session and enabled mode returns only an opaque UUID plus expiry.
Verify identifier columns contain ciphertext and URL columns contain sanitized values only.
Verify raw IP and raw User-Agent values are absent.
Do not claim ERP-attributed sales until FBM-15 and later reporting stages are implemented.
Restore the routes/web.php fail-closed local-maintenance expression manually before production release.
Run the repository security gate in a complete deployment checkout before release; this uploaded partial source does not include the gate script.
```

## FBM-13 completion summary

Completed in the compact patch on 2026-06-10:

```text
Guarded tenant migration adds fbm_module_settings.browser_pixel_mode with fail-closed disabled default
Allowed browser modes are disabled, dry_run and live only
Existing stable General Information Pixel status and Pixel ID remain the browser initialization source
Existing stable General Information Pixel configuration is read-only from FB MARKETING and is never imported, overwritten or deleted
Public GET /api/fb-marketing/pixel/browser-config runs through existing tenant switching, uses a dedicated throttle and returns a no-store secret-free contract
The public browser contract exposes the Pixel ID only when live delivery is ready
API keys, CAPI tokens, test-event codes, provider asset IDs, landing-attribution identifiers and reusable credentials remain excluded
Standalone storefront helper added with an explicit consent gate and no config fetch before consent
Disabled mode and dry_run mode never load connect.facebook.net/en_US/fbevents.js and never send a Meta browser event
Dry-run calls emit normalized local fbm:pixel-contract-event diagnostics for storefront verification
Live mode initializes the official Meta Pixel loader once per helper-managed Pixel ID and reuses an already loaded fbevents.js script when present
Supported methods are PageView, ViewContent, AddToCart, InitiateCheckout and Purchase
Event payloads are allow-listed and bounded; unknown keys are dropped before any live browser send
Purchase validation requires currency and value
Every event supports an explicit eventId and sends it as Meta browser eventID for the future FBM-14 CAPI deduplication boundary
The helper may generate a bounded opaque browser event ID when one is not supplied, but production Purchase integration must reuse an order-confirmed ID with FBM-14
Configuration and Setup Wizard expose safe readiness state, delivery mode, helper path and supported event names only
No Conversions API request, test-event-code browser exposure, event ledger, checkout controller update, order linkage or reporting claim added
Uploaded source remains a partial checkout without artisan, vendor, scripts/fbm-security-gate.sh or PATCH_DELETE_MANIFEST.txt
```

Operator action after deployment:

```text
Back up every tenant database and APP_KEY securely.
Apply the guarded FBM-13 migration through the approved tenant-aware migration process.
Run php artisan route:list --path=fb-marketing/pixel after restoring artisan and Composer dependencies.
Open FB MARKETING → Configuration through a mapped tenant domain.
Keep Browser Pixel mode disabled until storefront consent handling is ready.
Use dry_run first; load /assets/js/fb-marketing/fbm-browser-pixel.js and call initialize only after explicit consent.
Listen for fbm:pixel-contract-event and verify normalized product IDs, value, currency, generated-or-supplied event ID, dropped keys and validation errors.
Confirm dry_run loads no connect.facebook.net script and sends no Meta browser event.
Switch to live only after the stable General Information Pixel is enabled and a valid Pixel ID is configured.
Ensure the storefront has one intentional Pixel initialization owner; the helper avoids duplicate script loading but cannot infer every external initialization convention.
For production Purchase, generate one order-confirmed event ID and reserve the same value for the future FBM-14 server-side ledger event.
Do not expose general_infos.fb_pixel_api_key or general_infos.fb_test_event_code in the browser.
Do not claim server-side conversion delivery or ERP-attributed sales until FBM-14, FBM-15 and later reporting stages are implemented.
Restore the routes/web.php fail-closed local-maintenance expression manually before production release.
Run the repository security gate in a complete deployment checkout before release; this uploaded partial source does not include the gate script.
```


## FBM-14 completion summary

Completed in the compact patch on 2026-06-10:

```text
Guarded tenant migration adds encrypted server CAPI test-code storage, fail-closed fbm_module_settings.server_capi_mode and durable fbm_conversion_events plus append-only fbm_conversion_event_attempts
Allowed server modes are disabled, dry_run, test and live; disabled remains the default
Existing stable general_infos.fb_pixel_app_id remains the validated server destination and is not imported, overwritten or deleted
Existing encrypted fbm_connections.capi_access_token_ciphertext remains the delivery token boundary
Optional encrypted fbm_connections.capi_test_event_code_ciphertext is replacement-only and is used only during explicit test-mode delivery
Raw token, test code, destination Pixel ID, raw event ID, raw URL, customer information, browser identifiers, provider payloads and request fingerprints remain excluded from ERP screens and operational logs
Purchase event snapshots store encrypted immutable destination, event ID, source URL, user-data and custom-data values plus non-reversible hashes for local idempotency
Same tenant destination, event name and event ID deduplicate locally before any provider request
Dry-run diagnostics validate and append a redacted attempt row without sending any Meta request
Test diagnostics require an explicit checkbox plus a configured encrypted Test Events code before sending one synthetic Purchase
Live synthetic diagnostics are intentionally blocked; live mode is reserved for order-confirmed production snapshots from FBM-15
Conversions API client sends bounded POST /{version}/{pixel-id}/events requests only to the trusted configured Graph host
Live mode never silently attaches a Test Events code
Dedicated DeliverFbmConversionEventJob carries tenant registry reference plus event UUID only; token, Pixel ID, customer data and event payload are rehydrated inside tenant context
Manual Retry now action performs a bounded no-queue request-bound attempt for mapped-tenant-domain testing
Queue dispatch remains available separately after central queue readiness passes
Tracking & Attribution sidebar page exposes landing, browser Pixel and server CAPI readiness, browser-safe event rows and redacted attempt history only
New contextual permissions separate tracking read, diagnostic creation, queue dispatch and manual no-queue retry
Configuration and Setup Wizard expose safe server-CAPI readiness, server mode and hidden-secret configured counts only
Existing checkout controllers remain unchanged; automatic production Purchase snapshot creation and immutable ERP order attribution remain deferred to FBM-15
Uploaded source remains a partial checkout without artisan, vendor, scripts/fbm-security-gate.sh or PATCH_DELETE_MANIFEST.txt
```

Operator action after deployment:

```text
Back up every tenant database and APP_KEY securely.
Apply the guarded FBM-14 migration through the approved tenant-aware migration process.
Run php artisan route:list --path=fb-marketing after restoring artisan and Composer dependencies.
Grant fb_marketing_tracking_view.read to intended operators.
Grant fb_marketing_capi_diagnostic_run.create, fb_marketing_capi_event_dispatch.create and fb_marketing_capi_event_retry.update only to trusted operators.
Open FB MARKETING → Configuration through a mapped tenant domain and keep Server CAPI mode disabled until the vault and stable Pixel destination are reviewed.
Save the rotated Conversions API token in the encrypted FB MARKETING vault. Save a Test Events code only when an explicit provider-test session is required.
Use dry_run first from FB MARKETING → Tracking & Attribution and verify a DRY_RUN_VALIDATED event plus append-only redacted attempt row without a provider request.
Use test mode only for an intentional Meta Test Events-tagged verification and return to disabled or dry_run afterward.
Use Retry now for bounded no-queue testing. Use Queue only after FBM queue readiness and the dedicated worker are available.
Do not enable production live order delivery or claim ERP-attributed sales until FBM-15 and later reporting stages are implemented.
Restore the routes/web.php fail-closed local-maintenance expression manually before production release.
Run the repository security gate in a complete deployment checkout before release; this uploaded partial source does not include the gate script.
```

## FBM-15 completion summary

Completed in the packaged source on 2026-06-10:

```text
Guarded tenant-local migration adds immutable order attribution, item snapshot and append-only reconciliation tables
FBM-14 conversion-event rows gain a nullable local order-attribution link
Legacy API, cart, shipping, COD, online-payment and admin lifecycle paths call guarded best-effort bridge entrypoints
Eligible ecommerce product-order status, edit and return paths reconcile newer product_orders safely
POS-only and unrelated manual product orders remain excluded by default
One tenant-local source-type plus ERP-order-ID unique boundary prevents duplicate attribution snapshots
Browser helper attaches an opaque Purchase event ID and supports explicit new-checkout rotation, confirmation reuse and clearing
Attributed confirmed orders in live mode create at most one linked CAPI Purchase candidate
Queue-ready deployments dispatch through the dedicated worker path; no-queue deployments retain manual Retry now delivery
Dependency, schema, provider and queue failures never invalidate ERP order placement or lifecycle updates
Tracking & Attribution adds privacy-safe aggregate totals, bounded recent rows and manual recent reconciliation
Unknown product-order lifecycle values surface a safe warning because the existing ERP source contains status-schema drift
No raw credentials, Pixel IDs, event IDs, URLs, UTM values, browser identifiers or customer matching values are rendered
```

Operator action after deployment:

```text
Back up tenant databases and APP_KEY securely.
Apply the tenant-aware FBM-15 migration to every tenant database.
Run php artisan route:list --path=fb-marketing after restoring artisan and Composer dependencies.
Grant fb_marketing_order_attribution_reconcile.update only to intended trusted operators.
Verify the deployed product_orders.order_status column definition and real lifecycle values in each tenant.
Wire newly initiated storefront checkout payloads through withCheckoutAttribution(payload, { newPurchase: true }).
Reuse getPurchaseEventId() for the browser Pixel Purchase event on confirmed orders and clear it afterwards.
Test legacy guest and authenticated checkout, eligible ecommerce product orders, cancellation and return flows.
Keep production APP_DEBUG=false and ALLOW_LOCAL_UNSAFE_WEB_MAINTENANCE_ROUTES=false.
```

## FBM-16 completion summary

Completed in the packaged source on 2026-06-11:

```text
New permission-gated /fb-marketing/attribution-reports page added for local ERP-attributed sales and ad spend reporting
FbmAttributionReportService added so controller remains thin and all aggregation stays tenant-local and DB-only
Summary cards expose spend, attributed order count, attributed revenue, AOV, cost per attributed order, ROAS and revenue minus spend
Reconciliation and freshness panels expose considered order counts, attribution state counts, latest reconciliation, latest Insights snapshot and latest order snapshot timestamps
Campaign, ad set and ad performance table combines stored ad-level Insights spend with FBM-15 attributed confirmed ERP order revenue
Attribution status and method breakdowns added without rendering raw URLs, UTMs, event IDs, Pixel IDs, browser identifiers, customer values, ciphertext or provider sync keys
Date, campaign, ad set, ad, evidence-status and attribution-method filters added with tenant-local option validation
Manual report-page reconciliation action reuses the existing bounded FBM-15 reconcileRecent path and remains permission-controlled and idempotent
New sidebar link and canonical permissions added: fb_marketing_attribution_reports_view and fb_marketing_attribution_reports_reconcile
No Meta API call, provider write request, campaign mutation, budget mutation, webhook or new queue requirement added
```

Operator action after deployment:

```text
Apply all FBM-07, FBM-08 and FBM-15 tenant migrations before relying on attribution reports.
Run php artisan route:list --path=fb-marketing after restoring artisan and Composer dependencies.
Grant fb_marketing_attribution_reports_view.read to intended reporting users.
Grant fb_marketing_attribution_reports_reconcile.update only to trusted operators.
Open FB MARKETING -> Attribution Reports through a mapped tenant domain and verify report totals against stored FBM Insights snapshots and FBM order-attribution rows.
Follow the manual checklist in docs/fb-marketing/stages/STAGE_16_SALES_ATTRIBUTION_DASHBOARD.md.
Run manual reconciliation only as a bounded fallback; keep normal checkout/lifecycle bridge hooks active.
Keep production APP_DEBUG=false and ALLOW_LOCAL_UNSAFE_WEB_MAINTENANCE_ROUTES=false.
```

## FBM-17 completion summary

Completed in the packaged source on 2026-06-11:

```text
Product Performance sidebar page added at /fb-marketing/product-performance
Dedicated FbmProductPerformanceReportService performs local tenant-database aggregation outside the controller
Report rendering uses FBM-15 attributed order item snapshots plus local ERP product, cost and stock fields only
Optional product_order_products cost data and FBM-11 catalog mappings enrich rows when available
Summary cards expose product count, attributed order count, quantity sold, revenue, purchase cost, gross profit, gross margin and risk product count
Stock risk breakdown and product table expose healthy, low_stock, out_of_stock, oversold_risk, catalog_unmapped, missing_cost and no_sales states
Date, product, stock-risk and catalog-status filters preserve query string state and validate product IDs against local tenant products
New fb_marketing_product_performance_view permission and sidebar link added
Raw order references, landing evidence, browser identifiers, customer values, Pixel IDs, provider IDs, event IDs, encrypted fields and HMACs remain hidden
No Meta API call, provider write request, campaign mutation, budget mutation, webhook, export or new queue requirement added
FBM-17 stage documentation includes a manual verification checklist for operator testing
```

Operator action after deployment:

```text
Apply all FBM-15 tenant migrations before relying on product performance reports.
Run php artisan route:list --path=fb-marketing/product-performance after restoring artisan, Composer dependencies and database connectivity.
Grant fb_marketing_product_performance_view.read to intended reporting users.
Open FB MARKETING -> Product Performance through a mapped tenant domain and verify report totals against fbm_order_attribution_items and ERP product cost/stock fields.
Follow the manual checklist in docs/fb-marketing/stages/STAGE_17_PRODUCT_LEVEL_SALES_PROFIT_AND_STOCK_RISK.md.
Keep production APP_DEBUG=false and ALLOW_LOCAL_UNSAFE_WEB_MAINTENANCE_ROUTES=false.
```

<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Meta Graph API policy
    |--------------------------------------------------------------------------
    |
    | Keep Meta API versions in one place. FB MARKETING code must never scatter
    | hardcoded Graph versions across controllers, jobs or views.
    |
    */
    'graph_api' => [
        'base_url' => 'https://graph.facebook.com',
        'trusted_hosts' => ['graph.facebook.com'],
        'default_version' => 'v25.0',
        'allowed_versions' => [
            'v25.0',
            'v24.0',
            'v23.0',
            'v22.0',
            'v21.0',
            'v20.0',
        ],
        'connect_timeout_seconds' => 5,
        'timeout_seconds' => 12,
    ],

    /*
    |--------------------------------------------------------------------------
    | Read-only connection health
    |--------------------------------------------------------------------------
    */
    'health_check' => [
        'rate_limit_per_minute' => 6,
        'required_scopes' => ['ads_read'],
        'expiry_warning_days' => 14,
        'history_limit' => 30,
    ],


    /*
    |--------------------------------------------------------------------------
    | Bounded read-only Meta asset discovery
    |--------------------------------------------------------------------------
    */
    'asset_discovery' => [
        'rate_limit_per_minute' => 2,
        'history_limit' => 20,
        'max_pages_per_edge' => 5,
        'max_records_per_edge' => 250,
        'per_page_limit' => 100,
        'max_business_accounts' => 30,
        'max_total_records' => 2500,
    ],

    /*
    |--------------------------------------------------------------------------
    | Bounded read-only Campaign Hierarchy sync
    |--------------------------------------------------------------------------
    */
    'campaign_hierarchy' => [
        'max_selected_ad_accounts' => 25,
        'max_pages_per_edge' => 5,
        'max_records_per_edge' => 500,
        'per_page_limit' => 100,
        'max_total_records' => 5000,
    ],


    /*
    |--------------------------------------------------------------------------
    | Bounded read-only Ads Insights snapshots
    |--------------------------------------------------------------------------
    |
    | Recent windows use bounded direct GET pagination. Larger historical
    | windows are queued as Meta async report jobs. Raw payloads stay in memory
    | only long enough to normalize allow-listed application-local daily snapshots.
    |
    */
    'insights' => [
        'levels' => ['account', 'campaign', 'adset', 'ad'],
        'initial_backfill_days' => 90,
        'rolling_refresh_days' => 14,
        'direct_refresh_chunk_days' => 7,
        'async_backfill_chunk_days' => 30,
        'max_selected_ad_accounts' => 25,
        'max_direct_reports_per_sync' => 40,
        'max_async_reports_per_sync' => 12,
        'max_pages_per_report' => 20,
        'max_rows_per_report' => 10000,
        'per_page_limit' => 500,
        'max_poll_attempts' => 12,
        'poll_backoff_seconds' => [60, 180, 300, 600],
        'history_limit' => 30,
        'action_types' => [
            'purchase',
            'omni_purchase',
            'offsite_conversion.fb_pixel_purchase',
            'lead',
            'link_click',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Isolated application product-feed configuration
    |--------------------------------------------------------------------------
    |
    | These values belong to the FB MARKETING module. Existing general_infos
    | Pixel settings remain a separate stable storefront flow and are neither
    | imported nor overwritten here.
    |
    */
    'product_feed' => [
        'cache_version' => 'v2',
        'default_cache_ttl_minutes' => 360,
    ],

    /*
    |--------------------------------------------------------------------------
    | Privacy-bounded landing attribution capture
    |--------------------------------------------------------------------------
    |
    | The browser sends allow-listed landing evidence to the application API.
    | Raw Facebook browser identifiers are encrypted at rest and never exposed
    | through browser-safe projections, configuration screens or logs.
    |
    */
    'landing_attribution' => [
        'rate_limit_per_minute' => 30,
        'default_retention_days' => 90,
        'min_retention_days' => 1,
        'max_retention_days' => 365,
        'max_input_url_length' => 4096,
        'max_stored_url_length' => 2048,
        'max_identifier_length' => 2048,
        'max_expired_prune_per_capture' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Consent-gated browser Pixel event contract
    |--------------------------------------------------------------------------
    |
    | The storefront fetches a secret-free runtime contract only after explicit
    | consent. Disabled and dry-run modes never load Meta's browser script.
    | Server-side CAPI delivery and durable event ledgers belong to FBM-14.
    |
    */
    'browser_pixel' => [
        'contract_version' => 'v1',
        'configuration_rate_limit_per_minute' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Durable server-side Meta Conversions API ledger
    |--------------------------------------------------------------------------
    |
    | Disabled is the fail-closed default. Dry-run validates encrypted immutable
    | snapshots without a provider request. Test mode requires an encrypted Test
    | Events code. Live mode never attaches a test code implicitly.
    |
    */
    'conversions_api' => [
        'tries' => 3,
        'timeout_seconds' => 60,
        'backoff_seconds' => [30, 120, 300],
        'lock_ttl_seconds' => 90,
        'max_contents' => 100,
        'event_history_limit' => 100,
        'attempt_history_limit' => 100,
        'diagnostic_rate_limit_per_minute' => 3,
        'retry_rate_limit_per_minute' => 6,
        'dispatch_rate_limit_per_minute' => 6,
    ],



    /*
    |--------------------------------------------------------------------------
    | ERP order attribution bridge
    |--------------------------------------------------------------------------
    |
    | Request-bound reconciliation remains deliberately bounded. Immutable
    | snapshots and append-only lifecycle evidence stay application-local. Provider
    | delivery is never allowed to make ERP order placement fail.
    |
    */
    'order_attribution' => [
        'history_limit' => 100,
        'manual_reconcile_limit' => 100,
        'manual_reconcile_rate_limit_per_minute' => 2,
    ],


    /*
    |--------------------------------------------------------------------------
    | Bounded read-only catalog product mapping sync
    |--------------------------------------------------------------------------
    |
    | Catalog products and product sets are mirrored locally through GET-only
    | edges. The request-bound fallback is deliberately smaller than queued
    | worker traversal and never uploads a feed or mutates Meta catalog assets.
    |
    */
    'catalog_sync' => [
        'max_selected_catalogs' => 10,
        'max_pages_per_edge' => 10,
        'max_records_per_edge' => 1000,
        'per_page_limit' => 100,
        'max_product_sets_per_catalog' => 250,
        'manual_enabled' => (bool) env('FBM_MANUAL_CATALOG_SYNC_ENABLED', true),
        'manual_rate_limit_per_minute' => 1,
        'manual_max_selected_catalogs' => 1,
        'history_limit' => 30,
        'page_item_limit' => 250,
        'diagnostic_scan_limit' => 5000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Bounded manual application sync fallback
    |--------------------------------------------------------------------------
    |
    | This fallback deliberately bypasses Laravel queue storage and workers so a
    | operator can test the integration from the application domain. It runs inside the
    | browser request, limits recent Insights to account-level snapshots and does
    | not dispatch historical async backfill jobs. Keep the queued path for
    | production-scale background refreshes.
    |
    */
    'manual_sync' => [
        'enabled' => (bool) env('FBM_MANUAL_SYNC_ENABLED', true),
        'rate_limit_per_minute' => 1,
        'recent_days' => 7,
        'max_selected_ad_accounts' => 3,
        'insights_levels' => ['account'],
        'max_direct_reports_per_run' => 6,
    ],


    /*
    |--------------------------------------------------------------------------
    | Bounded manual campaign/ad-set/ad drilldown refresh fallback
    |--------------------------------------------------------------------------
    |
    | This second request-bound action intentionally skips asset discovery and
    | refreshes a small recent performance drilldown window only. It never
    | dispatches queue jobs or historical async reports.
    |
    */
    'manual_drilldown_sync' => [
        'enabled' => (bool) env('FBM_MANUAL_DRILLDOWN_SYNC_ENABLED', true),
        'rate_limit_per_minute' => 1,
        'recent_days' => 3,
        'max_selected_ad_accounts' => 1,
        'insights_levels' => ['campaign', 'adset', 'ad'],
        'max_direct_reports_per_run' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Stored-snapshot performance review indicators
    |--------------------------------------------------------------------------
    */
    'performance_drilldowns' => [
        'max_entities_per_table' => 250,
    ],

    'performance_worklists' => [
        'ctr_review_threshold_percent' => 0.5,
        'minimum_impressions_for_ctr_review' => 100,
        'stale_after_days' => 2,
        'max_items' => 100,
        'api_error_history_limit' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Local ERP sales attribution report policy
    |--------------------------------------------------------------------------
    */
    'attribution_reports' => [
        'max_rows' => 1000,
    ],

    'product_performance' => [
        'max_rows' => 1000,
        'low_stock_threshold' => 5,
    ],

    'profitability' => [
        'max_rows' => 1000,
        'adjustment_rate_limit_per_minute' => 6,
    ],

    'boosting_jobs' => [
        'max_rows' => 1000,
        'write_rate_limit_per_minute' => 6,
    ],

    'reports' => [
        'recent_exports_limit' => 20,
        'export_rate_limit_per_minute' => 6,
    ],

    'creative_library' => [
        'max_rows' => 1000,
        'write_rate_limit_per_minute' => 6,
        'preflight_rate_limit_per_minute' => 12,
    ],

    'audiences' => [
        'max_rows' => 1000,
        'write_rate_limit_per_minute' => 6,
        'sync_rate_limit_per_minute' => 2,
        'sync_history_limit' => 20,
        'audit_history_limit' => 30,
        'max_ad_accounts_per_sync' => 10,
    ],

    'campaign_drafts' => [
        'max_rows' => 1000,
        'write_rate_limit_per_minute' => 6,
        'approval_action_rate_limit_per_minute' => 6,
        'approval_history_limit' => 30,
    ],

    'campaign_publish' => [
        'provider_writes_enabled' => false,
        'publish_rate_limit_per_minute' => 2,
        'history_limit' => 30,
        'step_history_limit' => 80,
        'tries' => 3,
        'timeout_seconds' => 180,
        'backoff_seconds' => [30, 120, 300],
        'lock_ttl_seconds' => 300,
    ],

    'operational_actions' => [
        'provider_writes_enabled' => false,
        'write_rate_limit_per_minute' => 4,
        'history_limit' => 40,
        'tries' => 3,
        'timeout_seconds' => 120,
        'backoff_seconds' => [30, 120, 300],
        'lock_ttl_seconds' => 180,
    ],

    'provider_writer' => [
        'required_scopes' => ['ads_read', 'ads_management'],
        'max_single_daily_budget_amount' => 5000,
        'max_single_lifetime_budget_amount' => 50000,
        'idempotency_ttl_hours' => 72,
        'max_retry_attempts' => 3,
        'rollback_plan_required' => true,
        'post_write_reconciliation_required' => true,
        'default_targeting_countries' => [],
        'allowed_publish_edges' => [
            'act_{ad_account_id}/campaigns',
            'act_{ad_account_id}/adsets',
            'act_{ad_account_id}/adcreatives',
            'act_{ad_account_id}/ads',
        ],
        'allowed_operational_targets' => ['campaign', 'ad_set', 'ad'],
        'allowed_operational_actions' => ['pause', 'resume', 'update_budget', 'update_schedule', 'safe_edit'],
    ],

    'lead_ads' => [
        'verify_rate_limit_per_minute' => 20,
        'callback_rate_limit_per_minute' => 60,
        'event_history_limit' => 40,
        'webhook_history_limit' => 30,
    ],

    'ad_account_webhooks' => [
        'verify_rate_limit_per_minute' => 20,
        'callback_rate_limit_per_minute' => 60,
        'webhook_history_limit' => 30,
    ],

    'alerts' => [
        'reconciliation_rate_limit_per_minute' => 2,
        'reconciliation_history_limit' => 20,
        'alert_history_limit' => 50,
        'token_expiry_warning_days' => 14,
        'daily_spend_alert_threshold' => 0,
        'poor_ctr_threshold_percent' => 0.5,
        'minimum_impressions_for_poor_ctr' => 100,
        'stock_risk_limit' => 20,
    ],

    'recommendations' => [
        'refresh_rate_limit_per_minute' => 2,
        'decision_rate_limit_per_minute' => 12,
        'history_limit' => 50,
        'max_alerts_per_refresh' => 100,
        'autonomous_execution_enabled' => false,
    ],


    /*
    |--------------------------------------------------------------------------
    | Dedicated queue and scheduler policy
    |--------------------------------------------------------------------------
    |
    | The FB MARKETING worker uses the primary application database. Provider
    | credentials must never be serialized into job payloads.
    |
    */
    'queue' => [
        'connection' => env('FBM_QUEUE_CONNECTION', 'fb-marketing'),
        'name' => env('FBM_QUEUE_NAME', 'fb-marketing'),
        'database_connection' => env('FBM_QUEUE_DATABASE_CONNECTION', env('DB_CONNECTION', 'mysql')),
        'tries' => 3,
        'backoff_seconds' => [30, 120, 300],
        'timeout_seconds' => 300,
        'retry_after_seconds' => 360,
        'lock_ttl_seconds' => 900,
        'history_limit' => 30,
        'api_log_history_limit' => 60,
        'rate_limit_per_minute' => 2,
    ],

    'scheduler' => [
        'enabled' => (bool) env('FBM_SCHEDULER_ENABLED', false),
    ],

];

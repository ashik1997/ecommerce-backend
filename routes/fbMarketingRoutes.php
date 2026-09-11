<?php

use App\Http\Controllers\Backend\FbMarketing\FbMarketingAssetController;
use App\Http\Controllers\Backend\FbMarketing\FbMarketingAttributionReportController;
use App\Http\Controllers\Backend\FbMarketing\FbMarketingAudienceController;
use App\Http\Controllers\Backend\FbMarketing\FbMarketingAlertController;
use App\Http\Controllers\Backend\FbMarketing\FbMarketingBoostingJobController;
use App\Http\Controllers\Backend\FbMarketing\FbMarketingCampaignDraftController;
use App\Http\Controllers\Backend\FbMarketing\FbMarketingConfigurationController;
use App\Http\Controllers\Backend\FbMarketing\FbMarketingCreativeLibraryController;
use App\Http\Controllers\Backend\FbMarketing\FbMarketingDashboardController;
use App\Http\Controllers\Backend\FbMarketing\FbMarketingLeadAdsController;
use App\Http\Controllers\Backend\FbMarketing\FbMarketingPerformanceController;
use App\Http\Controllers\Backend\FbMarketing\FbMarketingProductPerformanceController;
use App\Http\Controllers\Backend\FbMarketing\FbMarketingProfitabilityController;
use App\Http\Controllers\Backend\FbMarketing\FbMarketingProductsCatalogController;
use App\Http\Controllers\Backend\FbMarketing\FbMarketingRecommendationController;
use App\Http\Controllers\Backend\FbMarketing\FbMarketingReportController;
use App\Http\Controllers\Backend\FbMarketing\FbMarketingSyncController;
use App\Http\Controllers\Backend\FbMarketing\FbMarketingTrackingAttributionController;
use App\Http\Controllers\Backend\FbMarketing\FbMarketingUserManualController;
use Illuminate\Support\Facades\Route;

Route::prefix('fb-marketing')
    ->name('fbMarketing.')
    ->middleware([
        'auth',
        'CheckUserType',
        'DemoMode',
        'fb-marketing.sidebar.permission:fb_marketing_access,read',
    ])
    ->group(function () {
        Route::get('/', [FbMarketingDashboardController::class, 'index'])
            ->middleware('fb-marketing.sidebar.permission:fb_marketing_dashboard_view,read')
            ->name('index');

        Route::get('/dashboard', [FbMarketingDashboardController::class, 'index'])
            ->middleware('fb-marketing.sidebar.permission:fb_marketing_dashboard_view,read')
            ->name('dashboard');

        Route::get('/performance', [FbMarketingPerformanceController::class, 'index'])
            ->middleware('fb-marketing.sidebar.permission:fb_marketing_performance_view,read')
            ->name('performance.index');

        Route::get('/performance/campaigns/{campaign}', [FbMarketingPerformanceController::class, 'campaign'])
            ->where('campaign', '[0-9]+')
            ->middleware('fb-marketing.sidebar.permission:fb_marketing_performance_view,read')
            ->name('performance.campaigns.show');

        Route::get('/performance/ad-sets/{adSet}', [FbMarketingPerformanceController::class, 'adSet'])
            ->where('adSet', '[0-9]+')
            ->middleware('fb-marketing.sidebar.permission:fb_marketing_performance_view,read')
            ->name('performance.ad-sets.show');

        Route::get('/performance/ads/{ad}', [FbMarketingPerformanceController::class, 'ad'])
            ->where('ad', '[0-9]+')
            ->middleware('fb-marketing.sidebar.permission:fb_marketing_performance_view,read')
            ->name('performance.ads.show');

        Route::get('/products-catalog', [FbMarketingProductsCatalogController::class, 'index'])
            ->middleware('fb-marketing.sidebar.permission:fb_marketing_catalog_view,read')
            ->name('products-catalog.index');

        Route::get('/product-performance', [FbMarketingProductPerformanceController::class, 'index'])
            ->middleware('fb-marketing.sidebar.permission:fb_marketing_product_performance_view,read')
            ->name('product-performance.index');

        Route::get('/profitability', [FbMarketingProfitabilityController::class, 'index'])
            ->middleware('fb-marketing.sidebar.permission:fb_marketing_profitability_view,read')
            ->name('profitability.index');

        Route::post('/profitability/cost-adjustments', [FbMarketingProfitabilityController::class, 'storeAdjustment'])
            ->middleware([
                'fb-marketing.sidebar.permission:fb_marketing_profitability_view,read',
                'fb-marketing.sidebar.permission:fb_marketing_profitability_adjustment_manage,create',
                'throttle:' . max(1, (int) config('fb_marketing.profitability.adjustment_rate_limit_per_minute', 6)) . ',1',
            ])
            ->name('profitability.cost-adjustments.store');

        Route::get('/boosting-jobs', [FbMarketingBoostingJobController::class, 'index'])
            ->middleware('fb-marketing.sidebar.permission:fb_marketing_boosting_jobs_view,read')
            ->name('boosting-jobs.index');

        Route::post('/boosting-jobs', [FbMarketingBoostingJobController::class, 'store'])
            ->middleware([
                'fb-marketing.sidebar.permission:fb_marketing_boosting_jobs_view,read',
                'fb-marketing.sidebar.permission:fb_marketing_boosting_jobs_manage,create',
                'throttle:' . max(1, (int) config('fb_marketing.boosting_jobs.write_rate_limit_per_minute', 6)) . ',1',
            ])
            ->name('boosting-jobs.store');

        Route::post('/boosting-jobs/campaigns', [FbMarketingBoostingJobController::class, 'attachCampaign'])
            ->middleware([
                'fb-marketing.sidebar.permission:fb_marketing_boosting_jobs_view,read',
                'fb-marketing.sidebar.permission:fb_marketing_boosting_jobs_manage,create',
                'throttle:' . max(1, (int) config('fb_marketing.boosting_jobs.write_rate_limit_per_minute', 6)) . ',1',
            ])
            ->name('boosting-jobs.campaigns.store');

        Route::post('/boosting-jobs/payments', [FbMarketingBoostingJobController::class, 'storePayment'])
            ->middleware([
                'fb-marketing.sidebar.permission:fb_marketing_boosting_jobs_view,read',
                'fb-marketing.sidebar.permission:fb_marketing_boosting_job_ledger_manage,create',
                'throttle:' . max(1, (int) config('fb_marketing.boosting_jobs.write_rate_limit_per_minute', 6)) . ',1',
            ])
            ->name('boosting-jobs.payments.store');

        Route::post('/boosting-jobs/costs', [FbMarketingBoostingJobController::class, 'storeCost'])
            ->middleware([
                'fb-marketing.sidebar.permission:fb_marketing_boosting_jobs_view,read',
                'fb-marketing.sidebar.permission:fb_marketing_boosting_job_ledger_manage,create',
                'throttle:' . max(1, (int) config('fb_marketing.boosting_jobs.write_rate_limit_per_minute', 6)) . ',1',
            ])
            ->name('boosting-jobs.costs.store');

        Route::get('/audiences', [FbMarketingAudienceController::class, 'index'])
            ->middleware('fb-marketing.sidebar.permission:fb_marketing_audiences_view,read')
            ->name('audiences.index');

        Route::post('/audiences', [FbMarketingAudienceController::class, 'store'])
            ->middleware([
                'fb-marketing.sidebar.permission:fb_marketing_audiences_view,read',
                'fb-marketing.sidebar.permission:fb_marketing_audience_manage,create',
                'throttle:' . max(1, (int) config('fb_marketing.audiences.write_rate_limit_per_minute', 6)) . ',1',
            ])
            ->name('audiences.store');

        Route::post('/audiences/sync/{connection}', [FbMarketingAudienceController::class, 'sync'])
            ->where('connection', '[0-9]+')
            ->middleware([
                'fb-marketing.sidebar.permission:fb_marketing_audiences_view,read',
                'fb-marketing.sidebar.permission:fb_marketing_audience_sync_run,read',
                'throttle:' . max(1, (int) config('fb_marketing.audiences.sync_rate_limit_per_minute', 2)) . ',1',
            ])
            ->name('audiences.sync');

        Route::patch('/audiences/{audience}/selection', [FbMarketingAudienceController::class, 'updateAudienceSelection'])
            ->where('audience', '[0-9]+')
            ->middleware([
                'fb-marketing.sidebar.permission:fb_marketing_audiences_view,read',
                'fb-marketing.sidebar.permission:fb_marketing_audience_selection_manage,update',
            ])
            ->name('audiences.selection.update');

        Route::patch('/audiences/product-sets/{productSet}/selection', [FbMarketingAudienceController::class, 'updateProductSetSelection'])
            ->where('productSet', '[0-9]+')
            ->middleware([
                'fb-marketing.sidebar.permission:fb_marketing_audiences_view,read',
                'fb-marketing.sidebar.permission:fb_marketing_audience_selection_manage,update',
            ])
            ->name('audiences.product-sets.selection.update');

        Route::get('/campaign-drafts', [FbMarketingCampaignDraftController::class, 'index'])
            ->middleware('fb-marketing.sidebar.permission:fb_marketing_campaign_drafts_view,read')
            ->name('campaign-drafts.index');

        Route::get('/lead-ads', [FbMarketingLeadAdsController::class, 'index'])
            ->middleware('fb-marketing.sidebar.permission:fb_marketing_lead_ads_view,read')
            ->name('lead-ads.index');

        Route::get('/alerts', [FbMarketingAlertController::class, 'index'])
            ->middleware('fb-marketing.sidebar.permission:fb_marketing_alerts_view,read')
            ->name('alerts.index');

        Route::post('/alerts/reconcile', [FbMarketingAlertController::class, 'reconcile'])
            ->middleware([
                'fb-marketing.sidebar.permission:fb_marketing_alerts_view,read',
                'fb-marketing.sidebar.permission:fb_marketing_alert_reconcile,update',
                'throttle:' . max(1, (int) config('fb_marketing.alerts.reconciliation_rate_limit_per_minute', 2)) . ',1',
            ])
            ->name('alerts.reconcile');

        Route::get('/rules-recommendations', [FbMarketingRecommendationController::class, 'index'])
            ->middleware('fb-marketing.sidebar.permission:fb_marketing_recommendations_view,read')
            ->name('recommendations.index');

        Route::post('/rules-recommendations/refresh', [FbMarketingRecommendationController::class, 'refresh'])
            ->middleware([
                'fb-marketing.sidebar.permission:fb_marketing_recommendations_view,read',
                'fb-marketing.sidebar.permission:fb_marketing_recommendation_refresh,update',
                'throttle:' . max(1, (int) config('fb_marketing.recommendations.refresh_rate_limit_per_minute', 2)) . ',1',
            ])
            ->name('recommendations.refresh');

        Route::post('/rules-recommendations/{uuid}/approve', [FbMarketingRecommendationController::class, 'approve'])
            ->where('uuid', '[0-9a-fA-F-]{36}')
            ->middleware([
                'fb-marketing.sidebar.permission:fb_marketing_recommendations_view,read',
                'fb-marketing.sidebar.permission:fb_marketing_recommendation_decide,update',
                'throttle:' . max(1, (int) config('fb_marketing.recommendations.decision_rate_limit_per_minute', 12)) . ',1',
            ])
            ->name('recommendations.approve');

        Route::post('/rules-recommendations/{uuid}/dismiss', [FbMarketingRecommendationController::class, 'dismiss'])
            ->where('uuid', '[0-9a-fA-F-]{36}')
            ->middleware([
                'fb-marketing.sidebar.permission:fb_marketing_recommendations_view,read',
                'fb-marketing.sidebar.permission:fb_marketing_recommendation_decide,update',
                'throttle:' . max(1, (int) config('fb_marketing.recommendations.decision_rate_limit_per_minute', 12)) . ',1',
            ])
            ->name('recommendations.dismiss');

        Route::post('/campaign-drafts', [FbMarketingCampaignDraftController::class, 'store'])
            ->middleware([
                'fb-marketing.sidebar.permission:fb_marketing_campaign_drafts_view,read',
                'fb-marketing.sidebar.permission:fb_marketing_campaign_draft_manage,create',
                'throttle:' . max(1, (int) config('fb_marketing.campaign_drafts.write_rate_limit_per_minute', 6)) . ',1',
            ])
            ->name('campaign-drafts.store');

        Route::post('/campaign-drafts/{draft}/submit', [FbMarketingCampaignDraftController::class, 'submit'])
            ->where('draft', '[0-9]+')
            ->middleware([
                'fb-marketing.sidebar.permission:fb_marketing_campaign_drafts_view,read',
                'fb-marketing.sidebar.permission:fb_marketing_campaign_draft_submit,update',
                'throttle:' . max(1, (int) config('fb_marketing.campaign_drafts.approval_action_rate_limit_per_minute', 6)) . ',1',
            ])
            ->name('campaign-drafts.submit');

        Route::post('/campaign-drafts/{draft}/approve', [FbMarketingCampaignDraftController::class, 'approve'])
            ->where('draft', '[0-9]+')
            ->middleware([
                'fb-marketing.sidebar.permission:fb_marketing_campaign_drafts_view,read',
                'fb-marketing.sidebar.permission:fb_marketing_campaign_draft_approve,update',
                'throttle:' . max(1, (int) config('fb_marketing.campaign_drafts.approval_action_rate_limit_per_minute', 6)) . ',1',
            ])
            ->name('campaign-drafts.approve');

        Route::post('/campaign-drafts/{draft}/reject', [FbMarketingCampaignDraftController::class, 'reject'])
            ->where('draft', '[0-9]+')
            ->middleware([
                'fb-marketing.sidebar.permission:fb_marketing_campaign_drafts_view,read',
                'fb-marketing.sidebar.permission:fb_marketing_campaign_draft_approve,update',
                'throttle:' . max(1, (int) config('fb_marketing.campaign_drafts.approval_action_rate_limit_per_minute', 6)) . ',1',
            ])
            ->name('campaign-drafts.reject');

        Route::post('/campaign-drafts/{draft}/publish', [FbMarketingCampaignDraftController::class, 'publish'])
            ->where('draft', '[0-9]+')
            ->middleware([
                'fb-marketing.sidebar.permission:fb_marketing_campaign_drafts_view,read',
                'fb-marketing.sidebar.permission:fb_marketing_campaign_publish_create,create',
                'throttle:' . max(1, (int) config('fb_marketing.campaign_publish.publish_rate_limit_per_minute', 2)) . ',1',
            ])
            ->name('campaign-drafts.publish');

        Route::post('/campaign-drafts/{draft}/operational-actions', [FbMarketingCampaignDraftController::class, 'operationalAction'])
            ->where('draft', '[0-9]+')
            ->middleware([
                'fb-marketing.sidebar.permission:fb_marketing_campaign_drafts_view,read',
                'fb-marketing.sidebar.permission:fb_marketing_campaign_operational_action_create,create',
                'throttle:' . max(1, (int) config('fb_marketing.operational_actions.write_rate_limit_per_minute', 4)) . ',1',
            ])
            ->name('campaign-drafts.operational-actions.store');

        Route::get('/reports', [FbMarketingReportController::class, 'index'])
            ->middleware('fb-marketing.sidebar.permission:fb_marketing_reports_view,read')
            ->name('reports.index');

        Route::get('/creative-library', [FbMarketingCreativeLibraryController::class, 'index'])
            ->middleware('fb-marketing.sidebar.permission:fb_marketing_creative_library_view,read')
            ->name('creative-library.index');

        Route::post('/creative-library/assets', [FbMarketingCreativeLibraryController::class, 'store'])
            ->middleware([
                'fb-marketing.sidebar.permission:fb_marketing_creative_library_view,read',
                'fb-marketing.sidebar.permission:fb_marketing_creative_asset_manage,create',
                'throttle:' . max(1, (int) config('fb_marketing.creative_library.write_rate_limit_per_minute', 6)) . ',1',
            ])
            ->name('creative-library.assets.store');

        Route::post('/creative-library/assets/{asset}/preflight', [FbMarketingCreativeLibraryController::class, 'preflight'])
            ->where('asset', '[0-9]+')
            ->middleware([
                'fb-marketing.sidebar.permission:fb_marketing_creative_library_view,read',
                'fb-marketing.sidebar.permission:fb_marketing_creative_preflight_run,create',
                'throttle:' . max(1, (int) config('fb_marketing.creative_library.preflight_rate_limit_per_minute', 12)) . ',1',
            ])
            ->name('creative-library.assets.preflight');

        Route::post('/reports/exports', [FbMarketingReportController::class, 'storeExport'])
            ->middleware([
                'fb-marketing.sidebar.permission:fb_marketing_reports_view,read',
                'fb-marketing.sidebar.permission:fb_marketing_report_export_create,create',
                'throttle:' . max(1, (int) config('fb_marketing.reports.export_rate_limit_per_minute', 6)) . ',1',
            ])
            ->name('reports.exports.store');

        Route::get('/reports/exports/{export}/{token}/download', [FbMarketingReportController::class, 'download'])
            ->where('export', '[0-9]+')
            ->where('token', '[A-Za-z0-9]{40,80}')
            ->middleware([
                'fb-marketing.sidebar.permission:fb_marketing_reports_view,read',
                'fb-marketing.sidebar.permission:fb_marketing_report_export_download,read',
            ])
            ->name('reports.download');

        Route::patch('/products-catalog/mappings/{mapping}', [FbMarketingProductsCatalogController::class, 'updateMapping'])
            ->where('mapping', '[0-9]+')
            ->middleware([
                'fb-marketing.sidebar.permission:fb_marketing_catalog_view,read',
                'fb-marketing.sidebar.permission:fb_marketing_catalog_mapping_manage,update',
            ])
            ->name('products-catalog.mappings.update');

        Route::get('/tracking-attribution', [FbMarketingTrackingAttributionController::class, 'index'])
            ->middleware('fb-marketing.sidebar.permission:fb_marketing_tracking_view,read')
            ->name('tracking-attribution.index');

        Route::get('/attribution-reports', [FbMarketingAttributionReportController::class, 'index'])
            ->middleware('fb-marketing.sidebar.permission:fb_marketing_attribution_reports_view,read')
            ->name('attribution-reports.index');

        Route::post('/attribution-reports/reconcile', [FbMarketingAttributionReportController::class, 'reconcile'])
            ->middleware([
                'fb-marketing.sidebar.permission:fb_marketing_attribution_reports_view,read',
                'fb-marketing.sidebar.permission:fb_marketing_attribution_reports_reconcile,update',
                'throttle:' . max(1, (int) config('fb_marketing.order_attribution.manual_reconcile_rate_limit_per_minute', 2)) . ',1',
            ])
            ->name('attribution-reports.reconcile');

        Route::post('/tracking-attribution/orders/reconcile-recent', [FbMarketingTrackingAttributionController::class, 'reconcileRecentOrders'])
            ->middleware([
                'fb-marketing.sidebar.permission:fb_marketing_tracking_view,read',
                'fb-marketing.sidebar.permission:fb_marketing_order_attribution_reconcile,update',
                'throttle:' . max(1, (int) config('fb_marketing.order_attribution.manual_reconcile_rate_limit_per_minute', 2)) . ',1',
            ])
            ->name('tracking-attribution.orders.reconcile-recent');

        Route::post('/tracking-attribution/diagnostics', [FbMarketingTrackingAttributionController::class, 'createDiagnostic'])
            ->middleware([
                'fb-marketing.sidebar.permission:fb_marketing_tracking_view,read',
                'fb-marketing.sidebar.permission:fb_marketing_capi_diagnostic_run,create',
                'throttle:' . max(1, (int) config('fb_marketing.conversions_api.diagnostic_rate_limit_per_minute', 3)) . ',1',
            ])
            ->name('tracking-attribution.diagnostics.store');

        Route::post('/tracking-attribution/events/{eventUuid}/retry-now', [FbMarketingTrackingAttributionController::class, 'retryNow'])
            ->where('eventUuid', '[0-9a-fA-F-]{36}')
            ->middleware([
                'fb-marketing.sidebar.permission:fb_marketing_tracking_view,read',
                'fb-marketing.sidebar.permission:fb_marketing_capi_event_retry,update',
                'throttle:' . max(1, (int) config('fb_marketing.conversions_api.retry_rate_limit_per_minute', 6)) . ',1',
            ])
            ->name('tracking-attribution.events.retry-now');

        Route::post('/tracking-attribution/events/{eventUuid}/dispatch', [FbMarketingTrackingAttributionController::class, 'dispatchEvent'])
            ->where('eventUuid', '[0-9a-fA-F-]{36}')
            ->middleware([
                'fb-marketing.sidebar.permission:fb_marketing_tracking_view,read',
                'fb-marketing.sidebar.permission:fb_marketing_capi_event_dispatch,create',
                'throttle:' . max(1, (int) config('fb_marketing.conversions_api.dispatch_rate_limit_per_minute', 6)) . ',1',
            ])
            ->name('tracking-attribution.events.dispatch');

        Route::get('/configuration', [FbMarketingConfigurationController::class, 'index'])
            ->middleware('fb-marketing.sidebar.permission:fb_marketing_configuration_view,read')
            ->name('configuration.index');

        Route::get('/configuration/setup-wizard', [FbMarketingConfigurationController::class, 'setupWizard'])
            ->middleware('fb-marketing.sidebar.permission:fb_marketing_setup_wizard_view,read')
            ->name('configuration.setup-wizard');

        Route::patch('/configuration/module-settings', [FbMarketingConfigurationController::class, 'updateModuleSettings'])
            ->middleware([
                'fb-marketing.sidebar.permission:fb_marketing_configuration_view,read',
                'fb-marketing.sidebar.permission:fb_marketing_configuration_manage,update',
            ])
            ->name('configuration.module-settings.update');

        Route::post('/configuration/connections', [FbMarketingConfigurationController::class, 'store'])
            ->middleware([
                'fb-marketing.sidebar.permission:fb_marketing_configuration_view,read',
                'fb-marketing.sidebar.permission:fb_marketing_credentials_manage,create',
            ])
            ->name('configuration.connections.store');

        Route::put('/configuration/connections/{connection}', [FbMarketingConfigurationController::class, 'update'])
            ->where('connection', '[0-9]+')
            ->middleware([
                'fb-marketing.sidebar.permission:fb_marketing_configuration_view,read',
                'fb-marketing.sidebar.permission:fb_marketing_credentials_manage,update',
            ])
            ->name('configuration.connections.update');

        Route::patch('/configuration/connections/{connection}/status', [FbMarketingConfigurationController::class, 'updateStatus'])
            ->where('connection', '[0-9]+')
            ->middleware([
                'fb-marketing.sidebar.permission:fb_marketing_configuration_view,read',
                'fb-marketing.sidebar.permission:fb_marketing_credentials_manage,update',
            ])
            ->name('configuration.connections.status');

        Route::post('/configuration/connections/{connection}/test', [FbMarketingConfigurationController::class, 'testConnection'])
            ->where('connection', '[0-9]+')
            ->middleware([
                'fb-marketing.sidebar.permission:fb_marketing_configuration_view,read',
                'fb-marketing.sidebar.permission:fb_marketing_connection_health_test,read',
                'throttle:' . max(1, (int) config('fb_marketing.health_check.rate_limit_per_minute', 6)) . ',1',
            ])
            ->name('configuration.connections.test');

        Route::post('/configuration/connections/{connection}/sync', [FbMarketingSyncController::class, 'queueSync'])
            ->where('connection', '[0-9]+')
            ->middleware([
                'fb-marketing.sidebar.permission:fb_marketing_configuration_view,read',
                'fb-marketing.sidebar.permission:fb_marketing_sync_run,read',
                'throttle:' . max(1, (int) config('fb_marketing.queue.rate_limit_per_minute', 2)) . ',1',
            ])
            ->name('configuration.connections.sync');

        Route::post('/configuration/connections/{connection}/sync-now', [FbMarketingSyncController::class, 'runManualNow'])
            ->where('connection', '[0-9]+')
            ->middleware([
                'fb-marketing.sidebar.permission:fb_marketing_configuration_view,read',
                'fb-marketing.sidebar.permission:fb_marketing_sync_run,read',
                'throttle:' . max(1, (int) config('fb_marketing.manual_sync.rate_limit_per_minute', 1)) . ',1',
            ])
            ->name('configuration.connections.sync-now');

        Route::post('/configuration/connections/{connection}/refresh-catalog-now', [FbMarketingProductsCatalogController::class, 'refreshCatalogNow'])
            ->where('connection', '[0-9]+')
            ->middleware([
                'fb-marketing.sidebar.permission:fb_marketing_configuration_view,read',
                'fb-marketing.sidebar.permission:fb_marketing_catalog_sync_run,read',
                'throttle:' . max(1, (int) config('fb_marketing.catalog_sync.manual_rate_limit_per_minute', 1)) . ',1',
            ])
            ->name('configuration.connections.refresh-catalog-now');

        Route::post('/configuration/connections/{connection}/refresh-drilldowns-now', [FbMarketingSyncController::class, 'runManualDrilldownsNow'])
            ->where('connection', '[0-9]+')
            ->middleware([
                'fb-marketing.sidebar.permission:fb_marketing_configuration_view,read',
                'fb-marketing.sidebar.permission:fb_marketing_sync_run,read',
                'throttle:' . max(1, (int) config('fb_marketing.manual_drilldown_sync.rate_limit_per_minute', 1)) . ',1',
            ])
            ->name('configuration.connections.refresh-drilldowns-now');

        // Backward-compatible FBM-04 URL. It now queues the same application-safe
        // read-only sync instead of performing provider calls in the request.
        Route::post('/configuration/connections/{connection}/discover-assets', [FbMarketingSyncController::class, 'queueSync'])
            ->where('connection', '[0-9]+')
            ->middleware([
                'fb-marketing.sidebar.permission:fb_marketing_configuration_view,read',
                'fb-marketing.sidebar.permission:fb_marketing_sync_run,read',
                'throttle:' . max(1, (int) config('fb_marketing.queue.rate_limit_per_minute', 2)) . ',1',
            ])
            ->name('configuration.connections.discover-assets');

        Route::patch('/assets/{assetType}/{asset}/selection', [FbMarketingAssetController::class, 'updateSelection'])
            ->where('assetType', 'business_accounts|ad_accounts|pages|pixels|datasets|catalogs|instagram_accounts')
            ->where('asset', '[0-9]+')
            ->middleware([
                'fb-marketing.sidebar.permission:fb_marketing_dashboard_view,read',
                'fb-marketing.sidebar.permission:fb_marketing_asset_selection_manage,update',
            ])
            ->name('assets.selection');

        Route::get('/user-manual', [FbMarketingUserManualController::class, 'index'])
            ->middleware('fb-marketing.sidebar.permission:fb_marketing_user_manual_view,read')
            ->name('user-manual.index');

        Route::get('/user-manual/{locale}', [FbMarketingUserManualController::class, 'index'])
            ->where('locale', 'bn|en')
            ->middleware('fb-marketing.sidebar.permission:fb_marketing_user_manual_view,read')
            ->name('user-manual.locale');
    });

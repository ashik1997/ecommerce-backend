<?php

namespace App\Http\Controllers\Backend\FbMarketing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\FbMarketing\StoreFbmConnectionRequest;
use App\Http\Requests\Backend\FbMarketing\UpdateFbmConnectionRequest;
use App\Http\Requests\Backend\FbMarketing\UpdateFbmConnectionStatusRequest;
use App\Http\Requests\Backend\FbMarketing\UpdateFbmModuleSettingsRequest;
use App\Models\FbMarketing\FbmApiRequestLog;
use App\Models\FbMarketing\FbmAssetDiscoveryRun;
use App\Models\FbMarketing\FbmConnection;
use App\Models\FbMarketing\FbmConnectionHealthCheck;
use App\Models\FbMarketing\FbmConnectionSecretAudit;
use App\Models\FbMarketing\FbmSyncRun;
use App\Services\FbMarketing\FbmAssetDiscoveryService;
use App\Services\FbMarketing\FbmBrowserPixelContractService;
use App\Services\FbMarketing\FbmConversionEventReadinessService;
use App\Services\FbMarketing\FbmCampaignHierarchySyncService;
use App\Services\FbMarketing\FbmCatalogProductMappingSyncService;
use App\Services\FbMarketing\FbmConnectionHealthService;
use App\Services\FbMarketing\FbmConfigurationService;
use App\Services\FbMarketing\FbmFeedDiagnosticsService;
use App\Services\FbMarketing\FbmCredentialVaultService;
use App\Services\FbMarketing\FbmGraphApiVersionPolicy;
use App\Services\FbMarketing\FbmInsightsSnapshotService;
use App\Services\FbMarketing\FbmLandingAttributionService;
use App\Services\FbMarketing\FbmProviderWriterLaunchChecklistService;
use App\Services\FbMarketing\FbmProviderWriterReadinessService;
use App\Services\FbMarketing\FbmQueueReadinessService;
use App\Services\RoleSidebarPermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class FbMarketingConfigurationController extends Controller
{
    public function index(
        Request $request,
        RoleSidebarPermissionService $permissionService,
        FbmGraphApiVersionPolicy $versionPolicy,
        FbmConfigurationService $configurationService,
        FbmFeedDiagnosticsService $feedDiagnosticsService,
        FbmCatalogProductMappingSyncService $catalogMappingService,
        FbmQueueReadinessService $queueReadinessService,
        FbmInsightsSnapshotService $insightsSnapshotService,
        FbmLandingAttributionService $landingAttributionService,
        FbmBrowserPixelContractService $browserPixelContractService,
        FbmConversionEventReadinessService $conversionEventReadinessService,
        FbmProviderWriterReadinessService $providerWriterReadinessService,
        FbmProviderWriterLaunchChecklistService $providerWriterLaunchChecklistService
    ): View {
        $vaultReady = $this->vaultReady();
        $healthReady = $this->healthLedgerReady();
        $assetDiscoveryReady = FbmAssetDiscoveryService::schemaReady();
        $hierarchyReady = FbmCampaignHierarchySyncService::schemaReady();
        $syncReadiness = $queueReadinessService->currentSummary();
        $syncReady = (bool) $syncReadiness['ready'];
        $insightsSummary = $insightsSnapshotService->operationalSummary();
        $manualSyncEnabled = (bool) config('fb_marketing.manual_sync.enabled', true);
        $manualSyncReady = $manualSyncEnabled
            && Schema::hasTable('fbm_sync_runs')
            && $assetDiscoveryReady
            && $hierarchyReady
            && (bool) $insightsSummary['schema_ready'];
        $manualDrilldownSyncEnabled = (bool) config('fb_marketing.manual_drilldown_sync.enabled', true);
        $manualDrilldownSyncReady = $manualDrilldownSyncEnabled
            && Schema::hasTable('fbm_sync_runs')
            && $hierarchyReady
            && (bool) $insightsSummary['schema_ready'];
        $connections = collect();
        $audits = collect();
        $healthChecks = collect();
        $discoveryRuns = collect();
        $syncRuns = collect();
        $apiRequestLogs = collect();
        $moduleSettings = $configurationService->current();
        $feedDiagnostics = $feedDiagnosticsService->summary();
        $catalogSummary = $catalogMappingService->operationalSummary();
        $landingAttributionSummary = $landingAttributionService->operationalSummary();
        $browserPixelSummary = $browserPixelContractService->operationalSummary();
        $capiSummary = $conversionEventReadinessService->operationalSummary();
        $providerWriterSummary = $providerWriterReadinessService->currentSummary();
        $providerWriterLaunchChecklist = $providerWriterLaunchChecklistService->build($providerWriterSummary, $syncReadiness);
        $manualCatalogSyncEnabled = (bool) config('fb_marketing.catalog_sync.manual_enabled', true);
        $manualCatalogSyncReady = $manualCatalogSyncEnabled && (bool) $catalogSummary['schema_ready'];
        $manualSyncReady = $manualSyncReady && (bool) $catalogSummary['schema_ready'];

        if ($vaultReady) {
            $query = FbmConnection::query();

            if ($healthReady) {
                $query->with('latestHealthCheck');
            }
            if ($assetDiscoveryReady) {
                $query->with('latestAssetDiscoveryRun');
            }
            if (Schema::hasTable('fbm_sync_runs')) {
                $query->with('latestSyncRun');
            }

            $connections = $query
                ->orderByDesc('is_active')
                ->orderBy('connection_name')
                ->get()
                ->map(fn(FbmConnection $connection): array => $connection->toSafeSummary());

            $audits = FbmConnectionSecretAudit::query()
                ->with('connection:id,connection_name')
                ->orderByDesc('created_at')
                ->limit(30)
                ->get()
                ->map(fn(FbmConnectionSecretAudit $audit): array => $audit->toSafeSummary());
        }

        if ($healthReady) {
            $healthChecks = FbmConnectionHealthCheck::query()
                ->with('connection:id,connection_name')
                ->orderByDesc('checked_at')
                ->orderByDesc('id')
                ->limit(max(1, (int) config('fb_marketing.health_check.history_limit', 30)))
                ->get()
                ->map(fn(FbmConnectionHealthCheck $check): array => $check->toSafeSummary());
        }

        if ($assetDiscoveryReady) {
            $discoveryRuns = FbmAssetDiscoveryRun::query()
                ->with('connection:id,connection_name')
                ->orderByDesc('completed_at')
                ->orderByDesc('id')
                ->limit(max(1, (int) config('fb_marketing.asset_discovery.history_limit', 20)))
                ->get()
                ->map(fn(FbmAssetDiscoveryRun $run): array => $run->toSafeSummary());
        }

        if (Schema::hasTable('fbm_sync_runs')) {
            $syncRuns = FbmSyncRun::query()
                ->with('connection:id,connection_name')
                ->orderByDesc('requested_at')
                ->orderByDesc('id')
                ->limit(max(1, (int) config('fb_marketing.queue.history_limit', 30)))
                ->get()
                ->map(fn(FbmSyncRun $run): array => $run->toSafeSummary());
        }

        if (Schema::hasTable('fbm_api_request_logs')) {
            $apiRequestLogs = FbmApiRequestLog::query()
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit(max(1, (int) config('fb_marketing.queue.api_log_history_limit', 60)))
                ->get()
                ->map(fn(FbmApiRequestLog $log): array => $log->toSafeSummary());
        }

        return view('backend.fb-marketing.configuration', [
            'vaultReady' => $vaultReady,
            'healthReady' => $healthReady,
            'assetDiscoveryReady' => $assetDiscoveryReady,
            'hierarchyReady' => $hierarchyReady,
            'syncReady' => $syncReady,
            'syncReadiness' => $syncReadiness,
            'manualSyncEnabled' => $manualSyncEnabled,
            'manualSyncReady' => $manualSyncReady,
            'manualDrilldownSyncEnabled' => $manualDrilldownSyncEnabled,
            'manualDrilldownSyncReady' => $manualDrilldownSyncReady,
            'connections' => $connections,
            'audits' => $audits,
            'healthChecks' => $healthChecks,
            'discoveryRuns' => $discoveryRuns,
            'syncRuns' => $syncRuns,
            'apiRequestLogs' => $apiRequestLogs,
            'moduleSettings' => $moduleSettings,
            'feedDiagnostics' => $feedDiagnostics,
            'catalogSummary' => $catalogSummary,
            'landingAttributionSummary' => $landingAttributionSummary,
            'browserPixelSummary' => $browserPixelSummary,
            'browserPixelModes' => FbmBrowserPixelContractService::MODES,
            'capiSummary' => $capiSummary,
            'providerWriterSummary' => $providerWriterSummary,
            'providerWriterLaunchChecklist' => $providerWriterLaunchChecklist,
            'serverCapiModes' => FbmConversionEventReadinessService::MODES,
            'manualCatalogSyncEnabled' => $manualCatalogSyncEnabled,
            'manualCatalogSyncReady' => $manualCatalogSyncReady,
            'insightsSummary' => $insightsSummary,
            'credentialModes' => FbmConnection::CREDENTIAL_MODES,
            'graphApiVersions' => $versionPolicy->allowedVersions(),
            'defaultGraphApiVersion' => $versionPolicy->defaultVersion(),
            'canViewPerformance' => $permissionService->userCan($request->user(), 'fb_marketing_performance_view', 'read'),
            'canViewCatalog' => $permissionService->userCan($request->user(), 'fb_marketing_catalog_view', 'read'),
            'canViewTracking' => $permissionService->userCan($request->user(), 'fb_marketing_tracking_view', 'read'),
            'canManageModuleSettings' => $permissionService->userCan($request->user(), 'fb_marketing_configuration_manage', 'update'),
            'canCreateCredentials' => $permissionService->userCan($request->user(), 'fb_marketing_credentials_manage', 'create'),
            'canUpdateCredentials' => $permissionService->userCan($request->user(), 'fb_marketing_credentials_manage', 'update'),
            'canTestConnections' => $permissionService->userCan($request->user(), 'fb_marketing_connection_health_test', 'read'),
            'canRunAssetDiscovery' => $permissionService->userCan($request->user(), 'fb_marketing_asset_discovery_run', 'read'),
            'canRunSync' => $permissionService->userCan($request->user(), 'fb_marketing_sync_run', 'read'),
            'canRunCatalogSync' => $permissionService->userCan($request->user(), 'fb_marketing_catalog_sync_run', 'read'),
        ]);
    }

    public function setupWizard(
        Request $request,
        RoleSidebarPermissionService $permissionService,
        FbmFeedDiagnosticsService $feedDiagnosticsService,
        FbmCatalogProductMappingSyncService $catalogMappingService,
        FbmQueueReadinessService $queueReadinessService,
        FbmInsightsSnapshotService $insightsSnapshotService,
        FbmLandingAttributionService $landingAttributionService,
        FbmBrowserPixelContractService $browserPixelContractService,
        FbmConversionEventReadinessService $conversionEventReadinessService,
        FbmProviderWriterReadinessService $providerWriterReadinessService,
        FbmProviderWriterLaunchChecklistService $providerWriterLaunchChecklistService
    ): View
    {
        $vaultReady = $this->vaultReady();
        $healthReady = $this->healthLedgerReady();
        $assetDiscoveryReady = FbmAssetDiscoveryService::schemaReady();
        $hierarchyReady = FbmCampaignHierarchySyncService::schemaReady();
        $syncReadiness = $queueReadinessService->currentSummary();
        $syncReady = (bool) $syncReadiness['ready'];
        $insightsSummary = $insightsSnapshotService->operationalSummary();
        $manualSyncEnabled = (bool) config('fb_marketing.manual_sync.enabled', true);
        $manualSyncReady = $manualSyncEnabled
            && Schema::hasTable('fbm_sync_runs')
            && $assetDiscoveryReady
            && $hierarchyReady
            && (bool) $insightsSummary['schema_ready'];
        $manualDrilldownSyncEnabled = (bool) config('fb_marketing.manual_drilldown_sync.enabled', true);
        $manualDrilldownSyncReady = $manualDrilldownSyncEnabled
            && Schema::hasTable('fbm_sync_runs')
            && $hierarchyReady
            && (bool) $insightsSummary['schema_ready'];
        $latestDiscoveryRun = $assetDiscoveryReady
            ? FbmAssetDiscoveryRun::query()->orderByDesc('completed_at')->orderByDesc('id')->first()
            : null;
        $catalogSummary = $catalogMappingService->operationalSummary();
        $landingAttributionSummary = $landingAttributionService->operationalSummary();
        $browserPixelSummary = $browserPixelContractService->operationalSummary();
        $capiSummary = $conversionEventReadinessService->operationalSummary();
        $providerWriterSummary = $providerWriterReadinessService->currentSummary();
        $providerWriterLaunchChecklist = $providerWriterLaunchChecklistService->build($providerWriterSummary, $syncReadiness);
        $manualCatalogSyncEnabled = (bool) config('fb_marketing.catalog_sync.manual_enabled', true);
        $manualCatalogSyncReady = $manualCatalogSyncEnabled && (bool) $catalogSummary['schema_ready'];
        $manualSyncReady = $manualSyncReady && (bool) $catalogSummary['schema_ready'];

        return view('backend.fb-marketing.setup-wizard', [
            'vaultReady' => $vaultReady,
            'healthReady' => $healthReady,
            'assetDiscoveryReady' => $assetDiscoveryReady,
            'hierarchyReady' => $hierarchyReady,
            'syncReady' => $syncReady,
            'syncReadiness' => $syncReadiness,
            'manualSyncEnabled' => $manualSyncEnabled,
            'manualSyncReady' => $manualSyncReady,
            'manualDrilldownSyncEnabled' => $manualDrilldownSyncEnabled,
            'manualDrilldownSyncReady' => $manualDrilldownSyncReady,
            'canViewPerformance' => $permissionService->userCan($request->user(), 'fb_marketing_performance_view', 'read'),
            'canViewCatalog' => $permissionService->userCan($request->user(), 'fb_marketing_catalog_view', 'read'),
            'canViewTracking' => $permissionService->userCan($request->user(), 'fb_marketing_tracking_view', 'read'),
            'catalogSummary' => $catalogSummary,
            'landingAttributionSummary' => $landingAttributionSummary,
            'browserPixelSummary' => $browserPixelSummary,
            'capiSummary' => $capiSummary,
            'providerWriterSummary' => $providerWriterSummary,
            'providerWriterLaunchChecklist' => $providerWriterLaunchChecklist,
            'manualCatalogSyncEnabled' => $manualCatalogSyncEnabled,
            'manualCatalogSyncReady' => $manualCatalogSyncReady,
            'insightsSummary' => $insightsSummary,
            'connectionCount' => $vaultReady ? FbmConnection::query()->count() : 0,
            'activeConnectionCount' => $vaultReady ? FbmConnection::query()->where('is_active', true)->count() : 0,
            'testedConnectionCount' => $healthReady ? FbmConnectionHealthCheck::query()->distinct()->count('fbm_connection_id') : 0,
            'healthyConnectionCount' => $healthReady ? $this->healthyConnectionCount() : 0,
            'discoveryRunCount' => $assetDiscoveryReady ? FbmAssetDiscoveryRun::query()->count() : 0,
            'latestDiscoveryStatus' => $latestDiscoveryRun ? (string) $latestDiscoveryRun->status : null,
            'syncRunCount' => Schema::hasTable('fbm_sync_runs') ? FbmSyncRun::query()->count() : 0,
            'availableAssetCounts' => $assetDiscoveryReady ? $this->assetCounts(false) : [],
            'selectedAvailableAssetCounts' => $assetDiscoveryReady ? $this->assetCounts(true) : [],
            'feedDiagnostics' => $feedDiagnosticsService->summary(),
            'advancedSteps' => $this->advancedSetupSteps($providerWriterSummary, $providerWriterLaunchChecklist),
        ]);
    }

    public function updateModuleSettings(UpdateFbmModuleSettingsRequest $request, FbmConfigurationService $configurationService): RedirectResponse
    {
        $configurationService->update($request->validated(), $request->user());

        return redirect()
            ->route('fbMarketing.configuration.index')
            ->with('success', 'Isolated FB MARKETING module settings updated. Existing stable General Information Pixel settings were not changed.');
    }

    public function store(StoreFbmConnectionRequest $request, FbmCredentialVaultService $vault): RedirectResponse
    {
        $this->ensureVaultReady();

        $vault->create($request->validated(), $request->user(), $request->ip());

        return redirect()
            ->route('fbMarketing.configuration.index')
            ->with('success', 'FB MARKETING connection saved. Stored secrets are encrypted and cannot be displayed again.');
    }

    public function update(UpdateFbmConnectionRequest $request, int $connection, FbmCredentialVaultService $vault): RedirectResponse
    {
        $this->ensureVaultReady();

        $vault->update($this->findConnection($connection), $request->validated(), $request->user(), $request->ip());

        return redirect()
            ->route('fbMarketing.configuration.index')
            ->with('success', 'FB MARKETING connection updated. Blank secret fields preserved the existing encrypted values.');
    }

    public function updateStatus(UpdateFbmConnectionStatusRequest $request, int $connection, FbmCredentialVaultService $vault): RedirectResponse
    {
        $this->ensureVaultReady();

        $data = $request->validated();
        $isActive = (bool) $data['is_active'];

        $vault->setActive(
            $this->findConnection($connection),
            $isActive,
            $request->user(),
            $request->ip(),
            $data['change_reason'] ?? null
        );

        return redirect()
            ->route('fbMarketing.configuration.index')
            ->with('success', $isActive ? 'FB MARKETING connection enabled.' : 'FB MARKETING connection disabled.');
    }

    public function testConnection(Request $request, int $connection, FbmConnectionHealthService $healthService): RedirectResponse
    {
        $this->ensureHealthLedgerReady();

        $check = $healthService->test($this->findConnection($connection), $request->user(), $request->ip());

        return redirect()
            ->route('fbMarketing.configuration.index')
            ->with('success', 'Read-only connection health check recorded with status: ' . strtoupper($check->status) . '.');
    }

    private function vaultReady(): bool
    {
        return Schema::hasTable('fbm_connections')
            && Schema::hasTable('fbm_connection_secret_audits');
    }

    private function healthLedgerReady(): bool
    {
        return $this->vaultReady()
            && Schema::hasTable('fbm_connection_health_checks');
    }

    private function ensureVaultReady(): void
    {
        abort_unless(
            $this->vaultReady(),
            503,
            'FB MARKETING credential vault schema is not ready. Run the application migrations before saving credentials.'
        );
    }

    private function ensureHealthLedgerReady(): void
    {
        abort_unless(
            $this->healthLedgerReady(),
            503,
            'FB MARKETING connection-health schema is not ready. Run the application migrations before testing a connection.'
        );
    }

    private function findConnection(int $connection): FbmConnection
    {
        return FbmConnection::query()->findOrFail($connection);
    }

    private function healthyConnectionCount(): int
    {
        $latestIds = FbmConnectionHealthCheck::query()
            ->selectRaw('MAX(id)')
            ->groupBy('fbm_connection_id');

        return FbmConnectionHealthCheck::query()
            ->whereIn('id', $latestIds)
            ->where('status', FbmConnectionHealthCheck::STATUS_HEALTHY)
            ->count();
    }

    private function assetCounts(bool $selectedOnly): array
    {
        $counts = [];
        foreach (FbmAssetDiscoveryService::ASSET_MODEL_MAP as $family => $modelClass) {
            $query = $modelClass::query()->where('is_available', true);
            if ($selectedOnly) {
                $query->where('is_selected', true);
            }
            $counts[$family] = $query->count();
        }

        return $counts;
    }

    private function advancedSetupSteps(array $providerWriterSummary, array $providerWriterLaunchChecklist): array
    {
        $steps = [
            $this->setupStep(15, 'ERP order attribution bridge', ['fbm_order_attributions', 'fbm_order_attribution_items', 'fbm_attribution_reconciliations'], 'Order attribution snapshot schema ready'),
            $this->setupStep(16, 'Sales attribution dashboard', ['fbm_order_attributions', 'fbm_insight_daily_snapshots'], 'Stored sales and spend inputs ready'),
            $this->setupStep(17, 'Product sales, profit and stock risk', ['fbm_order_attribution_items', 'fbm_catalog_product_mappings'], 'Product attribution and catalog mapping inputs ready'),
            $this->setupStep(18, 'Campaign cost and profitability', ['fbm_campaign_cost_adjustments'], 'Local campaign cost adjustment ledger ready'),
            $this->setupStep(19, 'Boosting jobs and client ledger', ['fbm_boosting_jobs', 'fbm_boosting_job_campaigns', 'fbm_boosting_job_payments', 'fbm_boosting_job_cost_adjustments'], 'Boosting job ledgers ready'),
            $this->setupStep(20, 'Reporting center and exports', ['fbm_report_exports'], 'Report export ledger ready'),
            $this->setupStep(21, 'Creative library and preflight', ['fbm_creative_assets', 'fbm_creative_preflight_checks'], 'Creative asset and preflight ledgers ready'),
            $this->setupStep(22, 'Audience and product-set management', ['fbm_audiences', 'fbm_audience_sync_runs', 'fbm_product_sets'], 'Audience and product-set planning ready'),
            $this->setupStep(23, 'Campaign draft planner and approvals', ['fbm_campaign_drafts', 'fbm_campaign_draft_approvals'], 'Draft approval workflow ready'),
            $this->setupStep(24, 'Controlled campaign publish engine', ['fbm_campaign_publish_attempts', 'fbm_campaign_publish_steps'], 'Publish attempt ledger ready'),
            $this->setupStep(25, 'Controlled operational actions', ['fbm_campaign_operational_actions'], 'Operational action ledger ready'),
            $this->setupStep(26, 'Lead Ads to CRM', ['fbm_lead_ad_events', 'fbm_lead_ad_webhook_logs'], 'Lead Ads ingestion ledger ready'),
            $this->setupStep(27, 'Ad-account webhooks and alerts', ['fbm_ad_account_webhook_logs', 'fbm_ad_account_reconciliation_runs', 'fbm_alerts'], 'Webhook recovery and alert ledgers ready'),
            $this->setupStep(28, 'Setup Wizard and embedded manual', ['fbm_user_manual_sections'], 'Database-driven manual ready'),
            $this->setupStep(30, 'Controlled rules and recommendations', ['fbm_recommendation_rules', 'fbm_recommendations'], 'Recommendation approval ledgers ready'),
        ];

        $steps[] = [
            'number' => 31,
            'title' => 'Provider writer foundation',
            'ready' => (bool) ($providerWriterSummary['schema_ready'] ?? false),
            'ready_message' => (string) ($providerWriterSummary['message'] ?? 'Provider writer readiness can be evaluated.'),
            'missing' => (bool) ($providerWriterSummary['schema_ready'] ?? false) ? [] : ['prior FB MARKETING writer prerequisite tables'],
        ];
        $steps[] = [
            'number' => 32,
            'title' => 'Campaign publish worker',
            'ready' => class_exists(\App\Jobs\FbMarketing\PublishFbmCampaignAttemptJob::class)
                && class_exists(\App\Services\FbMarketing\FbmCampaignPublishWorkerService::class)
                && class_exists(\App\Services\FbMarketing\FbmCampaignPublishProviderClient::class),
            'ready_message' => 'Queue-backed campaign publish worker and provider client are available.',
            'missing' => ['publish worker job/service/client'],
        ];
        $steps[] = [
            'number' => 33,
            'title' => 'Operational actions writer',
            'ready' => class_exists(\App\Jobs\FbMarketing\RunFbmOperationalActionJob::class)
                && class_exists(\App\Services\FbMarketing\FbmCampaignOperationalActionWorkerService::class)
                && class_exists(\App\Services\FbMarketing\FbmCampaignOperationalProviderClient::class),
            'ready_message' => 'Queue-backed operational action writer and provider client are available.',
            'missing' => ['operational action worker job/service/client'],
        ];
        $steps[] = [
            'number' => 34,
            'title' => 'Safety, rollback and reconciliation',
            'ready' => class_exists(\App\Services\FbMarketing\FbmProviderWriteSafetyService::class)
                && (bool) config('fb_marketing.provider_writer.rollback_plan_required', false)
                && (bool) config('fb_marketing.provider_writer.post_write_reconciliation_required', false),
            'ready_message' => 'Provider-write rollback plans and reconciliation summaries are required.',
            'missing' => ['provider write safety service or required safety config'],
        ];
        $steps[] = [
            'number' => 35,
            'title' => 'Final provider-writer launch gate',
            'ready' => (bool) ($providerWriterLaunchChecklist['ready_for_signed_enablement'] ?? false),
            'ready_message' => (string) ($providerWriterLaunchChecklist['message'] ?? 'Final provider-writer launch gate has not been evaluated.'),
            'missing' => array_map(fn(array $item): string => (string) $item['label'], array_values(array_filter(
                (array) ($providerWriterLaunchChecklist['items'] ?? []),
                fn(array $item): bool => empty($item['ready'])
            ))),
        ];

        return $steps;
    }

    private function setupStep(int $number, string $title, array $tables, string $readyMessage): array
    {
        $missing = array_values(array_filter($tables, fn(string $table): bool => !Schema::hasTable($table)));

        return [
            'number' => $number,
            'title' => $title,
            'ready' => $missing === [],
            'ready_message' => $readyMessage,
            'missing' => $missing,
        ];
    }
}

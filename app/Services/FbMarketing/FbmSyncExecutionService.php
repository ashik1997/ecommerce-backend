<?php

namespace App\Services\FbMarketing;

use App\Models\FbMarketing\FbmAssetDiscoveryRun;
use App\Models\FbMarketing\FbmConnection;
use App\Models\FbMarketing\FbmSyncRun;
use App\Models\User;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class FbmSyncExecutionService
{
    public function __construct(
        protected FbmAssetDiscoveryService $assetDiscovery,
        protected FbmCampaignHierarchySyncService $campaignHierarchy,
        protected FbmCatalogProductMappingSyncService $catalogMappings,
        protected FbmInsightReportCoordinatorService $insights,
        protected FbmApiRequestLogService $apiRequestLogs
    ) {
    }

    public function execute(string $runUuid, bool $directOnly = false): ?FbmSyncRun
    {
        $run = FbmSyncRun::query()->where('run_uuid', $runUuid)->firstOrFail();
        if (in_array($run->status, FbmSyncRun::terminalStatuses(), true)) {
            return $run;
        }

        $lock = Cache::lock(
            $this->lockKey((int) $run->fbm_connection_id, $this->lockScope((string) $run->sync_scope)),
            max(60, (int) config('fb_marketing.queue.lock_ttl_seconds', 900))
        );

        if (!$lock->get()) {
            return $this->markSkipped($run, 'Another read-only FB MARKETING sync already owns the connection scope lock.');
        }

        try {
            return $this->executeLocked($run, $directOnly);
        } catch (Throwable $exception) {
            $this->markFailed($runUuid, 'Read-only FB MARKETING sync stopped safely before completion.');

            Log::warning('FB MARKETING sync execution failed safely.', [
                'fbm_sync_run_uuid_hash' => hash('sha256', $runUuid),
                'fbm_connection_id' => (int) $run->fbm_connection_id,
                'execution_mode' => $this->executionMode($run, $directOnly),
                'exception_class' => get_class($exception),
            ]);

            throw $exception;
        } finally {
            $this->releaseLock($lock);
        }
    }

    public function executeManualDirect(FbmConnection $connection, ?User $actor): FbmSyncRun
    {
        if (!(bool) config('fb_marketing.manual_sync.enabled', true)) {
            throw new RuntimeException('Manual no-queue sync is disabled by the FB MARKETING configuration.');
        }

        return $this->executeManualRun(
            $connection,
            $actor,
            FbmSyncRun::SCOPE_MANUAL_DIRECT,
            FbmSyncRun::TRIGGER_MANUAL_DIRECT,
            'Manual no-queue read-only sync was prepared for immediate application execution.'
        );
    }

    public function executeManualDrilldownDirect(FbmConnection $connection, ?User $actor): FbmSyncRun
    {
        if (!(bool) config('fb_marketing.manual_drilldown_sync.enabled', true)) {
            throw new RuntimeException('Manual no-queue drilldown refresh is disabled by the FB MARKETING configuration.');
        }

        return $this->executeManualRun(
            $connection,
            $actor,
            FbmSyncRun::SCOPE_MANUAL_DRILLDOWN,
            FbmSyncRun::TRIGGER_MANUAL_DRILLDOWN,
            'Manual no-queue campaign, ad-set and ad drilldown refresh was prepared for immediate application execution.'
        );
    }

    public function markFailed(string $runUuid, string $safeMessage): void
    {
        $run = FbmSyncRun::query()->where('run_uuid', $runUuid)->first();
        if (!$run || in_array($run->status, [FbmSyncRun::STATUS_SUCCESS, FbmSyncRun::STATUS_PARTIAL_SUCCESS], true)) {
            return;
        }

        $startedAt = $run->started_at;
        $run->forceFill([
            'status' => FbmSyncRun::STATUS_FAILED,
            'completed_at' => now(),
            'duration_ms' => $startedAt ? max(0, now()->diffInMilliseconds($startedAt)) : null,
            'error_count' => max(1, (int) $run->error_count),
            'redacted_message' => $safeMessage,
        ])->save();
    }

    protected function executeManualRun(
        FbmConnection $connection,
        ?User $actor,
        string $scope,
        string $trigger,
        string $safeMessage
    ): FbmSyncRun {
        if (!Schema::hasTable('fbm_sync_runs')) {
            throw new RuntimeException('FBM-06 application migration is required before manual no-queue sync can run.');
        }
        if (!$connection->is_active) {
            throw new RuntimeException('Manual no-queue sync requires an active FB MARKETING connection.');
        }

        $existing = FbmSyncRun::query()
            ->where('fbm_connection_id', (int) $connection->id)
            ->where('sync_scope', $scope)
            ->whereIn('status', FbmSyncRun::activeStatuses())
            ->orderByDesc('id')
            ->first();
        if ($existing) {
            return $existing;
        }

        $run = FbmSyncRun::query()->create([
            'run_uuid' => (string) Str::uuid(),
            'fbm_connection_id' => (int) $connection->id,
            'sync_scope' => $scope,
            'trigger_type' => $trigger,
            'status' => FbmSyncRun::STATUS_QUEUED,
            'requested_by' => $actor?->id,
            'requested_at' => now(),
            'attempt_count' => 0,
            'warning_count' => 0,
            'error_count' => 0,
            'redacted_message' => $safeMessage,
            'safe_summary' => [],
            'application_context_fingerprint' => $this->applicationFingerprint(),
            'lock_fingerprint' => $this->lockFingerprint((int) $connection->id, FbmSyncRun::SCOPE_FULL_READ_ONLY),
        ]);

        return $this->execute((string) $run->run_uuid, true) ?: $run->fresh();
    }

    protected function executeLocked(FbmSyncRun $run, bool $directOnly): FbmSyncRun
    {
        $startedAt = microtime(true);
        $drilldownOnly = (string) $run->sync_scope === FbmSyncRun::SCOPE_MANUAL_DRILLDOWN;
        $executionMode = $this->executionMode($run, $directOnly);
        $run->forceFill([
            'status' => FbmSyncRun::STATUS_RUNNING,
            'started_at' => now(),
            'completed_at' => null,
            'attempt_count' => (int) $run->attempt_count + 1,
            'redacted_message' => $drilldownOnly
                ? 'Manual no-queue campaign hierarchy and recent campaign, ad-set and ad Insights drilldown refresh is running.'
                : ($directOnly
                    ? 'Manual no-queue Meta asset discovery, catalog mapping, campaign hierarchy and recent account-level Insights refresh is running.'
                    : 'Read-only Meta asset discovery, catalog mapping, campaign hierarchy and Ads Insights snapshot sync is running.'),
        ])->save();

        $connection = FbmConnection::query()->findOrFail((int) $run->fbm_connection_id);
        if (!$connection->is_active) {
            return $this->markSkipped($run, 'Connection became inactive before the read-only sync started.');
        }

        $actor = $run->requested_by ? User::query()->find((int) $run->requested_by) : null;
        $discoveryRun = null;

        if (!$drilldownOnly) {
            $discoveryRun = $this->apiRequestLogs->withinSyncRun((int) $run->id, function () use ($connection, $actor) {
                return $this->assetDiscovery->discover($connection, $actor, null);
            });
        }

        $catalogMappings = ['status' => 'skipped', 'warning_count' => 0, 'warnings' => [], 'counts' => []];
        if (!$drilldownOnly) {
            $catalogMappings = $this->apiRequestLogs->withinSyncRun((int) $run->id, function () use ($connection, $actor, $directOnly) {
                return $this->catalogMappings->syncSelectedCatalogs(
                    $connection,
                    $actor,
                    $directOnly ? 'manual_direct_request' : 'queued_worker',
                    $directOnly ? max(1, min(3, (int) config('fb_marketing.catalog_sync.manual_max_selected_catalogs', 1))) : null
                );
            });
        }

        $hierarchy = $this->apiRequestLogs->withinSyncRun((int) $run->id, function () use ($connection, $run, $drilldownOnly) {
            return $this->campaignHierarchy->sync(
                $connection,
                $run,
                $drilldownOnly ? $this->manualDrilldownMaxSelectedAdAccounts() : null
            );
        });
        $insights = $this->apiRequestLogs->withinSyncRun((int) $run->id, function () use ($connection, $run, $directOnly, $drilldownOnly) {
            if ($drilldownOnly) {
                return $this->insights->coordinateManualDrilldownDirect($connection, $run);
            }

            return $directOnly
                ? $this->insights->coordinateManualDirect($connection, $run)
                : $this->insights->coordinate($connection, $run);
        });

        $componentStatuses = [];
        $warnings = 0;
        if ($discoveryRun) {
            $componentStatuses[] = $this->mapDiscoveryStatus((string) $discoveryRun->status);
            $warnings += is_array($discoveryRun->warning_details) ? count($discoveryRun->warning_details) : 0;
        }
        if (!$drilldownOnly) {
            $componentStatuses[] = $this->mapCatalogStatus((string) ($catalogMappings['status'] ?? 'failed'));
        }
        $componentStatuses[] = $this->mapHierarchyStatus((string) ($hierarchy['status'] ?? 'failed'));
        $componentStatuses[] = $this->mapInsightsStatus((string) ($insights['status'] ?? 'failed'));
        $warnings += (int) ($catalogMappings['warning_count'] ?? 0) + (int) ($hierarchy['warning_count'] ?? 0) + (int) ($insights['warning_count'] ?? 0);
        $status = $this->combineStatuses($componentStatuses);
        $errors = in_array(FbmSyncRun::STATUS_FAILED, $componentStatuses, true) ? 1 : 0;

        $safeSummary = [
            'execution_mode' => $executionMode,
            'historical_backfill_dispatched' => (int) ($insights['counts']['async_backfill_queued_count'] ?? 0) > 0,
            'asset_discovery_skipped' => $drilldownOnly,
            'catalog_mapping_skipped' => $drilldownOnly,
            'catalog_mapping_counts' => is_array($catalogMappings['counts'] ?? null) ? $catalogMappings['counts'] : [],
            'catalog_mapping_warnings' => is_array($catalogMappings['warnings'] ?? null) ? $catalogMappings['warnings'] : [],
            'campaign_hierarchy_counts' => is_array($hierarchy['counts'] ?? null) ? $hierarchy['counts'] : [],
            'campaign_hierarchy_complete' => is_array($hierarchy['complete'] ?? null) ? $hierarchy['complete'] : [],
            'campaign_hierarchy_warnings' => is_array($hierarchy['warnings'] ?? null) ? $hierarchy['warnings'] : [],
            'ads_insights_counts' => is_array($insights['counts'] ?? null) ? $insights['counts'] : [],
            'ads_insights_warnings' => is_array($insights['warnings'] ?? null) ? $insights['warnings'] : [],
        ];
        if ($discoveryRun) {
            $safeSummary += [
                'asset_discovery_run_id' => (int) $discoveryRun->id,
                'asset_counts' => is_array($discoveryRun->asset_counts) ? $discoveryRun->asset_counts : [],
                'asset_successful_families' => is_array($discoveryRun->successful_families) ? $discoveryRun->successful_families : [],
                'asset_failed_families' => is_array($discoveryRun->failed_families) ? $discoveryRun->failed_families : [],
            ];
        }

        $run->forceFill([
            'status' => $status,
            'completed_at' => now(),
            'duration_ms' => max(0, (int) round((microtime(true) - $startedAt) * 1000)),
            'warning_count' => $warnings,
            'error_count' => $errors,
            'redacted_message' => $insights['message'] ?? ($hierarchy['message'] ?? optional($discoveryRun)->redacted_message),
            'safe_summary' => $safeSummary,
        ])->save();

        Log::info('FB MARKETING sync completed.', [
            'fbm_sync_run_id' => (int) $run->id,
            'fbm_connection_id' => (int) $run->fbm_connection_id,
            'execution_mode' => $executionMode,
            'status' => (string) $run->status,
            'duration_ms' => $run->duration_ms,
        ]);

        return $run->fresh();
    }

    protected function markSkipped(FbmSyncRun $run, string $safeMessage): FbmSyncRun
    {
        $run->forceFill([
            'status' => FbmSyncRun::STATUS_SKIPPED,
            'completed_at' => now(),
            'redacted_message' => $safeMessage,
        ])->save();

        return $run->fresh();
    }

    protected function lockScope(string $syncScope): string
    {
        return in_array($syncScope, [FbmSyncRun::SCOPE_MANUAL_DIRECT, FbmSyncRun::SCOPE_MANUAL_DRILLDOWN], true)
            ? FbmSyncRun::SCOPE_FULL_READ_ONLY
            : $syncScope;
    }

    protected function lockKey(int $connectionId, string $scope): string
    {
        return 'fbm:sync-lock:' . $this->lockFingerprint($connectionId, $scope);
    }

    protected function lockFingerprint(int $connectionId, string $scope): string
    {
        return hash_hmac('sha256', 'lock|' . $connectionId . '|' . $scope, (string) config('app.key'));
    }

    protected function applicationFingerprint(): string
    {
        return hash_hmac('sha256', 'application|' . (string) config('app.url'), (string) config('app.key'));
    }

    protected function executionMode(FbmSyncRun $run, bool $directOnly): string
    {
        if ((string) $run->sync_scope === FbmSyncRun::SCOPE_MANUAL_DRILLDOWN) {
            return 'manual_drilldown_request';
        }

        return $directOnly ? 'manual_direct_request' : 'queued_worker';
    }

    protected function mapDiscoveryStatus(string $status): string
    {
        return [
            FbmAssetDiscoveryRun::STATUS_SUCCESS => FbmSyncRun::STATUS_SUCCESS,
            FbmAssetDiscoveryRun::STATUS_PARTIAL_SUCCESS => FbmSyncRun::STATUS_PARTIAL_SUCCESS,
            FbmAssetDiscoveryRun::STATUS_FAILED => FbmSyncRun::STATUS_FAILED,
        ][$status] ?? FbmSyncRun::STATUS_FAILED;
    }

    protected function mapCatalogStatus(string $status): string
    {
        return [
            'success' => FbmSyncRun::STATUS_SUCCESS,
            'partial_success' => FbmSyncRun::STATUS_PARTIAL_SUCCESS,
            'skipped' => FbmSyncRun::STATUS_PARTIAL_SUCCESS,
            'failed' => FbmSyncRun::STATUS_FAILED,
        ][$status] ?? FbmSyncRun::STATUS_FAILED;
    }

    protected function mapHierarchyStatus(string $status): string
    {
        return [
            'success' => FbmSyncRun::STATUS_SUCCESS,
            'partial_success' => FbmSyncRun::STATUS_PARTIAL_SUCCESS,
            'skipped' => FbmSyncRun::STATUS_PARTIAL_SUCCESS,
            'failed' => FbmSyncRun::STATUS_FAILED,
        ][$status] ?? FbmSyncRun::STATUS_FAILED;
    }

    protected function mapInsightsStatus(string $status): string
    {
        return [
            'success' => FbmSyncRun::STATUS_SUCCESS,
            'partial_success' => FbmSyncRun::STATUS_PARTIAL_SUCCESS,
            'skipped' => FbmSyncRun::STATUS_PARTIAL_SUCCESS,
            'failed' => FbmSyncRun::STATUS_FAILED,
        ][$status] ?? FbmSyncRun::STATUS_FAILED;
    }

    protected function combineStatuses(array $statuses): string
    {
        if ($statuses !== [] && count(array_unique($statuses)) === 1 && $statuses[0] === FbmSyncRun::STATUS_SUCCESS) {
            return FbmSyncRun::STATUS_SUCCESS;
        }

        if ($statuses !== [] && count(array_filter($statuses, fn(string $status): bool => $status === FbmSyncRun::STATUS_FAILED)) === count($statuses)) {
            return FbmSyncRun::STATUS_FAILED;
        }

        return FbmSyncRun::STATUS_PARTIAL_SUCCESS;
    }

    protected function manualDrilldownMaxSelectedAdAccounts(): int
    {
        return max(1, min(3, (int) config('fb_marketing.manual_drilldown_sync.max_selected_ad_accounts', 1)));
    }

    protected function releaseLock(Lock $lock): void
    {
        try {
            $lock->release();
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}

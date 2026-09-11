<?php

namespace App\Services\FbMarketing;

use App\Jobs\FbMarketing\PollFbmInsightReportJob;
use App\Jobs\FbMarketing\RunFbmInsightReportJob;
use App\Models\FbMarketing\FbmAdAccount;
use App\Models\FbMarketing\FbmConnection;
use App\Models\FbMarketing\FbmInsightReportRun;
use App\Models\FbMarketing\FbmSyncRun;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class FbmInsightReportCoordinatorService
{
    public function __construct(
        protected FbmGraphClient $graphClient,
        protected FbmInsightsSnapshotService $snapshots
    ) {
    }

    public function coordinateManualDirect(FbmConnection $connection, FbmSyncRun $syncRun): array
    {
        return $this->coordinate($connection, $syncRun, true, 'account');
    }

    public function coordinateManualDrilldownDirect(FbmConnection $connection, FbmSyncRun $syncRun): array
    {
        return $this->coordinate($connection, $syncRun, true, 'drilldown');
    }

    public function coordinate(FbmConnection $connection, FbmSyncRun $syncRun, bool $directOnly = false, string $manualProfile = 'account'): array
    {
        if (!FbmInsightsSnapshotService::schemaReady()) {
            return $this->summary('skipped', ['FBM-08 application migration is required before Ads Insights snapshots can run.']);
        }

        $accounts = FbmAdAccount::query()
            ->where('fbm_connection_id', (int) $connection->id)
            ->where('is_available', true)
            ->where('is_selected', true)
            ->orderBy('id')
            ->limit($directOnly ? $this->manualMaxSelectedAdAccounts($manualProfile) : $this->maxSelectedAdAccounts())
            ->get();
        if ($accounts->isEmpty()) {
            return $this->summary('skipped', ['No selected and available Ad Account is ready for Ads Insights snapshots.']);
        }
        $accounts = $this->rotateAccounts($accounts, (int) $syncRun->id);

        $levels = $directOnly ? $this->manualLevels($manualProfile) : $this->levels();
        $warnings = [];
        $directReports = 0;
        $asyncQueued = 0;
        $upserted = 0;
        $directCap = $directOnly ? $this->manualMaxDirectReportsPerRun($manualProfile) : $this->maxDirectReportsPerSync();
        $asyncCap = $this->maxAsyncReportsPerSync();
        $yesterday = CarbonImmutable::today()->subDay();
        $rollingStart = $yesterday->subDays(max(0, ($directOnly ? $this->manualRecentDays($manualProfile) : $this->rollingRefreshDays()) - 1));
        $directChunkDays = $directOnly ? $this->manualDirectChunkDays($manualProfile) : $this->directChunkDays();

        foreach ($accounts as $account) {
            foreach ($levels as $level) {
                foreach ($this->dateChunks($rollingStart, $yesterday, $directChunkDays) as [$since, $until]) {
                    if ($directReports >= $directCap) {
                        $warnings[] = 'The bounded direct Insights refresh cap was reached. Remaining recent windows were deferred safely.';
                        break 3;
                    }
                    $report = $this->newReport($syncRun, $connection, $account, $level, $since, $until, FbmInsightReportRun::MODE_DIRECT);
                    $result = $this->executeDirect($report, $connection, $account);
                    $directReports++;
                    $upserted += (int) ($result['upserted_count'] ?? 0);
                    if (($result['status'] ?? 'failed') !== 'success') {
                        $warnings[] = $result['redacted_message'] ?? 'A direct Insights refresh window did not complete safely.';
                    }
                }
            }
        }

        $historyStart = $yesterday->subDays(max(1, $this->initialBackfillDays() - 1));
        $historyEnd = $rollingStart->subDay();
        if (!$directOnly && $historyStart->lte($historyEnd)) {
            foreach ($accounts as $account) {
                foreach ($levels as $level) {
                    foreach ($this->dateChunks($historyStart, $historyEnd, $this->asyncChunkDays()) as [$since, $until]) {
                        if ($asyncQueued >= $asyncCap) {
                            $warnings[] = 'The bounded historical Insights backfill queue cap was reached. Later windows will be scheduled by a future sync.';
                            break 3;
                        }
                        if ($this->existingHistoricalRun($connection, $account, $level, $since, $until)) {
                            continue;
                        }
                        $report = $this->newReport($syncRun, $connection, $account, $level, $since, $until, FbmInsightReportRun::MODE_ASYNC);
                        try {
                            dispatch(new RunFbmInsightReportJob((string) $report->report_uuid));
                            $asyncQueued++;
                        } catch (Throwable $exception) {
                            $this->failReport($report, 'Historical Insights backfill could not be enqueued safely.');
                            $warnings[] = 'A historical Insights backfill window could not be enqueued safely.';
                            Log::warning('FB MARKETING historical Insights enqueue failed safely.', [
                                'fbm_insight_report_id' => (int) $report->id,
                                'exception_class' => get_class($exception),
                            ]);
                        }
                    }
                }
            }
        }

        $status = $warnings === [] ? 'success' : 'partial_success';

        return $this->summary($status, $warnings, [
            'selected_ad_account_count' => $accounts->count(),
            'direct_report_count' => $directReports,
            'async_backfill_queued_count' => $asyncQueued,
            'upserted_snapshot_count' => $upserted,
            'freshness_watermark' => $yesterday->toDateString(),
            'execution_mode' => $directOnly ? ($manualProfile === 'drilldown' ? 'manual_drilldown_request' : 'manual_direct_request') : 'queued_worker',
            'historical_backfill_dispatched' => !$directOnly && $asyncQueued > 0,
        ]);
    }

    public function executeAsync(string $reportUuid): void
    {
        $report = $this->findReport($reportUuid);
        if (in_array($report->status, FbmInsightReportRun::terminalStatuses(), true)) {
            return;
        }

        $connection = FbmConnection::query()->findOrFail((int) $report->fbm_connection_id);
        $account = FbmAdAccount::query()->findOrFail((int) $report->fbm_ad_account_id);
        $report->forceFill([
            'status' => FbmInsightReportRun::STATUS_RUNNING,
            'started_at' => $report->started_at ?: now(),
            'attempt_count' => (int) $report->attempt_count + 1,
            'redacted_message' => 'Historical Ads Insights backfill report is being prepared safely.',
        ])->save();

        if (!$report->provider_report_run_key) {
            $result = $this->graphClient->createAsyncInsightsReport(
                $connection,
                (string) $account->provider_asset_id,
                (string) $report->insight_level,
                optional($report->window_start)->toDateString(),
                optional($report->window_end)->toDateString()
            );
            if (empty($result['successful']) || empty($result['provider_report_run_key'])) {
                $this->failReport($report, $result['redacted_message'] ?? 'Meta did not accept the historical Insights backfill report safely.');
                return;
            }
            $report->forceFill([
                'provider_report_run_key' => $result['provider_report_run_key'],
                'status' => FbmInsightReportRun::STATUS_POLLING,
                'redacted_message' => 'Historical Ads Insights backfill was accepted and is waiting for a bounded poll.',
            ])->save();
        }

        $this->dispatchPoll($report);
    }

    public function pollAsync(string $reportUuid): void
    {
        $report = $this->findReport($reportUuid);
        if (in_array($report->status, FbmInsightReportRun::terminalStatuses(), true)) {
            return;
        }
        if (!$report->provider_report_run_key) {
            $this->failReport($report, 'Historical Insights polling stopped because the internal report key was unavailable.');
            return;
        }

        $connection = FbmConnection::query()->findOrFail((int) $report->fbm_connection_id);
        $account = FbmAdAccount::query()->findOrFail((int) $report->fbm_ad_account_id);
        $report->forceFill([
            'status' => FbmInsightReportRun::STATUS_POLLING,
            'poll_count' => (int) $report->poll_count + 1,
            'next_poll_at' => null,
        ])->save();
        $status = $this->graphClient->readAsyncInsightsReportStatus($connection, (string) $report->provider_report_run_key);

        if (!empty($status['failed'])) {
            $this->failReport($report, $status['redacted_message'] ?? 'Meta historical Insights report failed safely.');
            return;
        }
        if (empty($status['complete'])) {
            if ((int) $report->poll_count >= $this->maxPollAttempts()) {
                $this->failReport($report, 'Historical Insights polling reached its bounded attempt limit and stopped safely.');
                return;
            }
            $report->forceFill([
                'warning_count' => !empty($status['request_failed']) ? (int) $report->warning_count + 1 : (int) $report->warning_count,
                'redacted_message' => 'Historical Ads Insights backfill is still processing and will be checked again safely.',
                'safe_summary' => [
                    'async_percent_completion' => (int) ($status['async_percent_completion'] ?? 0),
                    'async_status' => $status['async_status'] ?? null,
                ],
            ])->save();
            $this->dispatchPoll($report);
            return;
        }

        $result = $this->graphClient->paginateAsyncInsightsResult($connection, (string) $report->provider_report_run_key);
        $ingested = $this->snapshots->ingestRows($report, $account, is_array($result['items'] ?? null) ? $result['items'] : []);
        $this->completeReport($report, $result, $ingested, 'Historical Ads Insights backfill completed safely.');
    }

    public function markFailed(string $reportUuid, string $safeMessage): void
    {
        $report = FbmInsightReportRun::query()->where('report_uuid', $reportUuid)->first();
        if ($report && !in_array($report->status, FbmInsightReportRun::terminalStatuses(), true)) {
            $this->failReport($report, $safeMessage);
        }
    }

    protected function executeDirect(FbmInsightReportRun $report, FbmConnection $connection, FbmAdAccount $account): array
    {
        $report->forceFill([
            'status' => FbmInsightReportRun::STATUS_RUNNING,
            'started_at' => now(),
            'attempt_count' => (int) $report->attempt_count + 1,
            'redacted_message' => 'Recent Ads Insights daily refresh is running safely.',
        ])->save();
        $result = $this->graphClient->paginateInsights(
            $connection,
            (string) $account->provider_asset_id,
            (string) $report->insight_level,
            optional($report->window_start)->toDateString(),
            optional($report->window_end)->toDateString()
        );
        $ingested = $this->snapshots->ingestRows($report, $account, is_array($result['items'] ?? null) ? $result['items'] : []);

        return $this->completeReport($report, $result, $ingested, 'Recent Ads Insights daily refresh completed safely.');
    }

    protected function completeReport(FbmInsightReportRun $report, array $result, array $ingested, string $successMessage): array
    {
        $complete = !empty($result['complete']);
        $status = $complete ? FbmInsightReportRun::STATUS_SUCCESS : FbmInsightReportRun::STATUS_PARTIAL_SUCCESS;
        $message = $complete ? $successMessage : ($result['redacted_message'] ?? 'Ads Insights report completed with bounded warnings.');
        $report->forceFill([
            'status' => $status,
            'completed_at' => now(),
            'next_poll_at' => null,
            'row_count' => count(is_array($result['items'] ?? null) ? $result['items'] : []),
            'upserted_count' => (int) ($ingested['upserted_count'] ?? 0),
            'warning_count' => (int) ($report->warning_count ?? 0) + ($complete ? 0 : 1) + ((int) ($ingested['skipped_count'] ?? 0) > 0 ? 1 : 0),
            'error_count' => !empty($result['request_failed']) ? 1 : 0,
            'redacted_message' => $message,
            'safe_summary' => [
                'pages' => (int) ($result['pages'] ?? 0),
                'complete' => $complete,
                'truncated' => !empty($result['truncated']),
                'skipped_row_count' => (int) ($ingested['skipped_count'] ?? 0),
            ],
        ])->save();

        return [
            'status' => $status,
            'upserted_count' => (int) ($ingested['upserted_count'] ?? 0),
            'redacted_message' => $message,
        ];
    }

    protected function dispatchPoll(FbmInsightReportRun $report): void
    {
        $seconds = $this->pollBackoffSeconds((int) $report->poll_count);
        $report->forceFill(['next_poll_at' => now()->addSeconds($seconds)])->save();
        dispatch((new PollFbmInsightReportJob((string) $report->report_uuid))->delay($seconds));
    }

    protected function newReport(
        FbmSyncRun $syncRun,
        FbmConnection $connection,
        FbmAdAccount $account,
        string $level,
        CarbonImmutable $since,
        CarbonImmutable $until,
        string $mode
    ): FbmInsightReportRun {
        return FbmInsightReportRun::query()->create([
            'report_uuid' => (string) Str::uuid(),
            'fbm_sync_run_id' => (int) $syncRun->id,
            'fbm_connection_id' => (int) $connection->id,
            'fbm_ad_account_id' => (int) $account->id,
            'insight_level' => $level,
            'window_start' => $since->toDateString(),
            'window_end' => $until->toDateString(),
            'execution_mode' => $mode,
            'status' => FbmInsightReportRun::STATUS_QUEUED,
            'attempt_count' => 0,
            'poll_count' => 0,
            'row_count' => 0,
            'upserted_count' => 0,
            'warning_count' => 0,
            'error_count' => 0,
            'redacted_message' => $mode === FbmInsightReportRun::MODE_DIRECT
                ? 'Recent Ads Insights refresh window was prepared safely.'
                : 'Historical Ads Insights backfill window was queued safely.',
            'safe_summary' => [],
        ]);
    }

    protected function existingHistoricalRun(FbmConnection $connection, FbmAdAccount $account, string $level, CarbonImmutable $since, CarbonImmutable $until): bool
    {
        return FbmInsightReportRun::query()
            ->where('fbm_connection_id', (int) $connection->id)
            ->where('fbm_ad_account_id', (int) $account->id)
            ->where('insight_level', $level)
            ->where('execution_mode', FbmInsightReportRun::MODE_ASYNC)
            ->whereDate('window_start', $since->toDateString())
            ->whereDate('window_end', $until->toDateString())
            ->whereIn('status', array_merge(FbmInsightReportRun::activeStatuses(), [FbmInsightReportRun::STATUS_SUCCESS]))
            ->exists();
    }

    protected function findReport(string $reportUuid): FbmInsightReportRun
    {
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $reportUuid)) {
            throw new RuntimeException('The Insights report UUID is not allowed.');
        }

        return FbmInsightReportRun::query()->where('report_uuid', $reportUuid)->firstOrFail();
    }

    protected function failReport(FbmInsightReportRun $report, string $safeMessage): void
    {
        $report->forceFill([
            'status' => FbmInsightReportRun::STATUS_FAILED,
            'completed_at' => now(),
            'next_poll_at' => null,
            'error_count' => max(1, (int) $report->error_count),
            'redacted_message' => mb_substr($safeMessage, 0, 500),
        ])->save();
    }

    protected function dateChunks(CarbonImmutable $start, CarbonImmutable $end, int $days): array
    {
        $chunks = [];
        $cursor = $start;
        while ($cursor->lte($end)) {
            $chunkEnd = $cursor->addDays(max(1, $days) - 1);
            if ($chunkEnd->gt($end)) {
                $chunkEnd = $end;
            }
            $chunks[] = [$cursor, $chunkEnd];
            $cursor = $chunkEnd->addDay();
        }

        return $chunks;
    }

    protected function rotateAccounts($accounts, int $syncRunId)
    {
        $accounts = $accounts->values();
        $count = $accounts->count();
        if ($count <= 1) {
            return $accounts;
        }
        $offset = $syncRunId % $count;

        return $accounts->slice($offset)->concat($accounts->slice(0, $offset))->values();
    }

    protected function levels(): array
    {
        $levels = array_values(array_intersect((array) config('fb_marketing.insights.levels', []), ['account', 'campaign', 'adset', 'ad']));

        return $levels === [] ? ['account', 'campaign', 'adset', 'ad'] : $levels;
    }

    protected function summary(string $status, array $warnings = [], array $counts = []): array
    {
        $warnings = array_values(array_unique(array_filter(array_map(fn($warning) => mb_substr((string) $warning, 0, 500), $warnings))));

        return [
            'status' => $status,
            'warning_count' => count($warnings),
            'warnings' => array_slice($warnings, 0, 20),
            'counts' => $counts,
            'message' => $status === 'success'
                ? 'Ads Insights snapshot coordination completed safely.'
                : ($status === 'skipped' ? 'Ads Insights snapshot coordination skipped safely.' : 'Ads Insights snapshot coordination completed with safe warnings.'),
        ];
    }

    protected function manualLevels(string $profile): array
    {
        $default = $profile === 'drilldown' ? ['campaign', 'adset', 'ad'] : ['account'];
        $configKey = $profile === 'drilldown' ? 'fb_marketing.manual_drilldown_sync.insights_levels' : 'fb_marketing.manual_sync.insights_levels';
        $levels = array_values(array_intersect((array) config($configKey, $default), ['account', 'campaign', 'adset', 'ad']));

        return $levels === [] ? $default : $levels;
    }

    protected function manualMaxSelectedAdAccounts(string $profile): int
    {
        return $profile === 'drilldown'
            ? max(1, min(3, (int) config('fb_marketing.manual_drilldown_sync.max_selected_ad_accounts', 1)))
            : max(1, min(10, (int) config('fb_marketing.manual_sync.max_selected_ad_accounts', 3)));
    }

    protected function manualRecentDays(string $profile): int
    {
        return $profile === 'drilldown'
            ? max(1, min(7, (int) config('fb_marketing.manual_drilldown_sync.recent_days', 3)))
            : max(1, min(14, (int) config('fb_marketing.manual_sync.recent_days', 7)));
    }

    protected function manualDirectChunkDays(string $profile): int
    {
        return $profile === 'drilldown' ? $this->manualRecentDays($profile) : $this->directChunkDays();
    }

    protected function manualMaxDirectReportsPerRun(string $profile): int
    {
        return $profile === 'drilldown'
            ? max(1, min(9, (int) config('fb_marketing.manual_drilldown_sync.max_direct_reports_per_run', 3)))
            : max(1, min(20, (int) config('fb_marketing.manual_sync.max_direct_reports_per_run', 6)));
    }
    protected function maxSelectedAdAccounts(): int { return max(1, min(100, (int) config('fb_marketing.insights.max_selected_ad_accounts', 25))); }
    protected function rollingRefreshDays(): int { return max(1, min(90, (int) config('fb_marketing.insights.rolling_refresh_days', 14))); }
    protected function initialBackfillDays(): int { return max(1, min(730, (int) config('fb_marketing.insights.initial_backfill_days', 90))); }
    protected function directChunkDays(): int { return max(1, min(31, (int) config('fb_marketing.insights.direct_refresh_chunk_days', 7))); }
    protected function asyncChunkDays(): int { return max(1, min(90, (int) config('fb_marketing.insights.async_backfill_chunk_days', 30))); }
    protected function maxDirectReportsPerSync(): int { return max(1, min(500, (int) config('fb_marketing.insights.max_direct_reports_per_sync', 40))); }
    protected function maxAsyncReportsPerSync(): int { return max(1, min(100, (int) config('fb_marketing.insights.max_async_reports_per_sync', 12))); }
    protected function maxPollAttempts(): int { return max(1, min(50, (int) config('fb_marketing.insights.max_poll_attempts', 12))); }

    protected function pollBackoffSeconds(int $pollCount): int
    {
        $backoff = array_values((array) config('fb_marketing.insights.poll_backoff_seconds', [60, 180, 300, 600]));
        $seconds = $backoff[min(max(0, $pollCount), max(0, count($backoff) - 1))] ?? 60;

        return max(15, min(3600, (int) $seconds));
    }
}

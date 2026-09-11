<?php

namespace App\Services\FbMarketing;

use App\Jobs\FbMarketing\RunFbmSyncJob;
use App\Models\FbMarketing\FbmConnection;
use App\Models\FbMarketing\FbmSyncRun;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class FbmSyncDispatchService
{
    public function __construct(
        protected FbmQueueReadinessService $readiness
    ) {
    }

    public function dispatch(
        FbmConnection $connection,
        ?User $actor,
        string $triggerType = FbmSyncRun::TRIGGER_MANUAL
    ): FbmSyncRun {
        $this->readiness->assertCurrentReady();
        $this->assertTriggerType($triggerType);

        if (!$connection->is_active) {
            throw new RuntimeException('FB MARKETING sync requires an active connection.');
        }

        $existing = FbmSyncRun::query()
            ->where('fbm_connection_id', (int) $connection->id)
            ->where('sync_scope', FbmSyncRun::SCOPE_FULL_READ_ONLY)
            ->whereIn('status', FbmSyncRun::activeStatuses())
            ->orderByDesc('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        $run = FbmSyncRun::query()->create([
            'run_uuid' => (string) Str::uuid(),
            'fbm_connection_id' => (int) $connection->id,
            'sync_scope' => FbmSyncRun::SCOPE_FULL_READ_ONLY,
            'trigger_type' => $triggerType,
            'status' => FbmSyncRun::STATUS_QUEUED,
            'requested_by' => $actor?->id,
            'requested_at' => now(),
            'attempt_count' => 0,
            'warning_count' => 0,
            'error_count' => 0,
            'redacted_message' => 'Read-only Meta asset discovery, campaign hierarchy and Ads Insights snapshot sync was queued.',
            'safe_summary' => [],
            'application_context_fingerprint' => $this->applicationFingerprint(),
            'lock_fingerprint' => $this->lockFingerprint((int) $connection->id, FbmSyncRun::SCOPE_FULL_READ_ONLY),
        ]);

        try {
            dispatch(new RunFbmSyncJob((string) $run->run_uuid));
        } catch (Throwable $exception) {
            $run->forceFill([
                'status' => FbmSyncRun::STATUS_FAILED,
                'completed_at' => now(),
                'error_count' => 1,
                'redacted_message' => 'FB MARKETING could not enqueue the read-only sync. Verify the dedicated queue readiness checks.',
            ])->save();

            Log::warning('FB MARKETING sync enqueue failed safely.', [
                'fbm_sync_run_id' => (int) $run->id,
                'fbm_connection_id' => (int) $connection->id,
                'exception_class' => get_class($exception),
            ]);

            throw new RuntimeException('FB MARKETING could not enqueue the read-only sync. Verify the dedicated queue readiness checks.');
        }

        Log::info('FB MARKETING sync queued.', [
            'fbm_sync_run_id' => (int) $run->id,
            'fbm_connection_id' => (int) $connection->id,
            'sync_scope' => FbmSyncRun::SCOPE_FULL_READ_ONLY,
            'trigger_type' => $triggerType,
        ]);

        return $run;
    }

    protected function assertTriggerType(string $triggerType): void
    {
        if (!in_array($triggerType, [
            FbmSyncRun::TRIGGER_MANUAL,
            FbmSyncRun::TRIGGER_SCHEDULED,
            FbmSyncRun::TRIGGER_CLI,
        ], true)) {
            throw new RuntimeException('FB MARKETING received an unsupported sync trigger type.');
        }
    }

    protected function applicationFingerprint(): string
    {
        return hash_hmac('sha256', 'application|' . (string) config('app.url'), (string) config('app.key'));
    }

    protected function lockFingerprint(int $connectionId, string $scope): string
    {
        return hash_hmac('sha256', 'lock|' . $connectionId . '|' . $scope, (string) config('app.key'));
    }
}

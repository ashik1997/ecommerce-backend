<?php

namespace App\Services\FbMarketing;

use App\Exceptions\FbMarketing\FbmRetryableOperationalActionException;
use App\Models\FbMarketing\FbmAd;
use App\Models\FbMarketing\FbmAdSet;
use App\Models\FbMarketing\FbmCampaign;
use App\Models\FbMarketing\FbmCampaignOperationalAction;
use App\Models\FbMarketing\FbmConnection;
use App\Support\Security\SecretRedactor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

class FbmCampaignOperationalActionWorkerService
{
    public function __construct(
        protected FbmProviderWriterReadinessService $providerWriterReadiness,
        protected FbmCampaignOperationalProviderClient $providerClient,
        protected FbmProviderWriteSafetyService $writeSafety
    ) {
    }

    public function executeQueued(string $actionUuid): FbmCampaignOperationalAction
    {
        $action = $this->execute($actionUuid, 'queue');

        if ((string) $action->status === 'retryable_failed') {
            throw new FbmRetryableOperationalActionException('FB MARKETING operational action failed with a retryable safe status.');
        }

        return $action;
    }

    public function execute(string $actionUuid, string $origin = 'manual'): FbmCampaignOperationalAction
    {
        $action = $this->action($actionUuid);
        $lock = Cache::lock('fbm:operational-action:' . $this->hmac((string) $action->action_uuid), $this->lockSeconds());

        if (!$lock->get()) {
            throw new RuntimeException('Another FB MARKETING operational action worker currently owns this action.');
        }

        try {
            return $this->executeLocked($action->fresh(['draft', 'publishAttempt']), $origin);
        } finally {
            optional($lock)->release();
        }
    }

    public function markQueueExhausted(string $actionUuid): void
    {
        if (!$this->schemaReady()) {
            return;
        }

        FbmCampaignOperationalAction::query()
            ->where('action_uuid', $this->normalizeUuid($actionUuid))
            ->where('status', 'retryable_failed')
            ->update([
                'status' => 'failed',
                'redacted_message' => 'Operational action exhausted bounded queue attempts and stopped safely.',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    private function executeLocked(FbmCampaignOperationalAction $action, string $origin): FbmCampaignOperationalAction
    {
        if ((string) $action->status === 'completed') {
            return $action;
        }

        if (!(bool) config('fb_marketing.operational_actions.provider_writes_enabled', false)) {
            return $this->blockAction($action, 'Provider writes are disabled by configuration.');
        }

        $writerReadiness = $this->providerWriterReadiness->assertReadyFor('operational_actions');
        if (empty($writerReadiness['ready'])) {
            return $this->blockAction($action, (string) $writerReadiness['message']);
        }

        try {
            [$connection, $target] = $this->target($action);
            $payload = $this->payload($action, $target);
        } catch (Throwable $exception) {
            return $this->blockAction($action, $exception->getMessage());
        }

        $action->forceFill([
            'status' => 'running',
            'execution_mode' => 'provider_write',
            'redacted_message' => 'Operational action worker is executing an allow-listed Meta Marketing API mutation.',
            'started_at' => $action->started_at ?: now(),
            'completed_at' => null,
        ])->save();

        $result = $this->providerClient->post(
            $connection,
            (string) $target->provider_sync_key,
            $payload,
            'campaign_operational_' . (string) $action->action_type
        );

        if (empty($result['successful'])) {
            return $this->failAction(
                $action->fresh(),
                (string) ($result['redacted_message'] ?? 'Operational action failed safely.'),
                !empty($result['retryable']),
                $result
            );
        }

        $this->applyLocalMirror($target, $action, $payload);

        $after = is_array($action->fresh()->after_state) ? $action->fresh()->after_state : [];
        $after['rollback_plan'] = $this->writeSafety->operationalRollbackPlan($action->fresh(), $payload);
        $after['reconciliation'] = $this->writeSafety->operationalReconciliationSummary($action->fresh(), $target, $payload);

        $action->forceFill([
            'status' => 'completed',
            'after_state' => $after,
            'redacted_message' => 'Meta Marketing API accepted the operational action and the local mirror was updated.',
            'completed_at' => now(),
        ])->save();

        return $action->fresh();
    }

    private function target(FbmCampaignOperationalAction $action): array
    {
        $attempt = $action->publishAttempt;
        if (!$attempt || (string) $attempt->status !== 'completed') {
            throw new RuntimeException('Operational action requires a completed campaign publish attempt.');
        }

        $mapping = [
            'campaign' => [FbmCampaign::class, $attempt->provider_campaign_local_id],
            'ad_set' => [FbmAdSet::class, $attempt->provider_ad_set_local_id],
            'ad' => [FbmAd::class, $attempt->provider_ad_local_id],
        ][(string) $action->target_type] ?? null;
        if (!$mapping) {
            throw new RuntimeException('Operational action target type is not allow-listed.');
        }

        [$class, $localId] = $mapping;
        $target = $localId ? $class::query()->find((int) $localId) : null;
        if (!$target || !$target->provider_sync_key) {
            throw new RuntimeException('Operational action target provider mirror is unavailable.');
        }

        $connection = FbmConnection::query()->find((int) $target->fbm_connection_id);
        if (!$connection || !$connection->is_active) {
            throw new RuntimeException('Operational action requires an active encrypted connection.');
        }

        return [$connection, $target];
    }

    private function payload(FbmCampaignOperationalAction $action, Model $target): array
    {
        $type = (string) $action->action_type;
        if ($type === 'pause') {
            return ['status' => 'PAUSED'];
        }
        if ($type === 'resume') {
            return ['status' => 'ACTIVE'];
        }
        if ($type === 'update_budget') {
            if (!($target instanceof FbmAdSet)) {
                throw new RuntimeException('Budget updates are allowed only on ad set targets.');
            }
            $budgetType = (string) ($action->budget_type ?: ($action->after_state['budget_type'] ?? 'daily'));
            $budgetAmount = $action->budget_amount !== null ? (float) $action->budget_amount : (float) ($action->after_state['budget_amount'] ?? 0);
            $this->assertBudgetCap($budgetType, $budgetAmount);

            return [$budgetType === 'lifetime' ? 'lifetime_budget' : 'daily_budget' => $this->minorUnits($budgetAmount)];
        }
        if ($type === 'update_schedule') {
            if (!($target instanceof FbmAdSet)) {
                throw new RuntimeException('Schedule updates are allowed only on ad set targets.');
            }
            $payload = [];
            if ($action->starts_at) {
                $payload['start_time'] = $action->starts_at->toIso8601String();
            }
            if ($action->ends_at) {
                $payload['end_time'] = $action->ends_at->toIso8601String();
            }
            if ($payload === []) {
                throw new RuntimeException('Schedule update requires a start or end time.');
            }

            return $payload;
        }

        throw new RuntimeException('Safe edit provider writer is not mapped in FBM-33. Use pause, resume, budget or schedule actions.');
    }

    private function applyLocalMirror(Model $target, FbmCampaignOperationalAction $action, array $payload): void
    {
        $updates = ['updated_at' => now(), 'last_seen_at' => now()];
        if (isset($payload['status'])) {
            $updates['configured_status'] = (string) $payload['status'];
            if (in_array((string) $payload['status'], ['PAUSED', 'ACTIVE'], true)) {
                $updates['effective_status'] = (string) $payload['status'];
            }
        }
        if ($target instanceof FbmAdSet) {
            if (isset($payload['daily_budget'])) {
                $updates['daily_budget'] = (string) $payload['daily_budget'];
                $updates['lifetime_budget'] = null;
            }
            if (isset($payload['lifetime_budget'])) {
                $updates['lifetime_budget'] = (string) $payload['lifetime_budget'];
                $updates['daily_budget'] = null;
            }
        }

        $target->forceFill($updates)->save();

        $after = is_array($action->after_state) ? $action->after_state : [];
        $after['local_mirror_updated'] = true;
        $after['provider_payload_keys'] = array_values(array_keys($payload));
        $action->forceFill(['after_state' => $after])->save();
    }

    private function assertBudgetCap(string $budgetType, float $budgetAmount): void
    {
        $budgetType = in_array($budgetType, ['daily', 'lifetime'], true) ? $budgetType : 'daily';
        $cap = $budgetType === 'lifetime'
            ? (float) config('fb_marketing.provider_writer.max_single_lifetime_budget_amount', 50000)
            : (float) config('fb_marketing.provider_writer.max_single_daily_budget_amount', 5000);
        if ($budgetAmount <= 0 || $budgetAmount > $cap) {
            throw new RuntimeException('Operational action budget is outside the configured provider-writer safety cap.');
        }
    }

    private function minorUnits(float $amount): int
    {
        return max(1, (int) round($amount * 100));
    }

    private function blockAction(FbmCampaignOperationalAction $action, string $message): FbmCampaignOperationalAction
    {
        $action->forceFill([
            'status' => 'blocked_preflight',
            'redacted_message' => $this->message($message),
            'completed_at' => now(),
        ])->save();

        return $action->fresh();
    }

    private function failAction(FbmCampaignOperationalAction $action, string $message, bool $retryable, array $result): FbmCampaignOperationalAction
    {
        $after = is_array($action->after_state) ? $action->after_state : [];
        $after['provider_response_ref'] = $result['provider_response_ref'] ?? null;
        $after['http_status'] = $result['http_status'] ?? null;
        $after['retryable'] = $retryable;
        $after['rollback_plan'] = ['strategy' => 'no_provider_success_to_rollback', 'automatic_execution' => false];

        $action->forceFill([
            'status' => $retryable ? 'retryable_failed' : 'failed',
            'after_state' => $after,
            'redacted_message' => $this->message($message),
            'completed_at' => $retryable ? null : now(),
        ])->save();

        return $action->fresh();
    }

    private function message(string $message): string
    {
        $message = trim(SecretRedactor::redactString($message));

        return $message !== '' ? substr($message, 0, 500) : 'Operational action stopped safely.';
    }

    private function action(string $actionUuid): FbmCampaignOperationalAction
    {
        if (!$this->schemaReady()) {
            throw new RuntimeException('FB MARKETING operational action schema is not ready.');
        }

        return FbmCampaignOperationalAction::query()
            ->with(['draft', 'publishAttempt'])
            ->where('action_uuid', $this->normalizeUuid($actionUuid))
            ->firstOrFail();
    }

    private function normalizeUuid(string $uuid): string
    {
        $uuid = trim($uuid);
        if (!preg_match('/^[0-9a-fA-F-]{36}$/', $uuid)) {
            throw new RuntimeException('FB MARKETING received an invalid operational action reference.');
        }

        return $uuid;
    }

    private function schemaReady(): bool
    {
        foreach (['fbm_campaign_operational_actions', 'fbm_campaign_publish_attempts', 'fbm_campaigns', 'fbm_ad_sets', 'fbm_ads'] as $table) {
            if (!Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }

    private function lockSeconds(): int
    {
        return max(30, min(900, (int) config('fb_marketing.operational_actions.lock_ttl_seconds', 180)));
    }

    private function hmac(string $value): string
    {
        return hash_hmac('sha256', $value, (string) config('app.key', ''));
    }
}

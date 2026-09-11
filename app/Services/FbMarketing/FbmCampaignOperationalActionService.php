<?php

namespace App\Services\FbMarketing;

use App\Models\FbMarketing\FbmCampaignDraft;
use App\Models\FbMarketing\FbmCampaignOperationalAction;
use App\Models\FbMarketing\FbmCampaignPublishAttempt;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class FbmCampaignOperationalActionService
{
    protected FbmProviderWriterReadinessService $providerWriterReadiness;
    protected FbmCampaignOperationalActionDispatchService $actionDispatch;

    public function __construct(
        FbmProviderWriterReadinessService $providerWriterReadiness,
        FbmCampaignOperationalActionDispatchService $actionDispatch
    )
    {
        $this->providerWriterReadiness = $providerWriterReadiness;
        $this->actionDispatch = $actionDispatch;
    }

    public function build(): array
    {
        $schemaReady = $this->schemaReady();

        return [
            'schema_ready' => $schemaReady,
            'provider_writes_enabled' => $this->providerWritesEnabled(),
            'provider_writer' => $this->providerWriterReadiness->currentSummary(),
            'action_type_options' => $this->actionTypeOptions(),
            'target_type_options' => $this->targetTypeOptions(),
            'actions' => $schemaReady ? $this->actions()->values()->all() : [],
            'warnings' => $schemaReady ? [] : [$this->warning('danger', 'FBM-25 operational action tables are required before action ledgers are available.')],
        ];
    }

    public function create(int $draftId, array $input, ?int $userId): array
    {
        if (!$this->schemaReady()) {
            return ['status' => 'skipped', 'message' => 'FBM-25 operational action schema is not ready.'];
        }

        $draft = FbmCampaignDraft::query()->find($draftId);
        if (!$draft || $draft->status !== 'approved') {
            return ['status' => 'skipped', 'message' => 'Only approved campaign drafts can receive controlled operational actions.'];
        }

        $latestAttempt = FbmCampaignPublishAttempt::query()
            ->where('fbm_campaign_draft_id', (int) $draft->id)
            ->orderByDesc('created_at')
            ->first();

        $actionType = $this->safeOption($input['action_type'] ?? 'pause', $this->actionTypeOptions(), 'pause');
        $targetType = $this->safeOption($input['target_type'] ?? 'campaign', $this->targetTypeOptions(), 'campaign');
        $before = $this->beforeState($draft, $latestAttempt, $targetType);
        $after = $this->afterState($before, $actionType, $input);
        $idempotencyKey = $this->idempotencyKey($draft, $latestAttempt, $actionType, $targetType, $after);

        $actionToDispatch = null;
        $result = DB::transaction(function () use ($draft, $latestAttempt, $actionType, $targetType, $before, $after, $input, $userId, $idempotencyKey, &$actionToDispatch): array {
            $action = FbmCampaignOperationalAction::query()->firstOrCreate([
                'idempotency_key' => $idempotencyKey,
            ], [
                'action_uuid' => (string) Str::uuid(),
                'fbm_campaign_draft_id' => (int) $draft->id,
                'fbm_campaign_publish_attempt_id' => optional($latestAttempt)->id,
                'action_type' => $actionType,
                'target_type' => $targetType,
                'target_local_id' => $this->targetLocalId($latestAttempt, $targetType),
                'status' => 'queued',
                'execution_mode' => $this->providerWritesEnabled() ? 'provider_write' : 'provider_writes_disabled',
                'before_state' => $before,
                'after_state' => $after,
                'budget_amount' => $this->money($input['budget_amount'] ?? null),
                'budget_type' => $this->safeBudgetType($input['budget_type'] ?? null),
                'starts_at' => $this->dateValue($input['starts_at'] ?? null),
                'ends_at' => $this->dateValue($input['ends_at'] ?? null),
                'actor_user_id' => $userId,
                'reason' => $this->safeString($input['reason'] ?? null, 500),
                'started_at' => now(),
            ]);

            if (!$action->wasRecentlyCreated && in_array($action->status, ['blocked_preflight', 'ready_for_provider_worker', 'queued_for_provider_worker', 'running', 'retryable_failed', 'failed', 'completed'], true)) {
                return ['status' => $action->status, 'message' => 'Existing operational action reused by idempotency key.'];
            }

            if (!$this->providerWritesEnabled()) {
                $action->forceFill([
                    'status' => 'blocked_preflight',
                    'redacted_message' => 'Provider writes are disabled. No Meta operational mutation was sent.',
                    'completed_at' => now(),
                ])->save();

                return ['status' => 'blocked_preflight', 'message' => 'Operational action recorded locally; provider writes are disabled by configuration.'];
            }

            $writerReadiness = $this->providerWriterReadiness->assertReadyFor('operational_actions');
            if (empty($writerReadiness['ready'])) {
                $action->forceFill([
                    'status' => 'blocked_preflight',
                    'redacted_message' => $writerReadiness['message'],
                    'completed_at' => now(),
                ])->save();

                return ['status' => 'blocked_preflight', 'message' => $writerReadiness['message']];
            }

            $action->forceFill([
                'status' => 'ready_for_provider_worker',
                'redacted_message' => 'Controlled provider operational writer is enabled; execute mutation with before/after audit.',
            ])->save();
            $actionToDispatch = (int) $action->id;

            return ['status' => 'ready_for_provider_worker', 'message' => 'Operational action is ready for the controlled provider writer.'];
        });

        if ($actionToDispatch) {
            try {
                $queuedAction = $this->actionDispatch->dispatch(
                    FbmCampaignOperationalAction::query()->findOrFail($actionToDispatch)
                );
            } catch (\Throwable $exception) {
                return ['status' => 'retryable_failed', 'message' => $exception->getMessage()];
            }

            return ['status' => (string) $queuedAction->status, 'message' => 'Operational action queued for the controlled provider writer.'];
        }

        return $result;
    }

    private function beforeState(FbmCampaignDraft $draft, ?FbmCampaignPublishAttempt $attempt, string $targetType): array
    {
        return [
            'target_type' => $targetType,
            'draft_status' => (string) $draft->status,
            'approval_version' => (int) $draft->approval_version,
            'publish_attempt_status' => optional($attempt)->status,
            'configured_status' => 'PAUSED',
            'budget_type' => (string) $draft->budget_type,
            'budget_amount' => (float) $draft->budget_amount,
            'starts_at' => optional($draft->starts_at)->toDateTimeString(),
            'ends_at' => optional($draft->ends_at)->toDateTimeString(),
        ];
    }

    private function afterState(array $before, string $actionType, array $input): array
    {
        $after = $before;
        if ($actionType === 'pause') {
            $after['configured_status'] = 'PAUSED';
        } elseif ($actionType === 'resume') {
            $after['configured_status'] = 'ACTIVE';
        } elseif ($actionType === 'update_budget') {
            $after['budget_type'] = $this->safeBudgetType($input['budget_type'] ?? null) ?: $before['budget_type'];
            $after['budget_amount'] = $this->money($input['budget_amount'] ?? null) ?: $before['budget_amount'];
        } elseif ($actionType === 'update_schedule') {
            $after['starts_at'] = $this->dateValue($input['starts_at'] ?? null) ?: $before['starts_at'];
            $after['ends_at'] = $this->dateValue($input['ends_at'] ?? null) ?: $before['ends_at'];
        } else {
            $after['safe_edit_requested'] = true;
        }
        $after['action_type'] = $actionType;

        return $after;
    }

    private function actions(): Collection
    {
        return FbmCampaignOperationalAction::query()
            ->with('draft:id,draft_name,status,approval_version')
            ->orderByDesc('created_at')
            ->limit(max(1, (int) config('fb_marketing.operational_actions.history_limit', 40)))
            ->get()
            ->map(fn(FbmCampaignOperationalAction $action): array => $action->toSafeSummary());
    }

    private function targetLocalId(?FbmCampaignPublishAttempt $attempt, string $targetType): ?int
    {
        if (!$attempt) {
            return null;
        }

        return [
            'campaign' => $attempt->provider_campaign_local_id,
            'ad_set' => $attempt->provider_ad_set_local_id,
            'ad' => $attempt->provider_ad_local_id,
        ][$targetType] ?? null;
    }

    private function idempotencyKey(FbmCampaignDraft $draft, ?FbmCampaignPublishAttempt $attempt, string $actionType, string $targetType, array $after): string
    {
        return hash_hmac('sha256', json_encode([
            'fbm-operational-action',
            'draft_id' => (int) $draft->id,
            'approval_version' => (int) $draft->approval_version,
            'publish_attempt_id' => optional($attempt)->id,
            'action_type' => $actionType,
            'target_type' => $targetType,
            'after' => $after,
        ]), (string) config('app.key', ''));
    }

    private function schemaReady(): bool
    {
        foreach (['fbm_campaign_drafts', 'fbm_campaign_publish_attempts', 'fbm_campaign_operational_actions'] as $table) {
            if (!Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }

    private function actionTypeOptions(): array
    {
        return ['pause' => 'Pause', 'resume' => 'Resume', 'update_budget' => 'Update budget', 'update_schedule' => 'Update schedule', 'safe_edit' => 'Safe edit'];
    }

    private function targetTypeOptions(): array
    {
        return ['campaign' => 'Campaign', 'ad_set' => 'Ad set', 'ad' => 'Ad'];
    }

    private function safeOption($value, array $options, string $fallback): string
    {
        $value = is_scalar($value) ? strtolower(trim((string) $value)) : '';

        return array_key_exists($value, $options) ? $value : $fallback;
    }

    private function safeBudgetType($value): ?string
    {
        $value = is_scalar($value) ? strtolower(trim((string) $value)) : '';

        return in_array($value, ['daily', 'lifetime'], true) ? $value : null;
    }

    private function safeString($value, int $length): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }
        $value = trim((string) $value);

        return $value === '' ? null : substr($value, 0, $length);
    }

    private function dateValue($value): ?string
    {
        if (!is_scalar($value) || trim((string) $value) === '') {
            return null;
        }

        return date('Y-m-d H:i:s', strtotime((string) $value));
    }

    private function money($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return max(0, round((float) $value, 2));
    }

    private function providerWritesEnabled(): bool
    {
        return (bool) config('fb_marketing.operational_actions.provider_writes_enabled', false);
    }

    private function warning(string $type, string $message): array
    {
        return ['type' => $type, 'message' => $message];
    }
}

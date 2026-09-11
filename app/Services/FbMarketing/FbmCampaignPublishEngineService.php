<?php

namespace App\Services\FbMarketing;

use App\Models\FbMarketing\FbmCampaignDraft;
use App\Models\FbMarketing\FbmCampaignPublishAttempt;
use App\Models\FbMarketing\FbmCampaignPublishSnapshot;
use App\Models\FbMarketing\FbmCampaignPublishStep;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class FbmCampaignPublishEngineService
{
    protected FbmProviderWriterReadinessService $providerWriterReadiness;
    protected FbmCampaignPublishDispatchService $publishDispatch;

    public function __construct(
        FbmProviderWriterReadinessService $providerWriterReadiness,
        FbmCampaignPublishDispatchService $publishDispatch
    )
    {
        $this->providerWriterReadiness = $providerWriterReadiness;
        $this->publishDispatch = $publishDispatch;
    }

    public function build(): array
    {
        $schemaReady = $this->schemaReady();
        $attempts = $schemaReady ? $this->attempts() : collect();
        $providerWriter = $this->providerWriterReadiness->currentSummary();

        return [
            'schema_ready' => $schemaReady,
            'provider_writes_enabled' => $this->providerWritesEnabled(),
            'provider_writer' => $providerWriter,
            'attempts' => $attempts->values()->all(),
            'steps' => $schemaReady ? $this->steps()->values()->all() : [],
            'warnings' => $schemaReady ? [] : [$this->warning('danger', 'FBM-24 publish attempt tables are required before publish attempts are available.')],
        ];
    }

    public function publish(int $draftId, ?int $userId): array
    {
        if (!$this->schemaReady()) {
            return ['status' => 'skipped', 'message' => 'FBM-24 publish schema is not ready.'];
        }

        $draft = FbmCampaignDraft::query()->find($draftId);
        if (!$draft || $draft->status !== 'approved') {
            return ['status' => 'skipped', 'message' => 'Only approved campaign drafts can enter the publish engine.'];
        }

        $snapshot = FbmCampaignPublishSnapshot::query()
            ->where('fbm_campaign_draft_id', (int) $draft->id)
            ->where('approval_version', (int) $draft->approval_version)
            ->orderByDesc('id')
            ->first();

        if (!$snapshot) {
            return ['status' => 'skipped', 'message' => 'Approved draft does not have a matching immutable publish snapshot.'];
        }

        $idempotencyKey = $this->idempotencyKey($draft, $snapshot);

        $attemptToDispatch = null;
        $result = DB::transaction(function () use ($draft, $snapshot, $idempotencyKey, $userId, &$attemptToDispatch): array {
            $attempt = FbmCampaignPublishAttempt::query()->firstOrCreate([
                'fbm_campaign_draft_id' => (int) $draft->id,
                'fbm_campaign_publish_snapshot_id' => (int) $snapshot->id,
                'idempotency_key' => $idempotencyKey,
            ], [
                'attempt_uuid' => (string) Str::uuid(),
                'status' => 'queued',
                'execution_mode' => $this->providerWritesEnabled() ? 'provider_write' : 'provider_writes_disabled',
                'actor_user_id' => $userId,
                'started_at' => now(),
            ]);

            $this->ensureSteps($attempt, $snapshot);

            if (!$attempt->wasRecentlyCreated && in_array($attempt->status, ['completed', 'blocked_preflight', 'partial_failed', 'retryable_failed', 'queued_for_provider_worker', 'running', 'ready_for_provider_worker'], true)) {
                return ['status' => $attempt->status, 'message' => 'Existing publish attempt reused by idempotency key.'];
            }

            if (!$this->providerWritesEnabled()) {
                $this->blockProviderWrites($attempt);

                return ['status' => 'blocked_preflight', 'message' => 'Provider writes are disabled by configuration. Publish attempt and step ledger were created safely.'];
            }

            $writerReadiness = $this->providerWriterReadiness->assertReadyFor('campaign_publish');
            if (empty($writerReadiness['ready'])) {
                $this->blockProviderWriterReadiness($attempt, $writerReadiness);

                return ['status' => 'blocked_preflight', 'message' => $writerReadiness['message']];
            }

            $this->markReadyForProviderWorker($attempt);
            $attemptToDispatch = (int) $attempt->id;

            return ['status' => 'ready_for_provider_worker', 'message' => 'Publish attempt is ready for the controlled provider writer.'];
        });

        if ($attemptToDispatch) {
            try {
                $queuedAttempt = $this->publishDispatch->dispatch(
                    FbmCampaignPublishAttempt::query()->findOrFail($attemptToDispatch)
                );
            } catch (\Throwable $exception) {
                return ['status' => 'retryable_failed', 'message' => $exception->getMessage()];
            }

            return ['status' => (string) $queuedAttempt->status, 'message' => 'Publish attempt queued for the controlled provider writer.'];
        }

        return $result;
    }

    private function ensureSteps(FbmCampaignPublishAttempt $attempt, FbmCampaignPublishSnapshot $snapshot): void
    {
        foreach ($this->stepDefinitions($snapshot) as $definition) {
            FbmCampaignPublishStep::query()->firstOrCreate([
                'fbm_campaign_publish_attempt_id' => (int) $attempt->id,
                'step_key' => $definition['step_key'],
            ], [
                'step_order' => $definition['step_order'],
                'status' => 'pending',
                'http_method' => 'POST',
                'graph_edge' => $definition['graph_edge'],
                'safe_request_summary' => $definition['safe_request_summary'],
            ]);
        }
    }

    private function blockProviderWrites(FbmCampaignPublishAttempt $attempt): void
    {
        $attempt->steps()->update([
            'status' => 'blocked_preflight',
            'redacted_message' => 'Provider writes are disabled. No Meta request was sent.',
            'completed_at' => now(),
        ]);

        $attempt->forceFill([
            'status' => 'blocked_preflight',
            'safe_response_summary' => ['provider_request_count' => 0, 'blocked_reason' => 'provider_writes_disabled'],
            'redacted_message' => 'Provider writes are disabled by config fb_marketing.campaign_publish.provider_writes_enabled.',
            'completed_at' => now(),
        ])->save();
    }

    private function blockProviderWriterReadiness(FbmCampaignPublishAttempt $attempt, array $writerReadiness): void
    {
        $reason = (string) ($writerReadiness['reason'] ?? 'provider_writer_not_ready');
        $message = (string) ($writerReadiness['message'] ?? 'Provider writer readiness check failed.');

        $attempt->steps()->update([
            'status' => 'blocked_preflight',
            'redacted_message' => $message,
            'completed_at' => now(),
        ]);

        $attempt->forceFill([
            'status' => 'blocked_preflight',
            'safe_response_summary' => ['provider_request_count' => 0, 'blocked_reason' => $reason],
            'redacted_message' => $message,
            'completed_at' => now(),
        ])->save();
    }

    private function markReadyForProviderWorker(FbmCampaignPublishAttempt $attempt): void
    {
        $attempt->forceFill([
            'status' => 'ready_for_provider_worker',
            'safe_response_summary' => ['provider_request_count' => 0, 'worker_required' => true],
            'redacted_message' => 'Controlled provider writer is enabled; execute steps with retry and response capture.',
            'completed_at' => null,
        ])->save();
    }

    private function stepDefinitions(FbmCampaignPublishSnapshot $snapshot): array
    {
        $payload = is_array($snapshot->snapshot_payload) ? $snapshot->snapshot_payload : [];
        $draft = is_array($payload['draft'] ?? null) ? $payload['draft'] : [];
        $assets = collect(is_array($payload['assets'] ?? null) ? $payload['assets'] : []);

        return [
            [
                'step_key' => 'campaign',
                'step_order' => 1,
                'graph_edge' => 'act_{ad_account_id}/campaigns',
                'safe_request_summary' => [
                    'draft_name' => $draft['draft_name'] ?? null,
                    'objective' => $draft['objective'] ?? null,
                    'special_ad_categories' => $draft['special_ad_categories'] ?? [],
                    'status' => 'PAUSED',
                ],
            ],
            [
                'step_key' => 'ad_set',
                'step_order' => 2,
                'graph_edge' => 'act_{ad_account_id}/adsets',
                'safe_request_summary' => [
                    'budget_type' => $draft['budget_type'] ?? null,
                    'budget_amount' => $draft['budget_amount'] ?? null,
                    'currency' => $draft['currency'] ?? null,
                    'has_audience' => $assets->where('asset_type', 'audience')->isNotEmpty(),
                    'has_product_set' => $assets->where('asset_type', 'product_set')->isNotEmpty(),
                    'status' => 'PAUSED',
                ],
            ],
            [
                'step_key' => 'creative',
                'step_order' => 3,
                'graph_edge' => 'act_{ad_account_id}/adcreatives',
                'safe_request_summary' => [
                    'has_creative_asset' => $assets->where('asset_type', 'creative_asset')->isNotEmpty(),
                    'has_destination_url' => !empty($draft['has_destination_url']),
                ],
            ],
            [
                'step_key' => 'ad',
                'step_order' => 4,
                'graph_edge' => 'act_{ad_account_id}/ads',
                'safe_request_summary' => [
                    'status' => 'PAUSED',
                    'depends_on' => ['campaign', 'ad_set', 'creative'],
                ],
            ],
        ];
    }

    private function attempts(): Collection
    {
        return FbmCampaignPublishAttempt::query()
            ->with(['draft:id,draft_name,status,approval_version', 'steps'])
            ->orderByDesc('created_at')
            ->limit(max(1, (int) config('fb_marketing.campaign_publish.history_limit', 30)))
            ->get()
            ->map(function (FbmCampaignPublishAttempt $attempt): array {
                $row = $attempt->toSafeSummary();
                $row['draft_name'] = optional($attempt->draft)->draft_name;
                $row['step_counts'] = $attempt->steps->groupBy('status')->map->count()->all();

                return $row;
            });
    }

    private function steps(): Collection
    {
        return FbmCampaignPublishStep::query()
            ->orderByDesc('created_at')
            ->orderBy('step_order')
            ->limit(max(1, (int) config('fb_marketing.campaign_publish.step_history_limit', 80)))
            ->get()
            ->map(fn(FbmCampaignPublishStep $step): array => $step->toSafeSummary());
    }

    private function idempotencyKey(FbmCampaignDraft $draft, FbmCampaignPublishSnapshot $snapshot): string
    {
        return hash_hmac('sha256', implode('|', [
            'fbm-campaign-publish',
            (int) $draft->id,
            (int) $draft->approval_version,
            (string) $snapshot->payload_hash,
        ]), (string) config('app.key', ''));
    }

    private function providerWritesEnabled(): bool
    {
        return (bool) config('fb_marketing.campaign_publish.provider_writes_enabled', false);
    }

    private function schemaReady(): bool
    {
        foreach (['fbm_campaign_drafts', 'fbm_campaign_publish_snapshots', 'fbm_campaign_publish_attempts', 'fbm_campaign_publish_steps'] as $table) {
            if (!Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }

    private function warning(string $type, string $message): array
    {
        return ['type' => $type, 'message' => $message];
    }
}

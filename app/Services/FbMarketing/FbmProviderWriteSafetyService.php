<?php

namespace App\Services\FbMarketing;

use App\Models\FbMarketing\FbmAd;
use App\Models\FbMarketing\FbmAdSet;
use App\Models\FbMarketing\FbmCampaign;
use App\Models\FbMarketing\FbmCampaignOperationalAction;
use App\Models\FbMarketing\FbmCampaignPublishAttempt;
use Illuminate\Database\Eloquent\Model;

class FbmProviderWriteSafetyService
{
    public function publishRollbackPlan(FbmCampaignPublishAttempt $attempt): array
    {
        $completedSteps = $attempt->steps()
            ->where('status', 'completed')
            ->orderBy('step_order')
            ->pluck('step_key')
            ->values()
            ->all();

        return [
            'strategy' => 'pause_and_reconcile',
            'automatic_delete' => false,
            'manual_operator_review_required' => true,
            'reason' => 'Published objects are created in PAUSED status; rollback keeps audit ledgers and verifies local mirrors before any activation.',
            'completed_step_keys' => $completedSteps,
            'recommended_actions' => [
                'keep_created_objects_paused',
                'run_read_only_hierarchy_sync',
                'compare_local_mirror_with_provider',
                'retry_or_close_attempt_after_operator_review',
            ],
        ];
    }

    public function publishReconciliationSummary(FbmCampaignPublishAttempt $attempt): array
    {
        $attempt = $attempt->fresh();
        $objects = [
            'campaign' => $this->safeObjectSummary(FbmCampaign::query()->find((int) $attempt->provider_campaign_local_id)),
            'ad_set' => $this->safeObjectSummary(FbmAdSet::query()->find((int) $attempt->provider_ad_set_local_id)),
            'creative' => $this->safeObjectSummary($attempt->provider_creative_local_id ? \App\Models\FbMarketing\FbmCreative::query()->find((int) $attempt->provider_creative_local_id) : null),
            'ad' => $this->safeObjectSummary(FbmAd::query()->find((int) $attempt->provider_ad_local_id)),
        ];

        $missing = [];
        foreach ($objects as $key => $summary) {
            if (empty($summary['local_mirror_present'])) {
                $missing[] = $key;
            }
        }

        return [
            'mode' => 'local_mirror_reconciliation',
            'provider_ids_hidden' => true,
            'missing_local_mirrors' => $missing,
            'requires_read_only_sync' => $missing !== [],
            'objects' => $objects,
        ];
    }

    public function operationalRollbackPlan(FbmCampaignOperationalAction $action, array $payload): array
    {
        $before = is_array($action->before_state) ? $action->before_state : [];
        $inverse = [];

        if (array_key_exists('status', $payload)) {
            $inverse['status'] = (string) ($before['configured_status'] ?? 'PAUSED');
        }
        if (array_key_exists('daily_budget', $payload) || array_key_exists('lifetime_budget', $payload)) {
            $budgetType = (string) ($before['budget_type'] ?? 'daily');
            $inverse[$budgetType === 'lifetime' ? 'lifetime_budget' : 'daily_budget'] = $this->minorUnits((float) ($before['budget_amount'] ?? 0));
        }
        if (array_key_exists('start_time', $payload) && !empty($before['starts_at'])) {
            $inverse['start_time'] = (string) $before['starts_at'];
        }
        if (array_key_exists('end_time', $payload) && !empty($before['ends_at'])) {
            $inverse['end_time'] = (string) $before['ends_at'];
        }

        return [
            'strategy' => 'inverse_allow_listed_mutation',
            'automatic_execution' => false,
            'manual_operator_review_required' => true,
            'provider_ids_hidden' => true,
            'target_type' => (string) $action->target_type,
            'inverse_payload_keys' => array_values(array_keys(array_filter($inverse, fn($value) => $value !== null && $value !== ''))),
            'recommended_actions' => [
                'run_read_only_hierarchy_sync',
                'confirm_provider_state',
                'submit_inverse_action_if_approved',
            ],
        ];
    }

    public function operationalReconciliationSummary(FbmCampaignOperationalAction $action, Model $target, array $payload): array
    {
        $target = $target->fresh();
        $driftFlags = [];

        if (isset($payload['status']) && (string) $target->configured_status !== (string) $payload['status']) {
            $driftFlags[] = 'configured_status_mismatch';
        }
        if ($target instanceof FbmAdSet && isset($payload['daily_budget']) && (string) $target->daily_budget !== (string) $payload['daily_budget']) {
            $driftFlags[] = 'daily_budget_mismatch';
        }
        if ($target instanceof FbmAdSet && isset($payload['lifetime_budget']) && (string) $target->lifetime_budget !== (string) $payload['lifetime_budget']) {
            $driftFlags[] = 'lifetime_budget_mismatch';
        }

        return [
            'mode' => 'post_write_local_mirror_check',
            'provider_ids_hidden' => true,
            'action_uuid' => (string) $action->action_uuid,
            'target_type' => (string) $action->target_type,
            'payload_keys' => array_values(array_keys($payload)),
            'drift_flags' => $driftFlags,
            'requires_read_only_sync' => true,
            'local_mirror' => $this->safeObjectSummary($target),
        ];
    }

    private function safeObjectSummary(?Model $model): array
    {
        if (!$model) {
            return ['local_mirror_present' => false];
        }

        return [
            'local_mirror_present' => true,
            'local_id' => (int) $model->id,
            'configured_status' => (string) ($model->configured_status ?? ''),
            'effective_status' => (string) ($model->effective_status ?? ''),
            'is_available' => (bool) ($model->is_available ?? false),
            'last_seen_at' => optional($model->last_seen_at ?? null)->toDateTimeString(),
            'updated_at' => optional($model->updated_at ?? null)->toDateTimeString(),
        ];
    }

    private function minorUnits(float $amount): int
    {
        return max(1, (int) round($amount * 100));
    }
}

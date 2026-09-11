<?php

namespace App\Services\FbMarketing;

use App\Models\FbMarketing\FbmAlert;
use App\Models\FbMarketing\FbmRecommendation;
use App\Models\FbMarketing\FbmRecommendationRule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class FbmRecommendationRuleService
{
    public function build(): array
    {
        $schemaReady = $this->schemaReady();
        if ($schemaReady) {
            $this->ensureDefaultRules();
        }

        return [
            'schema_ready' => $schemaReady,
            'summary' => $schemaReady ? $this->summary() : $this->emptySummary(),
            'rules' => $schemaReady ? $this->rules()->values()->all() : [],
            'recommendations' => $schemaReady ? $this->recommendations()->values()->all() : [],
            'provider_writes_enabled' => false,
            'warnings' => $schemaReady ? [] : [$this->warning('danger', 'FBM-30 recommendation tables are required before controlled rules are available.')],
        ];
    }

    public function refresh(?int $actorUserId): array
    {
        if (!$this->schemaReady()) {
            return ['status' => 'skipped', 'message' => 'Recommendation schema is not ready.', 'count' => 0];
        }

        $this->ensureDefaultRules();
        $count = 0;

        foreach ($this->openAlerts() as $alert) {
            $rule = FbmRecommendationRule::query()
                ->where('rule_key', $this->ruleKeyForAlert($alert))
                ->where('is_enabled', true)
                ->first();
            if (!$rule) {
                continue;
            }

            $this->upsertRecommendation($rule, $alert, $actorUserId);
            $count++;
        }

        return ['status' => 'completed', 'message' => 'Controlled recommendations refreshed from local alerts.', 'count' => $count];
    }

    public function approve(string $uuid, ?int $actorUserId, ?string $note): array
    {
        return $this->decide($uuid, FbmRecommendation::STATUS_APPROVED, $actorUserId, $note);
    }

    public function dismiss(string $uuid, ?int $actorUserId, ?string $note): array
    {
        return $this->decide($uuid, FbmRecommendation::STATUS_DISMISSED, $actorUserId, $note);
    }

    private function decide(string $uuid, string $status, ?int $actorUserId, ?string $note): array
    {
        if (!$this->schemaReady()) {
            return ['status' => 'skipped', 'message' => 'Recommendation schema is not ready.'];
        }

        $recommendation = FbmRecommendation::query()->where('recommendation_uuid', $uuid)->first();
        if (!$recommendation) {
            return ['status' => 'skipped', 'message' => 'Recommendation was not found.'];
        }

        if (!in_array((string) $recommendation->status, [FbmRecommendation::STATUS_SUGGESTED, FbmRecommendation::STATUS_APPROVED], true)) {
            return ['status' => (string) $recommendation->status, 'message' => 'Recommendation decision was already finalized.'];
        }

        $fields = [
            'status' => $status,
            'decision_note' => $this->safeString($note, 500),
        ];
        if ($status === FbmRecommendation::STATUS_APPROVED) {
            $fields['approved_by'] = $actorUserId;
            $fields['approved_at'] = now();
            $fields['dismissed_by'] = null;
            $fields['dismissed_at'] = null;
        } else {
            $fields['dismissed_by'] = $actorUserId;
            $fields['dismissed_at'] = now();
        }

        $recommendation->forceFill($fields)->save();

        return [
            'status' => $status,
            'message' => $status === FbmRecommendation::STATUS_APPROVED
                ? 'Recommendation approved locally. Create any operational action through the controlled FBM-25 flow.'
                : 'Recommendation dismissed locally.',
        ];
    }

    private function upsertRecommendation(FbmRecommendationRule $rule, FbmAlert $alert, ?int $actorUserId): FbmRecommendation
    {
        $dedupeKey = hash('sha256', implode('|', ['fbm-recommendation', $rule->rule_key, (int) $alert->id]));
        $context = is_array($alert->safe_context) ? $alert->safe_context : [];

        return DB::transaction(function () use ($rule, $alert, $dedupeKey, $context, $actorUserId): FbmRecommendation {
            $recommendation = FbmRecommendation::query()->firstOrNew(['dedupe_key' => $dedupeKey]);
            if (!$recommendation->exists) {
                $recommendation->recommendation_uuid = (string) Str::uuid();
                $recommendation->first_seen_at = now();
            }

            if (in_array((string) $recommendation->status, [FbmRecommendation::STATUS_DISMISSED], true)) {
                return $recommendation;
            }

            $recommendation->forceFill([
                'fbm_recommendation_rule_id' => (int) $rule->id,
                'fbm_alert_id' => (int) $alert->id,
                'recommendation_type' => (string) $rule->rule_type,
                'severity' => (string) $rule->severity,
                'status' => $recommendation->status ?: FbmRecommendation::STATUS_SUGGESTED,
                'source_type' => $alert->source_type,
                'source_id' => $alert->source_id,
                'title' => $this->titleFor($rule, $alert),
                'recommended_action_type' => $rule->recommended_action_type,
                'recommended_target_type' => $rule->recommended_target_type,
                'safe_context' => [
                    'alert_type' => (string) $alert->alert_type,
                    'alert_title' => (string) $alert->title,
                    'alert_context' => $context,
                    'approval_required' => true,
                    'autonomous_execution' => false,
                    'created_or_refreshed_by' => $actorUserId,
                ],
                'last_seen_at' => now(),
            ])->save();

            return $recommendation;
        });
    }

    private function ensureDefaultRules(): void
    {
        foreach ($this->defaultRules() as $row) {
            FbmRecommendationRule::query()->updateOrCreate(
                ['rule_key' => $row['rule_key']],
                array_merge($row, [
                    'rule_uuid' => $row['rule_uuid'] ?? (string) Str::uuid(),
                    'is_enabled' => true,
                    'requires_approval' => true,
                    'status' => 'active',
                ])
            );
        }
    }

    private function defaultRules(): array
    {
        return [
            $this->rule('overspend_review', 'overspend', 'Review budget pacing for overspend alert', 'warning', 'update_budget', 'campaign'),
            $this->rule('poor_performance_review', 'poor_performance', 'Review low-performance campaign before scaling spend', 'info', 'safe_edit', 'campaign'),
            $this->rule('stock_risk_review', 'stock_risk', 'Review product availability before continuing ads', 'warning', 'pause', 'campaign'),
            $this->rule('sync_health_review', 'sync_failure', 'Restore read-only sync health before acting on stale data', 'warning', null, null),
            $this->rule('token_health_review', 'token_expiry', 'Refresh Meta token health before campaign operations', 'critical', null, null),
        ];
    }

    private function rule(string $key, string $type, string $title, string $severity, ?string $actionType, ?string $targetType): array
    {
        return [
            'rule_key' => $key,
            'rule_type' => $type,
            'title' => $title,
            'severity' => $severity,
            'recommended_action_type' => $actionType,
            'recommended_target_type' => $targetType,
            'safe_conditions' => ['source' => 'fbm_alerts', 'requires_operator_approval' => true],
            'redacted_message' => 'Local recommendation rule. Autonomous provider execution is disabled.',
        ];
    }

    private function openAlerts(): Collection
    {
        if (!Schema::hasTable('fbm_alerts')) {
            return collect();
        }

        return FbmAlert::query()
            ->where('status', FbmAlert::STATUS_OPEN)
            ->whereIn('alert_type', ['overspend', 'poor_performance', 'stock_risk', 'sync_failure', 'token_expiry'])
            ->orderByDesc('last_seen_at')
            ->limit(max(1, (int) config('fb_marketing.recommendations.max_alerts_per_refresh', 100)))
            ->get();
    }

    private function rules(): Collection
    {
        return FbmRecommendationRule::query()
            ->orderBy('rule_type')
            ->get()
            ->map(fn(FbmRecommendationRule $rule): array => $rule->toSafeSummary());
    }

    private function recommendations(): Collection
    {
        return FbmRecommendation::query()
            ->with('rule:id,title')
            ->orderByRaw("FIELD(status, 'suggested', 'approved', 'dismissed')")
            ->orderByRaw("FIELD(severity, 'critical', 'warning', 'info')")
            ->orderByDesc('last_seen_at')
            ->limit(max(1, (int) config('fb_marketing.recommendations.history_limit', 50)))
            ->get()
            ->map(fn(FbmRecommendation $recommendation): array => $recommendation->toSafeSummary());
    }

    private function summary(): array
    {
        return [
            'rule_count' => FbmRecommendationRule::query()->count(),
            'suggested_count' => FbmRecommendation::query()->where('status', FbmRecommendation::STATUS_SUGGESTED)->count(),
            'approved_count' => FbmRecommendation::query()->where('status', FbmRecommendation::STATUS_APPROVED)->count(),
            'dismissed_count' => FbmRecommendation::query()->where('status', FbmRecommendation::STATUS_DISMISSED)->count(),
        ];
    }

    private function emptySummary(): array
    {
        return ['rule_count' => 0, 'suggested_count' => 0, 'approved_count' => 0, 'dismissed_count' => 0];
    }

    private function ruleKeyForAlert(FbmAlert $alert): string
    {
        return [
            'overspend' => 'overspend_review',
            'poor_performance' => 'poor_performance_review',
            'stock_risk' => 'stock_risk_review',
            'sync_failure' => 'sync_health_review',
            'token_expiry' => 'token_health_review',
        ][(string) $alert->alert_type] ?? 'sync_health_review';
    }

    private function titleFor(FbmRecommendationRule $rule, FbmAlert $alert): string
    {
        return substr((string) $rule->title . ': ' . (string) $alert->title, 0, 255);
    }

    private function schemaReady(): bool
    {
        return Schema::hasTable('fbm_recommendation_rules')
            && Schema::hasTable('fbm_recommendations');
    }

    private function safeString($value, int $length): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);
        return $value === '' ? null : substr($value, 0, $length);
    }

    private function warning(string $type, string $message): array
    {
        return ['type' => $type, 'message' => $message];
    }
}

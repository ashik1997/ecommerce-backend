<?php

namespace App\Services\FbMarketing;

use App\Models\FbMarketing\FbmAdAccount;
use App\Models\FbMarketing\FbmAdAccountReconciliationRun;
use App\Models\FbMarketing\FbmAdAccountWebhookLog;
use App\Models\FbMarketing\FbmAlert;
use App\Models\FbMarketing\FbmConnection;
use App\Models\FbMarketing\FbmConnectionHealthCheck;
use App\Models\FbMarketing\FbmSyncRun;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class FbmWebhookReconciliationAlertService
{
    public function build(): array
    {
        $schemaReady = $this->schemaReady();

        return [
            'schema_ready' => $schemaReady,
            'webhook_path' => '/api/fb-marketing/webhooks/ad-account',
            'summary' => $schemaReady ? $this->summary() : $this->emptySummary(),
            'alerts' => $schemaReady ? $this->alerts()->values()->all() : [],
            'webhook_logs' => $schemaReady ? $this->webhookLogs()->values()->all() : [],
            'reconciliation_runs' => $schemaReady ? $this->reconciliationRuns()->values()->all() : [],
            'warnings' => $schemaReady ? [] : [$this->warning('danger', 'FBM-27 webhook, reconciliation and alert tables are required before this page is available.')],
        ];
    }

    public function verify(Request $request): array
    {
        $mode = (string) $request->query('hub_mode', $request->query('hub.mode', ''));
        $token = (string) $request->query('hub_verify_token', $request->query('hub.verify_token', ''));
        $challenge = (string) $request->query('hub_challenge', $request->query('hub.challenge', ''));
        $connection = $this->connectionForVerifyToken($token);

        if ($mode === 'subscribe' && $connection && $challenge !== '') {
            $this->recordWebhookLog('verify', 'success', $request, $connection, null, [], 'Ad-account webhook verification succeeded.');
            return ['ok' => true, 'challenge' => $challenge];
        }

        $this->recordWebhookLog('verify', 'failed', $request, $connection, null, [], 'Ad-account webhook verification failed safely.');
        return ['ok' => false, 'challenge' => null];
    }

    public function receive(Request $request): array
    {
        if (!$this->schemaReady()) {
            return ['status' => 'schema_not_ready', 'processed' => 0];
        }

        $payload = $request->all();
        $processed = 0;

        foreach ((array) ($payload['entry'] ?? []) as $entry) {
            $entryObjectId = $this->safeProviderId($entry['id'] ?? null);
            foreach ((array) ($entry['changes'] ?? []) as $change) {
                if (!is_array($change)) {
                    continue;
                }

                $value = is_array($change['value'] ?? null) ? $change['value'] : [];
                $providerObjectId = $this->safeProviderId($value['ad_account_id'] ?? $value['account_id'] ?? $entryObjectId);
                $adAccount = $this->adAccountForProviderId($providerObjectId);
                $connection = $adAccount ? $adAccount->connection : null;

                $this->recordWebhookLog(
                    'callback',
                    'received',
                    $request,
                    $connection,
                    $adAccount,
                    [
                        'object_type' => $this->safeString($payload['object'] ?? 'ad_account', 60),
                        'provider_object_id' => $providerObjectId,
                        'change_field' => $this->safeString($change['field'] ?? null, 120),
                        'summary' => $this->safeChangeSummary($change, $value),
                    ],
                    'Ad-account webhook callback received.'
                );
                $processed++;
            }
        }

        if ($processed === 0) {
            $this->recordWebhookLog('callback', 'ignored', $request, null, null, [], 'Ad-account webhook contained no recognized change.');
        }

        return ['status' => 'ok', 'processed' => $processed];
    }

    public function runReconciliation(?int $actorUserId): FbmAdAccountReconciliationRun
    {
        $startedAt = now();
        $run = FbmAdAccountReconciliationRun::query()->create([
            'run_uuid' => (string) Str::uuid(),
            'actor_user_id' => $actorUserId,
            'execution_mode' => 'manual',
            'status' => 'running',
            'source' => 'manual',
            'started_at' => $startedAt,
            'redacted_message' => 'Ad-account reconciliation started.',
        ]);

        $alerts = [];
        $alerts = array_merge($alerts, $this->tokenExpiryAlerts($actorUserId));
        $alerts = array_merge($alerts, $this->syncFailureAlerts($actorUserId));
        $alerts = array_merge($alerts, $this->overspendAlerts($actorUserId));
        $alerts = array_merge($alerts, $this->poorPerformanceAlerts($actorUserId));
        $alerts = array_merge($alerts, $this->stockRiskAlerts($actorUserId));

        $syncRunCount = Schema::hasTable('fbm_sync_runs') ? FbmSyncRun::query()->count() : 0;
        $failedSyncCount = Schema::hasTable('fbm_sync_runs')
            ? FbmSyncRun::query()->where('status', FbmSyncRun::STATUS_FAILED)->count()
            : 0;
        $staleHealthCount = $this->staleHealthCount();

        $run->forceFill([
            'status' => 'completed',
            'sync_run_count' => $syncRunCount,
            'failed_sync_count' => $failedSyncCount,
            'stale_health_count' => $staleHealthCount,
            'alert_count' => count($alerts),
            'safe_summary' => [
                'polling_fallback' => 'Use existing bounded manual or scheduled read-only sync when webhook delivery is absent.',
                'detectors' => ['token_expiry', 'sync_failure', 'overspend', 'poor_performance', 'stock_risk'],
                'new_or_refreshed_alerts' => count($alerts),
            ],
            'redacted_message' => 'Ad-account reconciliation completed with local alert refresh.',
            'completed_at' => now(),
        ])->save();

        return $run;
    }

    private function tokenExpiryAlerts(?int $actorUserId): array
    {
        if (!Schema::hasTable('fbm_connection_health_checks')) {
            return [];
        }

        $warningDays = max(1, (int) config('fb_marketing.alerts.token_expiry_warning_days', 14));
        return FbmConnectionHealthCheck::query()
            ->with('connection:id,connection_name')
            ->where(function ($query) use ($warningDays) {
                $query->where('token_is_valid', false)
                    ->orWhere('status', FbmConnectionHealthCheck::STATUS_FAILED)
                    ->orWhere('expires_at', '<=', now()->addDays($warningDays))
                    ->orWhere('data_access_expires_at', '<=', now()->addDays($warningDays));
            })
            ->orderByDesc('checked_at')
            ->limit(20)
            ->get()
            ->map(fn(FbmConnectionHealthCheck $check): FbmAlert => $this->upsertAlert(
                'token_expiry',
                $check->token_is_valid === false ? 'critical' : 'warning',
                'connection_health_check',
                (int) $check->id,
                'Meta token or data access requires attention',
                [
                    'connection_name' => optional($check->connection)->connection_name,
                    'status' => (string) $check->status,
                    'token_is_valid' => $check->token_is_valid,
                    'expires_at' => optional($check->expires_at)->toDateTimeString(),
                    'data_access_expires_at' => optional($check->data_access_expires_at)->toDateTimeString(),
                ],
                $actorUserId
            ))
            ->all();
    }

    private function syncFailureAlerts(?int $actorUserId): array
    {
        if (!Schema::hasTable('fbm_sync_runs')) {
            return [];
        }

        return FbmSyncRun::query()
            ->with('connection:id,connection_name')
            ->where('status', FbmSyncRun::STATUS_FAILED)
            ->orderByDesc('completed_at')
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->map(fn(FbmSyncRun $run): FbmAlert => $this->upsertAlert(
                'sync_failure',
                'warning',
                'sync_run',
                (int) $run->id,
                'Read-only FB Marketing sync failed',
                [
                    'connection_name' => optional($run->connection)->connection_name,
                    'sync_scope' => (string) $run->sync_scope,
                    'trigger_type' => (string) $run->trigger_type,
                    'completed_at' => optional($run->completed_at)->toDateTimeString(),
                    'redacted_message' => $run->redacted_message,
                ],
                $actorUserId
            ))
            ->all();
    }

    private function overspendAlerts(?int $actorUserId): array
    {
        if (!Schema::hasTable('fbm_insight_daily_snapshots')) {
            return [];
        }

        $threshold = (float) config('fb_marketing.alerts.daily_spend_alert_threshold', 0);
        if ($threshold <= 0) {
            return [];
        }

        return DB::table('fbm_insight_daily_snapshots')
            ->selectRaw('fbm_ad_account_id, account_currency, SUM(spend) as spend_total, MAX(snapshot_date) as latest_snapshot_date')
            ->where('snapshot_date', '>=', now()->subDays(1)->toDateString())
            ->groupBy('fbm_ad_account_id', 'account_currency')
            ->havingRaw('SUM(spend) >= ?', [$threshold])
            ->limit(20)
            ->get()
            ->map(fn($row): FbmAlert => $this->upsertAlert(
                'overspend',
                'warning',
                'ad_account',
                (int) $row->fbm_ad_account_id,
                'Ad-account spend crossed the local alert threshold',
                [
                    'ad_account_id' => (int) $row->fbm_ad_account_id,
                    'spend_total' => round((float) $row->spend_total, 2),
                    'currency' => $row->account_currency,
                    'threshold' => $threshold,
                    'latest_snapshot_date' => $row->latest_snapshot_date,
                ],
                $actorUserId
            ))
            ->all();
    }

    private function poorPerformanceAlerts(?int $actorUserId): array
    {
        if (!Schema::hasTable('fbm_insight_daily_snapshots')) {
            return [];
        }

        $minImpressions = max(1, (int) config('fb_marketing.alerts.minimum_impressions_for_poor_ctr', 100));
        $ctrThreshold = (float) config('fb_marketing.alerts.poor_ctr_threshold_percent', 0.5);

        return DB::table('fbm_insight_daily_snapshots')
            ->selectRaw('fbm_campaign_id, SUM(impressions) as impressions, AVG(ctr) as avg_ctr, MAX(snapshot_date) as latest_snapshot_date')
            ->whereNotNull('fbm_campaign_id')
            ->where('snapshot_date', '>=', now()->subDays(7)->toDateString())
            ->groupBy('fbm_campaign_id')
            ->havingRaw('SUM(impressions) >= ? AND AVG(ctr) < ?', [$minImpressions, $ctrThreshold])
            ->limit(20)
            ->get()
            ->map(fn($row): FbmAlert => $this->upsertAlert(
                'poor_performance',
                'info',
                'campaign',
                (int) $row->fbm_campaign_id,
                'Campaign CTR is below the local review threshold',
                [
                    'campaign_id' => (int) $row->fbm_campaign_id,
                    'impressions' => (int) $row->impressions,
                    'average_ctr' => round((float) $row->avg_ctr, 4),
                    'threshold' => $ctrThreshold,
                    'latest_snapshot_date' => $row->latest_snapshot_date,
                ],
                $actorUserId
            ))
            ->all();
    }

    private function stockRiskAlerts(?int $actorUserId): array
    {
        if (!Schema::hasTable('fbm_catalog_product_mappings')) {
            return [];
        }

        $limit = max(1, (int) config('fb_marketing.alerts.stock_risk_limit', 20));
        return DB::table('fbm_catalog_product_mappings')
            ->select('id', 'item_name', 'provider_availability', 'mapping_status', 'last_seen_at')
            ->where(function ($query) {
                $query->where('is_available', false)
                    ->orWhereIn('provider_availability', ['out of stock', 'out_of_stock', 'discontinued', 'archived']);
            })
            ->orderByDesc('last_seen_at')
            ->limit($limit)
            ->get()
            ->map(fn($row): FbmAlert => $this->upsertAlert(
                'stock_risk',
                'warning',
                'catalog_product_mapping',
                (int) $row->id,
                'Catalog item may be unavailable for ads',
                [
                    'item_name' => $this->safeString($row->item_name, 255),
                    'provider_availability' => $this->safeString($row->provider_availability, 80),
                    'mapping_status' => $this->safeString($row->mapping_status, 30),
                    'last_seen_at' => $row->last_seen_at,
                ],
                $actorUserId
            ))
            ->all();
    }

    private function upsertAlert(string $type, string $severity, string $sourceType, int $sourceId, string $title, array $context, ?int $actorUserId): FbmAlert
    {
        $dedupeKey = hash('sha256', implode('|', [$type, $sourceType, $sourceId]));
        $alert = FbmAlert::query()->firstOrNew(['dedupe_key' => $dedupeKey]);
        if (!$alert->exists) {
            $alert->alert_uuid = (string) Str::uuid();
            $alert->first_seen_at = now();
            $alert->created_by = $actorUserId;
        }

        $alert->forceFill([
            'alert_type' => $type,
            'severity' => $severity,
            'status' => FbmAlert::STATUS_OPEN,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'title' => $title,
            'safe_context' => $context,
            'last_seen_at' => now(),
            'resolved_at' => null,
            'updated_by' => $actorUserId,
        ])->save();

        return $alert;
    }

    private function recordWebhookLog(string $eventType, string $status, Request $request, ?FbmConnection $connection, ?FbmAdAccount $adAccount, array $details, string $message): void
    {
        if (!Schema::hasTable('fbm_ad_account_webhook_logs')) {
            return;
        }

        FbmAdAccountWebhookLog::query()->create([
            'event_uuid' => (string) Str::uuid(),
            'fbm_connection_id' => optional($connection)->id,
            'fbm_ad_account_id' => optional($adAccount)->id,
            'event_type' => $eventType,
            'object_type' => $details['object_type'] ?? null,
            'provider_object_id' => $details['provider_object_id'] ?? null,
            'change_field' => $details['change_field'] ?? null,
            'status' => $status,
            'safe_change_summary' => $details['summary'] ?? [],
            'redacted_message' => $message,
            'request_fingerprint' => $this->fingerprint($request),
            'received_at' => now(),
        ]);
    }

    private function connectionForVerifyToken(string $token): ?FbmConnection
    {
        if ($token === '' || !Schema::hasTable('fbm_connections')) {
            return null;
        }

        return FbmConnection::query()->where('is_active', true)->get()->first(function (FbmConnection $connection) use ($token): bool {
            $configured = (string) $connection->webhook_verify_token_ciphertext;
            return $configured !== '' && hash_equals($configured, $token);
        });
    }

    private function adAccountForProviderId(?string $providerObjectId): ?FbmAdAccount
    {
        if (!$providerObjectId || !Schema::hasTable('fbm_ad_accounts')) {
            return null;
        }

        return FbmAdAccount::query()->where('provider_asset_id', $providerObjectId)->first();
    }

    private function summary(): array
    {
        return [
            'open_alert_count' => FbmAlert::query()->where('status', FbmAlert::STATUS_OPEN)->count(),
            'critical_alert_count' => FbmAlert::query()->where('severity', 'critical')->where('status', FbmAlert::STATUS_OPEN)->count(),
            'webhook_log_count' => FbmAdAccountWebhookLog::query()->count(),
            'reconciliation_run_count' => FbmAdAccountReconciliationRun::query()->count(),
        ];
    }

    private function emptySummary(): array
    {
        return ['open_alert_count' => 0, 'critical_alert_count' => 0, 'webhook_log_count' => 0, 'reconciliation_run_count' => 0];
    }

    private function alerts(): Collection
    {
        return FbmAlert::query()
            ->orderByRaw("FIELD(severity, 'critical', 'warning', 'info')")
            ->orderByDesc('last_seen_at')
            ->limit(max(1, (int) config('fb_marketing.alerts.alert_history_limit', 50)))
            ->get()
            ->map(fn(FbmAlert $alert): array => $alert->toSafeSummary());
    }

    private function webhookLogs(): Collection
    {
        return FbmAdAccountWebhookLog::query()
            ->with(['connection:id,connection_name', 'adAccount:id,asset_name'])
            ->orderByDesc('received_at')
            ->limit(max(1, (int) config('fb_marketing.ad_account_webhooks.webhook_history_limit', 30)))
            ->get()
            ->map(fn(FbmAdAccountWebhookLog $log): array => $log->toSafeSummary());
    }

    private function reconciliationRuns(): Collection
    {
        return FbmAdAccountReconciliationRun::query()
            ->orderByDesc('started_at')
            ->limit(max(1, (int) config('fb_marketing.alerts.reconciliation_history_limit', 20)))
            ->get()
            ->map(fn(FbmAdAccountReconciliationRun $run): array => $run->toSafeSummary());
    }

    private function staleHealthCount(): int
    {
        if (!Schema::hasTable('fbm_connection_health_checks')) {
            return 0;
        }

        return FbmConnectionHealthCheck::query()
            ->where(function ($query) {
                $query->where('token_is_valid', false)
                    ->orWhere('status', FbmConnectionHealthCheck::STATUS_FAILED);
            })
            ->count();
    }

    private function schemaReady(): bool
    {
        return Schema::hasTable('fbm_ad_account_webhook_logs')
            && Schema::hasTable('fbm_ad_account_reconciliation_runs')
            && Schema::hasTable('fbm_alerts');
    }

    private function safeChangeSummary(array $change, array $value): array
    {
        return [
            'field' => $this->safeString($change['field'] ?? null, 120),
            'verb' => $this->safeString($value['verb'] ?? $value['event'] ?? null, 80),
            'has_time' => isset($value['time']) || isset($change['time']),
            'value_keys' => array_values(array_slice(array_map('strval', array_keys($value)), 0, 20)),
        ];
    }

    private function safeProviderId($value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);
        return $value === '' ? null : substr($value, 0, 190);
    }

    private function safeString($value, int $length): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);
        return $value === '' ? null : substr($value, 0, $length);
    }

    private function fingerprint(Request $request): string
    {
        return hash('sha256', implode('|', [
            (string) $request->ip(),
            (string) $request->userAgent(),
            json_encode(array_keys($request->all())),
            now()->format('Y-m-d-H'),
        ]));
    }

    private function warning(string $type, string $message): array
    {
        return ['type' => $type, 'message' => $message];
    }
}

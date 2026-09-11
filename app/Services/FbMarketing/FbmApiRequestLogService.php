<?php

namespace App\Services\FbMarketing;

use App\Models\FbMarketing\FbmApiRequestLog;
use App\Models\FbMarketing\FbmConnection;
use App\Support\Security\SecretRedactor;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class FbmApiRequestLogService
{
    protected ?int $syncRunId = null;

    public function withinSyncRun(int $syncRunId, callable $callback)
    {
        $previous = $this->syncRunId;
        $this->syncRunId = $syncRunId;

        try {
            return $callback();
        } finally {
            $this->syncRunId = $previous;
        }
    }

    public function record(FbmConnection $connection, string $operationKey, ?string $version, array $result): void
    {
        try {
            if (!Schema::hasTable('fbm_api_request_logs')) {
                return;
            }
        } catch (Throwable $exception) {
            return;
        }

        $operationKey = $this->safeOperationKey($operationKey);
        if ($operationKey === null) {
            return;
        }

        try {
            FbmApiRequestLog::query()->create([
                'fbm_sync_run_id' => $this->syncRunId,
                'fbm_connection_id' => (int) $connection->id,
                'operation_key' => $operationKey,
                'graph_api_version' => $this->safeScalar($version, 20),
                'http_method' => $this->safeHttpMethod($result['http_method'] ?? 'GET'),
                'page_number' => $this->positiveIntegerOrNull($result['pages'] ?? 1),
                'http_status' => $this->positiveIntegerOrNull($result['http_status'] ?? null),
                'duration_ms' => max(0, (int) ($result['duration_ms'] ?? 0)),
                'provider_error_code' => $this->safeScalar($result['provider_error_code'] ?? null, 80),
                'provider_error_subcode' => $this->safeScalar($result['provider_error_subcode'] ?? null, 80),
                'request_fingerprint' => $this->safeFingerprint($result['request_fingerprint'] ?? null),
                'redacted_message' => $this->safeScalar($result['redacted_message'] ?? null, 500),
                'created_at' => now(),
            ]);
        } catch (Throwable $exception) {
            Log::warning('FB MARKETING API request ledger write failed safely.', [
                'fbm_connection_id' => (int) $connection->id,
                'operation_key' => $operationKey,
                'exception_class' => get_class($exception),
            ]);
        }
    }

    protected function safeOperationKey(string $value): ?string
    {
        return in_array($value, [
            'debug_token',
            'asset_discovery_edge',
            'campaign_hierarchy_campaigns',
            'campaign_hierarchy_ad_sets',
            'campaign_hierarchy_ads',
            'campaign_hierarchy_creatives',
            'catalog_products',
            'catalog_product_sets',
            'ads_insights_account',
            'ads_insights_campaign',
            'ads_insights_adset',
            'ads_insights_ad',
            'ads_insights_async_create',
            'ads_insights_async_status',
            'ads_insights_async_result',
            'campaign_publish_campaign',
            'campaign_publish_ad_set',
            'campaign_publish_creative',
            'campaign_publish_ad',
            'campaign_operational_pause',
            'campaign_operational_resume',
            'campaign_operational_update_budget',
            'campaign_operational_update_schedule',
        ], true) ? $value : null;
    }

    protected function safeHttpMethod($value): string
    {
        $value = is_scalar($value) ? strtoupper(trim((string) $value)) : 'GET';

        return in_array($value, ['GET', 'POST'], true) ? $value : 'GET';
    }

    protected function safeFingerprint($value): string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';

        return preg_match('/^[a-f0-9]{64}$/', $value) ? $value : hash_hmac('sha256', 'fbm-api-log-fallback', (string) config('app.key', ''));
    }

    protected function safeScalar($value, int $length): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $value = trim(SecretRedactor::redactString((string) $value));
        if ($value === '') {
            return null;
        }

        return strlen($value) <= $length ? $value : substr($value, 0, $length);
    }

    protected function positiveIntegerOrNull($value): ?int
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }
}

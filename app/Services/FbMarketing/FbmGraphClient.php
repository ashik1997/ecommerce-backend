<?php

namespace App\Services\FbMarketing;

use App\Models\FbMarketing\FbmConnection;
use App\Support\Security\SecretRedactor;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class FbmGraphClient
{
    protected FbmGraphApiVersionPolicy $versionPolicy;
    protected FbmApiRequestLogService $apiRequestLogs;

    public function __construct(FbmGraphApiVersionPolicy $versionPolicy, FbmApiRequestLogService $apiRequestLogs)
    {
        $this->versionPolicy = $versionPolicy;
        $this->apiRequestLogs = $apiRequestLogs;
    }

    /**
     * Execute the read-only provider operation introduced in FBM-03.
     * The return value is intentionally allow-listed and never contains tokens,
     * app secrets, query strings, raw provider payloads or provider user IDs.
     */
    public function debugToken(FbmConnection $connection, ?string $requestedVersion = null): array
    {
        $version = $this->versionPolicy->resolveAllowed($requestedVersion ?: $connection->graph_api_version);
        $requestFingerprint = $this->requestFingerprint($connection, $version, 'debug_token');
        $startedAt = microtime(true);

        try {
            $appId = trim((string) $connection->app_id);
            $appSecret = trim((string) $connection->app_secret_ciphertext);
            $inputToken = trim((string) $connection->access_token_ciphertext);

            if ($appId === '' || $appSecret === '' || $inputToken === '') {
                throw new RuntimeException('The Graph token-debug request is missing required server-side credential material.');
            }

            $response = Http::acceptJson()
                ->connectTimeout($this->connectTimeoutSeconds())
                ->timeout($this->timeoutSeconds())
                ->withOptions(['allow_redirects' => false])
                ->get($this->graphEndpoint($version, 'debug_token'), [
                    'input_token' => $inputToken,
                    'access_token' => $appId . '|' . $appSecret,
                ]);

            $payload = $response->json();
            $payload = is_array($payload) ? $payload : [];
            $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
            $error = is_array($payload['error'] ?? null) ? $payload['error'] : [];

            return $this->debugResult($connection, $version, [
                'request_fingerprint' => $requestFingerprint,
                'http_status' => $response->status(),
                'duration_ms' => $this->durationMs($startedAt),
                'token_metadata' => $this->safeTokenMetadata($data),
                'provider_error_code' => $this->safeScalar($error['code'] ?? null, 80),
                'provider_error_subcode' => $this->safeScalar($error['error_subcode'] ?? null, 80),
                'redacted_message' => $this->safeProviderMessage($error['message'] ?? null, $response->successful()),
                'request_failed' => false,
            ]);
        } catch (Throwable $exception) {
            Log::warning('FB MARKETING Graph token-debug request failed safely.', [
                'fbm_connection_id' => (int) $connection->id,
                'graph_api_version' => $version,
                'request_fingerprint' => $requestFingerprint,
                'exception_class' => get_class($exception),
            ]);

            return $this->debugResult($connection, $version, [
                'request_fingerprint' => $requestFingerprint,
                'http_status' => null,
                'duration_ms' => $this->durationMs($startedAt),
                'token_metadata' => [],
                'provider_error_code' => null,
                'provider_error_subcode' => null,
                'redacted_message' => 'Meta Graph request failed before a safe response was received.',
                'request_failed' => true,
            ]);
        }
    }

    /**
     * Execute a bounded read-only Graph edge walk for FBM-04 asset discovery.
     * Raw provider rows exist in memory only long enough for the discovery
     * service to normalize allow-listed fields. They are never logged or saved.
     */
    public function paginateEdge(
        FbmConnection $connection,
        string $path,
        array $fields,
        ?string $requestedVersion = null,
        string $operationKey = 'asset_discovery_edge',
        string $limitProfile = 'asset_discovery'
    ): array {
        $version = $this->versionPolicy->resolveAllowed($requestedVersion ?: $connection->graph_api_version);
        $safePath = $this->normalizeGraphPath($path);
        $requestFingerprint = $this->requestFingerprint($connection, $version, 'edge:' . $safePath);
        $startedAt = microtime(true);
        $items = [];
        $pages = 0;
        $after = null;
        $lastHttpStatus = null;

        try {
            $accessToken = trim((string) $connection->access_token_ciphertext);
            if ($accessToken === '') {
                throw new RuntimeException('The Graph edge request is missing the encrypted server-side access token.');
            }

            $safeFields = $this->normalizeFields($fields);
            $endpoint = $this->graphEndpoint($version, $safePath);
            $maxPages = $this->maxPagesPerEdge($limitProfile);
            $maxRecords = $this->maxRecordsPerEdge($limitProfile);

            do {
                $query = [
                    'fields' => implode(',', $safeFields),
                    'limit' => $this->perPageLimit($limitProfile),
                ];
                if ($after !== null) {
                    $query['after'] = $after;
                }

                $response = Http::acceptJson()
                    ->withToken($accessToken)
                    ->connectTimeout($this->connectTimeoutSeconds())
                    ->timeout($this->timeoutSeconds())
                    ->withOptions(['allow_redirects' => false])
                    ->get($endpoint, $query);

                $pages++;
                $lastHttpStatus = $response->status();
                $payload = $response->json();
                $payload = is_array($payload) ? $payload : [];
                $error = is_array($payload['error'] ?? null) ? $payload['error'] : [];

                if (!$response->successful()) {
                    return $this->edgeResult($connection, $version, 
                        $operationKey,
                        $requestFingerprint,
                        $startedAt,
                        $items,
                        $pages,
                        false,
                        false,
                        $lastHttpStatus,
                        $error['code'] ?? null,
                        $error['error_subcode'] ?? null,
                        $this->safeProviderMessage($error['message'] ?? null, false),
                        false
                    );
                }

                $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
                $recordsTruncated = false;
                foreach ($data as $row) {
                    if (!is_array($row)) {
                        continue;
                    }

                    if (count($items) >= $maxRecords) {
                        $recordsTruncated = true;
                        break;
                    }

                    $items[] = $row;
                }

                $pagination = $this->nextCursor($payload);
                $after = $pagination['cursor'];
                if (!empty($pagination['invalid'])) {
                    return $this->edgeResult($connection, $version, 
                        $operationKey,
                        $requestFingerprint,
                        $startedAt,
                        $items,
                        $pages,
                        false,
                        true,
                        $lastHttpStatus,
                        null,
                        null,
                        'Meta Graph returned an unusable pagination cursor. Existing unseen local mirror rows were preserved as available-state unknown.',
                        false
                    );
                }

                if ($recordsTruncated || (!empty($pagination['has_next']) && count($items) >= $maxRecords)) {
                    return $this->edgeResult($connection, $version, 
                        $operationKey,
                        $requestFingerprint,
                        $startedAt,
                        $items,
                        $pages,
                        false,
                        true,
                        $lastHttpStatus,
                        null,
                        null,
                        'Meta Graph edge record limit reached. Existing unseen local mirror rows were preserved as available-state unknown.',
                        false
                    );
                }

                if (!empty($pagination['has_next']) && $pages >= $maxPages) {
                    return $this->edgeResult($connection, $version, 
                        $operationKey,
                        $requestFingerprint,
                        $startedAt,
                        $items,
                        $pages,
                        false,
                        true,
                        $lastHttpStatus,
                        null,
                        null,
                        'Meta Graph edge page limit reached. Existing unseen local mirror rows were preserved as available-state unknown.',
                        false
                    );
                }
            } while (!empty($pagination['has_next']) && $after !== null);

            return $this->edgeResult($connection, $version, 
                $operationKey,
                $requestFingerprint,
                $startedAt,
                $items,
                $pages,
                true,
                false,
                $lastHttpStatus,
                null,
                null,
                null,
                false
            );
        } catch (Throwable $exception) {
            Log::warning('FB MARKETING bounded Graph edge request failed safely.', [
                'fbm_connection_id' => (int) $connection->id,
                'graph_api_version' => $version,
                'request_fingerprint' => $requestFingerprint,
                'exception_class' => get_class($exception),
            ]);

            return $this->edgeResult($connection, $version, 
                $operationKey,
                $requestFingerprint,
                $startedAt,
                $items,
                $pages,
                false,
                false,
                $lastHttpStatus,
                null,
                null,
                'Meta Graph edge request failed before a safe complete response was received.',
                true
            );
        }
    }

    /**
     * Fetch a bounded daily Ads Insights window. Raw rows remain in memory only
     * until FbmInsightsSnapshotService normalizes allow-listed metrics.
     */
    public function paginateInsights(
        FbmConnection $connection,
        string $adAccountNode,
        string $level,
        string $since,
        string $until,
        ?string $requestedVersion = null
    ): array {
        $version = $this->versionPolicy->resolveAllowed($requestedVersion ?: $connection->graph_api_version);
        $level = $this->normalizeInsightLevel($level);
        $path = $this->normalizeAdAccountNode($adAccountNode) . '/insights';
        $query = $this->insightsQuery($level, $since, $until);

        return $this->paginateInsightsPath($connection, $version, $path, $query, 'ads_insights_' . $level);
    }

    /**
     * Create a Meta-hosted asynchronous insights report for a larger historical
     * window. This creates reporting work only; it never mutates Ads assets.
     */
    public function createAsyncInsightsReport(
        FbmConnection $connection,
        string $adAccountNode,
        string $level,
        string $since,
        string $until,
        ?string $requestedVersion = null
    ): array {
        $version = $this->versionPolicy->resolveAllowed($requestedVersion ?: $connection->graph_api_version);
        $level = $this->normalizeInsightLevel($level);
        $path = $this->normalizeAdAccountNode($adAccountNode) . '/insights';
        $requestFingerprint = $this->requestFingerprint($connection, $version, 'async-create:' . $path . ':' . $level);
        $startedAt = microtime(true);

        try {
            $accessToken = $this->accessToken($connection);
            $query = $this->insightsQuery($level, $since, $until);
            $query['async'] = 'true';
            $response = Http::acceptJson()
                ->asForm()
                ->withToken($accessToken)
                ->connectTimeout($this->connectTimeoutSeconds())
                ->timeout($this->timeoutSeconds())
                ->withOptions(['allow_redirects' => false])
                ->post($this->graphEndpoint($version, $path), $query);
            $payload = $response->json();
            $payload = is_array($payload) ? $payload : [];
            $error = is_array($payload['error'] ?? null) ? $payload['error'] : [];
            $providerReportRunKey = $response->successful()
                ? $this->normalizeProviderKey($payload['report_run_id'] ?? null)
                : null;
            $successful = $response->successful() && $providerReportRunKey !== null;
            $result = [
                'request_fingerprint' => $requestFingerprint,
                'http_method' => 'POST',
                'http_status' => $response->status(),
                'duration_ms' => $this->durationMs($startedAt),
                'provider_report_run_key' => $providerReportRunKey,
                'successful' => $successful,
                'provider_error_code' => $this->safeScalar($error['code'] ?? null, 80),
                'provider_error_subcode' => $this->safeScalar($error['error_subcode'] ?? null, 80),
                'redacted_message' => $successful
                    ? null
                    : $this->safeProviderMessage($error['message'] ?? 'Meta did not return a usable asynchronous report key.', false),
                'request_failed' => false,
            ];
            $this->apiRequestLogs->record($connection, 'ads_insights_async_create', $version, $result);

            return $result;
        } catch (Throwable $exception) {
            Log::warning('FB MARKETING async Insights report creation failed safely.', [
                'fbm_connection_id' => (int) $connection->id,
                'graph_api_version' => $version,
                'request_fingerprint' => $requestFingerprint,
                'exception_class' => get_class($exception),
            ]);
            $result = [
                'request_fingerprint' => $requestFingerprint,
                'http_method' => 'POST',
                'http_status' => null,
                'duration_ms' => $this->durationMs($startedAt),
                'provider_report_run_key' => null,
                'successful' => false,
                'provider_error_code' => null,
                'provider_error_subcode' => null,
                'redacted_message' => 'Meta asynchronous Insights report creation failed before a safe response was received.',
                'request_failed' => true,
            ];
            $this->apiRequestLogs->record($connection, 'ads_insights_async_create', $version, $result);

            return $result;
        }
    }

    /**
     * Read allow-listed status fields from a Meta asynchronous Insights report.
     */
    public function readAsyncInsightsReportStatus(
        FbmConnection $connection,
        string $providerReportRunKey,
        ?string $requestedVersion = null
    ): array {
        $version = $this->versionPolicy->resolveAllowed($requestedVersion ?: $connection->graph_api_version);
        $reportKey = $this->normalizeProviderKey($providerReportRunKey);
        if ($reportKey === null) {
            throw new RuntimeException('The asynchronous Insights report key is not allowed.');
        }
        $requestFingerprint = $this->requestFingerprint($connection, $version, 'async-status:' . $reportKey);
        $startedAt = microtime(true);

        try {
            $response = Http::acceptJson()
                ->withToken($this->accessToken($connection))
                ->connectTimeout($this->connectTimeoutSeconds())
                ->timeout($this->timeoutSeconds())
                ->withOptions(['allow_redirects' => false])
                ->get($this->graphEndpoint($version, $reportKey), [
                    'fields' => 'async_status,async_percent_completion',
                ]);
            $payload = $response->json();
            $payload = is_array($payload) ? $payload : [];
            $error = is_array($payload['error'] ?? null) ? $payload['error'] : [];
            $status = $this->safeScalar($payload['async_status'] ?? null, 80);
            $percent = $this->boundedPercent($payload['async_percent_completion'] ?? null);
            $result = [
                'request_fingerprint' => $requestFingerprint,
                'http_method' => 'GET',
                'http_status' => $response->status(),
                'duration_ms' => $this->durationMs($startedAt),
                'async_status' => $status,
                'async_percent_completion' => $percent,
                'complete' => $response->successful() && $percent >= 100 && $this->asyncStatusIsComplete($status),
                'failed' => !$response->successful() || $this->asyncStatusIsFailed($status),
                'provider_error_code' => $this->safeScalar($error['code'] ?? null, 80),
                'provider_error_subcode' => $this->safeScalar($error['error_subcode'] ?? null, 80),
                'redacted_message' => $this->safeProviderMessage($error['message'] ?? null, $response->successful()),
                'request_failed' => false,
            ];
            $this->apiRequestLogs->record($connection, 'ads_insights_async_status', $version, $result);

            return $result;
        } catch (Throwable $exception) {
            Log::warning('FB MARKETING async Insights status request failed safely.', [
                'fbm_connection_id' => (int) $connection->id,
                'graph_api_version' => $version,
                'request_fingerprint' => $requestFingerprint,
                'exception_class' => get_class($exception),
            ]);
            $result = [
                'request_fingerprint' => $requestFingerprint,
                'http_method' => 'GET',
                'http_status' => null,
                'duration_ms' => $this->durationMs($startedAt),
                'async_status' => null,
                'async_percent_completion' => 0,
                'complete' => false,
                'failed' => false,
                'provider_error_code' => null,
                'provider_error_subcode' => null,
                'redacted_message' => 'Meta asynchronous Insights status request failed before a safe response was received.',
                'request_failed' => true,
            ];
            $this->apiRequestLogs->record($connection, 'ads_insights_async_status', $version, $result);

            return $result;
        }
    }

    /**
     * Fetch completed asynchronous Insight rows through the same bounded cursor
     * pagination contract used for recent direct refreshes.
     */
    public function paginateAsyncInsightsResult(
        FbmConnection $connection,
        string $providerReportRunKey,
        ?string $requestedVersion = null
    ): array {
        $version = $this->versionPolicy->resolveAllowed($requestedVersion ?: $connection->graph_api_version);
        $reportKey = $this->normalizeProviderKey($providerReportRunKey);
        if ($reportKey === null) {
            throw new RuntimeException('The asynchronous Insights report key is not allowed.');
        }

        return $this->paginateInsightsPath(
            $connection,
            $version,
            $reportKey . '/insights',
            ['fields' => implode(',', $this->insightFields())],
            'ads_insights_async_result'
        );
    }

    protected function paginateInsightsPath(
        FbmConnection $connection,
        string $version,
        string $path,
        array $query,
        string $operationKey
    ): array {
        $path = $this->normalizeGraphPath($path);
        $requestFingerprint = $this->requestFingerprint($connection, $version, 'insights:' . $path . ':' . $operationKey);
        $startedAt = microtime(true);
        $items = [];
        $pages = 0;
        $after = null;
        $lastHttpStatus = null;

        try {
            $endpoint = $this->graphEndpoint($version, $path);
            $accessToken = $this->accessToken($connection);
            $maxPages = $this->maxInsightsPages();
            $maxRows = $this->maxInsightsRows();

            do {
                $pageQuery = $query;
                $pageQuery['limit'] = $this->insightsPerPageLimit();
                if ($after !== null) {
                    $pageQuery['after'] = $after;
                }
                $response = Http::acceptJson()
                    ->withToken($accessToken)
                    ->connectTimeout($this->connectTimeoutSeconds())
                    ->timeout($this->timeoutSeconds())
                    ->withOptions(['allow_redirects' => false])
                    ->get($endpoint, $pageQuery);
                $pages++;
                $lastHttpStatus = $response->status();
                $payload = $response->json();
                $payload = is_array($payload) ? $payload : [];
                $error = is_array($payload['error'] ?? null) ? $payload['error'] : [];

                if (!$response->successful()) {
                    return $this->insightResult($connection, $version, $operationKey, $requestFingerprint, $startedAt, $items, $pages, false, false, $lastHttpStatus, $error['code'] ?? null, $error['error_subcode'] ?? null, $this->safeProviderMessage($error['message'] ?? null, false), false);
                }

                foreach ((array) ($payload['data'] ?? []) as $row) {
                    if (!is_array($row)) {
                        continue;
                    }
                    if (count($items) >= $maxRows) {
                        return $this->insightResult($connection, $version, $operationKey, $requestFingerprint, $startedAt, $items, $pages, false, true, $lastHttpStatus, null, null, 'Meta Insights row limit reached. Remaining rows were not ingested.', false);
                    }
                    $items[] = $row;
                }

                $pagination = $this->nextCursor($payload);
                $after = $pagination['cursor'];
                if (!empty($pagination['invalid'])) {
                    return $this->insightResult($connection, $version, $operationKey, $requestFingerprint, $startedAt, $items, $pages, false, true, $lastHttpStatus, null, null, 'Meta Insights returned an unusable pagination cursor. Remaining rows were not ingested.', false);
                }
                if (!empty($pagination['has_next']) && $pages >= $maxPages) {
                    return $this->insightResult($connection, $version, $operationKey, $requestFingerprint, $startedAt, $items, $pages, false, true, $lastHttpStatus, null, null, 'Meta Insights page limit reached. Remaining rows were not ingested.', false);
                }
            } while (!empty($pagination['has_next']) && $after !== null);

            return $this->insightResult($connection, $version, $operationKey, $requestFingerprint, $startedAt, $items, $pages, true, false, $lastHttpStatus, null, null, null, false);
        } catch (Throwable $exception) {
            Log::warning('FB MARKETING bounded Insights request failed safely.', [
                'fbm_connection_id' => (int) $connection->id,
                'graph_api_version' => $version,
                'request_fingerprint' => $requestFingerprint,
                'operation_key' => $operationKey,
                'exception_class' => get_class($exception),
            ]);

            return $this->insightResult($connection, $version, $operationKey, $requestFingerprint, $startedAt, $items, $pages, false, false, $lastHttpStatus, null, null, 'Meta Insights request failed before a safe complete response was received.', true);
        }
    }

    protected function insightResult(
        FbmConnection $connection,
        string $version,
        string $operationKey,
        string $requestFingerprint,
        float $startedAt,
        array $items,
        int $pages,
        bool $complete,
        bool $truncated,
        ?int $httpStatus,
        $providerErrorCode,
        $providerErrorSubcode,
        ?string $message,
        bool $requestFailed
    ): array {
        $result = [
            'request_fingerprint' => $requestFingerprint,
            'http_method' => 'GET',
            'http_status' => $httpStatus,
            'duration_ms' => $this->durationMs($startedAt),
            'items' => $items,
            'pages' => $pages,
            'complete' => $complete,
            'truncated' => $truncated,
            'provider_error_code' => $this->safeScalar($providerErrorCode, 80),
            'provider_error_subcode' => $this->safeScalar($providerErrorSubcode, 80),
            'redacted_message' => $message === null ? null : $this->limit(SecretRedactor::redactString($message), 500),
            'request_failed' => $requestFailed,
        ];
        $this->apiRequestLogs->record($connection, $operationKey, $version, $result);

        return $result;
    }

    protected function edgeResult(
        FbmConnection $connection,
        string $version,
        string $operationKey,
        string $requestFingerprint,
        float $startedAt,
        array $items,
        int $pages,
        bool $complete,
        bool $truncated,
        ?int $httpStatus,
        $providerErrorCode,
        $providerErrorSubcode,
        ?string $message,
        bool $requestFailed
    ): array {
        $result = [
            'request_fingerprint' => $requestFingerprint,
            'http_status' => $httpStatus,
            'duration_ms' => $this->durationMs($startedAt),
            'items' => $items,
            'pages' => $pages,
            'complete' => $complete,
            'truncated' => $truncated,
            'provider_error_code' => $this->safeScalar($providerErrorCode, 80),
            'provider_error_subcode' => $this->safeScalar($providerErrorSubcode, 80),
            'redacted_message' => $message === null ? null : $this->limit(SecretRedactor::redactString($message), 500),
            'request_failed' => $requestFailed,
        ];

        $this->apiRequestLogs->record($connection, $this->safeOperationKey($operationKey), $version, $result);

        return $result;
    }

    protected function debugResult(FbmConnection $connection, string $version, array $result): array
    {
        $this->apiRequestLogs->record($connection, 'debug_token', $version, $result);

        return $result;
    }

    protected function insightsQuery(string $level, string $since, string $until): array
    {
        $since = $this->normalizeInsightDate($since);
        $until = $this->normalizeInsightDate($until);
        if ($since > $until) {
            throw new RuntimeException('The requested Insights date range is not allowed.');
        }

        return [
            'fields' => implode(',', $this->insightFields()),
            'level' => $this->normalizeInsightLevel($level),
            'time_increment' => 1,
            'time_range' => json_encode(['since' => $since, 'until' => $until], JSON_UNESCAPED_SLASHES),
        ];
    }

    protected function insightFields(): array
    {
        return [
            'account_id', 'account_name', 'campaign_id', 'campaign_name',
            'adset_id', 'adset_name', 'ad_id', 'ad_name', 'date_start',
            'date_stop', 'spend', 'impressions', 'reach', 'clicks',
            'inline_link_clicks', 'frequency', 'ctr', 'cpc', 'cpm',
            'actions', 'action_values', 'attribution_setting',
        ];
    }

    protected function accessToken(FbmConnection $connection): string
    {
        $accessToken = trim((string) $connection->access_token_ciphertext);
        if ($accessToken === '') {
            throw new RuntimeException('The Graph request is missing the encrypted server-side access token.');
        }

        return $accessToken;
    }

    protected function normalizeInsightLevel(string $level): string
    {
        $level = trim($level);
        $allowed = (array) config('fb_marketing.insights.levels', ['account', 'campaign', 'adset', 'ad']);
        if (!in_array($level, $allowed, true)) {
            throw new RuntimeException('The requested Insights level is not allowed.');
        }

        return $level;
    }

    protected function normalizeInsightDate(string $date): string
    {
        $date = trim($date);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            throw new RuntimeException('The requested Insights date is not allowed.');
        }
        [$year, $month, $day] = array_map('intval', explode('-', $date));
        if (!checkdate($month, $day, $year)) {
            throw new RuntimeException('The requested Insights date is invalid.');
        }

        return $date;
    }

    protected function normalizeAdAccountNode(string $node): string
    {
        $node = trim($node);
        if (preg_match('/^\d{1,40}$/', $node)) {
            $node = 'act_' . $node;
        }
        if (!preg_match('/^act_[A-Za-z0-9_.:-]{1,190}$/', $node)) {
            throw new RuntimeException('The requested Ad Account node is not allowed.');
        }

        return $node;
    }

    protected function normalizeProviderKey($value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }
        $value = trim((string) $value);

        return $value !== '' && strlen($value) <= 190 && preg_match('/^[A-Za-z0-9_.:-]+$/', $value) ? $value : null;
    }

    protected function boundedPercent($value): int
    {
        return is_numeric($value) ? max(0, min(100, (int) $value)) : 0;
    }

    protected function asyncStatusIsComplete(?string $status): bool
    {
        $status = strtolower(trim((string) $status));

        return in_array($status, ['job completed', 'completed', 'success'], true);
    }

    protected function asyncStatusIsFailed(?string $status): bool
    {
        $status = strtolower(trim((string) $status));

        return in_array($status, ['job failed', 'failed', 'error'], true);
    }

    protected function maxInsightsPages(): int
    {
        return max(1, min(50, (int) config('fb_marketing.insights.max_pages_per_report', 20)));
    }

    protected function maxInsightsRows(): int
    {
        return max(1, min(50000, (int) config('fb_marketing.insights.max_rows_per_report', 10000)));
    }

    protected function insightsPerPageLimit(): int
    {
        return max(1, min(1000, (int) config('fb_marketing.insights.per_page_limit', 500)));
    }

    protected function graphEndpoint(string $version, string $path): string
    {
        $baseUrl = rtrim((string) config('fb_marketing.graph_api.base_url', 'https://graph.facebook.com'), '/');
        $parts = parse_url($baseUrl);
        $host = strtolower((string) ($parts['host'] ?? ''));
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $basePath = (string) ($parts['path'] ?? '');
        $port = isset($parts['port']) ? (int) $parts['port'] : null;
        $hasUnexpectedComponent = isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['query'])
            || isset($parts['fragment'])
            || ($port !== null && $port !== 443)
            || !in_array($basePath, ['', '/'], true);

        if ($scheme !== 'https' || $host === '' || $hasUnexpectedComponent || !in_array($host, $this->trustedHosts(), true)) {
            throw new RuntimeException('The configured FB MARKETING Graph base URL is not trusted.');
        }

        return $baseUrl . '/' . $version . '/' . $this->normalizeGraphPath($path);
    }

    protected function normalizeGraphPath(string $path): string
    {
        $path = trim($path, '/ ');
        if ($path === '' || strlen($path) > 300 || !preg_match('/^[A-Za-z0-9_.:-]+(?:\/[A-Za-z0-9_.:-]+)*$/', $path)) {
            throw new RuntimeException('The requested FB MARKETING Graph path is not allowed.');
        }

        return $path;
    }

    protected function normalizeFields(array $fields): array
    {
        $safe = [];
        foreach ($fields as $field) {
            if (!is_string($field)) {
                continue;
            }

            $field = trim($field);
            if ($field === '' || strlen($field) > 240 || !preg_match('/^[A-Za-z0-9_.,{}]+$/', $field)) {
                throw new RuntimeException('A requested FB MARKETING Graph field projection is not allowed.');
            }

            $safe[] = $field;
        }

        if ($safe === []) {
            throw new RuntimeException('A Graph edge request requires an explicit allow-listed field projection.');
        }

        return array_values(array_unique($safe));
    }

    protected function nextCursor(array $payload): array
    {
        $paging = is_array($payload['paging'] ?? null) ? $payload['paging'] : [];
        $next = $paging['next'] ?? null;

        if (!is_scalar($next) || trim((string) $next) === '') {
            return ['has_next' => false, 'cursor' => null, 'invalid' => false];
        }

        $after = null;
        $query = parse_url((string) $next, PHP_URL_QUERY);
        if (is_string($query)) {
            parse_str($query, $parsed);
            $after = $parsed['after'] ?? null;
        }

        if (!is_scalar($after)) {
            $cursors = is_array($paging['cursors'] ?? null) ? $paging['cursors'] : [];
            $after = $cursors['after'] ?? null;
        }

        if (!is_scalar($after)) {
            return ['has_next' => true, 'cursor' => null, 'invalid' => true];
        }

        $after = trim((string) $after);
        if ($after === '' || strlen($after) > 4096 || preg_match('/[\x00-\x20\x7F]/', $after)) {
            return ['has_next' => true, 'cursor' => null, 'invalid' => true];
        }

        return ['has_next' => true, 'cursor' => $after, 'invalid' => false];
    }

    protected function trustedHosts(): array
    {
        $configured = config('fb_marketing.graph_api.trusted_hosts', ['graph.facebook.com']);
        $configured = is_array($configured) ? $configured : ['graph.facebook.com'];
        $trusted = [];

        foreach ($configured as $host) {
            if (!is_string($host)) {
                continue;
            }

            $host = strtolower(trim($host));
            if ($host !== '' && preg_match('/^[a-z0-9.-]+$/', $host)) {
                $trusted[] = $host;
            }
        }

        return array_values(array_unique($trusted));
    }

    protected function safeTokenMetadata(array $data): array
    {
        return [
            'app_id' => $this->safeScalar($data['app_id'] ?? null, 120),
            'type' => $this->safeScalar($data['type'] ?? null, 100),
            'is_valid' => array_key_exists('is_valid', $data) ? (bool) $data['is_valid'] : null,
            'issued_at' => $this->positiveIntegerOrNull($data['issued_at'] ?? null),
            'expires_at' => $this->positiveIntegerOrNull($data['expires_at'] ?? null),
            'data_access_expires_at' => $this->positiveIntegerOrNull($data['data_access_expires_at'] ?? null),
            'scopes' => $this->safeScopes($data['scopes'] ?? []),
        ];
    }

    protected function safeScopes($scopes): array
    {
        if (!is_array($scopes)) {
            return [];
        }

        $safe = [];
        foreach ($scopes as $scope) {
            if (!is_string($scope)) {
                continue;
            }

            $scope = trim($scope);
            if ($scope === '' || strlen($scope) > 160 || !preg_match('/^[A-Za-z0-9_:.-]+$/', $scope)) {
                continue;
            }

            $safe[] = $scope;
        }

        sort($safe);

        return array_values(array_unique($safe));
    }

    protected function safeProviderMessage($message, bool $successful): ?string
    {
        if (!is_scalar($message) || trim((string) $message) === '') {
            return $successful ? null : 'Meta Graph returned an unsuccessful response without a safe diagnostic message.';
        }

        return $this->limit(SecretRedactor::redactString((string) $message), 500);
    }

    protected function safeScalar($value, int $length): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $this->limit(SecretRedactor::redactString($value), $length);
    }

    protected function positiveIntegerOrNull($value): ?int
    {
        if (!is_numeric($value) || (int) $value <= 0) {
            return null;
        }

        return (int) $value;
    }

    protected function requestFingerprint(FbmConnection $connection, string $version, string $operation): string
    {
        $payload = implode('|', [
            'fbm-graph-read-only',
            (string) $connection->id,
            $version,
            (string) $connection->secret_version,
            $operation,
        ]);

        return hash_hmac('sha256', $payload, (string) config('app.key', ''));
    }

    protected function connectTimeoutSeconds(): int
    {
        return max(1, (int) config('fb_marketing.graph_api.connect_timeout_seconds', 5));
    }

    protected function timeoutSeconds(): int
    {
        return max(1, (int) config('fb_marketing.graph_api.timeout_seconds', 12));
    }

    protected function maxPagesPerEdge(string $profile = 'asset_discovery'): int
    {
        $profile = in_array($profile, ['campaign_hierarchy', 'catalog_sync'], true) ? $profile : 'asset_discovery';
        return max(1, min(20, (int) config('fb_marketing.' . $profile . '.max_pages_per_edge', 5)));
    }

    protected function maxRecordsPerEdge(string $profile = 'asset_discovery'): int
    {
        $profile = in_array($profile, ['campaign_hierarchy', 'catalog_sync'], true) ? $profile : 'asset_discovery';
        return max(1, min(5000, (int) config('fb_marketing.' . $profile . '.max_records_per_edge', 250)));
    }

    protected function perPageLimit(string $profile = 'asset_discovery'): int
    {
        $profile = in_array($profile, ['campaign_hierarchy', 'catalog_sync'], true) ? $profile : 'asset_discovery';
        return max(1, min(100, (int) config('fb_marketing.' . $profile . '.per_page_limit', 100)));
    }

    protected function safeOperationKey(string $operationKey): string
    {
        $operationKey = trim($operationKey);
        if ($operationKey === '' || strlen($operationKey) > 80 || !preg_match('/^[A-Za-z0-9_.:-]+$/', $operationKey)) {
            return 'asset_discovery_edge';
        }
        return $operationKey;
    }

    protected function durationMs(float $startedAt): int
    {
        return max(0, (int) round((microtime(true) - $startedAt) * 1000));
    }

    protected function limit(string $value, int $length): string
    {
        return strlen($value) <= $length ? $value : substr($value, 0, $length);
    }
}

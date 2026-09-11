<?php

namespace App\Services\FbMarketing;

use App\Models\FbMarketing\FbmConnection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class FbmConversionsApiClient
{
    public function __construct(protected FbmGraphApiVersionPolicy $versionPolicy)
    {
    }

    /**
     * Send one normalized server event. Raw provider payloads, credentials and
     * customer data stay in memory only and never enter logs or attempt rows.
     */
    public function send(FbmConnection $connection, string $destinationId, array $eventPayload, string $mode): array
    {
        if (!in_array($mode, [FbmConversionEventReadinessService::MODE_TEST, FbmConversionEventReadinessService::MODE_LIVE], true)) {
            throw new RuntimeException('FB MARKETING CAPI client only accepts explicit test or live delivery modes.');
        }

        $version = $this->versionPolicy->resolveAllowed($connection->graph_api_version);
        $endpoint = $this->endpoint($version, $destinationId);
        $accessToken = trim((string) $connection->capi_access_token_ciphertext);
        if ($accessToken === '') {
            throw new RuntimeException('The encrypted server-side CAPI token is missing.');
        }

        $body = [
            'data' => [$eventPayload],
            'access_token' => $accessToken,
        ];

        if ($mode === FbmConversionEventReadinessService::MODE_TEST) {
            $testEventCode = trim((string) $connection->capi_test_event_code_ciphertext);
            if ($testEventCode === '') {
                throw new RuntimeException('The encrypted server-side Test Events code is missing.');
            }
            $body['test_event_code'] = $testEventCode;
        }

        $requestFingerprint = $this->requestFingerprint($connection, $destinationId, $eventPayload, $mode, $version);
        $startedAt = microtime(true);

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->connectTimeout($this->connectTimeoutSeconds())
                ->timeout($this->timeoutSeconds())
                ->withOptions(['allow_redirects' => false])
                ->post($endpoint, $body);

            $payload = $response->json();
            $payload = is_array($payload) ? $payload : [];
            $error = is_array($payload['error'] ?? null) ? $payload['error'] : [];
            $successful = $response->successful() && $error === [];
            $status = $response->status();

            return [
                'successful' => $successful,
                'retryable' => !$successful && $this->isRetryableHttpStatus($status),
                'http_status' => $status,
                'provider_error_code' => $this->safeScalar($error['code'] ?? null, 80),
                'provider_error_subcode' => $this->safeScalar($error['error_subcode'] ?? null, 80),
                'redacted_message' => $successful
                    ? 'Meta Conversions API accepted the normalized server event.'
                    : 'Meta Conversions API rejected the normalized server event.',
                'request_fingerprint' => $requestFingerprint,
                'duration_ms' => $this->durationMs($startedAt),
            ];
        } catch (Throwable $exception) {
            Log::warning('FB MARKETING CAPI request failed safely.', [
                'fbm_connection_id' => (int) $connection->id,
                'delivery_mode' => $mode,
                'exception_class' => get_class($exception),
            ]);

            return [
                'successful' => false,
                'retryable' => true,
                'http_status' => null,
                'provider_error_code' => null,
                'provider_error_subcode' => null,
                'redacted_message' => 'Meta Conversions API request failed before a safe response was received.',
                'request_fingerprint' => $requestFingerprint,
                'duration_ms' => $this->durationMs($startedAt),
            ];
        }
    }

    private function endpoint(string $version, string $destinationId): string
    {
        if (!preg_match('/^[0-9]{5,32}$/', $destinationId)) {
            throw new RuntimeException('FB MARKETING received an invalid CAPI destination identifier.');
        }

        $baseUrl = rtrim((string) config('fb_marketing.graph_api.base_url', 'https://graph.facebook.com'), '/');
        $host = strtolower((string) parse_url($baseUrl, PHP_URL_HOST));
        $trustedHosts = array_map('strtolower', (array) config('fb_marketing.graph_api.trusted_hosts', ['graph.facebook.com']));

        if (!in_array($host, $trustedHosts, true) || parse_url($baseUrl, PHP_URL_SCHEME) !== 'https') {
            throw new RuntimeException('FB MARKETING Graph host policy rejected the configured CAPI endpoint.');
        }

        return $baseUrl . '/' . $version . '/' . $destinationId . '/events';
    }

    private function requestFingerprint(FbmConnection $connection, string $destinationId, array $eventPayload, string $mode, string $version): string
    {
        return hash_hmac('sha256', implode('|', [
            'capi',
            (int) $connection->id,
            $destinationId,
            (string) ($eventPayload['event_name'] ?? ''),
            (string) ($eventPayload['event_id'] ?? ''),
            $mode,
            $version,
        ]), (string) config('app.key', ''));
    }

    private function safeScalar($value, int $maxLength): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : substr($value, 0, $maxLength);
    }

    private function isRetryableHttpStatus(int $status): bool
    {
        return in_array($status, [408, 425, 429], true) || $status >= 500;
    }

    private function durationMs(float $startedAt): int
    {
        return max(0, (int) round((microtime(true) - $startedAt) * 1000));
    }

    private function connectTimeoutSeconds(): int
    {
        return max(1, min(30, (int) config('fb_marketing.graph_api.connect_timeout_seconds', 5)));
    }

    private function timeoutSeconds(): int
    {
        return max(1, min(120, (int) config('fb_marketing.graph_api.timeout_seconds', 12)));
    }
}

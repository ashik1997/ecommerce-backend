<?php

namespace App\Services\FbMarketing;

use App\Models\FbMarketing\FbmConnection;
use App\Support\Security\SecretRedactor;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class FbmCampaignOperationalProviderClient
{
    public function __construct(
        protected FbmGraphApiVersionPolicy $versionPolicy,
        protected FbmApiRequestLogService $apiRequestLogs
    ) {
    }

    public function post(FbmConnection $connection, string $providerObjectId, array $payload, string $operationKey): array
    {
        $version = $this->versionPolicy->resolveAllowed($connection->graph_api_version);
        $providerObjectId = $this->providerId($providerObjectId);
        $endpoint = $this->endpoint($version, $providerObjectId);
        $accessToken = trim((string) $connection->access_token_ciphertext);
        if ($accessToken === '') {
            throw new RuntimeException('The encrypted Meta access token is missing.');
        }

        $body = $this->safeBody($payload);
        if ($body === []) {
            throw new RuntimeException('Operational action payload is empty.');
        }

        $requestFingerprint = $this->requestFingerprint($connection, $version, $providerObjectId, $body);
        $startedAt = microtime(true);

        try {
            $response = Http::acceptJson()
                ->asForm()
                ->withToken($accessToken)
                ->connectTimeout($this->connectTimeoutSeconds())
                ->timeout($this->timeoutSeconds())
                ->withOptions(['allow_redirects' => false])
                ->post($endpoint, $body);

            $responsePayload = $response->json();
            $responsePayload = is_array($responsePayload) ? $responsePayload : [];
            $error = is_array($responsePayload['error'] ?? null) ? $responsePayload['error'] : [];
            $successful = $response->successful() && $error === [] && ($responsePayload['success'] ?? true);
            $status = $response->status();

            $result = [
                'successful' => (bool) $successful,
                'retryable' => !$successful && $this->isRetryableHttpStatus($status),
                'http_method' => 'POST',
                'http_status' => $status,
                'provider_response_ref' => $this->responseRef($providerObjectId),
                'provider_error_code' => $this->safeScalar($error['code'] ?? null, 80),
                'provider_error_subcode' => $this->safeScalar($error['error_subcode'] ?? null, 80),
                'redacted_message' => $successful
                    ? 'Meta Marketing API accepted the operational action.'
                    : $this->safeProviderMessage($error['message'] ?? null),
                'request_fingerprint' => $requestFingerprint,
                'duration_ms' => $this->durationMs($startedAt),
            ];
        } catch (Throwable $exception) {
            Log::warning('FB MARKETING operational provider request failed safely.', [
                'fbm_connection_id' => (int) $connection->id,
                'operation_key' => $operationKey,
                'exception_class' => get_class($exception),
            ]);

            $result = [
                'successful' => false,
                'retryable' => true,
                'http_method' => 'POST',
                'http_status' => null,
                'provider_response_ref' => $this->responseRef($providerObjectId),
                'provider_error_code' => null,
                'provider_error_subcode' => null,
                'redacted_message' => 'Meta Marketing API request failed before a safe response was received.',
                'request_fingerprint' => $requestFingerprint,
                'duration_ms' => $this->durationMs($startedAt),
            ];
        }

        $this->apiRequestLogs->record($connection, $operationKey, $version, $result);

        return $result;
    }

    private function endpoint(string $version, string $providerObjectId): string
    {
        $baseUrl = rtrim((string) config('fb_marketing.graph_api.base_url', 'https://graph.facebook.com'), '/');
        $parts = parse_url($baseUrl);
        $host = strtolower((string) ($parts['host'] ?? ''));
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $trustedHosts = array_map('strtolower', (array) config('fb_marketing.graph_api.trusted_hosts', ['graph.facebook.com']));

        if ($scheme !== 'https' || $host === '' || !in_array($host, $trustedHosts, true)) {
            throw new RuntimeException('FB MARKETING Graph host policy rejected the operational action endpoint.');
        }

        return $baseUrl . '/' . $version . '/' . $providerObjectId;
    }

    private function safeBody(array $payload): array
    {
        $body = [];
        foreach ($payload as $key => $value) {
            if (!is_string($key) || !preg_match('/^[A-Za-z0-9_]+$/', $key)) {
                continue;
            }
            if (is_bool($value)) {
                $body[$key] = $value ? 'true' : 'false';
            } elseif (is_scalar($value) && trim((string) $value) !== '') {
                $body[$key] = (string) $value;
            }
        }

        return $body;
    }

    private function providerId(string $value): string
    {
        $value = trim($value);
        if ($value === '' || strlen($value) > 190 || !preg_match('/^[A-Za-z0-9_.:-]+$/', $value)) {
            throw new RuntimeException('Operational action provider identifier is unavailable.');
        }

        return $value;
    }

    private function responseRef(string $providerObjectId): string
    {
        return hash_hmac('sha256', 'operational-provider-response|' . $providerObjectId, (string) config('app.key', ''));
    }

    private function requestFingerprint(FbmConnection $connection, string $version, string $providerObjectId, array $body): string
    {
        return hash_hmac('sha256', json_encode([
            'fbm-operational-write',
            (int) $connection->id,
            (int) $connection->secret_version,
            $version,
            $providerObjectId,
            array_keys($body),
        ]), (string) config('app.key', ''));
    }

    private function safeProviderMessage($message): string
    {
        $message = is_scalar($message) ? trim(SecretRedactor::redactString((string) $message)) : '';

        return $message !== '' ? substr($message, 0, 500) : 'Meta Marketing API rejected the operational action.';
    }

    private function safeScalar($value, int $length): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }
        $value = trim(SecretRedactor::redactString((string) $value));

        return $value === '' ? null : substr($value, 0, $length);
    }

    private function isRetryableHttpStatus(int $status): bool
    {
        return in_array($status, [408, 425, 429], true) || $status >= 500;
    }

    private function connectTimeoutSeconds(): int
    {
        return max(1, min(30, (int) config('fb_marketing.graph_api.connect_timeout_seconds', 5)));
    }

    private function timeoutSeconds(): int
    {
        return max(1, min(120, (int) config('fb_marketing.graph_api.timeout_seconds', 12)));
    }

    private function durationMs(float $startedAt): int
    {
        return max(0, (int) round((microtime(true) - $startedAt) * 1000));
    }
}

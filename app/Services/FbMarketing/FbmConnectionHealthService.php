<?php

namespace App\Services\FbMarketing;

use App\Models\FbMarketing\FbmConnection;
use App\Models\FbMarketing\FbmConnectionHealthCheck;
use App\Models\User;
use App\Support\Security\SecretRedactor;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class FbmConnectionHealthService
{
    protected FbmGraphClient $graphClient;
    protected FbmGraphApiVersionPolicy $versionPolicy;

    public function __construct(FbmGraphClient $graphClient, FbmGraphApiVersionPolicy $versionPolicy)
    {
        $this->graphClient = $graphClient;
        $this->versionPolicy = $versionPolicy;
    }

    /**
     * Record one append-only read-only connection-health attempt.
     */
    public function test(FbmConnection $connection, User $actor, ?string $ipAddress = null): FbmConnectionHealthCheck
    {
        $version = $this->versionPolicy->resolve($connection->graph_api_version);
        $base = $this->baseLedgerAttributes($connection, $actor, $version, $ipAddress);

        if (!$connection->is_active) {
            return $this->persist($base, [
                'status' => FbmConnectionHealthCheck::STATUS_FAILED,
                'redacted_message' => 'Connection is disabled. Enable it before running a provider health check.',
            ]);
        }

        if (trim((string) $connection->app_id) === '') {
            return $this->persist($base, [
                'status' => FbmConnectionHealthCheck::STATUS_FAILED,
                'redacted_message' => 'Meta App ID is not configured.',
            ]);
        }

        if (!$connection->hasConfiguredSecret('app_secret') || !$connection->hasConfiguredSecret('access_token')) {
            return $this->persist($base, [
                'status' => FbmConnectionHealthCheck::STATUS_FAILED,
                'redacted_message' => 'Meta App Secret and Meta Access Token must both be configured before testing.',
            ]);
        }

        if (!$this->versionPolicy->isAllowed($version)) {
            return $this->persist($base, [
                'status' => FbmConnectionHealthCheck::STATUS_FAILED,
                'redacted_message' => 'Configured Graph API version is not allowed by the centralized FB MARKETING policy.',
            ]);
        }

        $result = $this->graphClient->debugToken($connection, $version);
        $metadata = is_array($result['token_metadata'] ?? null) ? $result['token_metadata'] : [];
        $scopes = is_array($metadata['scopes'] ?? null) ? $metadata['scopes'] : [];
        $missingScopes = array_values(array_diff($this->requiredScopes(), $scopes));
        $classification = $this->classify($connection, $result, $metadata, $missingScopes);

        return $this->persist($base, [
            'status' => $classification['status'],
            'token_is_valid' => $metadata['is_valid'] ?? null,
            'token_type' => $metadata['type'] ?? null,
            'provider_app_id' => $metadata['app_id'] ?? null,
            'issued_at' => $this->timestampToDateTime($metadata['issued_at'] ?? null),
            'expires_at' => $this->timestampToDateTime($metadata['expires_at'] ?? null),
            'data_access_expires_at' => $this->timestampToDateTime($metadata['data_access_expires_at'] ?? null),
            'scopes' => $scopes,
            'missing_required_scopes' => $missingScopes,
            'http_status' => $result['http_status'] ?? null,
            'provider_error_code' => $result['provider_error_code'] ?? null,
            'provider_error_subcode' => $result['provider_error_subcode'] ?? null,
            'redacted_message' => $classification['message'],
            'duration_ms' => $result['duration_ms'] ?? null,
            'request_fingerprint' => $result['request_fingerprint'] ?? $base['request_fingerprint'],
        ]);
    }

    protected function classify(FbmConnection $connection, array $result, array $metadata, array $missingScopes): array
    {
        if (!empty($result['request_failed'])) {
            return $this->classification(FbmConnectionHealthCheck::STATUS_FAILED, $result['redacted_message'] ?? 'Meta Graph request failed safely.');
        }

        $httpStatus = (int) ($result['http_status'] ?? 0);
        if ($httpStatus < 200 || $httpStatus >= 300) {
            return $this->classification(FbmConnectionHealthCheck::STATUS_FAILED, $result['redacted_message'] ?? 'Meta Graph returned an unsuccessful response.');
        }

        if (($metadata['is_valid'] ?? null) !== true) {
            return $this->classification(FbmConnectionHealthCheck::STATUS_FAILED, 'Meta reported that the access token is not valid.');
        }

        $providerAppId = trim((string) ($metadata['app_id'] ?? ''));
        if ($providerAppId === '') {
            return $this->classification(FbmConnectionHealthCheck::STATUS_FAILED, 'Meta did not return an application identifier for the access token.');
        }

        if ($providerAppId !== trim((string) $connection->app_id)) {
            return $this->classification(FbmConnectionHealthCheck::STATUS_FAILED, 'Token application mismatch. The token belongs to a different Meta App ID.');
        }

        if ($this->isExpired($metadata['expires_at'] ?? null)) {
            return $this->classification(FbmConnectionHealthCheck::STATUS_FAILED, 'Meta access token is expired.');
        }

        if ($this->isExpired($metadata['data_access_expires_at'] ?? null)) {
            return $this->classification(FbmConnectionHealthCheck::STATUS_FAILED, 'Meta data-access authorization is expired.');
        }

        if ($missingScopes !== []) {
            return $this->classification(FbmConnectionHealthCheck::STATUS_WARNING, 'Token is valid, but one or more required baseline scopes are missing.');
        }

        if ($this->expiresSoon($metadata['expires_at'] ?? null) || $this->expiresSoon($metadata['data_access_expires_at'] ?? null)) {
            return $this->classification(FbmConnectionHealthCheck::STATUS_WARNING, 'Token is valid, but an expiry boundary is approaching.');
        }

        return $this->classification(FbmConnectionHealthCheck::STATUS_HEALTHY, 'Token metadata validated successfully through the read-only Graph health check.');
    }

    protected function baseLedgerAttributes(FbmConnection $connection, User $actor, string $version, ?string $ipAddress): array
    {
        return [
            'fbm_connection_id' => (int) $connection->id,
            'actor_user_id' => (int) $actor->id,
            'status' => FbmConnectionHealthCheck::STATUS_FAILED,
            'graph_api_version' => $version,
            'token_is_valid' => null,
            'token_type' => null,
            'provider_app_id' => null,
            'issued_at' => null,
            'expires_at' => null,
            'data_access_expires_at' => null,
            'scopes' => [],
            'missing_required_scopes' => [],
            'http_status' => null,
            'provider_error_code' => null,
            'provider_error_subcode' => null,
            'redacted_message' => null,
            'duration_ms' => null,
            'request_fingerprint' => $this->localRequestFingerprint($connection, $version),
            'request_ip_hash' => $this->hashOptionalValue($ipAddress),
            'checked_at' => now(),
        ];
    }

    protected function persist(array $base, array $changes): FbmConnectionHealthCheck
    {
        $attributes = array_merge($base, $changes);
        $attributes['redacted_message'] = $this->sanitizeMessage($attributes['redacted_message'] ?? null);

        $healthCheck = FbmConnectionHealthCheck::create($attributes);

        Log::info('FB MARKETING connection health check recorded.', [
            'fbm_connection_id' => (int) $healthCheck->fbm_connection_id,
            'actor_user_id' => $healthCheck->actor_user_id ? (int) $healthCheck->actor_user_id : null,
            'status' => (string) $healthCheck->status,
            'graph_api_version' => $healthCheck->graph_api_version,
            'http_status' => $healthCheck->http_status,
            'provider_error_code' => $healthCheck->provider_error_code,
            'provider_error_subcode' => $healthCheck->provider_error_subcode,
            'duration_ms' => $healthCheck->duration_ms,
            'request_fingerprint' => $healthCheck->request_fingerprint,
        ]);

        return $healthCheck;
    }

    protected function classification(string $status, string $message): array
    {
        return ['status' => $status, 'message' => $message];
    }

    protected function requiredScopes(): array
    {
        $scopes = config('fb_marketing.health_check.required_scopes', []);

        if (!is_array($scopes)) {
            return [];
        }

        $normalized = [];
        foreach ($scopes as $scope) {
            if (!is_string($scope)) {
                continue;
            }

            $scope = trim($scope);
            if ($scope !== '' && preg_match('/^[A-Za-z0-9_:.-]+$/', $scope)) {
                $normalized[] = $scope;
            }
        }

        sort($normalized);

        return array_values(array_unique($normalized));
    }

    protected function timestampToDateTime($value): ?string
    {
        if (!is_numeric($value) || (int) $value <= 0) {
            return null;
        }

        return Carbon::createFromTimestamp((int) $value, 'UTC')->toDateTimeString();
    }

    protected function isExpired($value): bool
    {
        return is_numeric($value) && (int) $value > 0 && (int) $value <= time();
    }

    protected function expiresSoon($value): bool
    {
        if (!is_numeric($value) || (int) $value <= 0) {
            return false;
        }

        $warningSeconds = max(1, (int) config('fb_marketing.health_check.expiry_warning_days', 14)) * 86400;

        return (int) $value <= (time() + $warningSeconds);
    }

    protected function localRequestFingerprint(FbmConnection $connection, string $version): string
    {
        $payload = implode('|', [
            'fbm-connection-health',
            (string) $connection->id,
            $version,
            (string) $connection->secret_version,
        ]);

        return hash_hmac('sha256', $payload, (string) config('app.key', ''));
    }

    protected function hashOptionalValue(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : hash_hmac('sha256', $value, (string) config('app.key', ''));
    }

    protected function sanitizeMessage(?string $message): ?string
    {
        $message = trim((string) $message);

        if ($message === '') {
            return null;
        }

        $message = SecretRedactor::redactString($message);

        return strlen($message) <= 500 ? $message : substr($message, 0, 500);
    }
}

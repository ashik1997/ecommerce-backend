<?php

namespace App\Services\Crm\CampaignDispatch;

use Illuminate\Validation\ValidationException;

class BulkSmsBdCampaignSmsProtocol
{
    public const MAX_RECIPIENTS = 5;

    public const CONNECT_TIMEOUT_SECONDS = 3;

    public const TOTAL_TIMEOUT_SECONDS = 8;

    public const MAX_REDACTED_SUMMARY_LENGTH = 1000;

    public function readiness(): array
    {
        return [
            'ready' => true,
            'checks' => [
                [
                    'key' => 'single_recipient_boundary',
                    'label' => 'Single-recipient request boundary',
                    'passed' => true,
                    'message' => 'CRM provider execution is constrained to one immutable recipient command per provider POST request.',
                ],
                [
                    'key' => 'post_only_transport',
                    'label' => 'POST-only provider transport',
                    'passed' => true,
                    'message' => 'The hardened CRM protocol shape is HTTPS POST-only. Legacy GET transport is not reused.',
                ],
                [
                    'key' => 'recipient_cap',
                    'label' => 'Bounded recipient cap',
                    'passed' => true,
                    'message' => 'The real-send boundary is capped server-side at ' . self::MAX_RECIPIENTS . ' recipients per attempt.',
                ],
                [
                    'key' => 'timeout_policy',
                    'label' => 'Timeout and unknown-outcome policy',
                    'passed' => true,
                    'message' => 'The adapter policy uses a ' . self::CONNECT_TIMEOUT_SECONDS . '-second connect timeout, an ' . self::TOTAL_TIMEOUT_SECONDS . '-second total timeout, and no automatic retry for unknown outcomes.',
                ],
            ],
            'future_recipient_cap' => self::MAX_RECIPIENTS,
            'future_connect_timeout_seconds' => self::CONNECT_TIMEOUT_SECONDS,
            'future_total_timeout_seconds' => self::TOTAL_TIMEOUT_SECONDS,
            'single_recipient_requests_only' => true,
            'post_only' => true,
            'automatic_retry_available' => false,
            'transport_available' => true,
        ];
    }

    /**
     * Build one sensitive form payload in memory only. Callers must unset the
     * returned value in a finally block and never persist, log, or return it.
     */
    public function buildSingleRecipientPostPayload(string $apiKey, string $senderId, string $destination, string $message): array
    {
        $apiKey = trim($apiKey);
        $senderId = trim($senderId);

        if ($apiKey === '') {
            $this->throwValidation('api_key', 'A server-side BulkSMSBD API key is required.');
        }
        if ($senderId === '') {
            $this->throwValidation('sender_id', 'A server-side BulkSMSBD sender ID is required.');
        }
        if (trim($message) === '') {
            $this->throwValidation('message', 'An SMS message body is required.');
        }

        return [
            'api_key' => $apiKey,
            'type' => 'text',
            'number' => $this->normalizeBangladeshiDestination($destination),
            'senderid' => $senderId,
            'message' => $message,
        ];
    }

    public function normalizeBangladeshiDestination(string $destination): string
    {
        $normalized = preg_replace('/[\s\-().]/', '', trim($destination));
        $normalized = is_string($normalized) ? $normalized : '';
        if ($normalized === '' || preg_match('/^(?:\+|00)?[0-9]+$/D', $normalized) !== 1) {
            $this->throwValidation('destination', 'The SMS destination must be a valid Bangladeshi mobile number.');
        }
        if (str_starts_with($normalized, '+')) {
            $normalized = substr($normalized, 1);
        }
        if (str_starts_with($normalized, '00880')) {
            $normalized = substr($normalized, 2);
        }
        if (preg_match('/^01[3-9][0-9]{8}$/D', $normalized) === 1) {
            $normalized = '88' . $normalized;
        }

        if (preg_match('/^8801[3-9][0-9]{8}$/D', $normalized) !== 1) {
            $this->throwValidation('destination', 'The SMS destination must be a valid Bangladeshi mobile number.');
        }

        return $normalized;
    }

    public function estimateSegments(string $message): array
    {
        $length = $this->stringLength($message);
        $unicode = preg_match('/[^\x00-\x7F]/', $message) === 1;
        $singleLimit = $unicode ? 70 : 160;
        $multiLimit = $unicode ? 67 : 153;
        $segments = $length <= $singleLimit ? 1 : (int) ceil($length / max(1, $multiLimit));

        return [
            'unicode' => $unicode,
            'character_count' => $length,
            'estimated_segments' => max(1, $segments),
            'estimate_only' => true,
        ];
    }

    /**
     * Return a server-only redacted summary and a SHA-256 hash of the exact raw
     * provider response. The raw body itself must never be persisted.
     */
    public function redactRawProviderResponse(string $rawResponse, $decodedResponse, array $sensitiveValues = []): array
    {
        $allowlisted = $this->allowlistedResponse($decodedResponse);

        return [
            'response_hash' => hash('sha256', $rawResponse),
            'redacted_response_summary' => $this->safeSummary($this->encode($allowlisted), $sensitiveValues),
            'raw_response_persisted' => false,
        ];
    }

    public function redactProviderResponse($response, array $sensitiveValues = []): array
    {
        return $this->redactRawProviderResponse($this->encode($response), $response, $sensitiveValues);
    }

    /**
     * BulkSMSBD documents response code 202 as the submitted-success code.
     * Any ambiguous two-hundred response remains unknown and is never retried.
     */
    public function classifyProviderResponse($response, ?int $httpStatus = null): array
    {
        if ($httpStatus !== null && ($httpStatus < 200 || $httpStatus >= 300)) {
            return ['status' => 'failed', 'failure_category' => 'provider_rejection'];
        }

        $responseCode = $this->providerResponseCode($response);
        if ($responseCode === '202') {
            return ['status' => 'succeeded', 'failure_category' => null];
        }
        if ($responseCode !== null && $responseCode !== '') {
            return ['status' => 'failed', 'failure_category' => 'provider_rejection'];
        }

        return ['status' => 'unknown', 'failure_category' => 'provider_unknown'];
    }

    public function providerResponseCode($response, array $sensitiveValues = []): ?string
    {
        $payload = $this->arrayPayload($response);
        foreach (['response_code', 'code'] as $key) {
            if (array_key_exists($key, $payload) && is_scalar($payload[$key])) {
                return $this->safeProviderIdentifier((string) $payload[$key], $sensitiveValues, 64);
            }
        }

        return null;
    }

    public function providerMessageId($response, array $sensitiveValues = []): ?string
    {
        $payload = $this->arrayPayload($response);
        foreach (['message_id', 'sms_id'] as $key) {
            if (array_key_exists($key, $payload) && is_scalar($payload[$key])) {
                return $this->safeProviderIdentifier((string) $payload[$key], $sensitiveValues, 191);
            }
        }

        return null;
    }

    public function safeFailureSummary(string $failureCategory): string
    {
        return match ($failureCategory) {
            'timeout_unknown' => 'Provider request outcome is unknown because the bounded request timed out after transmission may have started. Automatic retry is disabled.',
            'transport_failure' => 'Provider request failed before a successful provider submission could be confirmed. Automatic retry is disabled.',
            'provider_rejection' => 'Provider rejected the bounded SMS request.',
            'provider_unknown' => 'Provider returned an unrecognized bounded response. The outcome is recorded as unknown and automatic retry is disabled.',
            'execution_interrupted_unknown' => 'A previously started bounded execution was interrupted before a terminal result was recorded. The outcome is unknown and automatic retry is disabled.',
            'execution_interrupted_before_request' => 'A bounded execution was interrupted before this recipient request started. No automatic retry is performed.',
            default => 'The bounded SMS request ended with a safe server-side failure classification. Automatic retry is disabled.',
        };
    }

    public function safeSummary(string $value, array $sensitiveValues = []): string
    {
        return $this->limit($this->redactSensitiveText($value, $sensitiveValues));
    }

    protected function allowlistedResponse($response): array
    {
        $payload = $this->arrayPayload($response);
        $allowed = [];
        foreach (['status', 'response_code', 'code', 'message_id', 'sms_id', 'message', 'error', 'error_message'] as $key) {
            if (array_key_exists($key, $payload) && (is_scalar($payload[$key]) || $payload[$key] === null)) {
                $allowed[$key] = $payload[$key];
            }
        }

        return $allowed ?: ['summary' => 'Provider response received without an allowlisted field.'];
    }

    protected function arrayPayload($response): array
    {
        return is_array($response) ? $response : (is_object($response) ? (array) $response : []);
    }

    protected function redactSensitiveText(string $value, array $sensitiveValues = []): string
    {
        foreach ($sensitiveValues as $sensitiveValue) {
            $sensitiveValue = trim((string) $sensitiveValue);
            if (strlen($sensitiveValue) >= 4) {
                $value = str_replace($sensitiveValue, '[redacted-server-value]', $value);
            }
        }

        $value = preg_replace('/(?:\+?88)?01[3-9][0-9]{8}/', '[redacted-destination]', $value) ?? $value;
        $value = preg_replace('/(?i)(api[_-]?key|token|secret|password|sender[_-]?id|senderid|authorization)(["\s:=]+)[^,}\s"]+/', '$1$2[redacted]', $value) ?? $value;

        return $value;
    }


    protected function safeProviderIdentifier(string $value, array $sensitiveValues, int $maximumLength): ?string
    {
        $value = trim($value);
        if ($value === '' || strlen($value) > $maximumLength) {
            return null;
        }

        $redacted = $this->redactSensitiveText($value, $sensitiveValues);
        if (!hash_equals($value, $redacted) || str_contains($redacted, '[redacted')) {
            return null;
        }
        if (preg_match('/^[A-Za-z0-9._:-]+$/D', $value) !== 1) {
            return null;
        }

        return $value;
    }

    protected function limit(string $value): string
    {
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, self::MAX_REDACTED_SUMMARY_LENGTH, 'UTF-8');
        }

        $matched = preg_match_all('/./us', $value, $characters);
        if ($matched !== false && !empty($characters[0])) {
            return implode('', array_slice($characters[0], 0, self::MAX_REDACTED_SUMMARY_LENGTH));
        }

        return substr($value, 0, self::MAX_REDACTED_SUMMARY_LENGTH);
    }

    protected function stringLength(string $value): int
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($value, 'UTF-8');
        }

        $matched = preg_match_all('/./us', $value, $characters);

        return $matched === false ? strlen($value) : $matched;
    }

    protected function encode($value): string
    {
        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return is_string($encoded) ? $encoded : 'null';
    }

    protected function throwValidation(string $field, string $message): void
    {
        throw ValidationException::withMessages([$field => [$message]]);
    }
}

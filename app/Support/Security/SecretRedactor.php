<?php

namespace App\Support\Security;

use Throwable;

class SecretRedactor
{
    public const REDACTED = '[REDACTED]';

    /**
     * Sensitive keys that must never be written into logs, exported metadata,
     * exception payloads, or diagnostic browser responses.
     */
    private const SENSITIVE_KEYS = [
        'access_token',
        'app_access_token',
        'capi_access_token',
        'webhook_verify_token',
        'authorization',
        'input_token',
        'api_key',
        'apikey',
        'app_secret',
        'captcha_secret_key',
        'client_secret',
        'cookie',
        'fb_app_secret',
        'fb_pixel_api_key',
        'fb_test_event_code',
        'gmail_secret_id',
        'password',
        'password_confirmation',
        'secret',
        'secret_key',
        'set_cookie',
        'sms_api_key',
        'tiktok_pixel_secret',
        'tiktok_pixel_token',
        'token',
        'x_api_key',
    ];

    /**
     * Redact nested diagnostic data while preserving non-sensitive structure.
     *
     * @param mixed $value
     * @param string|null $key
     * @return mixed
     */
    public static function redact($value, ?string $key = null)
    {
        if ($key !== null && self::isSensitiveKey($key)) {
            return self::REDACTED;
        }

        if (is_string($value)) {
            return self::redactString($value);
        }

        if (is_array($value)) {
            $redacted = [];
            foreach ($value as $childKey => $childValue) {
                $redacted[$childKey] = self::redact($childValue, is_string($childKey) ? $childKey : null);
            }

            return $redacted;
        }

        if ($value instanceof Throwable) {
            return [
                'exception_class' => get_class($value),
                'message' => self::redactString($value->getMessage()),
                'file' => $value->getFile(),
                'line' => $value->getLine(),
                'trace' => self::redactString($value->getTraceAsString()),
            ];
        }

        if (is_object($value)) {
            return self::redact(get_object_vars($value));
        }

        return $value;
    }

    public static function redactString(string $value): string
    {
        $patterns = [
            '/(Bearer\s+)[A-Za-z0-9._~+\/=\-]+/i' => '$1' . self::REDACTED,
            '/((?:access[_-]?token|app[_-]?access[_-]?token|input[_-]?token|api[_-]?key|apikey|app[_-]?secret|client[_-]?secret|secret[_-]?key|password|token|authorization|fb[_-]?pixel[_-]?api[_-]?key|tiktok[_-]?pixel[_-]?(?:token|secret))\s*[=:]\s*)("[^"]*"|\'[^\']*\'|[^,;\s&}\]]+)/i' => '$1' . self::REDACTED,
            '/((?:access[_-]?token|app[_-]?access[_-]?token|input[_-]?token|api[_-]?key|apikey|app[_-]?secret|client[_-]?secret|secret[_-]?key|password|token|authorization|fb[_-]?pixel[_-]?api[_-]?key|tiktok[_-]?pixel[_-]?(?:token|secret))=)[^&\s]+/i' => '$1' . self::REDACTED,
        ];

        return preg_replace(array_keys($patterns), array_values($patterns), $value) ?? $value;
    }

    private static function isSensitiveKey(string $key): bool
    {
        $normalized = strtolower(str_replace(['-', '.', ' '], '_', trim($key)));

        return in_array($normalized, self::SENSITIVE_KEYS, true)
            || self::endsWith($normalized, '_password')
            || self::endsWith($normalized, '_secret')
            || self::endsWith($normalized, '_token')
            || self::endsWith($normalized, '_api_key');
    }

    /**
     * Laravel 8 in this ERP still permits PHP 7.3, where str_ends_with does not
     * exist. Keep the security helper compatible with the project's floor.
     */
    private static function endsWith(string $value, string $suffix): bool
    {
        return $suffix === '' || substr($value, -strlen($suffix)) === $suffix;
    }
}

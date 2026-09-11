<?php

namespace App\Services\FbMarketing;

use App\Models\FbMarketing\FbmVisitorAttributionSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class FbmLandingAttributionService
{
    private const UTM_FIELDS = [
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_content',
        'utm_term',
        'utm_id',
    ];

    public function capture(array $input, ?string $ipAddress = null, ?string $userAgent = null): array
    {
        if (!$this->schemaReady()) {
            return $this->safeUnavailableSummary();
        }

        $this->pruneExpiredSessions();

        $settings = $this->settings();
        if (!$settings['landing_attribution_enabled']) {
            return [
                'attribution_session_uuid' => null,
                'expires_at' => null,
                'capture_state' => 'disabled',
                'http_status' => 200,
            ];
        }

        $now = now();
        $expiresAt = $now->copy()->addDays($settings['landing_attribution_retention_days']);
        $touch = $this->normalizeTouch($input, (int) floor(microtime(true) * 1000));
        $requestedUuid = $this->normalizeUuid($input['session_uuid'] ?? null);

        $session = DB::transaction(function () use ($requestedUuid, $touch, $ipAddress, $userAgent, $now, $expiresAt) {
            $session = null;

            if ($requestedUuid !== null) {
                $session = FbmVisitorAttributionSession::query()
                    ->where('session_uuid', $requestedUuid)
                    ->where('expires_at', '>', $now)
                    ->lockForUpdate()
                    ->first();
            }

            if (!$session) {
                return FbmVisitorAttributionSession::query()->create(array_merge(
                    $this->newSessionTouch($touch),
                    [
                        'session_uuid' => (string) Str::uuid(),
                        'first_seen_at' => $now,
                        'last_seen_at' => $now,
                        'expires_at' => $expiresAt,
                        'capture_count' => 1,
                        'request_ip_hash' => $this->hashOptionalValue($ipAddress),
                        'user_agent_hash' => $this->hashOptionalValue($userAgent),
                    ]
                ));
            }

            $session->last_seen_at = $now;
            $session->expires_at = $expiresAt;
            $session->capture_count = min(4294967295, max(1, (int) $session->capture_count) + 1);
            $session->request_ip_hash = $this->hashOptionalValue($ipAddress);
            $session->user_agent_hash = $this->hashOptionalValue($userAgent);

            foreach ($this->latestTouch($touch) as $column => $value) {
                if ($value !== null) {
                    $session->{$column} = $value;
                }
            }

            $session->save();

            return $session;
        });

        return array_merge($session->toBrowserSafeSummary(), ['http_status' => 200]);
    }

    public function operationalSummary(): array
    {
        $settings = $this->settings();
        $schemaReady = $this->schemaReady();
        $sessionCount = 0;
        $latestCaptureAt = null;

        if ($schemaReady) {
            $sessionCount = FbmVisitorAttributionSession::query()->where('expires_at', '>', now())->count();
            $latestCaptureAt = FbmVisitorAttributionSession::query()->where('expires_at', '>', now())->max('last_seen_at');
        }

        return [
            'schema_ready' => $schemaReady,
            'settings_schema_ready' => $settings['settings_schema_ready'],
            'enabled' => $settings['landing_attribution_enabled'],
            'retention_days' => $settings['landing_attribution_retention_days'],
            'session_count' => $sessionCount,
            'latest_capture_at' => $latestCaptureAt ?: null,
            'capture_endpoint' => '/api/fb-marketing/attribution/landing',
            'frontend_integration_required' => true,
        ];
    }

    public function schemaReady(): bool
    {
        return Schema::hasTable('fbm_visitor_attribution_sessions')
            && Schema::hasTable('fbm_module_settings')
            && Schema::hasColumn('fbm_module_settings', 'landing_attribution_enabled')
            && Schema::hasColumn('fbm_module_settings', 'landing_attribution_retention_days');
    }

    private function settings(): array
    {
        $defaults = [
            'settings_schema_ready' => false,
            'landing_attribution_enabled' => false,
            'landing_attribution_retention_days' => $this->boundedRetentionDays(
                (int) config('fb_marketing.landing_attribution.default_retention_days', 90)
            ),
        ];

        if (!Schema::hasTable('fbm_module_settings')
            || !Schema::hasColumn('fbm_module_settings', 'landing_attribution_enabled')
            || !Schema::hasColumn('fbm_module_settings', 'landing_attribution_retention_days')) {
            return $defaults;
        }

        $row = DB::table('fbm_module_settings')->where('id', 1)->first();
        if (!$row) {
            return array_merge($defaults, ['settings_schema_ready' => true]);
        }

        return [
            'settings_schema_ready' => true,
            'landing_attribution_enabled' => (bool) $row->landing_attribution_enabled,
            'landing_attribution_retention_days' => $this->boundedRetentionDays((int) $row->landing_attribution_retention_days),
        ];
    }

    private function normalizeTouch(array $input, int $captureTimestampMs): array
    {
        $landingUrl = $this->sanitizeUrl($input['landing_url'] ?? null, true);
        $referrerUrl = $this->sanitizeUrl($input['referrer_url'] ?? null, false);
        $utmFromUrl = $this->utmFromUrl($input['landing_url'] ?? null);
        $utm = [];

        foreach (self::UTM_FIELDS as $field) {
            $utm[$field] = $this->sanitizeBoundedString($input[$field] ?? ($utmFromUrl[$field] ?? null), 255);
        }

        $fbclid = $this->sanitizeBoundedString($input['fbclid'] ?? null, $this->identifierMaxLength());
        $fbc = $this->sanitizeBoundedString($input['fbc'] ?? null, $this->identifierMaxLength());
        $fbp = $this->sanitizeBoundedString($input['fbp'] ?? null, $this->identifierMaxLength());

        if ($fbc === null && $fbclid !== null) {
            $fbc = $this->sanitizeBoundedString('fb.1.' . $captureTimestampMs . '.' . $fbclid, $this->identifierMaxLength());
        }

        return array_merge($utm, [
            'landing_url' => $landingUrl,
            'referrer_url' => $referrerUrl,
            'fbclid_ciphertext' => $fbclid,
            'fbc_ciphertext' => $fbc,
            'fbp_ciphertext' => $fbp,
        ]);
    }

    private function newSessionTouch(array $touch): array
    {
        $attributes = [
            'first_landing_url' => $touch['landing_url'],
            'latest_landing_url' => $touch['landing_url'],
            'first_referrer_url' => $touch['referrer_url'],
            'latest_referrer_url' => $touch['referrer_url'],
            'fbclid_ciphertext' => $touch['fbclid_ciphertext'],
            'fbc_ciphertext' => $touch['fbc_ciphertext'],
            'fbp_ciphertext' => $touch['fbp_ciphertext'],
        ];

        foreach (self::UTM_FIELDS as $field) {
            $attributes['first_' . $field] = $touch[$field];
            $attributes['latest_' . $field] = $touch[$field];
        }

        return $attributes;
    }

    private function latestTouch(array $touch): array
    {
        $attributes = [
            'latest_landing_url' => $touch['landing_url'],
            'latest_referrer_url' => $touch['referrer_url'],
            'fbclid_ciphertext' => $touch['fbclid_ciphertext'],
            'fbc_ciphertext' => $touch['fbc_ciphertext'],
            'fbp_ciphertext' => $touch['fbp_ciphertext'],
        ];

        foreach (self::UTM_FIELDS as $field) {
            $attributes['latest_' . $field] = $touch[$field];
        }

        return $attributes;
    }

    private function sanitizeUrl($value, bool $keepUtmQuery): ?string
    {
        $value = $this->sanitizeBoundedString($value, (int) config('fb_marketing.landing_attribution.max_input_url_length', 4096));
        if ($value === null) {
            return null;
        }

        $parts = parse_url($value);
        if (!is_array($parts)) {
            return null;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        if (!in_array($scheme, ['http', 'https'], true) || $host === '') {
            return null;
        }

        $url = $scheme . '://' . $host;
        if (isset($parts['port']) && is_numeric($parts['port'])) {
            $url .= ':' . (int) $parts['port'];
        }
        $url .= (string) ($parts['path'] ?? '/');

        if ($keepUtmQuery) {
            $utm = $this->utmFromUrl($value);
            $utm = array_filter($utm, fn($item) => $item !== null && $item !== '');
            if (!empty($utm)) {
                $url .= '?' . http_build_query($utm, '', '&', PHP_QUERY_RFC3986);
            }
        }

        return $this->sanitizeBoundedString($url, (int) config('fb_marketing.landing_attribution.max_stored_url_length', 2048));
    }

    private function utmFromUrl($value): array
    {
        $value = is_scalar($value) ? trim((string) $value) : '';
        if ($value === '') {
            return [];
        }

        $query = parse_url($value, PHP_URL_QUERY);
        if (!is_string($query) || $query === '') {
            return [];
        }

        parse_str($query, $parameters);
        $utm = [];
        foreach (self::UTM_FIELDS as $field) {
            $utm[$field] = $this->sanitizeBoundedString($parameters[$field] ?? null, 255);
        }

        return $utm;
    }

    private function sanitizeBoundedString($value, int $maxLength): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);
        $value = preg_replace('/[\x00-\x1F\x7F]/', '', $value) ?? '';
        if ($value === '') {
            return null;
        }

        return substr($value, 0, max(1, $maxLength));
    }

    private function normalizeUuid($value): ?string
    {
        $value = $this->sanitizeBoundedString($value, 36);

        return $value !== null && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $value) ? $value : null;
    }

    private function pruneExpiredSessions(): void
    {
        $limit = max(1, min(500, (int) config('fb_marketing.landing_attribution.max_expired_prune_per_capture', 100)));
        $ids = FbmVisitorAttributionSession::query()
            ->where('expires_at', '<=', now())
            ->orderBy('expires_at')
            ->limit($limit)
            ->pluck('id')
            ->all();

        if (!empty($ids)) {
            FbmVisitorAttributionSession::query()->whereIn('id', $ids)->delete();
        }
    }

    private function boundedRetentionDays(int $days): int
    {
        $min = max(1, (int) config('fb_marketing.landing_attribution.min_retention_days', 1));
        $max = max($min, (int) config('fb_marketing.landing_attribution.max_retention_days', 365));

        return max($min, min($max, $days));
    }

    private function identifierMaxLength(): int
    {
        return max(128, min(4096, (int) config('fb_marketing.landing_attribution.max_identifier_length', 2048)));
    }

    private function hashOptionalValue(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : hash_hmac('sha256', $value, (string) config('app.key', ''));
    }

    private function safeUnavailableSummary(): array
    {
        return [
            'attribution_session_uuid' => null,
            'expires_at' => null,
            'capture_state' => 'unavailable',
            'http_status' => 503,
        ];
    }
}

<?php

namespace App\Services\FbMarketing;

use App\Exceptions\FbMarketing\FbmRetryableConversionDeliveryException;
use App\Models\FbMarketing\FbmConnection;
use App\Models\FbMarketing\FbmConversionEvent;
use App\Models\FbMarketing\FbmConversionEventAttempt;
use App\Models\FbMarketing\FbmVisitorAttributionSession;
use App\Models\User;
use App\Support\Security\SecretRedactor;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class FbmConversionEventService
{
    public function __construct(
        protected FbmConversionEventReadinessService $readiness,
        protected FbmConversionsApiClient $client
    ) {
    }

    public function createDiagnostic(FbmConnection $connection, string $mode, ?User $actor = null): FbmConversionEvent
    {
        if (!in_array($mode, [FbmConversionEventReadinessService::MODE_DRY_RUN, FbmConversionEventReadinessService::MODE_TEST], true)) {
            throw new RuntimeException('FB MARKETING diagnostics support dry-run or explicit Meta Test Events mode only.');
        }
        if ($this->readiness->currentMode() !== $mode) {
            throw new RuntimeException('Select the matching server CAPI mode in Configuration before creating this diagnostic.');
        }

        $this->readiness->assertConnectionReadyForMode($connection, $mode);

        $event = $this->createPurchaseEvent($connection, [
            'event_id' => 'fbm14-diagnostic-' . (string) Str::uuid(),
            'event_time' => time(),
            'event_source_url' => $this->diagnosticSourceUrl(),
            'client_user_agent' => 'FBM-14-Diagnostic/1.0',
            'currency' => 'BDT',
            'value' => 0.01,
            'content_ids' => ['fbm14-diagnostic-product'],
            'contents' => [[
                'id' => 'fbm14-diagnostic-product',
                'quantity' => 1,
                'item_price' => 0.01,
            ]],
        ], $actor, $mode);

        return $this->deliverNow((string) $event->event_uuid, 'diagnostic');
    }

    /**
     * Reusable FBM-15 boundary: create one immutable Purchase snapshot. Calling
     * this twice with the same destination, event name and event ID returns the
     * original row and never creates a duplicate provider-delivery candidate.
     */
    public function createPurchaseEvent(
        FbmConnection $connection,
        array $input,
        ?User $actor = null,
        ?string $modeOverride = null
    ): FbmConversionEvent {
        $this->assertSchemaReady();
        $mode = $modeOverride ?: $this->readiness->currentMode();
        $this->readiness->assertConnectionReadyForMode($connection, $mode);
        $destinationId = (string) $this->readiness->destinationPixelId();
        $normalized = $this->normalizePurchaseInput($input);
        $attributionSession = $this->resolveAttributionSession($input['attribution_session_uuid'] ?? null);
        $normalized['user_data'] = $this->mergeAttributionUserData($normalized['user_data'], $attributionSession);

        $eventIdHash = $this->hmac('event-id|' . $normalized['event_id']);
        $destinationHash = $this->hmac('destination|' . $destinationId);

        $existing = FbmConversionEvent::query()
            ->where('destination_id_hash', $destinationHash)
            ->where('event_name', FbmConversionEvent::EVENT_PURCHASE)
            ->where('event_id_hash', $eventIdHash)
            ->first();

        if ($existing) {
            return $this->attachOrderAttribution($existing, $input);
        }

        $attributes = [
            'event_uuid' => (string) Str::uuid(),
            'fbm_connection_id' => (int) $connection->id,
            'fbm_visitor_attribution_session_id' => $attributionSession?->id,
            'event_name' => FbmConversionEvent::EVENT_PURCHASE,
            'event_id_ciphertext' => $normalized['event_id'],
            'event_id_hash' => $eventIdHash,
            'destination_id_ciphertext' => $destinationId,
            'destination_id_hash' => $destinationHash,
            'action_source' => 'website',
            'event_time' => $normalized['event_time'],
            'event_source_url_ciphertext' => $normalized['event_source_url'],
            'user_data_ciphertext' => $this->encodeJson($normalized['user_data']),
            'custom_data_ciphertext' => $this->encodeJson($normalized['custom_data']),
            'delivery_mode_snapshot' => $mode,
            'status' => FbmConversionEvent::STATUS_PENDING,
            'attempt_count' => 0,
            'created_by' => $actor?->id,
        ];
        if (Schema::hasColumn('fbm_conversion_events', 'fbm_order_attribution_id')) {
            $attributes['fbm_order_attribution_id'] = $this->orderAttributionId($input);
        }

        try {
            return FbmConversionEvent::query()->create($attributes);
        } catch (QueryException $exception) {
            $existing = FbmConversionEvent::query()
                ->where('destination_id_hash', $destinationHash)
                ->where('event_name', FbmConversionEvent::EVENT_PURCHASE)
                ->where('event_id_hash', $eventIdHash)
                ->first();

            if ($existing) {
                return $this->attachOrderAttribution($existing, $input);
            }

            throw $exception;
        }
    }


    private function attachOrderAttribution(FbmConversionEvent $event, array $input): FbmConversionEvent
    {
        $attributionId = $this->orderAttributionId($input);
        if ($attributionId !== null
            && Schema::hasColumn('fbm_conversion_events', 'fbm_order_attribution_id')
            && $event->fbm_order_attribution_id === null) {
            $event->forceFill(['fbm_order_attribution_id' => $attributionId])->save();
        }

        return $event;
    }

    private function orderAttributionId(array $input): ?int
    {
        $value = $input['fbm_order_attribution_id'] ?? null;

        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    public function deliverNow(string $eventUuid, string $origin = 'manual'): FbmConversionEvent
    {
        $this->assertSchemaReady();
        $eventUuid = $this->normalizeUuid($eventUuid);
        $lock = Cache::lock('fbm:capi-event:' . $this->hmac($eventUuid), $this->lockSeconds());

        if (!$lock->get()) {
            throw new RuntimeException('Another FB MARKETING CAPI delivery attempt currently owns this event lock.');
        }

        try {
            return $this->deliverLocked($eventUuid, $origin);
        } finally {
            optional($lock)->release();
        }
    }

    public function deliverQueued(string $eventUuid): void
    {
        $event = $this->deliverNow($eventUuid, 'queue');

        if ((string) $event->status === FbmConversionEvent::STATUS_RETRYABLE_FAILED) {
            throw new FbmRetryableConversionDeliveryException('FB MARKETING CAPI delivery failed with a retryable safe status.');
        }
    }

    public function markQueueExhausted(string $eventUuid): void
    {
        if (!$this->schemaReady()) {
            return;
        }

        FbmConversionEvent::query()
            ->where('event_uuid', $this->normalizeUuid($eventUuid))
            ->where('status', FbmConversionEvent::STATUS_RETRYABLE_FAILED)
            ->update([
                'status' => FbmConversionEvent::STATUS_PERMANENT_FAILED,
                'next_attempt_at' => null,
                'updated_at' => now(),
            ]);
    }

    public function schemaReady(): bool
    {
        return $this->readiness->schemaReady();
    }

    private function deliverLocked(string $eventUuid, string $origin): FbmConversionEvent
    {
        $event = FbmConversionEvent::query()->with('connection')->where('event_uuid', $eventUuid)->firstOrFail();
        if ($event->isDelivered()) {
            return $event;
        }

        $mode = (string) $event->delivery_mode_snapshot;
        $attemptNumber = max(1, (int) $event->attempt_count + 1);
        $now = now();

        if ($mode === FbmConversionEventReadinessService::MODE_DRY_RUN) {
            $this->recordAttempt($event, [
                'attempt_number' => $attemptNumber,
                'origin' => $origin,
                'delivery_mode' => $mode,
                'status' => FbmConversionEventAttempt::STATUS_DRY_RUN_VALIDATED,
                'redacted_message' => 'Dry-run normalized Purchase validation passed. No Meta request was sent.',
                'attempted_at' => $now,
            ]);

            $event->forceFill([
                'status' => FbmConversionEvent::STATUS_DRY_RUN_VALIDATED,
                'attempt_count' => $attemptNumber,
                'last_attempt_at' => $now,
                'next_attempt_at' => null,
            ])->save();

            return $event->fresh();
        }

        $connection = $event->connection;
        if (!$connection) {
            return $this->rejectWithoutProvider($event, $attemptNumber, $origin, 'The encrypted FB MARKETING connection is unavailable.');
        }

        try {
            $destinationId = trim((string) $event->destination_id_ciphertext);
            $this->readiness->assertConnectionReadyForMode($connection, $mode, $destinationId);
            $result = $this->client->send($connection, $destinationId, $this->providerPayload($event), $mode);
        } catch (Throwable $exception) {
            return $this->rejectWithoutProvider($event, $attemptNumber, $origin, $exception->getMessage());
        }

        $attemptStatus = $result['successful']
            ? FbmConversionEventAttempt::STATUS_DELIVERED
            : ($result['retryable'] ? FbmConversionEventAttempt::STATUS_RETRYABLE_FAILED : FbmConversionEventAttempt::STATUS_PERMANENT_FAILED);
        $eventStatus = $result['successful']
            ? FbmConversionEvent::STATUS_DELIVERED
            : ($result['retryable'] ? FbmConversionEvent::STATUS_RETRYABLE_FAILED : FbmConversionEvent::STATUS_PERMANENT_FAILED);

        $this->recordAttempt($event, array_merge($result, [
            'attempt_number' => $attemptNumber,
            'origin' => $origin,
            'delivery_mode' => $mode,
            'status' => $attemptStatus,
            'attempted_at' => $now,
        ]));

        $event->forceFill([
            'status' => $eventStatus,
            'attempt_count' => $attemptNumber,
            'last_attempt_at' => $now,
            'next_attempt_at' => $eventStatus === FbmConversionEvent::STATUS_RETRYABLE_FAILED
                ? $now->copy()->addSeconds($this->backoffSeconds($attemptNumber))
                : null,
            'delivered_at' => $eventStatus === FbmConversionEvent::STATUS_DELIVERED ? $now : null,
        ])->save();

        return $event->fresh();
    }

    private function rejectWithoutProvider(FbmConversionEvent $event, int $attemptNumber, string $origin, string $message): FbmConversionEvent
    {
        $message = substr(SecretRedactor::redactString(trim($message)), 0, 500);
        $message = $message !== '' ? $message : 'FB MARKETING CAPI validation rejected the server event before provider delivery.';
        $now = now();

        $this->recordAttempt($event, [
            'attempt_number' => $attemptNumber,
            'origin' => $origin,
            'delivery_mode' => (string) $event->delivery_mode_snapshot,
            'status' => FbmConversionEventAttempt::STATUS_REJECTED,
            'redacted_message' => $message,
            'attempted_at' => $now,
        ]);

        $event->forceFill([
            'status' => FbmConversionEvent::STATUS_REJECTED,
            'attempt_count' => $attemptNumber,
            'last_attempt_at' => $now,
            'next_attempt_at' => null,
        ])->save();

        return $event->fresh();
    }

    private function recordAttempt(FbmConversionEvent $event, array $attributes): void
    {
        FbmConversionEventAttempt::query()->create([
            'fbm_conversion_event_id' => (int) $event->id,
            'attempt_number' => (int) $attributes['attempt_number'],
            'origin' => substr((string) $attributes['origin'], 0, 30),
            'delivery_mode' => substr((string) $attributes['delivery_mode'], 0, 20),
            'status' => substr((string) $attributes['status'], 0, 40),
            'http_status' => $attributes['http_status'] ?? null,
            'provider_error_code' => $attributes['provider_error_code'] ?? null,
            'provider_error_subcode' => $attributes['provider_error_subcode'] ?? null,
            'redacted_message' => isset($attributes['redacted_message'])
                ? substr(SecretRedactor::redactString((string) $attributes['redacted_message']), 0, 500)
                : null,
            'request_fingerprint' => $attributes['request_fingerprint'] ?? null,
            'duration_ms' => $attributes['duration_ms'] ?? null,
            'attempted_at' => $attributes['attempted_at'] ?? now(),
            'created_at' => now(),
        ]);
    }

    private function providerPayload(FbmConversionEvent $event): array
    {
        return [
            'event_name' => (string) $event->event_name,
            'event_time' => (int) $event->event_time,
            'event_id' => (string) $event->event_id_ciphertext,
            'action_source' => (string) $event->action_source,
            'event_source_url' => (string) $event->event_source_url_ciphertext,
            'user_data' => $this->decodeJson((string) $event->user_data_ciphertext),
            'custom_data' => $this->decodeJson((string) $event->custom_data_ciphertext),
        ];
    }

    private function normalizePurchaseInput(array $input): array
    {
        $eventId = $this->boundedString($input['event_id'] ?? null, 255, true);
        $sourceUrl = $this->safeUrl($input['event_source_url'] ?? null);
        $userAgent = $this->boundedString($input['client_user_agent'] ?? null, 512, true);
        $currency = strtoupper($this->boundedString($input['currency'] ?? null, 3, true));
        $purchaseValue = $input['value'] ?? null;

        if (!preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new RuntimeException('FB MARKETING Purchase currency must be a three-letter ISO code.');
        }
        if (!is_numeric($purchaseValue) || (float) $purchaseValue < 0 || (float) $purchaseValue > 999999999999.99) {
            throw new RuntimeException('FB MARKETING Purchase value is outside the accepted numeric range.');
        }

        $contentIds = $this->contentIds($input['content_ids'] ?? []);
        $contents = $this->contents($input['contents'] ?? []);
        if ($contentIds === [] && $contents === []) {
            throw new RuntimeException('FB MARKETING Purchase requires at least one bounded content identifier.');
        }

        $userData = [
            'client_user_agent' => $userAgent,
        ];

        $ipAddress = trim((string) ($input['client_ip_address'] ?? ''));
        if ($ipAddress !== '' && filter_var($ipAddress, FILTER_VALIDATE_IP)) {
            $userData['client_ip_address'] = $ipAddress;
        }
        foreach (['fbc', 'fbp'] as $field) {
            $value = $this->boundedString($input[$field] ?? null, 2048, false);
            if ($value !== null) {
                $userData[$field] = $value;
            }
        }
        foreach (['em' => 'email', 'ph' => 'phone', 'external_id' => 'external_id'] as $providerField => $inputField) {
            $value = $this->normalizedHashSource($input[$inputField] ?? null, $inputField);
            if ($value !== null) {
                $userData[$providerField] = [hash('sha256', $value)];
            }
        }

        return [
            'event_id' => $eventId,
            'event_time' => max(1, min(time() + 300, (int) ($input['event_time'] ?? time()))),
            'event_source_url' => $sourceUrl,
            'user_data' => $userData,
            'custom_data' => [
                'currency' => $currency,
                'value' => round((float) $purchaseValue, 2),
                'content_type' => 'product',
                'content_ids' => $contentIds,
                'contents' => $contents,
            ],
        ];
    }

    private function mergeAttributionUserData(array $userData, ?FbmVisitorAttributionSession $session): array
    {
        if (!$session) {
            return $userData;
        }

        foreach (['fbc_ciphertext' => 'fbc', 'fbp_ciphertext' => 'fbp'] as $column => $providerField) {
            $value = $this->boundedString($session->{$column}, 2048, false);
            if ($value !== null) {
                $userData[$providerField] = $value;
            }
        }

        return $userData;
    }

    private function resolveAttributionSession($uuid): ?FbmVisitorAttributionSession
    {
        if (!Schema::hasTable('fbm_visitor_attribution_sessions')) {
            return null;
        }

        $uuid = is_scalar($uuid) ? trim((string) $uuid) : '';
        if ($uuid === '' || !Str::isUuid($uuid)) {
            return null;
        }

        return FbmVisitorAttributionSession::query()
            ->where('session_uuid', $uuid)
            ->where('expires_at', '>', now())
            ->first();
    }

    private function contentIds($value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $ids = [];
        foreach (array_slice($value, 0, $this->maxContents()) as $id) {
            $id = $this->boundedString($id, 120, false);
            if ($id !== null) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    private function contents($value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $rows = [];
        foreach (array_slice($value, 0, $this->maxContents()) as $row) {
            if (!is_array($row)) {
                continue;
            }
            $id = $this->boundedString($row['id'] ?? null, 120, false);
            $quantity = $row['quantity'] ?? 1;
            $price = $row['item_price'] ?? null;
            if ($id === null || !is_numeric($quantity) || (int) $quantity < 1 || (int) $quantity > 100000) {
                continue;
            }

            $normalized = [
                'id' => $id,
                'quantity' => (int) $quantity,
            ];
            if (is_numeric($price) && (float) $price >= 0 && (float) $price <= 999999999999.99) {
                $normalized['item_price'] = round((float) $price, 2);
            }
            $rows[] = $normalized;
        }

        return $rows;
    }

    private function normalizedHashSource($value, string $type): ?string
    {
        $value = $this->boundedString($value, 500, false);
        if ($value === null) {
            return null;
        }

        if ($type === 'email') {
            $value = strtolower($value);
            return filter_var($value, FILTER_VALIDATE_EMAIL) ? $value : null;
        }
        if ($type === 'phone') {
            $value = preg_replace('/[^0-9]/', '', $value) ?? '';
            return $value === '' ? null : $value;
        }

        return strtolower($value);
    }

    private function safeUrl($value): string
    {
        $value = $this->boundedString($value, 2048, true);
        $parts = parse_url($value);

        if (!is_array($parts)
            || !in_array(strtolower((string) ($parts['scheme'] ?? '')), ['http', 'https'], true)
            || trim((string) ($parts['host'] ?? '')) === '') {
            throw new RuntimeException('FB MARKETING Purchase event source URL must be an absolute HTTP or HTTPS URL.');
        }

        return $value;
    }

    private function boundedString($value, int $maxLength, bool $required): ?string
    {
        if (!is_scalar($value)) {
            if ($required) {
                throw new RuntimeException('FB MARKETING Purchase is missing a required bounded string value.');
            }
            return null;
        }

        $value = trim((string) $value);
        $value = preg_replace('/[\x00-\x1F\x7F]/', '', $value) ?? '';
        if ($value === '') {
            if ($required) {
                throw new RuntimeException('FB MARKETING Purchase is missing a required bounded string value.');
            }
            return null;
        }

        return substr($value, 0, max(1, $maxLength));
    }

    private function diagnosticSourceUrl(): string
    {
        $url = trim((string) config('app.url', ''));

        return preg_match('/^https?:\/\//i', $url) ? rtrim($url, '/') . '/fb-marketing/tracking-attribution' : 'https://localhost/fb-marketing/tracking-attribution';
    }

    private function encodeJson(array $data): string
    {
        $json = json_encode($data, JSON_UNESCAPED_SLASHES);
        if (!is_string($json)) {
            throw new RuntimeException('FB MARKETING could not encode a normalized encrypted CAPI snapshot.');
        }

        return $json;
    }

    private function decodeJson(string $json): array
    {
        $data = json_decode($json, true);
        if (!is_array($data)) {
            throw new RuntimeException('FB MARKETING could not decode an encrypted CAPI snapshot safely.');
        }

        return $data;
    }

    private function normalizeUuid(string $uuid): string
    {
        $uuid = trim($uuid);
        if (!Str::isUuid($uuid)) {
            throw new RuntimeException('FB MARKETING received an invalid conversion event reference.');
        }

        return $uuid;
    }

    private function assertSchemaReady(): void
    {
        if (!$this->schemaReady()) {
            throw new RuntimeException('FB MARKETING CAPI schema is not ready. Apply the FBM-14 migration first.');
        }
    }

    private function maxContents(): int
    {
        return max(1, min(500, (int) config('fb_marketing.conversions_api.max_contents', 100)));
    }

    private function lockSeconds(): int
    {
        return max(10, min(600, (int) config('fb_marketing.conversions_api.lock_ttl_seconds', 90)));
    }

    private function backoffSeconds(int $attemptNumber): int
    {
        $backoff = array_values((array) config('fb_marketing.conversions_api.backoff_seconds', [30, 120, 300]));
        $index = max(0, min(count($backoff) - 1, $attemptNumber - 1));

        return max(1, (int) ($backoff[$index] ?? 300));
    }

    private function hmac(string $value): string
    {
        return hash_hmac('sha256', $value, (string) config('app.key', ''));
    }
}

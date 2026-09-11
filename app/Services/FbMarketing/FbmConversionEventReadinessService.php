<?php

namespace App\Services\FbMarketing;

use App\Models\FbMarketing\FbmConnection;
use App\Models\FbMarketing\FbmConversionEvent;
use App\Models\GeneralInfo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class FbmConversionEventReadinessService
{
    public const MODE_DISABLED = 'disabled';
    public const MODE_DRY_RUN = 'dry_run';
    public const MODE_TEST = 'test';
    public const MODE_LIVE = 'live';

    public const MODES = [
        self::MODE_DISABLED,
        self::MODE_DRY_RUN,
        self::MODE_TEST,
        self::MODE_LIVE,
    ];

    public function operationalSummary(): array
    {
        $schemaReady = $this->schemaReady();
        $mode = $this->currentMode();
        $destinationConfigured = $this->destinationPixelId() !== null;
        $connectionSchemaReady = Schema::hasTable('fbm_connections')
            && Schema::hasColumn('fbm_connections', 'capi_test_event_code_ciphertext');
        $activeConnectionCount = 0;
        $capiTokenConnectionCount = 0;
        $testCodeConnectionCount = 0;
        $eventCount = 0;
        $deliveredCount = 0;
        $retryableCount = 0;
        $latestEventAt = null;

        if ($connectionSchemaReady) {
            $connections = FbmConnection::query()->where('is_active', true)->get();
            $activeConnectionCount = $connections->count();
            $capiTokenConnectionCount = $connections->filter(
                fn(FbmConnection $connection): bool => $connection->hasConfiguredSecret('capi_access_token')
            )->count();
            $testCodeConnectionCount = $connections->filter(
                fn(FbmConnection $connection): bool => $connection->hasConfiguredSecret('capi_test_event_code')
            )->count();
        }

        if ($schemaReady) {
            $eventCount = FbmConversionEvent::query()->count();
            $deliveredCount = FbmConversionEvent::query()->where('status', FbmConversionEvent::STATUS_DELIVERED)->count();
            $retryableCount = FbmConversionEvent::query()->where('status', FbmConversionEvent::STATUS_RETRYABLE_FAILED)->count();
            $latestEventAt = FbmConversionEvent::query()->max('created_at');
        }

        $liveReady = $schemaReady
            && $mode === self::MODE_LIVE
            && $destinationConfigured
            && $capiTokenConnectionCount > 0;
        $testReady = $schemaReady
            && $mode === self::MODE_TEST
            && $destinationConfigured
            && $capiTokenConnectionCount > 0
            && $testCodeConnectionCount > 0;

        return [
            'schema_ready' => $schemaReady,
            'settings_schema_ready' => $this->settingsSchemaReady(),
            'connection_schema_ready' => $connectionSchemaReady,
            'mode' => $mode,
            'enabled' => $schemaReady && $mode !== self::MODE_DISABLED,
            'destination_pixel_configured' => $destinationConfigured,
            'active_connection_count' => $activeConnectionCount,
            'capi_token_connection_count' => $capiTokenConnectionCount,
            'test_code_connection_count' => $testCodeConnectionCount,
            'dry_run_ready' => $schemaReady && $mode === self::MODE_DRY_RUN && $destinationConfigured,
            'test_ready' => $testReady,
            'live_ready' => $liveReady,
            'event_count' => $eventCount,
            'delivered_count' => $deliveredCount,
            'retryable_count' => $retryableCount,
            'latest_event_at' => $latestEventAt ?: null,
            'supported_events' => [FbmConversionEvent::EVENT_PURCHASE],
        ];
    }

    public function schemaReady(): bool
    {
        return $this->settingsSchemaReady()
            && Schema::hasTable('fbm_conversion_events')
            && Schema::hasTable('fbm_conversion_event_attempts')
            && Schema::hasTable('fbm_connections')
            && Schema::hasColumn('fbm_connections', 'capi_test_event_code_ciphertext');
    }

    public function settingsSchemaReady(): bool
    {
        return Schema::hasTable('fbm_module_settings')
            && Schema::hasColumn('fbm_module_settings', 'server_capi_mode');
    }

    public function currentMode(): string
    {
        if (!$this->settingsSchemaReady()) {
            return self::MODE_DISABLED;
        }

        $mode = (string) (DB::table('fbm_module_settings')->where('id', 1)->value('server_capi_mode') ?: self::MODE_DISABLED);

        return in_array($mode, self::MODES, true) ? $mode : self::MODE_DISABLED;
    }

    public function destinationPixelId(): ?string
    {
        if (!Schema::hasTable('general_infos')
            || !Schema::hasColumn('general_infos', 'fb_pixel_app_id')) {
            return null;
        }

        $pixelId = trim((string) (GeneralInfo::query()->whereKey(1)->value('fb_pixel_app_id') ?? ''));

        return preg_match('/^[0-9]{5,32}$/', $pixelId) ? $pixelId : null;
    }

    public function assertConnectionReadyForMode(FbmConnection $connection, string $mode, ?string $expectedDestinationId = null): void
    {
        if (!$this->schemaReady()) {
            throw new RuntimeException('FB MARKETING CAPI schema is not ready. Apply the FBM-14 migration first.');
        }
        if (!in_array($mode, self::MODES, true) || $mode === self::MODE_DISABLED) {
            throw new RuntimeException('FB MARKETING server CAPI delivery is disabled.');
        }
        if ($this->currentMode() !== $mode) {
            throw new RuntimeException('FB MARKETING server CAPI mode changed after the immutable event snapshot was created. Review Configuration before delivery.');
        }
        if (!$connection->is_active) {
            throw new RuntimeException('FB MARKETING CAPI requires an active encrypted connection.');
        }

        $destinationId = $this->destinationPixelId();
        if ($destinationId === null) {
            throw new RuntimeException('FB MARKETING CAPI requires a validated stable General Information Pixel ID.');
        }
        if ($expectedDestinationId !== null && !hash_equals($expectedDestinationId, $destinationId)) {
            throw new RuntimeException('FB MARKETING CAPI destination changed after the immutable event snapshot was created. Create a new event after reviewing the Pixel configuration.');
        }

        if (in_array($mode, [self::MODE_TEST, self::MODE_LIVE], true)
            && !$connection->hasConfiguredSecret('capi_access_token')) {
            throw new RuntimeException('FB MARKETING CAPI token is not configured in the encrypted vault.');
        }
        if ($mode === self::MODE_TEST && !$connection->hasConfiguredSecret('capi_test_event_code')) {
            throw new RuntimeException('FB MARKETING CAPI test mode requires an encrypted Meta Test Events code.');
        }
    }
}

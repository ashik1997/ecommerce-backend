<?php

namespace App\Services\FbMarketing;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class FbmProductFeedCacheService
{
    public function rememberXml(callable $builder): string
    {
        return (string) Cache::remember(
            $this->cacheKey(),
            now()->addMinutes($this->settings()['feed_cache_ttl_minutes']),
            $builder
        );
    }

    public function invalidate(): void
    {
        Cache::forget($this->cacheKey());

        if (!Schema::hasTable('fbm_module_settings')) {
            return;
        }

        DB::table('fbm_module_settings')
            ->where('id', 1)
            ->update([
                'last_feed_cache_invalidated_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function settings(): array
    {
        $defaults = [
            'settings_schema_ready' => false,
            'feed_enabled' => true,
            'feed_cache_ttl_minutes' => max(5, min(1440, (int) config('fb_marketing.product_feed.default_cache_ttl_minutes', 360))),
            'last_feed_cache_invalidated_at' => null,
            'scheduled_sync_enabled' => false,
            'scheduled_sync_interval_minutes' => 60,
            'last_scheduled_sync_dispatched_at' => null,
            'sync_settings_schema_ready' => false,
            'landing_attribution_enabled' => false,
            'landing_attribution_retention_days' => max(1, min(365, (int) config('fb_marketing.landing_attribution.default_retention_days', 90))),
            'attribution_settings_schema_ready' => false,
            'browser_pixel_mode' => 'disabled',
            'browser_pixel_settings_schema_ready' => false,
            'server_capi_mode' => 'disabled',
            'server_capi_settings_schema_ready' => false,
            'updated_at' => null,
        ];

        if (!Schema::hasTable('fbm_module_settings')) {
            return $defaults;
        }

        $row = DB::table('fbm_module_settings')->where('id', 1)->first();
        $syncSettingsReady = Schema::hasColumn('fbm_module_settings', 'scheduled_sync_enabled')
            && Schema::hasColumn('fbm_module_settings', 'scheduled_sync_interval_minutes')
            && Schema::hasColumn('fbm_module_settings', 'last_scheduled_sync_dispatched_at');
        $attributionSettingsReady = Schema::hasColumn('fbm_module_settings', 'landing_attribution_enabled')
            && Schema::hasColumn('fbm_module_settings', 'landing_attribution_retention_days');
        $browserPixelSettingsReady = Schema::hasColumn('fbm_module_settings', 'browser_pixel_mode');
        $serverCapiSettingsReady = Schema::hasColumn('fbm_module_settings', 'server_capi_mode');

        if (!$row) {
            return array_merge($defaults, [
                'settings_schema_ready' => true,
                'sync_settings_schema_ready' => $syncSettingsReady,
                'attribution_settings_schema_ready' => $attributionSettingsReady,
                'browser_pixel_settings_schema_ready' => $browserPixelSettingsReady,
                'server_capi_settings_schema_ready' => $serverCapiSettingsReady,
            ]);
        }

        return [
            'settings_schema_ready' => true,
            'feed_enabled' => (bool) $row->feed_enabled,
            'feed_cache_ttl_minutes' => max(5, min(1440, (int) $row->feed_cache_ttl_minutes)),
            'last_feed_cache_invalidated_at' => $row->last_feed_cache_invalidated_at ?: null,
            'scheduled_sync_enabled' => $syncSettingsReady ? (bool) $row->scheduled_sync_enabled : false,
            'scheduled_sync_interval_minutes' => $syncSettingsReady ? max(15, min(1440, (int) $row->scheduled_sync_interval_minutes)) : 60,
            'last_scheduled_sync_dispatched_at' => $syncSettingsReady ? ($row->last_scheduled_sync_dispatched_at ?: null) : null,
            'sync_settings_schema_ready' => $syncSettingsReady,
            'landing_attribution_enabled' => $attributionSettingsReady ? (bool) $row->landing_attribution_enabled : false,
            'landing_attribution_retention_days' => $attributionSettingsReady
                ? max(1, min(365, (int) $row->landing_attribution_retention_days))
                : max(1, min(365, (int) config('fb_marketing.landing_attribution.default_retention_days', 90))),
            'attribution_settings_schema_ready' => $attributionSettingsReady,
            'browser_pixel_mode' => $browserPixelSettingsReady && in_array((string) $row->browser_pixel_mode, ['disabled', 'dry_run', 'live'], true)
                ? (string) $row->browser_pixel_mode
                : 'disabled',
            'browser_pixel_settings_schema_ready' => $browserPixelSettingsReady,
            'server_capi_mode' => $serverCapiSettingsReady && in_array((string) $row->server_capi_mode, ['disabled', 'dry_run', 'test', 'live'], true)
                ? (string) $row->server_capi_mode
                : 'disabled',
            'server_capi_settings_schema_ready' => $serverCapiSettingsReady,
            'updated_at' => $row->updated_at ?: null,
        ];
    }

    public function cacheKey(): string
    {
        $version = (string) config('fb_marketing.product_feed.cache_version', 'v2');
        $namespace = hash('sha256', $this->applicationHost() . '|' . $this->databaseName());

        return 'fbm:product-feed:' . $version . ':' . $namespace;
    }

    private function applicationHost(): string
    {
        try {
            if (app()->bound('request')) {
                $host = strtolower(trim((string) request()->getHost()));
                if ($host !== '') {
                    return $host;
                }
            }
        } catch (Throwable $exception) {
            // Fall back to the configured application URL below.
        }

        return strtolower((string) (parse_url((string) config('app.url', ''), PHP_URL_HOST) ?: 'unknown-host'));
    }

    private function databaseName(): string
    {
        try {
            return (string) DB::connection()->getDatabaseName();
        } catch (Throwable $exception) {
            return 'unknown-database';
        }
    }
}

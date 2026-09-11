<?php

namespace App\Services\FbMarketing;

use App\Models\FbMarketing\FbmModuleSetting;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class FbmConfigurationService
{
    public function __construct(private FbmProductFeedCacheService $feedCache)
    {
    }

    public function current(): array
    {
        return $this->feedCache->settings();
    }

    public function update(array $data, ?User $actor = null): array
    {
        abort_unless(
            Schema::hasTable('fbm_module_settings'),
            503,
            'FB MARKETING module settings schema is not ready. Run the FBM-05 migration first.'
        );

        $settings = FbmModuleSetting::query()->firstOrNew(['id' => 1]);
        $settings->feed_enabled = (bool) $data['feed_enabled'];
        $settings->feed_cache_ttl_minutes = max(5, min(1440, (int) $data['feed_cache_ttl_minutes']));
        if (Schema::hasColumn('fbm_module_settings', 'scheduled_sync_enabled')
            && Schema::hasColumn('fbm_module_settings', 'scheduled_sync_interval_minutes')) {
            $settings->scheduled_sync_enabled = (bool) $data['scheduled_sync_enabled'];
            $settings->scheduled_sync_interval_minutes = max(15, min(1440, (int) $data['scheduled_sync_interval_minutes']));
        }
        if (Schema::hasColumn('fbm_module_settings', 'landing_attribution_enabled')
            && Schema::hasColumn('fbm_module_settings', 'landing_attribution_retention_days')
            && array_key_exists('landing_attribution_enabled', $data)
            && array_key_exists('landing_attribution_retention_days', $data)) {
            $settings->landing_attribution_enabled = (bool) $data['landing_attribution_enabled'];
            $settings->landing_attribution_retention_days = max(1, min(365, (int) $data['landing_attribution_retention_days']));
        }
        if (Schema::hasColumn('fbm_module_settings', 'browser_pixel_mode')
            && array_key_exists('browser_pixel_mode', $data)) {
            $settings->browser_pixel_mode = in_array((string) $data['browser_pixel_mode'], ['disabled', 'dry_run', 'live'], true)
                ? (string) $data['browser_pixel_mode']
                : 'disabled';
        }
        if (Schema::hasColumn('fbm_module_settings', 'server_capi_mode')
            && array_key_exists('server_capi_mode', $data)) {
            $settings->server_capi_mode = in_array((string) $data['server_capi_mode'], ['disabled', 'dry_run', 'test', 'live'], true)
                ? (string) $data['server_capi_mode']
                : 'disabled';
        }
        $settings->updated_by = $actor?->id;
        $settings->save();

        $this->feedCache->invalidate();

        return $this->current();
    }
}

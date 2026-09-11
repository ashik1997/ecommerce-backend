<?php

namespace App\Models\FbMarketing;

use Illuminate\Database\Eloquent\Model;

class FbmModuleSetting extends Model
{
    protected $table = 'fbm_module_settings';

    protected $fillable = [
        'feed_enabled',
        'feed_cache_ttl_minutes',
        'last_feed_cache_invalidated_at',
        'scheduled_sync_enabled',
        'scheduled_sync_interval_minutes',
        'last_scheduled_sync_dispatched_at',
        'landing_attribution_enabled',
        'landing_attribution_retention_days',
        'browser_pixel_mode',
        'server_capi_mode',
        'updated_by',
    ];

    protected $casts = [
        'feed_enabled' => 'boolean',
        'feed_cache_ttl_minutes' => 'integer',
        'last_feed_cache_invalidated_at' => 'datetime',
        'scheduled_sync_enabled' => 'boolean',
        'scheduled_sync_interval_minutes' => 'integer',
        'last_scheduled_sync_dispatched_at' => 'datetime',
        'landing_attribution_enabled' => 'boolean',
        'landing_attribution_retention_days' => 'integer',
        'browser_pixel_mode' => 'string',
        'server_capi_mode' => 'string',
    ];

    public function toSafeSummary(): array
    {
        return [
            'feed_enabled' => (bool) $this->feed_enabled,
            'feed_cache_ttl_minutes' => (int) $this->feed_cache_ttl_minutes,
            'last_feed_cache_invalidated_at' => optional($this->last_feed_cache_invalidated_at)->toDateTimeString(),
            'scheduled_sync_enabled' => (bool) $this->scheduled_sync_enabled,
            'scheduled_sync_interval_minutes' => max(15, min(1440, (int) $this->scheduled_sync_interval_minutes)),
            'last_scheduled_sync_dispatched_at' => optional($this->last_scheduled_sync_dispatched_at)->toDateTimeString(),
            'landing_attribution_enabled' => (bool) $this->landing_attribution_enabled,
            'landing_attribution_retention_days' => max(1, min(365, (int) $this->landing_attribution_retention_days)),
            'browser_pixel_mode' => in_array((string) $this->browser_pixel_mode, ['disabled', 'dry_run', 'live'], true)
                ? (string) $this->browser_pixel_mode
                : 'disabled',
            'server_capi_mode' => in_array((string) $this->server_capi_mode, ['disabled', 'dry_run', 'test', 'live'], true)
                ? (string) $this->server_capi_mode
                : 'disabled',
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
        ];
    }
}

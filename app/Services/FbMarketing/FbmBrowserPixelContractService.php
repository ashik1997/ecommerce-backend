<?php

namespace App\Services\FbMarketing;

use App\Models\GeneralInfo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FbmBrowserPixelContractService
{
    public const MODE_DISABLED = 'disabled';
    public const MODE_DRY_RUN = 'dry_run';
    public const MODE_LIVE = 'live';

    public const MODES = [
        self::MODE_DISABLED,
        self::MODE_DRY_RUN,
        self::MODE_LIVE,
    ];

    public const SUPPORTED_EVENTS = [
        'PageView',
        'ViewContent',
        'AddToCart',
        'InitiateCheckout',
        'Purchase',
    ];

    public function publicConfiguration(): array
    {
        $summary = $this->operationalSummary();

        return [
            'contract_version' => (string) config('fb_marketing.browser_pixel.contract_version', 'v1'),
            'mode' => $summary['mode'],
            'enabled' => $summary['enabled'],
            'live_delivery_ready' => $summary['live_delivery_ready'],
            'pixel_id' => $summary['live_delivery_ready'] ? $this->legacyPixelId() : null,
            'supported_events' => self::SUPPORTED_EVENTS,
        ];
    }

    public function operationalSummary(): array
    {
        $mode = $this->currentMode();
        $legacyPixelStatusEnabled = $this->legacyPixelStatusEnabled();
        $pixelIdConfigured = $this->legacyPixelId() !== null;
        $schemaReady = $this->schemaReady();

        return [
            'schema_ready' => $schemaReady,
            'mode' => $mode,
            'enabled' => $schemaReady && $mode !== self::MODE_DISABLED,
            'dry_run_available' => $schemaReady && $mode === self::MODE_DRY_RUN,
            'live_delivery_ready' => $schemaReady
                && $mode === self::MODE_LIVE
                && $legacyPixelStatusEnabled
                && $pixelIdConfigured,
            'legacy_pixel_status_enabled' => $legacyPixelStatusEnabled,
            'pixel_id_configured' => $pixelIdConfigured,
            'configuration_endpoint' => '/api/fb-marketing/pixel/browser-config',
            'storefront_helper' => '/assets/js/fb-marketing/fbm-browser-pixel.js',
            'supported_events' => self::SUPPORTED_EVENTS,
        ];
    }

    public function schemaReady(): bool
    {
        return Schema::hasTable('fbm_module_settings')
            && Schema::hasColumn('fbm_module_settings', 'browser_pixel_mode');
    }

    private function currentMode(): string
    {
        if (!$this->schemaReady()) {
            return self::MODE_DISABLED;
        }

        $mode = (string) (DB::table('fbm_module_settings')->where('id', 1)->value('browser_pixel_mode') ?: self::MODE_DISABLED);

        return in_array($mode, self::MODES, true) ? $mode : self::MODE_DISABLED;
    }

    private function legacyPixelStatusEnabled(): bool
    {
        if (!Schema::hasTable('general_infos')
            || !Schema::hasColumn('general_infos', 'fb_pixel_status')) {
            return false;
        }

        return (int) (GeneralInfo::query()->whereKey(1)->value('fb_pixel_status') ?? 0) === 1;
    }

    private function legacyPixelId(): ?string
    {
        if (!Schema::hasTable('general_infos')
            || !Schema::hasColumn('general_infos', 'fb_pixel_app_id')) {
            return null;
        }

        $pixelId = trim((string) (GeneralInfo::query()->whereKey(1)->value('fb_pixel_app_id') ?? ''));

        return preg_match('/^[0-9]{5,32}$/', $pixelId) ? $pixelId : null;
    }
}

<?php

namespace App\Services\FbMarketing;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FbmFeedDiagnosticsService
{
    public function __construct(
        private FbmProductFeedService $feed,
        private FbmProductFeedCacheService $feedCache,
        private FbmFeedProductProjectionService $products
    ) {
    }

    public function summary(): array
    {
        $settings = $this->feedCache->settings();
        $productsReady = Schema::hasTable('products');
        $selectionMode = $this->feed->selectionMode();
        $warnings = [];
        $excluded = [
            'inactive' => 0,
            'missing_title' => 0,
            'missing_image' => 0,
            'non_positive_price' => 0,
            'zero_stock_but_feed_in_stock' => 0,
            'positive_stock_but_feed_out_of_stock' => 0,
            'variant_parent_level_export' => 0,
        ];

        $summary = [
            'module_configuration_isolated' => true,
            'legacy_general_info_imported' => false,
            'settings_schema_ready' => (bool) $settings['settings_schema_ready'],
            'canonical_flag_ready' => $productsReady && Schema::hasColumn('products', FbmProductFeedService::CANONICAL_FLAG),
            'legacy_flag_present' => $productsReady && Schema::hasColumn('products', FbmProductFeedService::LEGACY_FLAG),
            'selection_mode' => $selectionMode,
            'feed_url' => url('/api/facebook-product-feed.xml'),
            'storefront_url' => $this->feed->siteUrl(),
            'feed_enabled' => (bool) $settings['feed_enabled'],
            'feed_cache_ttl_minutes' => (int) $settings['feed_cache_ttl_minutes'],
            'last_feed_cache_invalidated_at' => $settings['last_feed_cache_invalidated_at'],
            'total_products' => 0,
            'opted_in_products' => 0,
            'active_opted_in_products' => 0,
            'feed_ready_products' => 0,
            'excluded_counts' => $excluded,
            'diagnostic_scanned_products' => 0,
            'diagnostic_scan_limit' => max(1, min(10000, (int) config('fb_marketing.catalog_sync.diagnostic_scan_limit', 5000))),
            'diagnostic_scan_truncated' => false,
            'selected_pixels' => $this->selectedAssetCount('fbm_pixels'),
            'selected_catalogs' => $this->selectedAssetCount('fbm_catalogs'),
            'warnings' => [],
        ];

        if (!$settings['settings_schema_ready']) {
            $warnings[] = 'FBM-05 module settings migration is not applied yet.';
        }
        if (!$productsReady) {
            $warnings[] = 'Products table is not available for feed diagnostics.';
            $summary['warnings'] = $warnings;
            return $summary;
        }
        if ($selectionMode === 'legacy_fallback') {
            $warnings[] = 'Legacy product-feed flag fallback is active. Apply the FBM-05 migration.';
        }
        if ($selectionMode === 'unavailable') {
            $warnings[] = 'No product-feed opt-in flag exists. The feed safely emits zero products.';
        }

        $selected = $this->products->selectedProductsQuery();
        $summary['total_products'] = Product::query()->count();
        $summary['opted_in_products'] = (clone $selected)->count();
        $summary['active_opted_in_products'] = (clone $selected)->where('status', 1)->count();
        $summary['feed_ready_products'] = $this->products->feedReadyProductsQuery()->count();
        $summary['excluded_counts']['inactive'] = (clone $selected)->where(fn($query) => $query->whereNull('status')->orWhere('status', '!=', 1))->count();
        $summary['excluded_counts']['missing_title'] = (clone $selected)->where('status', 1)->where(fn($query) => $query->whereNull('name')->orWhere('name', ''))->count();
        $summary['excluded_counts']['missing_image'] = (clone $selected)->where('status', 1)->where(fn($query) => $query->whereNull('image')->orWhere('image', ''))->count();
        $summary['excluded_counts']['non_positive_price'] = (clone $selected)->where('status', 1)->where(fn($query) => $query->whereNull('price')->orWhere('price', '<=', 0))->count();

        $scan = $this->products->diagnosticWarningCounts((int) $summary['diagnostic_scan_limit']);
        foreach ((array) $scan['counts'] as $reason => $count) {
            $summary['excluded_counts'][$reason] = (int) $count;
        }
        $summary['diagnostic_scanned_products'] = (int) $scan['scanned_products'];
        $summary['diagnostic_scan_truncated'] = (bool) $scan['truncated'];

        foreach ($summary['excluded_counts'] as $reason => $count) {
            if ($count > 0) {
                $warnings[] = str_replace('_', ' ', ucfirst($reason)) . ': ' . $count . ' selected product(s).';
            }
        }
        if ($summary['diagnostic_scan_truncated']) {
            $warnings[] = 'Stock and variant diagnostic warning counts are bounded to the latest ' . $summary['diagnostic_scan_limit'] . ' opted-in products. Narrow the product scope or run an offline audit for an exhaustive result.';
        }
        if (!$summary['feed_enabled']) {
            $warnings[] = 'Product feed is disabled in the isolated FB MARKETING configuration.';
        }
        if ($summary['selected_pixels'] === 0) {
            $warnings[] = 'No discovered Pixel is locally selected yet.';
        }
        if ($summary['selected_catalogs'] === 0) {
            $warnings[] = 'No discovered catalog is locally selected yet.';
        }

        $summary['warnings'] = $warnings;
        return $summary;
    }

    public function productDiagnostics(int $limit = 250)
    {
        return $this->products->selectedProductsForDiagnostics($limit);
    }

    private function selectedAssetCount(string $table): int
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'is_selected') || !Schema::hasColumn($table, 'is_available')) {
            return 0;
        }

        return DB::table($table)->where('is_selected', 1)->where('is_available', 1)->count();
    }
}

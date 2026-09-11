<?php

namespace App\Services\FbMarketing;

use App\Models\FbMarketing\FbmCatalog;
use App\Models\FbMarketing\FbmCatalogProductMapping;
use App\Models\FbMarketing\FbmCatalogSyncRun;
use App\Models\FbMarketing\FbmConnection;
use App\Models\FbMarketing\FbmConnectionHealthCheck;
use App\Models\FbMarketing\FbmProductSet;
use App\Models\Product;
use App\Models\User;
use App\Support\Security\SecretRedactor;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

class FbmCatalogProductMappingSyncService
{
    public const REQUIRED_TABLES = [
        'fbm_catalogs',
        'fbm_catalog_product_mappings',
        'fbm_product_sets',
        'fbm_catalog_sync_runs',
        'fbm_connection_health_checks',
        'products',
    ];

    public function __construct(
        protected FbmGraphClient $graphClient,
        protected FbmGraphApiVersionPolicy $versionPolicy,
        protected FbmFeedProductProjectionService $feedProducts
    ) {
    }

    public static function schemaReady(): bool
    {
        foreach (self::REQUIRED_TABLES as $table) {
            if (!Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }

    public function syncSelectedCatalogs(
        FbmConnection $connection,
        ?User $actor = null,
        string $executionMode = FbmCatalogSyncRun::MODE_QUEUED_WORKER,
        ?int $maxSelectedCatalogs = null
    ): array {
        if (!self::schemaReady()) {
            return $this->summary('failed', ['FBM-11 catalog mapping schema is not ready.'], []);
        }
        if (!$connection->is_active) {
            return $this->summary('skipped', ['Catalog mapping sync requires an active FB MARKETING connection.'], []);
        }
        $preflightFailure = $this->preflightFailure($connection);
        if ($preflightFailure !== null) {
            $this->recordSummaryRun($connection, $actor, $executionMode, FbmCatalogSyncRun::STATUS_FAILED, [$preflightFailure]);
            return $this->summary(FbmCatalogSyncRun::STATUS_FAILED, [$preflightFailure], []);
        }

        $limit = max(1, min(25, $maxSelectedCatalogs ?: (int) config('fb_marketing.catalog_sync.max_selected_catalogs', 10)));
        $catalogs = FbmCatalog::query()
            ->where('fbm_connection_id', (int) $connection->id)
            ->where('is_available', true)
            ->where('is_selected', true)
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($catalogs->isEmpty()) {
            $this->recordSummaryRun($connection, $actor, $executionMode, 'skipped', ['Select at least one available catalog before catalog mapping sync.']);
            return $this->summary('skipped', ['Select at least one available catalog before catalog mapping sync.'], []);
        }

        $totals = $this->emptyCounts();
        $warnings = [];
        $statuses = [];
        foreach ($catalogs as $catalog) {
            $result = $this->syncCatalog($connection, $catalog, $actor, $executionMode);
            $statuses[] = $result['status'];
            foreach ($totals as $key => $value) {
                $totals[$key] += (int) ($result['counts'][$key] ?? 0);
            }
            foreach ((array) ($result['warnings'] ?? []) as $warning) {
                $warnings[] = $warning;
            }
        }

        return $this->summary($this->combineStatuses($statuses), array_values(array_unique(array_slice($warnings, 0, 30))), $totals);
    }

    public function refreshManual(FbmConnection $connection, ?User $actor): array
    {
        if (!(bool) config('fb_marketing.catalog_sync.manual_enabled', true)) {
            throw new RuntimeException('Manual no-queue catalog mapping refresh is disabled by FB MARKETING configuration.');
        }
        if (!self::schemaReady()) {
            throw new RuntimeException('FBM-11 application migration is required before catalog mapping refresh can run.');
        }
        if (!$connection->is_active) {
            throw new RuntimeException('Manual no-queue catalog mapping refresh requires an active connection.');
        }

        $lock = Cache::lock(
            $this->lockKey((int) $connection->id, 'full_read_only'),
            max(60, (int) config('fb_marketing.queue.lock_ttl_seconds', 900))
        );

        if (!$lock->get()) {
            $this->recordSummaryRun($connection, $actor, FbmCatalogSyncRun::MODE_MANUAL_CATALOG_REQUEST, 'skipped', [
                'Another read-only FB MARKETING sync already owns the connection lock.',
            ]);
            return $this->summary('skipped', ['Another read-only FB MARKETING sync already owns the connection lock.'], []);
        }

        try {
            return $this->syncSelectedCatalogs(
                $connection,
                $actor,
                FbmCatalogSyncRun::MODE_MANUAL_CATALOG_REQUEST,
                max(1, min(3, (int) config('fb_marketing.catalog_sync.manual_max_selected_catalogs', 1)))
            );
        } finally {
            $this->releaseLock($lock);
        }
    }

    public function updateManualMapping(FbmCatalogProductMapping $mapping, ?Product $product, ?User $actor, ?string $note = null): FbmCatalogProductMapping
    {
        if (!self::schemaReady()) {
            throw new RuntimeException('FBM-11 application migration is required before local mapping can be updated.');
        }

        $mapping->forceFill([
            'product_id' => $product?->id,
            'mapping_status' => $product ? FbmCatalogProductMapping::STATUS_MANUAL : FbmCatalogProductMapping::STATUS_UNMATCHED,
            'mapping_source' => $product ? FbmCatalogProductMapping::SOURCE_MANUAL_OVERRIDE : null,
            'mapped_by' => $actor?->id,
            'mapped_at' => now(),
            'mapping_note' => $this->safe($note, 500),
        ])->save();

        if (!$product) {
            $duplicateRetailerId = $mapping->retailer_id !== null
                && FbmCatalogProductMapping::query()
                    ->where('fbm_catalog_id', (int) $mapping->fbm_catalog_id)
                    ->where('retailer_id', (string) $mapping->retailer_id)
                    ->where('is_available', true)
                    ->count() > 1;
            $this->reconcileMapping($mapping, $duplicateRetailerId);
        } else {
            $mapping->forceFill(['diagnostic_flags' => $this->diagnosticFlags($mapping, $product, false)])->save();
        }

        return $mapping->fresh(['catalog:id,asset_name', 'product:id,name,status']);
    }

    public function operationalSummary(): array
    {
        $summary = [
            'schema_ready' => self::schemaReady(),
            'selected_catalogs' => 0,
            'mapping_count' => 0,
            'automatic_mapping_count' => 0,
            'manual_mapping_count' => 0,
            'unmatched_count' => 0,
            'ambiguous_count' => 0,
            'unavailable_mapping_count' => 0,
            'product_set_count' => 0,
            'sync_run_count' => 0,
            'latest_run' => null,
        ];

        if (!$summary['schema_ready']) {
            return $summary;
        }

        $summary['selected_catalogs'] = FbmCatalog::query()->where('is_available', true)->where('is_selected', true)->count();
        $summary['mapping_count'] = FbmCatalogProductMapping::query()->where('is_available', true)->count();
        $summary['automatic_mapping_count'] = FbmCatalogProductMapping::query()->where('is_available', true)->where('mapping_status', FbmCatalogProductMapping::STATUS_AUTOMATIC)->count();
        $summary['manual_mapping_count'] = FbmCatalogProductMapping::query()->where('is_available', true)->where('mapping_status', FbmCatalogProductMapping::STATUS_MANUAL)->count();
        $summary['unmatched_count'] = FbmCatalogProductMapping::query()->where('is_available', true)->where('mapping_status', FbmCatalogProductMapping::STATUS_UNMATCHED)->count();
        $summary['ambiguous_count'] = FbmCatalogProductMapping::query()->where('is_available', true)->where('mapping_status', FbmCatalogProductMapping::STATUS_AMBIGUOUS)->count();
        $summary['unavailable_mapping_count'] = FbmCatalogProductMapping::query()->where('is_available', false)->count();
        $summary['product_set_count'] = FbmProductSet::query()->where('is_available', true)->count();
        $summary['sync_run_count'] = FbmCatalogSyncRun::query()->count();
        $latest = FbmCatalogSyncRun::query()->with(['connection:id,connection_name', 'catalog:id,asset_name'])->orderByDesc('created_at')->orderByDesc('id')->first();
        $summary['latest_run'] = $latest?->toSafeSummary();

        return $summary;
    }

    protected function syncCatalog(FbmConnection $connection, FbmCatalog $catalog, ?User $actor, string $executionMode): array
    {
        $startedAt = microtime(true);
        $version = $this->versionPolicy->resolve($connection->graph_api_version);
        $run = FbmCatalogSyncRun::query()->create([
            'fbm_connection_id' => (int) $connection->id,
            'fbm_catalog_id' => (int) $catalog->id,
            'actor_user_id' => $actor?->id,
            'execution_mode' => $this->safeExecutionMode($executionMode),
            'status' => FbmCatalogSyncRun::STATUS_FAILED,
            'graph_api_version' => $version,
            'warning_details' => [],
            'redacted_message' => 'Read-only catalog product mapping refresh started.',
            'started_at' => now(),
            'created_at' => now(),
        ]);

        try {
            $products = $this->syncProducts($connection, $catalog, $version);
            $sets = $this->syncProductSets($connection, $catalog, $version);
            $warnings = array_values(array_unique(array_filter(array_merge($products['warnings'], $sets['warnings']))));
            $status = $warnings === []
                ? FbmCatalogSyncRun::STATUS_SUCCESS
                : (($products['counts']['product_item_count'] + $sets['counts']['product_set_count']) > 0 ? FbmCatalogSyncRun::STATUS_PARTIAL_SUCCESS : FbmCatalogSyncRun::STATUS_FAILED);
            $counts = $this->emptyCounts();
            foreach ($counts as $key => $value) {
                $counts[$key] = (int) ($products['counts'][$key] ?? 0) + (int) ($sets['counts'][$key] ?? 0);
            }
            $counts['catalog_count'] = 1;
            $message = $status === FbmCatalogSyncRun::STATUS_SUCCESS
                ? 'Read-only catalog products and product sets refreshed successfully.'
                : 'Read-only catalog mapping refresh completed with safe warnings. Existing unseen rows were preserved when a family was incomplete.';

            $run->forceFill($counts + [
                'status' => $status,
                'warning_details' => array_slice($warnings, 0, 30),
                'redacted_message' => $message,
                'duration_ms' => max(0, (int) round((microtime(true) - $startedAt) * 1000)),
                'completed_at' => now(),
            ])->save();

            return ['status' => $status, 'warnings' => $warnings, 'counts' => $counts, 'message' => $message];
        } catch (Throwable $exception) {
            $message = 'Catalog mapping refresh stopped safely before completion. Existing unseen rows were preserved.';
            $run->forceFill([
                'status' => FbmCatalogSyncRun::STATUS_FAILED,
                'warning_details' => [$message],
                'redacted_message' => $message,
                'duration_ms' => max(0, (int) round((microtime(true) - $startedAt) * 1000)),
                'completed_at' => now(),
            ])->save();

            return ['status' => FbmCatalogSyncRun::STATUS_FAILED, 'warnings' => [$message], 'counts' => $this->emptyCounts(), 'message' => $message];
        }
    }

    protected function syncProducts(FbmConnection $connection, FbmCatalog $catalog, string $version): array
    {
        $result = $this->graphClient->paginateEdge(
            $connection,
            $catalog->provider_asset_id . '/products',
            ['id', 'retailer_id', 'retailer_product_group_id', 'name', 'availability', 'price', 'currency'],
            $version,
            'catalog_products',
            'catalog_sync'
        );
        $rows = is_array($result['items'] ?? null) ? $result['items'] : [];
        $normalized = [];
        $retailerCounts = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $providerId = $this->providerId($row['id'] ?? null);
            if (!$providerId) {
                continue;
            }
            $retailerId = $this->safe($row['retailer_id'] ?? null, 190);
            $normalized[] = ['provider_id' => $providerId, 'retailer_id' => $retailerId, 'row' => $row];
            if ($retailerId !== null) {
                $retailerCounts[$retailerId] = ($retailerCounts[$retailerId] ?? 0) + 1;
            }
        }

        $seen = [];
        $counts = $this->emptyCounts();
        foreach ($normalized as $item) {
            $seen[] = $item['provider_id'];
            $mapping = FbmCatalogProductMapping::query()->firstOrNew([
                'fbm_catalog_id' => (int) $catalog->id,
                'provider_product_item_id' => $item['provider_id'],
            ]);
            $mapping->forceFill([
                'retailer_id' => $item['retailer_id'],
                'retailer_product_group_id' => $this->safe($item['row']['retailer_product_group_id'] ?? null, 190),
                'item_name' => $this->safe($item['row']['name'] ?? null, 255),
                'provider_availability' => $this->safe($item['row']['availability'] ?? null, 80),
                'provider_price_amount' => $this->price($item['row']['price'] ?? null),
                'provider_currency' => $this->safe($item['row']['currency'] ?? null, 20),
                'is_available' => true,
                'last_seen_at' => now(),
            ]);
            $mapping->save();
            $this->reconcileMapping($mapping, ($retailerCounts[$item['retailer_id']] ?? 0) > 1);
            $counts['product_item_count']++;
            $counts[$this->statusCounter($mapping->fresh()->mapping_status)]++;
        }

        if (!empty($result['complete'])) {
            $query = FbmCatalogProductMapping::query()->where('fbm_catalog_id', (int) $catalog->id)->where('is_available', true);
            if ($seen !== []) {
                $query->whereNotIn('provider_product_item_id', $seen);
            }
            $query->update(['is_available' => false]);
        }

        $warnings = [];
        if (empty($result['complete'])) {
            $warnings[] = $result['redacted_message'] ?: 'Catalog products edge was incomplete. Existing unseen catalog items were preserved.';
        }
        return ['counts' => $counts, 'warnings' => $warnings];
    }

    protected function syncProductSets(FbmConnection $connection, FbmCatalog $catalog, string $version): array
    {
        $result = $this->graphClient->paginateEdge(
            $connection,
            $catalog->provider_asset_id . '/product_sets',
            ['id', 'name', 'filter'],
            $version,
            'catalog_product_sets',
            'catalog_sync'
        );
        $rows = is_array($result['items'] ?? null) ? $result['items'] : [];
        $maxProductSets = max(1, min(1000, (int) config('fb_marketing.catalog_sync.max_product_sets_per_catalog', 250)));
        $locallyTruncated = count($rows) > $maxProductSets;
        if ($locallyTruncated) {
            $rows = array_slice($rows, 0, $maxProductSets);
        }

        $seen = [];
        $counts = $this->emptyCounts();
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $providerId = $this->providerId($row['id'] ?? null);
            if (!$providerId) {
                continue;
            }
            $seen[] = $providerId;
            FbmProductSet::query()->updateOrCreate([
                'fbm_catalog_id' => (int) $catalog->id,
                'provider_product_set_id' => $providerId,
            ], [
                'set_name' => $this->safe($row['name'] ?? null, 255),
                'filter_summary' => ['configured' => !empty($row['filter'])],
                'item_count' => null,
                'is_available' => true,
                'last_seen_at' => now(),
            ]);
            $counts['product_set_count']++;
        }

        if (!empty($result['complete']) && !$locallyTruncated) {
            $query = FbmProductSet::query()->where('fbm_catalog_id', (int) $catalog->id)->where('is_available', true);
            if ($seen !== []) {
                $query->whereNotIn('provider_product_set_id', $seen);
            }
            $query->update(['is_available' => false]);
        }

        $warnings = [];
        if (empty($result['complete'])) {
            $warnings[] = $result['redacted_message'] ?: 'Catalog product sets edge was incomplete. Existing unseen product sets were preserved.';
        }
        if ($locallyTruncated) {
            $warnings[] = 'Catalog product sets exceeded the local per-catalog mirror limit. Existing unseen product sets were preserved.';
        }
        return ['counts' => $counts, 'warnings' => $warnings];
    }

    protected function preflightFailure(FbmConnection $connection): ?string
    {
        if (!$connection->hasConfiguredSecret('access_token')) {
            return 'Meta Access Token must be configured before catalog mapping refresh.';
        }

        $latestHealthCheck = $connection->latestHealthCheck()->first();
        if (!$latestHealthCheck) {
            return 'Run a read-only connection health test before catalog mapping refresh.';
        }

        if (!in_array($latestHealthCheck->status, [FbmConnectionHealthCheck::STATUS_HEALTHY, FbmConnectionHealthCheck::STATUS_WARNING], true)) {
            return 'Latest connection health status blocks catalog mapping refresh. Resolve it and run the health test again.';
        }

        return null;
    }

    protected function reconcileMapping(FbmCatalogProductMapping $mapping, bool $duplicateRetailerId = false): void
    {
        $product = $mapping->product_id ? Product::query()->find((int) $mapping->product_id) : null;
        if ($mapping->mapping_source === FbmCatalogProductMapping::SOURCE_MANUAL_OVERRIDE && $product) {
            $mapping->forceFill([
                'mapping_status' => FbmCatalogProductMapping::STATUS_MANUAL,
                'diagnostic_flags' => $this->diagnosticFlags($mapping, $product, $duplicateRetailerId),
            ])->save();
            return;
        }

        $product = $this->automaticProduct($mapping->retailer_id);
        $mapping->forceFill([
            'product_id' => $duplicateRetailerId ? null : $product?->id,
            'mapping_status' => $duplicateRetailerId
                ? FbmCatalogProductMapping::STATUS_AMBIGUOUS
                : ($product ? FbmCatalogProductMapping::STATUS_AUTOMATIC : FbmCatalogProductMapping::STATUS_UNMATCHED),
            'mapping_source' => $duplicateRetailerId || !$product ? null : FbmCatalogProductMapping::SOURCE_RETAILER_ID,
            'diagnostic_flags' => $this->diagnosticFlags($mapping, $duplicateRetailerId ? null : $product, $duplicateRetailerId),
        ])->save();
    }

    protected function diagnosticFlags(FbmCatalogProductMapping $mapping, ?Product $product, bool $duplicateRetailerId): array
    {
        $flags = [];
        if ($duplicateRetailerId) {
            $flags[] = 'duplicate_retailer_id';
        }
        if (!$product) {
            $flags[] = 'erp_product_missing';
            return $flags;
        }
        if ((int) $product->status !== 1) {
            $flags[] = 'erp_product_inactive';
        }
        $projection = $this->feedProducts->project($product);
        $providerAvailability = strtolower(str_replace(' ', '_', trim((string) $mapping->provider_availability)));
        if ($providerAvailability !== '' && $providerAvailability !== str_replace(' ', '_', $projection['availability'])) {
            $flags[] = 'provider_availability_mismatch';
        }
        if ($mapping->provider_price_amount !== null && abs((float) $mapping->provider_price_amount - (float) $projection['price_amount']) > 0.01) {
            $flags[] = 'provider_price_mismatch';
        }
        foreach ($projection['diagnostic_flags'] as $flag) {
            $flags[] = $flag;
        }
        return array_values(array_unique($flags));
    }

    protected function automaticProduct(?string $retailerId): ?Product
    {
        if ($retailerId === null || !preg_match('/^[1-9][0-9]{0,18}$/', $retailerId)) {
            return null;
        }
        return Product::query()->find((int) $retailerId);
    }

    protected function statusCounter(string $status): string
    {
        return match ($status) {
            FbmCatalogProductMapping::STATUS_AUTOMATIC => 'automatic_mapping_count',
            FbmCatalogProductMapping::STATUS_MANUAL => 'manual_mapping_count',
            FbmCatalogProductMapping::STATUS_AMBIGUOUS => 'ambiguous_count',
            default => 'unmatched_count',
        };
    }

    protected function emptyCounts(): array
    {
        return [
            'catalog_count' => 0,
            'product_item_count' => 0,
            'product_set_count' => 0,
            'automatic_mapping_count' => 0,
            'manual_mapping_count' => 0,
            'unmatched_count' => 0,
            'ambiguous_count' => 0,
        ];
    }

    protected function combineStatuses(array $statuses): string
    {
        if ($statuses !== [] && count(array_unique($statuses)) === 1 && $statuses[0] === FbmCatalogSyncRun::STATUS_SUCCESS) {
            return FbmCatalogSyncRun::STATUS_SUCCESS;
        }
        if ($statuses !== [] && count(array_filter($statuses, fn(string $status): bool => $status === FbmCatalogSyncRun::STATUS_FAILED)) === count($statuses)) {
            return FbmCatalogSyncRun::STATUS_FAILED;
        }
        return FbmCatalogSyncRun::STATUS_PARTIAL_SUCCESS;
    }

    protected function summary(string $status, array $warnings, array $counts): array
    {
        $counts = array_merge($this->emptyCounts(), $counts);
        $message = match ($status) {
            FbmCatalogSyncRun::STATUS_SUCCESS => 'Read-only catalog product mapping refresh completed successfully.',
            FbmCatalogSyncRun::STATUS_SKIPPED => 'Read-only catalog product mapping refresh skipped safely.',
            FbmCatalogSyncRun::STATUS_FAILED => 'Read-only catalog product mapping refresh failed safely.',
            default => 'Read-only catalog product mapping refresh completed with safe warnings.',
        };
        return ['status' => $status, 'warning_count' => count($warnings), 'warnings' => $warnings, 'counts' => $counts, 'message' => $message];
    }

    protected function recordSummaryRun(FbmConnection $connection, ?User $actor, string $mode, string $status, array $warnings): void
    {
        if (!self::schemaReady()) {
            return;
        }
        FbmCatalogSyncRun::query()->create([
            'fbm_connection_id' => (int) $connection->id,
            'fbm_catalog_id' => null,
            'actor_user_id' => $actor?->id,
            'execution_mode' => $this->safeExecutionMode($mode),
            'status' => $status,
            'graph_api_version' => $this->versionPolicy->resolve($connection->graph_api_version),
            'warning_details' => array_slice($warnings, 0, 30),
            'redacted_message' => $warnings[0] ?? 'Catalog mapping refresh skipped safely.',
            'started_at' => now(),
            'completed_at' => now(),
            'created_at' => now(),
        ]);
    }

    protected function providerId($value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }
        $value = trim((string) $value);
        return $value !== '' && strlen($value) <= 190 && preg_match('/^[A-Za-z0-9_.:-]+$/', $value) ? $value : null;
    }

    protected function price($value): ?float
    {
        if (is_numeric($value)) {
            return round((float) $value, 4);
        }
        if (is_scalar($value) && preg_match('/-?[0-9]+(?:\.[0-9]+)?/', (string) $value, $match)) {
            return round((float) $match[0], 4);
        }
        return null;
    }

    protected function safe($value, int $length): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }
        $value = trim(SecretRedactor::redactString((string) $value));
        return $value === '' ? null : mb_substr($value, 0, $length);
    }

    protected function safeExecutionMode(string $mode): string
    {
        return in_array($mode, [FbmCatalogSyncRun::MODE_QUEUED_WORKER, FbmCatalogSyncRun::MODE_MANUAL_CATALOG_REQUEST, FbmCatalogSyncRun::MODE_MANUAL_DIRECT_REQUEST], true)
            ? $mode
            : FbmCatalogSyncRun::MODE_QUEUED_WORKER;
    }

    protected function lockKey(int $connectionId, string $scope): string
    {
        $fingerprint = hash_hmac(
            'sha256',
            'lock|' . $connectionId . '|' . $scope,
            (string) config('app.key')
        );

        return 'fbm:sync-lock:' . $fingerprint;
    }

    protected function releaseLock(Lock $lock): void
    {
        try {
            $lock->release();
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}

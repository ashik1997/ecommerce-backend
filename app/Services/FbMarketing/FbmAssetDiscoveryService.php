<?php

namespace App\Services\FbMarketing;

use App\Models\FbMarketing\FbmAdAccount;
use App\Models\FbMarketing\FbmAssetDiscoveryRun;
use App\Models\FbMarketing\FbmBusinessAccount;
use App\Models\FbMarketing\FbmCatalog;
use App\Models\FbMarketing\FbmConnection;
use App\Models\FbMarketing\FbmConnectionHealthCheck;
use App\Models\FbMarketing\FbmDataset;
use App\Models\FbMarketing\FbmInstagramAccount;
use App\Models\FbMarketing\FbmPage;
use App\Models\FbMarketing\FbmPixel;
use App\Models\User;
use App\Support\Security\SecretRedactor;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class FbmAssetDiscoveryService
{
    public const ASSET_MODEL_MAP = [
        'business_accounts' => FbmBusinessAccount::class,
        'ad_accounts' => FbmAdAccount::class,
        'pages' => FbmPage::class,
        'pixels' => FbmPixel::class,
        'datasets' => FbmDataset::class,
        'catalogs' => FbmCatalog::class,
        'instagram_accounts' => FbmInstagramAccount::class,
    ];

    public const ASSET_TABLES = [
        'fbm_business_accounts',
        'fbm_ad_accounts',
        'fbm_pages',
        'fbm_pixels',
        'fbm_datasets',
        'fbm_catalogs',
        'fbm_instagram_accounts',
        'fbm_asset_discovery_runs',
        'fbm_asset_selection_audits',
    ];

    protected FbmGraphClient $graphClient;
    protected FbmGraphApiVersionPolicy $versionPolicy;

    public function __construct(FbmGraphClient $graphClient, FbmGraphApiVersionPolicy $versionPolicy)
    {
        $this->graphClient = $graphClient;
        $this->versionPolicy = $versionPolicy;
    }

    public static function schemaReady(): bool
    {
        foreach (self::ASSET_TABLES as $table) {
            if (!Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Run one explicit, bounded and read-only asset discovery attempt.
     * Provider rows are normalized in memory and raw Graph payloads are never
     * logged, persisted or returned to the browser.
     */
    public function discover(FbmConnection $connection, ?User $actor, ?string $ipAddress = null): FbmAssetDiscoveryRun
    {
        $startedAt = microtime(true);
        $version = $this->versionPolicy->resolve($connection->graph_api_version);
        $run = FbmAssetDiscoveryRun::create([
            'fbm_connection_id' => (int) $connection->id,
            'actor_user_id' => $actor?->id,
            'status' => FbmAssetDiscoveryRun::STATUS_FAILED,
            'graph_api_version' => $version,
            'asset_counts' => [],
            'successful_families' => [],
            'failed_families' => [],
            'warning_details' => [],
            'redacted_message' => null,
            'duration_ms' => null,
            'request_fingerprint' => $this->discoveryFingerprint($connection, $version),
            'request_ip_hash' => $this->hashOptionalValue($ipAddress),
            'started_at' => now(),
            'completed_at' => null,
        ]);

        try {
            $preflightFailure = $this->preflightFailure($connection, $version);
            if ($preflightFailure !== null) {
                return $this->finalizeRun($run, $startedAt, [
                    'warnings' => [$this->warning('preflight_failed', 'preflight', $preflightFailure)],
                    'family_complete' => [],
                    'family_requested' => [],
                    'successful_requests' => 0,
                ], FbmAssetDiscoveryRun::STATUS_FAILED, $preflightFailure);
            }

            $state = $this->initialState();

            $businessRows = $this->fetchRows(
                $connection,
                $version,
                $state,
                'business_accounts',
                'businesses',
                'me/businesses',
                ['id', 'name', 'verification_status']
            );
            $businesses = $this->syncBusinesses($connection, $businessRows, $state);

            if (!$state['family_complete']['business_accounts']) {
                $this->markDependentFamiliesIncomplete($state, 'Business discovery was incomplete, so unseen business-scoped assets were preserved.');
            }

            $this->syncAssets($connection, 'ad_accounts', $this->fetchRows(
                $connection,
                $version,
                $state,
                'ad_accounts',
                'direct',
                'me/adaccounts',
                ['id', 'name', 'account_status', 'currency', 'timezone_name']
            ), 'direct', null, $state);

            $this->syncPages($connection, $this->fetchRows(
                $connection,
                $version,
                $state,
                'pages',
                'direct',
                'me/accounts',
                ['id', 'name', 'category', 'instagram_business_account{id,name,username}']
            ), 'direct', null, $state);

            $businessLimit = max(1, min(100, (int) config('fb_marketing.asset_discovery.max_business_accounts', 30)));
            if (count($businesses) > $businessLimit) {
                $businesses = array_slice($businesses, 0, $businessLimit, true);
                $this->markDependentFamiliesIncomplete($state, 'Configured business-account traversal limit was reached. Existing unseen assets were preserved.');
                $this->addWarning($state, 'business_limit_reached', 'businesses', 'Configured business-account traversal limit was reached.');
            }

            foreach ($businesses as $providerBusinessId => $localBusinessId) {
                $this->syncBusinessScopedAssets($connection, $version, $providerBusinessId, $localBusinessId, $state);
            }

            // Instagram mappings are discovered through Page projections. An
            // incomplete Page family must preserve unseen Instagram availability.
            if (empty($state['family_complete']['pages'])) {
                $state['family_complete']['instagram_accounts'] = false;
            }

            foreach (array_keys(self::ASSET_MODEL_MAP) as $family) {
                if (!empty($state['family_complete'][$family])) {
                    $this->markUnavailableExcept($connection, $family, array_keys($state['seen'][$family]));
                }
            }

            $status = $state['warnings'] === []
                ? FbmAssetDiscoveryRun::STATUS_SUCCESS
                : ($state['successful_requests'] > 0 ? FbmAssetDiscoveryRun::STATUS_PARTIAL_SUCCESS : FbmAssetDiscoveryRun::STATUS_FAILED);

            $message = $status === FbmAssetDiscoveryRun::STATUS_SUCCESS
                ? 'Read-only Meta asset discovery completed successfully.'
                : ($status === FbmAssetDiscoveryRun::STATUS_PARTIAL_SUCCESS
                    ? 'Read-only Meta asset discovery completed with safe partial-result warnings.'
                    : 'Read-only Meta asset discovery failed before a complete safe result was available.');

            return $this->finalizeRun($run, $startedAt, $state, $status, $message);
        } catch (Throwable $exception) {
            Log::warning('FB MARKETING asset discovery failed safely.', [
                'fbm_connection_id' => (int) $connection->id,
                'fbm_asset_discovery_run_id' => (int) $run->id,
                'exception_class' => get_class($exception),
            ]);

            return $this->finalizeRun($run, $startedAt, [
                'warnings' => [$this->warning('unexpected_failure', 'discovery', 'Asset discovery stopped safely before a complete result was available.')],
                'family_complete' => [],
                'family_requested' => [],
                'successful_requests' => 0,
            ], FbmAssetDiscoveryRun::STATUS_FAILED, 'Asset discovery stopped safely before a complete result was available.');
        }
    }

    protected function syncBusinessScopedAssets(
        FbmConnection $connection,
        string $version,
        string $providerBusinessId,
        int $localBusinessId,
        array &$state
    ): void {
        foreach (['owned' => 'owned_ad_accounts', 'client' => 'client_ad_accounts'] as $relationship => $edge) {
            $this->syncAssets($connection, 'ad_accounts', $this->fetchRows(
                $connection,
                $version,
                $state,
                'ad_accounts',
                $relationship,
                $providerBusinessId . '/' . $edge,
                ['id', 'name', 'account_status', 'currency', 'timezone_name']
            ), $relationship, $localBusinessId, $state);
        }

        foreach (['owned' => 'owned_pages', 'client' => 'client_pages'] as $relationship => $edge) {
            $this->syncPages($connection, $this->fetchRows(
                $connection,
                $version,
                $state,
                'pages',
                $relationship,
                $providerBusinessId . '/' . $edge,
                ['id', 'name', 'category', 'instagram_business_account{id,name,username}']
            ), $relationship, $localBusinessId, $state);
        }

        foreach (['owned' => 'owned_pixels', 'client' => 'client_pixels'] as $relationship => $edge) {
            $this->syncAssets($connection, 'pixels', $this->fetchRows(
                $connection,
                $version,
                $state,
                'pixels',
                $relationship,
                $providerBusinessId . '/' . $edge,
                ['id', 'name', 'data_use_setting']
            ), $relationship, $localBusinessId, $state);
        }

        foreach (['owned' => 'owned_product_catalogs', 'client' => 'client_product_catalogs'] as $relationship => $edge) {
            $this->syncAssets($connection, 'catalogs', $this->fetchRows(
                $connection,
                $version,
                $state,
                'catalogs',
                $relationship,
                $providerBusinessId . '/' . $edge,
                ['id', 'name', 'vertical']
            ), $relationship, $localBusinessId, $state);
        }

        // Dataset availability varies by account and capability. Treat each
        // unsupported edge as an asset-family warning without failing the run.
        foreach (['owned' => 'owned_datasets', 'client' => 'client_datasets'] as $relationship => $edge) {
            $this->syncAssets($connection, 'datasets', $this->fetchRows(
                $connection,
                $version,
                $state,
                'datasets',
                $relationship,
                $providerBusinessId . '/' . $edge,
                ['id', 'name', 'type']
            ), $relationship, $localBusinessId, $state);
        }
    }

    protected function fetchRows(
        FbmConnection $connection,
        string $version,
        array &$state,
        string $family,
        string $scope,
        string $path,
        array $fields
    ): array {
        $state['family_requested'][$family] = true;
        $result = $this->graphClient->paginateEdge($connection, $path, $fields, $version);

        if (!empty($result['http_status']) && (int) $result['http_status'] >= 200 && (int) $result['http_status'] < 300) {
            $state['successful_requests']++;
        }

        if (empty($result['complete'])) {
            $state['family_complete'][$family] = false;
            $code = !empty($result['truncated']) ? 'edge_truncated' : 'edge_failed';
            $message = !empty($result['truncated'])
                ? 'A bounded Meta asset edge limit was reached. Existing unseen assets were preserved.'
                : 'A Meta asset edge did not return a complete safe result. Check granted permissions and supported capabilities.';
            $this->addWarning($state, $code, $family . ':' . $scope, $message);
        }

        return is_array($result['items'] ?? null) ? $result['items'] : [];
    }

    protected function syncBusinesses(FbmConnection $connection, array $rows, array &$state): array
    {
        $businesses = [];
        foreach ($rows as $row) {
            if (!is_array($row) || !$this->canProcess($state, 'business_accounts')) {
                continue;
            }

            $providerId = $this->safeProviderId($row['id'] ?? null);
            if ($providerId === null) {
                $this->addWarning($state, 'invalid_provider_id', 'business_accounts', 'A business account row was skipped because its provider ID was invalid.');
                continue;
            }

            $asset = $this->upsertAsset(FbmBusinessAccount::class, $connection, $providerId, 'direct', null, [
                'asset_name' => $this->safeScalar($row['name'] ?? null, 255),
                'verification_status' => $this->safeScalar($row['verification_status'] ?? null, 80),
            ]);
            $state['seen']['business_accounts'][$providerId] = true;
            $businesses[$providerId] = (int) $asset->id;
        }

        return $businesses;
    }

    protected function syncAssets(
        FbmConnection $connection,
        string $family,
        array $rows,
        string $relationship,
        ?int $businessAccountId,
        array &$state
    ): void {
        $modelClass = self::ASSET_MODEL_MAP[$family];
        foreach ($rows as $row) {
            if (!is_array($row) || !$this->canProcess($state, $family)) {
                continue;
            }

            $providerId = $this->safeProviderId($row['id'] ?? null);
            if ($providerId === null) {
                $this->addWarning($state, 'invalid_provider_id', $family, 'An asset row was skipped because its provider ID was invalid.');
                continue;
            }

            $attributes = ['asset_name' => $this->safeScalar($row['name'] ?? null, 255)];
            if ($family === 'ad_accounts') {
                $attributes['account_status'] = $this->safeScalar($row['account_status'] ?? null, 80);
                $attributes['provider_status'] = $attributes['account_status'];
                $attributes['currency'] = $this->safeScalar($row['currency'] ?? null, 20);
                $attributes['timezone_name'] = $this->safeScalar($row['timezone_name'] ?? null, 120);
            } elseif ($family === 'pixels') {
                $attributes['data_use_setting'] = $this->safeScalar($row['data_use_setting'] ?? null, 80);
            } elseif ($family === 'datasets') {
                $attributes['dataset_type'] = $this->safeScalar($row['type'] ?? null, 80);
            } elseif ($family === 'catalogs') {
                $attributes['vertical'] = $this->safeScalar($row['vertical'] ?? null, 80);
            }

            $this->upsertAsset($modelClass, $connection, $providerId, $relationship, $businessAccountId, $attributes);
            $state['seen'][$family][$providerId] = true;
        }
    }

    protected function syncPages(
        FbmConnection $connection,
        array $rows,
        string $relationship,
        ?int $businessAccountId,
        array &$state
    ): void {
        foreach ($rows as $row) {
            if (!is_array($row) || !$this->canProcess($state, 'pages')) {
                continue;
            }

            $providerId = $this->safeProviderId($row['id'] ?? null);
            if ($providerId === null) {
                $this->addWarning($state, 'invalid_provider_id', 'pages', 'A Page row was skipped because its provider ID was invalid.');
                continue;
            }

            $page = $this->upsertAsset(FbmPage::class, $connection, $providerId, $relationship, $businessAccountId, [
                'asset_name' => $this->safeScalar($row['name'] ?? null, 255),
                'category' => $this->safeScalar($row['category'] ?? null, 160),
            ]);
            $state['seen']['pages'][$providerId] = true;

            $instagram = is_array($row['instagram_business_account'] ?? null) ? $row['instagram_business_account'] : null;
            if ($instagram !== null) {
                $this->syncInstagramAccount($connection, $instagram, $relationship, $businessAccountId, (int) $page->id, $state);
            }
        }
    }

    protected function syncInstagramAccount(
        FbmConnection $connection,
        array $row,
        string $relationship,
        ?int $businessAccountId,
        int $pageId,
        array &$state
    ): void {
        if (!$this->canProcess($state, 'instagram_accounts')) {
            return;
        }

        $providerId = $this->safeProviderId($row['id'] ?? null);
        if ($providerId === null) {
            $this->addWarning($state, 'invalid_provider_id', 'instagram_accounts', 'A linked Instagram row was skipped because its provider ID was invalid.');
            return;
        }

        $this->upsertAsset(FbmInstagramAccount::class, $connection, $providerId, $relationship, $businessAccountId, [
            'asset_name' => $this->safeScalar($row['name'] ?? null, 255),
            'username' => $this->safeScalar($row['username'] ?? null, 190),
            'fbm_page_id' => $pageId,
        ]);
        $state['seen']['instagram_accounts'][$providerId] = true;
        $state['family_requested']['instagram_accounts'] = true;
    }

    protected function upsertAsset(
        string $modelClass,
        FbmConnection $connection,
        string $providerId,
        string $relationship,
        ?int $businessAccountId,
        array $attributes
    ) {
        $asset = $modelClass::query()->firstOrNew([
            'fbm_connection_id' => (int) $connection->id,
            'provider_asset_id' => $providerId,
        ]);

        $resolvedRelationship = $this->preferredRelationship((string) $asset->asset_relationship, $relationship);
        $resolvedBusinessAccountId = $asset->exists && $this->relationshipRank((string) $asset->asset_relationship) > $this->relationshipRank($relationship)
            ? $asset->fbm_business_account_id
            : $businessAccountId;

        $asset->fill(array_merge($attributes, [
            'asset_relationship' => $resolvedRelationship,
            'is_available' => true,
            'last_seen_at' => now(),
        ]));

        if ($asset->getTable() !== 'fbm_business_accounts') {
            $asset->fbm_business_account_id = $resolvedBusinessAccountId;
        }

        $asset->save();

        return $asset;
    }

    protected function markUnavailableExcept(FbmConnection $connection, string $family, array $seenProviderIds): void
    {
        $modelClass = self::ASSET_MODEL_MAP[$family];
        $query = $modelClass::query()->where('fbm_connection_id', (int) $connection->id);

        if ($seenProviderIds !== []) {
            $query->whereNotIn('provider_asset_id', $seenProviderIds);
        }

        $query->update(['is_available' => false]);
    }

    protected function preflightFailure(FbmConnection $connection, string $version): ?string
    {
        if (!$connection->is_active) {
            return 'Connection is disabled. Enable it before running asset discovery.';
        }

        if (!$this->versionPolicy->isAllowed($version)) {
            return 'Configured Graph API version is not allowed by the centralized FB MARKETING policy.';
        }

        if (!$connection->hasConfiguredSecret('access_token')) {
            return 'Meta Access Token must be configured before running asset discovery.';
        }

        $latestHealthCheck = $connection->latestHealthCheck()->first();
        if (!$latestHealthCheck) {
            return 'Run a read-only connection health test before asset discovery.';
        }

        if (!in_array($latestHealthCheck->status, [FbmConnectionHealthCheck::STATUS_HEALTHY, FbmConnectionHealthCheck::STATUS_WARNING], true)) {
            return 'Latest connection health status blocks asset discovery. Resolve it and run the health test again.';
        }

        return null;
    }

    protected function initialState(): array
    {
        $families = array_keys(self::ASSET_MODEL_MAP);

        return [
            'seen' => array_fill_keys($families, []),
            'family_complete' => array_fill_keys($families, true),
            'family_requested' => array_fill_keys($families, false),
            'warnings' => [],
            'successful_requests' => 0,
            'processed_records' => 0,
            'total_limit_warned' => false,
        ];
    }

    protected function canProcess(array &$state, string $family): bool
    {
        $maximum = max(1, min(10000, (int) config('fb_marketing.asset_discovery.max_total_records', 2500)));
        if ($state['processed_records'] >= $maximum) {
            $state['family_complete'][$family] = false;
            if (!$state['total_limit_warned']) {
                $state['total_limit_warned'] = true;
                $this->addWarning($state, 'total_record_limit_reached', $family, 'Configured total asset record limit was reached. Existing unseen assets were preserved.');
            }

            return false;
        }

        $state['processed_records']++;

        return true;
    }

    protected function markDependentFamiliesIncomplete(array &$state, string $message): void
    {
        foreach (['ad_accounts', 'pages', 'pixels', 'datasets', 'catalogs', 'instagram_accounts'] as $family) {
            $state['family_complete'][$family] = false;
        }

        $this->addWarning($state, 'business_dependency_incomplete', 'businesses', $message);
    }

    protected function addWarning(array &$state, string $code, string $family, string $message): void
    {
        if (count($state['warnings']) >= 50) {
            return;
        }

        $warning = $this->warning($code, $family, $message);
        foreach ($state['warnings'] as $existing) {
            if (($existing['code'] ?? null) === $warning['code'] && ($existing['family'] ?? null) === $warning['family']) {
                return;
            }
        }

        $state['warnings'][] = $warning;
    }

    protected function warning(string $code, string $family, string $message): array
    {
        return [
            'code' => $this->safeIdentifier($code, 80),
            'family' => $this->safeIdentifier($family, 120),
            'message' => $this->safeScalar($message, 500),
        ];
    }

    protected function finalizeRun(FbmAssetDiscoveryRun $run, float $startedAt, array $state, string $status, string $message): FbmAssetDiscoveryRun
    {
        $familyComplete = is_array($state['family_complete'] ?? null) ? $state['family_complete'] : [];
        $familyRequested = is_array($state['family_requested'] ?? null) ? $state['family_requested'] : [];
        $successful = [];
        $failed = [];

        foreach ($familyRequested as $family => $requested) {
            if (!$requested) {
                continue;
            }
            if (!empty($familyComplete[$family])) {
                $successful[] = $family;
            } else {
                $failed[] = $family;
            }
        }

        sort($successful);
        sort($failed);

        $run->fill([
            'status' => $status,
            'asset_counts' => $this->availableCounts((int) $run->fbm_connection_id),
            'successful_families' => $successful,
            'failed_families' => $failed,
            'warning_details' => is_array($state['warnings'] ?? null) ? $state['warnings'] : [],
            'redacted_message' => $this->safeScalar($message, 500),
            'duration_ms' => max(0, (int) round((microtime(true) - $startedAt) * 1000)),
            'completed_at' => now(),
        ])->save();

        Log::info('FB MARKETING asset discovery run recorded.', [
            'fbm_connection_id' => (int) $run->fbm_connection_id,
            'fbm_asset_discovery_run_id' => (int) $run->id,
            'actor_user_id' => $run->actor_user_id ? (int) $run->actor_user_id : null,
            'status' => (string) $run->status,
            'duration_ms' => $run->duration_ms,
            'successful_families' => $successful,
            'failed_families' => $failed,
        ]);

        return $run->fresh();
    }

    protected function availableCounts(int $connectionId): array
    {
        $counts = [];
        foreach (self::ASSET_MODEL_MAP as $family => $modelClass) {
            $counts[$family] = $modelClass::query()
                ->where('fbm_connection_id', $connectionId)
                ->where('is_available', true)
                ->count();
        }

        return $counts;
    }

    protected function safeProviderId($value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);
        if ($value === '' || strlen($value) > 160 || !preg_match('/^[A-Za-z0-9_.:-]+$/', $value)) {
            return null;
        }

        return $value;
    }

    protected function safeScalar($value, int $length): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $value = trim(preg_replace('/[\x00-\x1F\x7F]/', ' ', (string) $value) ?? '');
        if ($value === '') {
            return null;
        }

        $value = SecretRedactor::redactString($value);

        return strlen($value) <= $length ? $value : substr($value, 0, $length);
    }

    protected function safeIdentifier(string $value, int $length): string
    {
        $value = preg_replace('/[^A-Za-z0-9_:.-]+/', '_', $value) ?? 'unknown';

        return strlen($value) <= $length ? $value : substr($value, 0, $length);
    }

    protected function preferredRelationship(string $current, string $candidate): string
    {
        return $this->relationshipRank($candidate) >= $this->relationshipRank($current) ? $candidate : $current;
    }

    protected function relationshipRank(string $relationship): int
    {
        return ['direct' => 1, 'client' => 2, 'owned' => 3][$relationship] ?? 0;
    }

    protected function discoveryFingerprint(FbmConnection $connection, string $version): string
    {
        return hash_hmac('sha256', implode('|', [
            'fbm-asset-discovery',
            (string) $connection->id,
            $version,
            (string) $connection->secret_version,
        ]), (string) config('app.key', ''));
    }

    protected function hashOptionalValue(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : hash_hmac('sha256', $value, (string) config('app.key', ''));
    }
}

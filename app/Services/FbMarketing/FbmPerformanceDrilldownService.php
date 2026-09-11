<?php

namespace App\Services\FbMarketing;

use App\Models\FbMarketing\FbmAd;
use App\Models\FbMarketing\FbmAdAccount;
use App\Models\FbMarketing\FbmAdSet;
use App\Models\FbMarketing\FbmCampaign;
use App\Models\FbMarketing\FbmInsightDailySnapshot;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class FbmPerformanceDrilldownService
{
    public static function schemaReady(): bool
    {
        foreach (['fbm_ad_accounts', 'fbm_campaigns', 'fbm_ad_sets', 'fbm_ads', 'fbm_insight_daily_snapshots'] as $table) {
            if (!Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }

    public function overview(array $filters): array
    {
        $range = $this->dateRange($filters);
        $accountOptions = Schema::hasTable('fbm_ad_accounts') ? $this->selectedAvailableAccounts() : collect();
        $requestedAccountId = $this->requestedAccountId($filters);
        $accountFilterValid = $requestedAccountId === null || $accountOptions->contains('id', $requestedAccountId);
        $accounts = $requestedAccountId === null
            ? $accountOptions
            : $accountOptions->where('id', $requestedAccountId)->values();
        $campaigns = collect();
        $campaignRowsTruncated = false;
        $snapshots = collect();

        if (self::schemaReady() && $accountFilterValid && $accounts->isNotEmpty()) {
            $query = FbmCampaign::query()
                ->with('adAccount:id,asset_name,currency,timezone_name')
                ->whereIn('fbm_ad_account_id', $accounts->pluck('id')->all())
                ->where('is_available', true);
            $this->applyEntityFilters($query, $filters);
            [$campaigns, $campaignRowsTruncated] = $this->boundedEntities(
                $query->orderBy('name')->orderBy('id')->limit($this->maxEntitiesPerTable() + 1)->get()
            );
            $snapshots = $this->snapshotRows('campaign', 'fbm_campaign_id', $campaigns->pluck('id')->all(), $range);
        }

        $rows = $this->entityRows($campaigns, $snapshots, 'campaign', $range);
        $summary = $this->aggregate($snapshots, $accountOptions);
        $coverage = $this->coverage($snapshots, 'fbm_campaign_id', $campaigns->pluck('id')->all(), $range);
        $warnings = $this->baseWarnings($accountFilterValid, $accountOptions, $campaigns, $coverage, $summary, $campaignRowsTruncated);

        return [
            'schema_ready' => self::schemaReady(),
            'filters' => $this->safeFilters($filters, $range, $requestedAccountId),
            'range' => $this->rangeSummary($range),
            'account_filter_valid' => $accountFilterValid,
            'account_options' => $accountOptions->map(fn(FbmAdAccount $account): array => $this->safeAccountSummary($account))->values(),
            'selected_accounts' => $accounts->map(fn(FbmAdAccount $account): array => $this->safeAccountSummary($account))->values(),
            'entity_level' => 'campaign',
            'entity_label' => 'Campaign',
            'rows' => $rows,
            'currency_groups' => $summary['currency_groups'],
            'mixed_currency' => count($summary['currency_groups']) > 1,
            'coverage' => $coverage,
            'latest_snapshot_date' => $summary['latest_snapshot_date'],
            'freshness_watermark' => $summary['freshness_watermark'],
            'rows_truncated' => $campaignRowsTruncated,
            'warnings' => $warnings,
        ];
    }

    public function campaign(FbmCampaign $campaign, array $filters): array
    {
        $campaign->load('adAccount:id,asset_name,currency,timezone_name');
        $range = $this->dateRange($filters);
        $adSets = collect();
        $childRowsTruncated = false;
        $parentSnapshots = collect();
        $childSnapshots = collect();

        if (self::schemaReady()) {
            $parentSnapshots = $this->snapshotRows('campaign', 'fbm_campaign_id', [(int) $campaign->id], $range);
            $query = FbmAdSet::query()
                ->with('adAccount:id,asset_name,currency,timezone_name')
                ->where('fbm_campaign_id', (int) $campaign->id)
                ->where('is_available', true);
            $this->applyEntityFilters($query, $filters);
            [$adSets, $childRowsTruncated] = $this->boundedEntities(
                $query->orderBy('name')->orderBy('id')->limit($this->maxEntitiesPerTable() + 1)->get()
            );
            $childSnapshots = $this->snapshotRows('adset', 'fbm_ad_set_id', $adSets->pluck('id')->all(), $range);
        }

        return $this->detailResult(
            'campaign',
            $this->safeCampaignSummary($campaign),
            'adset',
            'Ad Set',
            $adSets,
            $parentSnapshots,
            $childSnapshots,
            $filters,
            $range,
            $childRowsTruncated
        );
    }

    public function adSet(FbmAdSet $adSet, array $filters): array
    {
        $adSet->load(['campaign:id,name', 'adAccount:id,asset_name,currency,timezone_name']);
        $range = $this->dateRange($filters);
        $ads = collect();
        $childRowsTruncated = false;
        $parentSnapshots = collect();
        $childSnapshots = collect();

        if (self::schemaReady()) {
            $parentSnapshots = $this->snapshotRows('adset', 'fbm_ad_set_id', [(int) $adSet->id], $range);
            $query = FbmAd::query()
                ->with('adAccount:id,asset_name,currency,timezone_name')
                ->where('fbm_ad_set_id', (int) $adSet->id)
                ->where('is_available', true);
            $this->applyEntityFilters($query, $filters);
            [$ads, $childRowsTruncated] = $this->boundedEntities(
                $query->orderBy('name')->orderBy('id')->limit($this->maxEntitiesPerTable() + 1)->get()
            );
            $childSnapshots = $this->snapshotRows('ad', 'fbm_ad_id', $ads->pluck('id')->all(), $range);
        }

        return $this->detailResult(
            'adset',
            $this->safeAdSetSummary($adSet),
            'ad',
            'Ad',
            $ads,
            $parentSnapshots,
            $childSnapshots,
            $filters,
            $range,
            $childRowsTruncated
        );
    }

    public function ad(FbmAd $ad, array $filters): array
    {
        $ad->load(['campaign:id,name', 'adSet:id,name', 'creative:id,name', 'adAccount:id,asset_name,currency,timezone_name']);
        $range = $this->dateRange($filters);
        $snapshots = self::schemaReady()
            ? $this->snapshotRows('ad', 'fbm_ad_id', [(int) $ad->id], $range)
            : collect();
        $accounts = $ad->adAccount ? collect([$ad->adAccount]) : collect();
        $summary = $this->aggregate($snapshots, $accounts);
        $coverage = $this->coverage($snapshots, 'fbm_ad_id', [(int) $ad->id], $range);
        $warnings = $this->detailWarnings($coverage, $summary, $snapshots);

        return [
            'schema_ready' => self::schemaReady(),
            'filters' => $this->safeFilters($filters, $range, null),
            'range' => $this->rangeSummary($range),
            'entity_level' => 'ad',
            'entity_label' => 'Ad',
            'entity' => $this->safeAdSummary($ad),
            'currency_groups' => $summary['currency_groups'],
            'mixed_currency' => count($summary['currency_groups']) > 1,
            'coverage' => $coverage,
            'latest_snapshot_date' => $summary['latest_snapshot_date'],
            'freshness_watermark' => $summary['freshness_watermark'],
            'daily_rows' => $this->dailyRows($snapshots, $accounts),
            'warnings' => $warnings,
        ];
    }

    protected function detailResult(
        string $entityLevel,
        array $entity,
        string $childLevel,
        string $childLabel,
        Collection $children,
        Collection $parentSnapshots,
        Collection $childSnapshots,
        array $filters,
        array $range,
        bool $childRowsTruncated = false
    ): array {
        $accounts = $this->accountsFromSnapshotsOrEntities($parentSnapshots, $children, $entity);
        $summary = $this->aggregate($parentSnapshots, $accounts);
        $coverage = $this->coverage($parentSnapshots, $this->snapshotEntityColumn($entityLevel), [(int) $entity['id']], $range);
        $childCoverage = $this->coverage($childSnapshots, $this->snapshotEntityColumn($childLevel), $children->pluck('id')->all(), $range);
        $warnings = $this->detailWarnings($coverage, $summary, $parentSnapshots);
        if ($childRowsTruncated) {
            $warnings[] = $this->warning('warning', 'The child table reached the configured bounded row limit. Narrow the filters before treating this drilldown as exhaustive.');
        }

        return [
            'schema_ready' => self::schemaReady(),
            'filters' => $this->safeFilters($filters, $range, null),
            'range' => $this->rangeSummary($range),
            'entity_level' => $entityLevel,
            'entity_label' => ucwords(str_replace('_', ' ', $entityLevel)),
            'entity' => $entity,
            'currency_groups' => $summary['currency_groups'],
            'mixed_currency' => count($summary['currency_groups']) > 1,
            'coverage' => $coverage,
            'latest_snapshot_date' => $summary['latest_snapshot_date'],
            'freshness_watermark' => $summary['freshness_watermark'],
            'warnings' => $warnings,
            'child_level' => $childLevel,
            'child_label' => $childLabel,
            'child_rows' => $this->entityRows($children, $childSnapshots, $childLevel, $range),
            'child_rows_truncated' => $childRowsTruncated,
            'child_coverage' => $childCoverage,
        ];
    }

    protected function accountsFromSnapshotsOrEntities(Collection $snapshots, Collection $entities, array $entity): Collection
    {
        $ids = $snapshots->pluck('fbm_ad_account_id')
            ->merge($entities->pluck('fbm_ad_account_id'))
            ->push($entity['fbm_ad_account_id'] ?? null)
            ->filter()
            ->unique()
            ->values();

        return $ids->isEmpty()
            ? collect()
            : FbmAdAccount::query()->whereIn('id', $ids->all())->get(['id', 'asset_name', 'currency', 'timezone_name']);
    }

    protected function selectedAvailableAccounts(): Collection
    {
        return FbmAdAccount::query()
            ->where('is_selected', true)
            ->where('is_available', true)
            ->orderBy('asset_name')
            ->orderBy('id')
            ->get(['id', 'asset_name', 'currency', 'timezone_name']);
    }

    protected function snapshotRows(string $level, string $entityColumn, array $entityIds, array $range): Collection
    {
        $entityIds = array_values(array_unique(array_filter(array_map('intval', $entityIds))));
        if ($entityIds === []) {
            return collect();
        }

        return FbmInsightDailySnapshot::query()
            ->where('insight_level', $level)
            ->whereIn($entityColumn, $entityIds)
            ->whereBetween('snapshot_date', [$range['from']->toDateString(), $range['to']->toDateString()])
            ->orderBy('snapshot_date')
            ->orderBy($entityColumn)
            ->get([
                'fbm_ad_account_id',
                'fbm_campaign_id',
                'fbm_ad_set_id',
                'fbm_ad_id',
                'snapshot_date',
                'account_currency',
                'spend',
                'impressions',
                'reach',
                'clicks',
                'inline_link_clicks',
                'meta_result_count',
                'meta_result_value',
                'meta_purchase_count',
                'meta_purchase_value',
                'fetched_at',
                'freshness_watermark',
            ]);
    }

    protected function entityRows(Collection $entities, Collection $snapshots, string $level, array $range): array
    {
        $column = $this->snapshotEntityColumn($level);
        $rows = [];

        foreach ($entities as $entity) {
            $entitySnapshots = $snapshots->where($column, (int) $entity->id)->values();
            $fallbackCurrency = $this->currency(optional($entity->adAccount)->currency);
            $groups = $entitySnapshots->groupBy(function ($snapshot) use ($fallbackCurrency): string {
                return $this->currency($snapshot->account_currency, $fallbackCurrency);
            });

            if ($groups->isEmpty()) {
                $groups = collect([$fallbackCurrency => collect()]);
            }

            foreach ($groups as $currency => $groupRows) {
                $metrics = $this->metrics($groupRows);
                $rows[] = array_merge($this->safeEntitySummary($entity, $level), $metrics, [
                    'currency' => $currency,
                    'snapshot_count' => $groupRows->count(),
                    'covered_days' => $groupRows->pluck('snapshot_date')->filter()->unique()->count(),
                    'missing_days' => max(0, $range['days'] - $groupRows->pluck('snapshot_date')->filter()->unique()->count()),
                    'drilldown_route' => $this->drilldownRoute($level),
                    'drilldown_parameter' => $this->drilldownParameter($level),
                ]);
            }
        }

        usort($rows, fn(array $left, array $right): int => [strtolower((string) $left['name']), $left['currency']] <=> [strtolower((string) $right['name']), $right['currency']]);

        return $rows;
    }

    protected function aggregate(Collection $snapshots, Collection $accounts): array
    {
        $fallbacks = $accounts->mapWithKeys(fn(FbmAdAccount $account): array => [(int) $account->id => $this->currency($account->currency)]);
        $groups = [];
        $latestSnapshotDate = null;
        $freshnessWatermark = null;

        foreach ($snapshots as $snapshot) {
            $currency = $this->currency($snapshot->account_currency, $fallbacks->get((int) $snapshot->fbm_ad_account_id));
            $groups[$currency][] = $snapshot;
            $latestSnapshotDate = $this->maxDate($latestSnapshotDate, optional($snapshot->snapshot_date)->toDateString());
            $freshnessWatermark = $this->maxDate($freshnessWatermark, optional($snapshot->freshness_watermark)->toDateString());
        }

        ksort($groups);
        $currencyGroups = [];
        foreach ($groups as $currency => $rows) {
            $currencyGroups[] = array_merge(['currency' => $currency], $this->metrics(collect($rows)));
        }

        return [
            'currency_groups' => $currencyGroups,
            'latest_snapshot_date' => $latestSnapshotDate,
            'freshness_watermark' => $freshnessWatermark,
        ];
    }

    protected function dailyRows(Collection $snapshots, Collection $accounts): array
    {
        $fallbacks = $accounts->mapWithKeys(fn(FbmAdAccount $account): array => [(int) $account->id => $this->currency($account->currency)]);
        $groups = $snapshots->groupBy(function ($snapshot) use ($fallbacks): string {
            $date = optional($snapshot->snapshot_date)->toDateString() ?: 'unknown';
            $currency = $this->currency($snapshot->account_currency, $fallbacks->get((int) $snapshot->fbm_ad_account_id));

            return $date . '|' . $currency;
        });
        $rows = [];

        foreach ($groups as $key => $groupRows) {
            [$date, $currency] = explode('|', $key, 2);
            $rows[] = array_merge(['date' => $date, 'currency' => $currency], $this->metrics($groupRows));
        }
        usort($rows, fn(array $left, array $right): int => [$left['date'], $left['currency']] <=> [$right['date'], $right['currency']]);

        return $rows;
    }

    protected function metrics(Collection $rows): array
    {
        $metrics = $this->emptyMetrics();
        $latestSnapshotDate = null;
        $latestFetchedAt = null;
        $freshnessWatermark = null;

        foreach ($rows as $row) {
            $metrics['spend'] += (float) $row->spend;
            $metrics['impressions'] += (int) $row->impressions;
            $metrics['summed_daily_reach'] += (int) $row->reach;
            $metrics['clicks'] += (int) $row->clicks;
            $metrics['inline_link_clicks'] += (int) $row->inline_link_clicks;
            $metrics['meta_result_count'] += (float) $row->meta_result_count;
            $metrics['meta_result_value'] += (float) $row->meta_result_value;
            $metrics['meta_purchase_count'] += (float) $row->meta_purchase_count;
            $metrics['meta_purchase_value'] += (float) $row->meta_purchase_value;
            $latestSnapshotDate = $this->maxDate($latestSnapshotDate, optional($row->snapshot_date)->toDateString());
            $latestFetchedAt = $this->maxDate($latestFetchedAt, optional($row->fetched_at)->toDateTimeString());
            $freshnessWatermark = $this->maxDate($freshnessWatermark, optional($row->freshness_watermark)->toDateString());
        }

        $metrics['ctr'] = $metrics['impressions'] > 0 ? ($metrics['clicks'] / $metrics['impressions']) * 100 : 0.0;
        $metrics['cpc'] = $metrics['clicks'] > 0 ? ($metrics['spend'] / $metrics['clicks']) : 0.0;
        $metrics['cpm'] = $metrics['impressions'] > 0 ? (($metrics['spend'] / $metrics['impressions']) * 1000) : 0.0;
        $metrics['latest_snapshot_date'] = $latestSnapshotDate;
        $metrics['latest_fetched_at'] = $latestFetchedAt;
        $metrics['freshness_watermark'] = $freshnessWatermark;

        return $metrics;
    }

    protected function emptyMetrics(): array
    {
        return [
            'spend' => 0.0,
            'impressions' => 0,
            'summed_daily_reach' => 0,
            'clicks' => 0,
            'inline_link_clicks' => 0,
            'meta_result_count' => 0.0,
            'meta_result_value' => 0.0,
            'meta_purchase_count' => 0.0,
            'meta_purchase_value' => 0.0,
            'ctr' => 0.0,
            'cpc' => 0.0,
            'cpm' => 0.0,
        ];
    }

    protected function coverage(Collection $snapshots, string $column, array $entityIds, array $range): array
    {
        $entityIds = array_values(array_unique(array_filter(array_map('intval', $entityIds))));
        $covered = [];
        $dates = [];

        foreach ($snapshots as $snapshot) {
            $entityId = (int) ($snapshot->{$column} ?? 0);
            $date = optional($snapshot->snapshot_date)->toDateString();
            if ($entityId <= 0 || $date === null) {
                continue;
            }
            $covered[$entityId . '|' . $date] = true;
            $dates[$date] = true;
        }

        $expectedEntityDays = count($entityIds) * $range['days'];
        $coveredEntityDays = count($covered);

        return [
            'entity_count' => count($entityIds),
            'expected_days' => $range['days'],
            'covered_dates' => count($dates),
            'expected_entity_days' => $expectedEntityDays,
            'covered_entity_days' => $coveredEntityDays,
            'missing_entity_days' => max(0, $expectedEntityDays - $coveredEntityDays),
            'coverage_percent' => $expectedEntityDays > 0 ? round(($coveredEntityDays / $expectedEntityDays) * 100, 1) : 0.0,
        ];
    }

    protected function boundedEntities(Collection $entities): array
    {
        $limit = $this->maxEntitiesPerTable();
        $truncated = $entities->count() > $limit;

        return [$entities->take($limit)->values(), $truncated];
    }

    protected function maxEntitiesPerTable(): int
    {
        return max(25, min(1000, (int) config('fb_marketing.performance_drilldowns.max_entities_per_table', 250)));
    }

    protected function applyEntityFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['status'])) {
            $query->where(function (Builder $query) use ($filters) {
                $query->where('effective_status', (string) $filters['status'])
                    ->orWhere('configured_status', (string) $filters['status']);
            });
        }
        if (!empty($filters['search'])) {
            $search = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], (string) $filters['search']);
            $query->where('name', 'like', '%' . $search . '%');
        }
    }

    protected function safeEntitySummary($entity, string $level): array
    {
        if ($level === 'campaign') {
            return $this->safeCampaignSummary($entity);
        }
        if ($level === 'adset') {
            return $this->safeAdSetSummary($entity);
        }

        return $this->safeAdSummary($entity);
    }

    protected function safeCampaignSummary(FbmCampaign $campaign): array
    {
        return [
            'id' => (int) $campaign->id,
            'fbm_ad_account_id' => (int) $campaign->fbm_ad_account_id,
            'ad_account_name' => optional($campaign->adAccount)->asset_name ?: 'Unnamed Ad Account',
            'name' => $campaign->name ?: 'Unnamed Campaign',
            'objective' => $campaign->objective,
            'configured_status' => $campaign->configured_status,
            'effective_status' => $campaign->effective_status,
            'is_available' => (bool) $campaign->is_available,
        ];
    }

    protected function safeAdSetSummary(FbmAdSet $adSet): array
    {
        return [
            'id' => (int) $adSet->id,
            'fbm_ad_account_id' => (int) $adSet->fbm_ad_account_id,
            'fbm_campaign_id' => $adSet->fbm_campaign_id ? (int) $adSet->fbm_campaign_id : null,
            'ad_account_name' => optional($adSet->adAccount)->asset_name ?: 'Unnamed Ad Account',
            'campaign_name' => optional($adSet->campaign)->name,
            'name' => $adSet->name ?: 'Unnamed Ad Set',
            'optimization_goal' => $adSet->optimization_goal,
            'billing_event' => $adSet->billing_event,
            'configured_status' => $adSet->configured_status,
            'effective_status' => $adSet->effective_status,
            'is_available' => (bool) $adSet->is_available,
        ];
    }

    protected function safeAdSummary(FbmAd $ad): array
    {
        return [
            'id' => (int) $ad->id,
            'fbm_ad_account_id' => (int) $ad->fbm_ad_account_id,
            'fbm_campaign_id' => $ad->fbm_campaign_id ? (int) $ad->fbm_campaign_id : null,
            'fbm_ad_set_id' => $ad->fbm_ad_set_id ? (int) $ad->fbm_ad_set_id : null,
            'ad_account_name' => optional($ad->adAccount)->asset_name ?: 'Unnamed Ad Account',
            'campaign_name' => optional($ad->campaign)->name,
            'ad_set_name' => optional($ad->adSet)->name,
            'creative_name' => optional($ad->creative)->name,
            'name' => $ad->name ?: 'Unnamed Ad',
            'configured_status' => $ad->configured_status,
            'effective_status' => $ad->effective_status,
            'is_available' => (bool) $ad->is_available,
        ];
    }

    protected function safeAccountSummary(FbmAdAccount $account): array
    {
        return [
            'id' => (int) $account->id,
            'asset_name' => $account->asset_name ?: 'Unnamed Ad Account',
            'currency' => $this->currency($account->currency),
            'timezone_name' => $account->timezone_name,
        ];
    }

    protected function baseWarnings(bool $accountFilterValid, Collection $accountOptions, Collection $campaigns, array $coverage, array $summary, bool $campaignRowsTruncated): array
    {
        $warnings = [];
        if (!self::schemaReady()) {
            $warnings[] = $this->warning('danger', 'FBM-07 and FBM-08 migrations are required before performance drilldowns are available.');
        }
        if (!$accountFilterValid) {
            $warnings[] = $this->warning('warning', 'The requested Ad Account is not selected and available in this application. No performance data was exposed.');
        }
        if ($accountOptions->isEmpty()) {
            $warnings[] = $this->warning('warning', 'No selected and available Ad Account exists. Select an Ad Account before refreshing drilldown snapshots.');
        }
        if ($campaigns->isEmpty() && self::schemaReady() && $accountOptions->isNotEmpty()) {
            $warnings[] = $this->warning('warning', 'No locally mirrored Campaign matched the selected filters. Refresh the hierarchy or adjust the filters.');
        }
        if ($coverage['missing_entity_days'] > 0 && $campaigns->isNotEmpty()) {
            $warnings[] = $this->warning('warning', 'Campaign-level snapshot coverage is incomplete. Review missing coverage before treating totals as final.');
        }
        if (count($summary['currency_groups']) > 1) {
            $warnings[] = $this->warning('warning', 'Selected Campaigns use multiple currencies. Financial totals remain separated by currency.');
        }
        if ($campaignRowsTruncated) {
            $warnings[] = $this->warning('warning', 'The Campaign table reached the configured bounded row limit. Narrow the filters before treating this overview as exhaustive.');
        }

        return $warnings;
    }

    protected function detailWarnings(array $coverage, array $summary, Collection $snapshots): array
    {
        $warnings = [];
        if (!self::schemaReady()) {
            $warnings[] = $this->warning('danger', 'FBM-07 and FBM-08 migrations are required before performance drilldowns are available.');
        }
        if ($snapshots->isEmpty() && self::schemaReady()) {
            $warnings[] = $this->warning('warning', 'No matching stored snapshot exists for this entity and date range. Run a bounded drilldown refresh or queued full sync.');
        }
        if ($coverage['missing_entity_days'] > 0) {
            $warnings[] = $this->warning('warning', 'Stored snapshot coverage is incomplete for this entity and date range.');
        }
        if (count($summary['currency_groups']) > 1) {
            $warnings[] = $this->warning('warning', 'Multiple snapshot currencies were detected. Financial totals remain separated by currency.');
        }

        return $warnings;
    }

    protected function warning(string $type, string $message): array
    {
        return ['type' => $type, 'message' => $message];
    }

    protected function dateRange(array $filters): array
    {
        $today = CarbonImmutable::today();
        $from = $this->parseDate($filters['from_date'] ?? null) ?: $today->subDays(7);
        $to = $this->parseDate($filters['to_date'] ?? null) ?: $today->subDay();

        return ['from' => $from, 'to' => $to, 'days' => $from->diffInDays($to) + 1];
    }

    protected function rangeSummary(array $range): array
    {
        return [
            'from_date' => $range['from']->toDateString(),
            'to_date' => $range['to']->toDateString(),
            'days' => $range['days'],
        ];
    }

    protected function safeFilters(array $filters, array $range, ?int $requestedAccountId): array
    {
        return [
            'from_date' => $range['from']->toDateString(),
            'to_date' => $range['to']->toDateString(),
            'ad_account_id' => $requestedAccountId,
            'status' => !empty($filters['status']) ? (string) $filters['status'] : null,
            'search' => !empty($filters['search']) ? (string) $filters['search'] : null,
        ];
    }

    protected function requestedAccountId(array $filters): ?int
    {
        return isset($filters['ad_account_id']) && $filters['ad_account_id'] !== ''
            ? (int) $filters['ad_account_id']
            : null;
    }

    protected function parseDate($value): ?CarbonImmutable
    {
        if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return CarbonImmutable::createFromFormat('Y-m-d', $value)->startOfDay();
        } catch (\Throwable $exception) {
            return null;
        }
    }

    protected function snapshotEntityColumn(string $level): string
    {
        return [
            'campaign' => 'fbm_campaign_id',
            'adset' => 'fbm_ad_set_id',
            'ad' => 'fbm_ad_id',
        ][$level] ?? 'fbm_campaign_id';
    }

    protected function drilldownRoute(string $level): string
    {
        return [
            'campaign' => 'fbMarketing.performance.campaigns.show',
            'adset' => 'fbMarketing.performance.ad-sets.show',
            'ad' => 'fbMarketing.performance.ads.show',
        ][$level] ?? 'fbMarketing.performance.index';
    }

    protected function drilldownParameter(string $level): string
    {
        return [
            'campaign' => 'campaign',
            'adset' => 'adSet',
            'ad' => 'ad',
        ][$level] ?? 'campaign';
    }

    protected function currency($value, ?string $fallback = null): string
    {
        if (is_scalar($value)) {
            $value = strtoupper(trim((string) $value));
            if ($value !== '' && preg_match('/^[A-Z0-9_-]{1,20}$/', $value)) {
                return $value;
            }
        }

        return $fallback ?: 'UNSPECIFIED';
    }

    protected function maxDate(?string $current, ?string $candidate): ?string
    {
        if ($candidate === null) {
            return $current;
        }

        return $current === null || $candidate > $current ? $candidate : $current;
    }
}

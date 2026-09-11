<?php

namespace App\Services\FbMarketing;

use App\Models\FbMarketing\FbmAdAccount;
use App\Models\FbMarketing\FbmCampaign;
use App\Models\FbMarketing\FbmInsightDailySnapshot;
use App\Models\FbMarketing\FbmInsightReportRun;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class FbmExecutiveDashboardService
{
    public function build(array $filters): array
    {
        $range = $this->dateRange($filters);
        $compareEnabled = (bool) ($filters['compare_period'] ?? false);
        $comparisonRange = $compareEnabled ? $this->comparisonRange($range) : null;
        $snapshotSchemaReady = FbmInsightsSnapshotService::schemaReady();
        $accountSchemaReady = Schema::hasTable('fbm_ad_accounts');
        $accountOptions = $accountSchemaReady ? $this->selectedAvailableAccounts() : collect();
        $requestedAccountId = isset($filters['ad_account_id']) && $filters['ad_account_id'] !== ''
            ? (int) $filters['ad_account_id']
            : null;
        $accountFilterValid = $requestedAccountId === null || $accountOptions->contains('id', $requestedAccountId);
        $accounts = $requestedAccountId === null
            ? $accountOptions
            : $accountOptions->where('id', $requestedAccountId)->values();
        $warnings = [];

        if (!$snapshotSchemaReady) {
            $warnings[] = $this->warning('danger', 'FBM-08 migration required before executive reporting is available.');
        }
        if (!$accountSchemaReady) {
            $warnings[] = $this->warning('danger', 'FBM-04 migration required before Ad Account filters are available.');
        }
        if (!$accountFilterValid) {
            $warnings[] = $this->warning('warning', 'The requested Ad Account is not selected and available in this application. No reporting data was exposed.');
        }
        if ($accountSchemaReady && $accountOptions->isEmpty()) {
            $warnings[] = $this->warning('warning', 'No selected and available Ad Account exists. Select an Ad Account before running Insights sync.');
        }

        $currentRows = $snapshotSchemaReady ? $this->snapshotRows($accounts, $range) : collect();
        $previousRows = $snapshotSchemaReady && $comparisonRange !== null
            ? $this->snapshotRows($accounts, $comparisonRange)
            : collect();
        $coverage = $this->coverage($currentRows, $accounts, $range);
        $comparisonCoverage = $comparisonRange !== null
            ? $this->coverage($previousRows, $accounts, $comparisonRange)
            : null;
        $previousDataAvailable = $compareEnabled
            && $currentRows->isNotEmpty()
            && $coverage['missing_account_days'] === 0
            && $previousRows->isNotEmpty()
            && $comparisonCoverage !== null
            && $comparisonCoverage['missing_account_days'] === 0;
        $current = $this->aggregate($currentRows, $accounts);
        $previous = $this->aggregate($previousRows, $accounts);
        $currencyGroups = $this->currencyGroupsWithComparisons($current['currency_groups'], $previous['currency_groups'], $previousDataAvailable);
        $delivery = $current['delivery'];
        $delivery['comparisons'] = $this->metricComparisons($current['delivery'], $previous['delivery'], $previousDataAvailable, [
            'impressions',
            'clicks',
            'ctr',
            'summed_daily_reach',
            'meta_purchase_count',
        ]);
        $latestReport = $snapshotSchemaReady ? $this->latestReport($accounts) : null;
        $activeCampaignCount = $this->activeCampaignCount($accounts);
        $mixedCurrency = count($currencyGroups) > 1;

        if ($currentRows->isEmpty() && $snapshotSchemaReady && $accounts->isNotEmpty()) {
            $warnings[] = $this->warning('warning', 'No account-level Insights snapshots are available for the selected date range.');
        }
        if ($coverage['missing_account_days'] > 0 && $accounts->isNotEmpty()) {
            $warnings[] = $this->warning('warning', 'The selected date range is incomplete. Review missing snapshot coverage before treating totals as final.');
        }
        if ($compareEnabled && $comparisonCoverage !== null && $comparisonCoverage['missing_account_days'] > 0 && $accounts->isNotEmpty()) {
            $warnings[] = $this->warning('warning', 'The previous comparison range is incomplete. KPI comparison labels remain unavailable until its selected account-day coverage is complete.');
        }
        if ($mixedCurrency) {
            $warnings[] = $this->warning('warning', 'Selected Ad Accounts use multiple currencies. Financial totals are shown separately by currency.');
        }
        if ($latestReport && in_array($latestReport['status'], [FbmInsightReportRun::STATUS_FAILED, FbmInsightReportRun::STATUS_PARTIAL_SUCCESS], true)) {
            $warnings[] = $this->warning('warning', 'The latest Insights report did not finish cleanly. Review the safe report status before relying on the newest rows.');
        }

        return [
            'schema_ready' => $snapshotSchemaReady,
            'account_schema_ready' => $accountSchemaReady,
            'filters' => [
                'from_date' => $range['from']->toDateString(),
                'to_date' => $range['to']->toDateString(),
                'ad_account_id' => $requestedAccountId,
                'compare_period' => $compareEnabled,
            ],
            'range' => $this->rangeSummary($range),
            'comparison_range' => $comparisonRange ? $this->rangeSummary($comparisonRange) : null,
            'account_filter_valid' => $accountFilterValid,
            'account_options' => $accountOptions->map(fn(FbmAdAccount $account): array => $this->safeAccountSummary($account))->values(),
            'selected_accounts' => $accounts->map(fn(FbmAdAccount $account): array => $this->safeAccountSummary($account))->values(),
            'delivery' => $delivery,
            'currency_groups' => $currencyGroups,
            'mixed_currency' => $mixedCurrency,
            'active_campaign_count' => $activeCampaignCount,
            'coverage' => $coverage,
            'comparison_coverage' => $comparisonCoverage,
            'latest_snapshot_date' => $current['latest_snapshot_date'],
            'latest_fetched_at' => $current['latest_fetched_at'],
            'freshness_watermark' => $current['freshness_watermark'],
            'latest_report' => $latestReport,
            'trend_groups' => $this->trendGroups($current['daily_groups']),
            'daily_rows' => $current['daily_groups']->values(),
            'warnings' => $warnings,
            'has_rows' => $currentRows->isNotEmpty(),
        ];
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

    protected function snapshotRows(Collection $accounts, array $range): Collection
    {
        if ($accounts->isEmpty()) {
            return collect();
        }

        return FbmInsightDailySnapshot::query()
            ->where('insight_level', 'account')
            ->whereIn('fbm_ad_account_id', $accounts->pluck('id')->all())
            ->whereBetween('snapshot_date', [$range['from']->toDateString(), $range['to']->toDateString()])
            ->orderBy('snapshot_date')
            ->orderBy('fbm_ad_account_id')
            ->get([
                'fbm_ad_account_id',
                'snapshot_date',
                'account_currency',
                'spend',
                'impressions',
                'reach',
                'clicks',
                'inline_link_clicks',
                'meta_purchase_count',
                'meta_purchase_value',
                'fetched_at',
                'freshness_watermark',
            ]);
    }

    protected function aggregate(Collection $rows, Collection $accounts): array
    {
        $accountCurrencyFallbacks = $accounts->mapWithKeys(function (FbmAdAccount $account): array {
            return [(int) $account->id => $this->currency($account->currency)];
        });
        $delivery = $this->emptyMetrics();
        $currencyGroups = [];
        $dailyGroups = [];
        $latestSnapshotDate = null;
        $latestFetchedAt = null;
        $freshnessWatermark = null;

        foreach ($rows as $row) {
            $date = optional($row->snapshot_date)->toDateString();
            if ($date === null) {
                continue;
            }

            $currency = $this->currency($row->account_currency, $accountCurrencyFallbacks->get((int) $row->fbm_ad_account_id));
            $metrics = $this->rowMetrics($row);
            $this->addMetrics($delivery, $metrics);

            if (!isset($currencyGroups[$currency])) {
                $currencyGroups[$currency] = $this->emptyMetrics();
                $currencyGroups[$currency]['currency'] = $currency;
            }
            $this->addMetrics($currencyGroups[$currency], $metrics);

            $dailyKey = $date . '|' . $currency;
            if (!isset($dailyGroups[$dailyKey])) {
                $dailyGroups[$dailyKey] = $this->emptyMetrics();
                $dailyGroups[$dailyKey]['date'] = $date;
                $dailyGroups[$dailyKey]['currency'] = $currency;
            }
            $this->addMetrics($dailyGroups[$dailyKey], $metrics);

            $latestSnapshotDate = $this->maxDate($latestSnapshotDate, $date);
            $latestFetchedAt = $this->maxDateTime($latestFetchedAt, optional($row->fetched_at)->toDateTimeString());
            $freshnessWatermark = $this->maxDate($freshnessWatermark, optional($row->freshness_watermark)->toDateString());
        }

        $delivery = $this->deriveMetrics($delivery);
        foreach ($currencyGroups as $currency => $metrics) {
            $currencyGroups[$currency] = $this->deriveMetrics($metrics);
        }
        foreach ($dailyGroups as $key => $metrics) {
            $dailyGroups[$key] = $this->deriveMetrics($metrics);
        }
        ksort($currencyGroups);
        uasort($dailyGroups, fn(array $left, array $right): int => [$left['date'], $left['currency']] <=> [$right['date'], $right['currency']]);

        return [
            'delivery' => $delivery,
            'currency_groups' => $currencyGroups,
            'daily_groups' => collect($dailyGroups),
            'latest_snapshot_date' => $latestSnapshotDate,
            'latest_fetched_at' => $latestFetchedAt,
            'freshness_watermark' => $freshnessWatermark,
        ];
    }

    protected function currencyGroupsWithComparisons(array $currentGroups, array $previousGroups, bool $previousDataAvailable): array
    {
        $groups = [];

        foreach ($currentGroups as $currency => $metrics) {
            $previous = $previousGroups[$currency] ?? $this->emptyMetrics();
            $metrics['comparisons'] = $this->metricComparisons($metrics, $previous, $previousDataAvailable, [
                'spend',
                'cpc',
                'cpm',
                'meta_purchase_value',
            ]);
            $groups[] = $metrics;
        }

        return $groups;
    }

    protected function metricComparisons(array $current, array $previous, bool $previousDataAvailable, array $metricNames): array
    {
        $comparisons = [];
        foreach ($metricNames as $metricName) {
            $comparisons[$metricName] = $this->comparison(
                (float) ($current[$metricName] ?? 0),
                (float) ($previous[$metricName] ?? 0),
                $previousDataAvailable
            );
        }

        return $comparisons;
    }

    protected function comparison(float $current, float $previous, bool $previousDataAvailable): array
    {
        if (!$previousDataAvailable) {
            return ['state' => 'unavailable', 'percent' => null];
        }
        if (abs($previous) < 0.000001) {
            return abs($current) < 0.000001
                ? ['state' => 'no_change', 'percent' => 0.0]
                : ['state' => 'new_activity', 'percent' => null];
        }

        $percent = (($current - $previous) / $previous) * 100;
        if (abs($percent) < 0.005) {
            return ['state' => 'no_change', 'percent' => 0.0];
        }

        return ['state' => $percent > 0 ? 'up' : 'down', 'percent' => round(abs($percent), 1)];
    }

    protected function coverage(Collection $rows, Collection $accounts, array $range): array
    {
        $dateCounts = [];
        $coveredAccountDates = [];

        foreach ($rows as $row) {
            $date = optional($row->snapshot_date)->toDateString();
            if ($date === null) {
                continue;
            }
            $key = (int) $row->fbm_ad_account_id . '|' . $date;
            $coveredAccountDates[$key] = true;
            $dateCounts[$date][(int) $row->fbm_ad_account_id] = true;
        }

        $expectedDays = $range['days'];
        $expectedAccountDays = $expectedDays * $accounts->count();
        $coveredAccountDays = count($coveredAccountDates);
        $missingDates = [];
        $cursor = $range['from'];

        while ($cursor->lte($range['to'])) {
            $date = $cursor->toDateString();
            if (count($dateCounts[$date] ?? []) < $accounts->count()) {
                $missingDates[] = $date;
            }
            $cursor = $cursor->addDay();
        }

        return [
            'expected_days' => $expectedDays,
            'covered_dates' => count($dateCounts),
            'expected_account_days' => $expectedAccountDays,
            'covered_account_days' => $coveredAccountDays,
            'missing_account_days' => max(0, $expectedAccountDays - $coveredAccountDays),
            'coverage_percent' => $expectedAccountDays > 0 ? round(($coveredAccountDays / $expectedAccountDays) * 100, 1) : 0.0,
            'missing_dates' => array_slice($missingDates, 0, 14),
            'hidden_missing_date_count' => max(0, count($missingDates) - 14),
        ];
    }

    protected function trendGroups(Collection $dailyGroups): array
    {
        return $dailyGroups
            ->groupBy('currency')
            ->map(function (Collection $rows, string $currency): array {
                $maxSpend = max(0.0, (float) $rows->max('spend'));
                $maxClicks = max(0.0, (float) $rows->max('clicks'));
                $maxPurchases = max(0.0, (float) $rows->max('meta_purchase_count'));

                return [
                    'currency' => $currency,
                    'rows' => $rows->map(function (array $row) use ($maxSpend, $maxClicks, $maxPurchases): array {
                        $row['spend_bar_percent'] = $this->barPercent((float) $row['spend'], $maxSpend);
                        $row['clicks_bar_percent'] = $this->barPercent((float) $row['clicks'], $maxClicks);
                        $row['purchases_bar_percent'] = $this->barPercent((float) $row['meta_purchase_count'], $maxPurchases);

                        return $row;
                    })->values(),
                ];
            })
            ->values()
            ->all();
    }

    protected function activeCampaignCount(Collection $accounts): int
    {
        if ($accounts->isEmpty() || !Schema::hasTable('fbm_campaigns')) {
            return 0;
        }

        return FbmCampaign::query()
            ->whereIn('fbm_ad_account_id', $accounts->pluck('id')->all())
            ->where('is_available', true)
            ->where('effective_status', 'ACTIVE')
            ->count();
    }

    protected function latestReport(Collection $accounts): ?array
    {
        if ($accounts->isEmpty()) {
            return null;
        }

        $report = FbmInsightReportRun::query()
            ->with(['connection:id,connection_name', 'adAccount:id,asset_name'])
            ->whereIn('fbm_ad_account_id', $accounts->pluck('id')->all())
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        return $report ? $report->toSafeSummary() : null;
    }

    protected function dateRange(array $filters): array
    {
        $today = CarbonImmutable::today();
        $from = $this->parseDate($filters['from_date'] ?? null) ?: $today->subDays(7);
        $to = $this->parseDate($filters['to_date'] ?? null) ?: $today->subDay();

        return [
            'from' => $from,
            'to' => $to,
            'days' => $from->diffInDays($to) + 1,
        ];
    }

    protected function comparisonRange(array $range): array
    {
        $to = $range['from']->subDay();
        $from = $to->subDays($range['days'] - 1);

        return ['from' => $from, 'to' => $to, 'days' => $range['days']];
    }

    protected function rangeSummary(array $range): array
    {
        return [
            'from_date' => $range['from']->toDateString(),
            'to_date' => $range['to']->toDateString(),
            'days' => $range['days'],
        ];
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

    protected function rowMetrics($row): array
    {
        return [
            'spend' => (float) $row->spend,
            'impressions' => (int) $row->impressions,
            'summed_daily_reach' => (int) $row->reach,
            'clicks' => (int) $row->clicks,
            'inline_link_clicks' => (int) $row->inline_link_clicks,
            'meta_purchase_count' => (float) $row->meta_purchase_count,
            'meta_purchase_value' => (float) $row->meta_purchase_value,
        ];
    }

    protected function emptyMetrics(): array
    {
        return [
            'spend' => 0.0,
            'impressions' => 0,
            'summed_daily_reach' => 0,
            'clicks' => 0,
            'inline_link_clicks' => 0,
            'meta_purchase_count' => 0.0,
            'meta_purchase_value' => 0.0,
            'ctr' => 0.0,
            'cpc' => 0.0,
            'cpm' => 0.0,
        ];
    }

    protected function addMetrics(array &$target, array $source): void
    {
        foreach (['spend', 'impressions', 'summed_daily_reach', 'clicks', 'inline_link_clicks', 'meta_purchase_count', 'meta_purchase_value'] as $metric) {
            $target[$metric] += $source[$metric] ?? 0;
        }
    }

    protected function deriveMetrics(array $metrics): array
    {
        $impressions = (float) $metrics['impressions'];
        $clicks = (float) $metrics['clicks'];
        $metrics['ctr'] = $impressions > 0 ? ($clicks / $impressions) * 100 : 0.0;
        $metrics['cpc'] = $clicks > 0 ? ((float) $metrics['spend'] / $clicks) : 0.0;
        $metrics['cpm'] = $impressions > 0 ? (((float) $metrics['spend'] / $impressions) * 1000) : 0.0;

        return $metrics;
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

    protected function warning(string $type, string $message): array
    {
        return ['type' => $type, 'message' => $message];
    }

    protected function maxDate(?string $current, ?string $candidate): ?string
    {
        if ($candidate === null) {
            return $current;
        }

        return $current === null || $candidate > $current ? $candidate : $current;
    }

    protected function maxDateTime(?string $current, ?string $candidate): ?string
    {
        return $this->maxDate($current, $candidate);
    }

    protected function barPercent(float $value, float $max): float
    {
        return $max > 0 ? round(min(100, max(0, ($value / $max) * 100)), 1) : 0.0;
    }
}

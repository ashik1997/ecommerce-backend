<?php

namespace App\Services\FbMarketing;

use App\Models\FbMarketing\FbmAd;
use App\Models\FbMarketing\FbmAdAccount;
use App\Models\FbMarketing\FbmAdSet;
use App\Models\FbMarketing\FbmCampaign;
use App\Models\FbMarketing\FbmInsightDailySnapshot;
use App\Models\FbMarketing\FbmInsightReportRun;
use App\Support\Security\SecretRedactor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FbmInsightsSnapshotService
{
    public static function schemaReady(): bool
    {
        return Schema::hasTable('fbm_insight_report_runs')
            && Schema::hasTable('fbm_insight_daily_snapshots');
    }

    public function operationalSummary(): array
    {
        if (!self::schemaReady()) {
            return [
                'schema_ready' => false,
                'snapshot_count' => 0,
                'report_count' => 0,
                'latest_snapshot_date' => null,
                'freshness_watermark' => null,
                'mode_counts' => [],
                'status_counts' => [],
                'latest_report' => null,
            ];
        }

        $latestReport = FbmInsightReportRun::query()
            ->with(['connection:id,connection_name', 'adAccount:id,asset_name'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        return [
            'schema_ready' => true,
            'snapshot_count' => FbmInsightDailySnapshot::query()->count(),
            'report_count' => FbmInsightReportRun::query()->count(),
            'latest_snapshot_date' => FbmInsightDailySnapshot::query()->max('snapshot_date'),
            'freshness_watermark' => FbmInsightDailySnapshot::query()->max('freshness_watermark'),
            'mode_counts' => $this->countBy('execution_mode'),
            'status_counts' => $this->countBy('status'),
            'latest_report' => $latestReport ? $latestReport->toSafeSummary() : null,
        ];
    }

    public function ingestRows(FbmInsightReportRun $report, FbmAdAccount $account, array $rows): array
    {
        $upserted = 0;
        $skipped = 0;

        DB::transaction(function () use ($report, $account, $rows, &$upserted, &$skipped) {
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    $skipped++;
                    continue;
                }

                $snapshot = $this->normalizeSnapshot($report, $account, $row);
                if ($snapshot === null) {
                    $skipped++;
                    continue;
                }

                FbmInsightDailySnapshot::query()->updateOrCreate(
                    [
                        'fbm_connection_id' => $snapshot['fbm_connection_id'],
                        'fbm_ad_account_id' => $snapshot['fbm_ad_account_id'],
                        'insight_level' => $snapshot['insight_level'],
                        'entity_provider_sync_key' => $snapshot['entity_provider_sync_key'],
                        'snapshot_date' => $snapshot['snapshot_date'],
                    ],
                    $snapshot
                );
                $upserted++;
            }
        });

        return ['upserted_count' => $upserted, 'skipped_count' => $skipped];
    }

    protected function normalizeSnapshot(FbmInsightReportRun $report, FbmAdAccount $account, array $row): ?array
    {
        $level = $this->level((string) $report->insight_level);
        $snapshotDate = $this->date($row['date_start'] ?? null);
        $entityProviderSyncKey = $this->entityProviderSyncKey($account, $level, $row);
        if ($snapshotDate === null || $entityProviderSyncKey === null) {
            return null;
        }

        $actions = $this->actionMetrics($row['actions'] ?? []);
        $actionValues = $this->actionMetrics($row['action_values'] ?? []);
        [$resultCount, $resultValue] = $this->resultPair($actions, $actionValues);
        [$purchaseCount, $purchaseValue] = $this->purchasePair($actions, $actionValues);
        $freshnessWatermark = $this->date(optional($report->window_end)->toDateString()) ?: $snapshotDate;

        $values = [
            'fbm_connection_id' => (int) $report->fbm_connection_id,
            'fbm_ad_account_id' => (int) $account->id,
            'fbm_campaign_id' => $this->localCampaignId($report, $row['campaign_id'] ?? null),
            'fbm_ad_set_id' => $this->localAdSetId($report, $row['adset_id'] ?? null),
            'fbm_ad_id' => $this->localAdId($report, $row['ad_id'] ?? null),
            'insight_level' => $level,
            'entity_provider_sync_key' => $entityProviderSyncKey,
            'snapshot_date' => $snapshotDate,
            'account_currency' => $this->safeString($row['account_currency'] ?? $account->currency, 20),
            'attribution_setting' => $this->safeString($row['attribution_setting'] ?? null, 120),
            'spend' => $this->decimal($row['spend'] ?? 0),
            'impressions' => $this->whole($row['impressions'] ?? 0),
            'reach' => $this->whole($row['reach'] ?? 0),
            'clicks' => $this->whole($row['clicks'] ?? 0),
            'inline_link_clicks' => $this->whole($row['inline_link_clicks'] ?? 0),
            'frequency' => $this->decimal($row['frequency'] ?? 0),
            'ctr' => $this->decimal($row['ctr'] ?? 0),
            'cpc' => $this->decimal($row['cpc'] ?? 0),
            'cpm' => $this->decimal($row['cpm'] ?? 0),
            'meta_result_count' => $resultCount,
            'meta_result_value' => $resultValue,
            'meta_purchase_count' => $purchaseCount,
            'meta_purchase_value' => $purchaseValue,
            'action_metrics' => $actions,
            'action_value_metrics' => $actionValues,
            'source_report_run_id' => (int) $report->id,
            'source_sync_run_id' => $report->fbm_sync_run_id ? (int) $report->fbm_sync_run_id : null,
            'fetched_at' => now(),
            'freshness_watermark' => $freshnessWatermark,
        ];
        $hashValues = $values;
        unset(
            $hashValues['source_report_run_id'],
            $hashValues['source_sync_run_id'],
            $hashValues['fetched_at'],
            $hashValues['freshness_watermark']
        );
        $values['metrics_hash'] = hash('sha256', json_encode($hashValues, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return $values;
    }

    protected function countBy(string $column): array
    {
        return FbmInsightReportRun::query()
            ->selectRaw($column . ', COUNT(*) AS aggregate_count')
            ->groupBy($column)
            ->pluck('aggregate_count', $column)
            ->map(fn($count): int => (int) $count)
            ->all();
    }

    protected function entityProviderSyncKey(FbmAdAccount $account, string $level, array $row): ?string
    {
        if ($level === 'account') {
            return $this->providerId($account->provider_asset_id);
        }

        $field = ['campaign' => 'campaign_id', 'adset' => 'adset_id', 'ad' => 'ad_id'][$level] ?? null;

        return $field ? $this->providerId($row[$field] ?? null) : null;
    }

    protected function localCampaignId(FbmInsightReportRun $report, $providerId): ?int
    {
        $providerId = $this->providerId($providerId);

        return $providerId ? FbmCampaign::query()
            ->where('fbm_connection_id', (int) $report->fbm_connection_id)
            ->where('provider_sync_key', $providerId)
            ->value('id') : null;
    }

    protected function localAdSetId(FbmInsightReportRun $report, $providerId): ?int
    {
        $providerId = $this->providerId($providerId);

        return $providerId ? FbmAdSet::query()
            ->where('fbm_connection_id', (int) $report->fbm_connection_id)
            ->where('provider_sync_key', $providerId)
            ->value('id') : null;
    }

    protected function localAdId(FbmInsightReportRun $report, $providerId): ?int
    {
        $providerId = $this->providerId($providerId);

        return $providerId ? FbmAd::query()
            ->where('fbm_connection_id', (int) $report->fbm_connection_id)
            ->where('provider_sync_key', $providerId)
            ->value('id') : null;
    }

    protected function actionMetrics($rows): array
    {
        if (!is_array($rows)) {
            return [];
        }

        $allowed = array_flip((array) config('fb_marketing.insights.action_types', []));
        $metrics = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $type = $this->safeString($row['action_type'] ?? null, 120);
            if ($type === null || !isset($allowed[$type])) {
                continue;
            }
            $metrics[$type] = $this->decimal($row['value'] ?? 0);
        }
        ksort($metrics);

        return $metrics;
    }

    protected function resultPair(array $actions, array $actionValues): array
    {
        foreach (['purchase', 'omni_purchase', 'offsite_conversion.fb_pixel_purchase', 'lead', 'link_click'] as $type) {
            if (array_key_exists($type, $actions)) {
                return [$actions[$type], $actionValues[$type] ?? '0.000000'];
            }
        }

        return ['0.000000', '0.000000'];
    }

    protected function purchasePair(array $actions, array $actionValues): array
    {
        foreach (['purchase', 'omni_purchase', 'offsite_conversion.fb_pixel_purchase'] as $type) {
            if (array_key_exists($type, $actions)) {
                return [$actions[$type], $actionValues[$type] ?? '0.000000'];
            }
        }

        return ['0.000000', '0.000000'];
    }

    protected function level(string $level): string
    {
        return in_array($level, ['account', 'campaign', 'adset', 'ad'], true) ? $level : 'account';
    }

    protected function providerId($value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }
        $value = trim((string) $value);

        return $value !== '' && strlen($value) <= 190 && preg_match('/^[A-Za-z0-9_.:-]+$/', $value) ? $value : null;
    }

    protected function date($value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }
        $value = trim((string) $value);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }
        [$year, $month, $day] = array_map('intval', explode('-', $value));

        return checkdate($month, $day, $year) ? $value : null;
    }

    protected function decimal($value): string
    {
        if (!is_numeric($value)) {
            return '0.000000';
        }

        return number_format(max(0, (float) $value), 6, '.', '');
    }

    protected function whole($value): int
    {
        return is_numeric($value) ? max(0, (int) $value) : 0;
    }

    protected function safeString($value, int $limit): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }
        $value = trim(SecretRedactor::redactString((string) $value));

        return $value === '' ? null : mb_substr($value, 0, $limit);
    }
}

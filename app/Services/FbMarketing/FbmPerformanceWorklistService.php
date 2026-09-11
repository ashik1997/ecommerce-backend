<?php

namespace App\Services\FbMarketing;

use Carbon\CarbonImmutable;

class FbmPerformanceWorklistService
{
    public function forRows(array $rows, string $entityType): array
    {
        $items = [];
        foreach ($rows as $row) {
            $entityId = (int) ($row['id'] ?? 0);
            if ($entityId <= 0) {
                continue;
            }

            if ((int) ($row['snapshot_count'] ?? 0) === 0) {
                $this->add($items, $row, $entityType, 'missing_snapshots', 'warning', 'No stored performance snapshot exists for the selected range.');
                continue;
            }

            if ((int) ($row['missing_days'] ?? 0) > 0) {
                $this->add($items, $row, $entityType, 'incomplete_coverage', 'warning', 'Stored snapshot coverage is incomplete for the selected range.');
            }

            if ($this->isStale($row['latest_snapshot_date'] ?? null)) {
                $this->add($items, $row, $entityType, 'stale_snapshots', 'warning', 'The latest stored snapshot is stale and should be refreshed.');
            }

            $spend = (float) ($row['spend'] ?? 0);
            $clicks = (int) ($row['clicks'] ?? 0);
            $results = (float) ($row['meta_result_count'] ?? 0);
            $impressions = (int) ($row['impressions'] ?? 0);
            $inlineClicks = (int) ($row['inline_link_clicks'] ?? 0);
            $ctr = (float) ($row['ctr'] ?? 0);

            if ($spend > 0 && $clicks === 0) {
                $this->add($items, $row, $entityType, 'spend_without_clicks', 'danger', 'Spend was recorded but no click was stored.');
            }
            if ($spend > 0 && $results <= 0) {
                $this->add($items, $row, $entityType, 'spend_without_meta_results', 'warning', 'Spend was recorded but Meta-reported results remain zero.');
            }
            if ($impressions > 0 && $inlineClicks === 0) {
                $this->add($items, $row, $entityType, 'impressions_without_inline_link_clicks', 'warning', 'Impressions were recorded but inline-link clicks remain zero.');
            }
            if ($impressions >= $this->minimumImpressions() && $ctr < $this->ctrReviewThreshold()) {
                $this->add($items, $row, $entityType, 'low_ctr_review', 'warning', 'CTR is below the configured review threshold.');
            }
        }

        usort($items, function (array $left, array $right): int {
            $weights = ['danger' => 0, 'warning' => 1, 'info' => 2];

            return [$weights[$left['severity']] ?? 9, strtolower($left['entity_name'])]
                <=> [$weights[$right['severity']] ?? 9, strtolower($right['entity_name'])];
        });

        return [
            'items' => array_slice($items, 0, $this->maxItems()),
            'total_count' => count($items),
            'hidden_count' => max(0, count($items) - $this->maxItems()),
            'ctr_review_threshold_percent' => $this->ctrReviewThreshold(),
            'stale_after_days' => $this->staleAfterDays(),
            'minimum_impressions_for_ctr_review' => $this->minimumImpressions(),
        ];
    }

    protected function add(array &$items, array $row, string $entityType, string $indicator, string $severity, string $message): void
    {
        $items[] = [
            'entity_type' => $entityType,
            'entity_id' => (int) $row['id'],
            'entity_name' => (string) ($row['name'] ?? 'Unnamed entity'),
            'ad_account_name' => (string) ($row['ad_account_name'] ?? 'Unnamed Ad Account'),
            'currency' => (string) ($row['currency'] ?? 'UNSPECIFIED'),
            'indicator' => $indicator,
            'severity' => $severity,
            'message' => $message,
            'drilldown_route' => (string) ($row['drilldown_route'] ?? 'fbMarketing.performance.index'),
            'drilldown_parameter' => (string) ($row['drilldown_parameter'] ?? 'campaign'),
        ];
    }

    protected function isStale(?string $snapshotDate): bool
    {
        if (!$snapshotDate || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $snapshotDate)) {
            return true;
        }

        try {
            return CarbonImmutable::createFromFormat('Y-m-d', $snapshotDate)
                ->lt(CarbonImmutable::today()->subDays($this->staleAfterDays()));
        } catch (\Throwable $exception) {
            return true;
        }
    }

    protected function ctrReviewThreshold(): float
    {
        return max(0.0, min(100.0, (float) config('fb_marketing.performance_worklists.ctr_review_threshold_percent', 0.5)));
    }

    protected function staleAfterDays(): int
    {
        return max(1, min(30, (int) config('fb_marketing.performance_worklists.stale_after_days', 2)));
    }

    protected function minimumImpressions(): int
    {
        return max(1, min(1000000, (int) config('fb_marketing.performance_worklists.minimum_impressions_for_ctr_review', 100)));
    }

    protected function maxItems(): int
    {
        return max(1, min(500, (int) config('fb_marketing.performance_worklists.max_items', 100)));
    }
}

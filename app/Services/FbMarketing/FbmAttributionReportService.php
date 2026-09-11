<?php

namespace App\Services\FbMarketing;

use App\Models\FbMarketing\FbmAd;
use App\Models\FbMarketing\FbmAdSet;
use App\Models\FbMarketing\FbmAttributionReconciliation;
use App\Models\FbMarketing\FbmCampaign;
use App\Models\FbMarketing\FbmInsightDailySnapshot;
use App\Models\FbMarketing\FbmOrderAttribution;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Throwable;

class FbmAttributionReportService
{
    public function build(array $filters): array
    {
        $range = $this->dateRange($filters);
        $schemaReady = $this->schemaReady();
        $campaignOptions = Schema::hasTable('fbm_campaigns') ? $this->campaignOptions() : collect();
        $adSetOptions = Schema::hasTable('fbm_ad_sets') ? $this->adSetOptions($filters) : collect();
        $adOptions = Schema::hasTable('fbm_ads') ? $this->adOptions($filters) : collect();
        $safeFilters = $this->safeFilters($filters, $range, $campaignOptions, $adSetOptions, $adOptions);
        $warnings = [];

        if (!$schemaReady) {
            $warnings[] = $this->warning('danger', 'FBM-07, FBM-08 and FBM-15 migrations are required before attribution reports are available.');
        }
        foreach (['campaign_id' => 'Campaign', 'ad_set_id' => 'Ad Set', 'ad_id' => 'Ad'] as $key => $label) {
            if (!empty($filters[$key]) && $safeFilters[$key] === null) {
                $warnings[] = $this->warning('warning', "The requested {$label} is not available in this application. No unrelated reporting data was exposed.");
            }
        }

        $snapshots = $schemaReady ? $this->snapshotRows($safeFilters, $range) : collect();
        $attributions = $schemaReady ? $this->attributionRows($safeFilters, $range) : collect();
        $attributionRows = $this->projectAttributions($attributions, $safeFilters);
        $performanceRows = $this->performanceRows($snapshots, $attributionRows);
        $summary = $this->summary($snapshots, $attributionRows);
        $reconciliation = $this->reconciliationSummary($attributionRows, $range);
        $freshness = $this->freshness($snapshots, $attributions);

        if ($schemaReady && $snapshots->isEmpty()) {
            $warnings[] = $this->warning('warning', 'No stored ad-level Insights snapshot matched this date range. Spend is local-only and may show as zero until FBM sync runs.');
        }
        if ($schemaReady && $attributionRows->isEmpty()) {
            $warnings[] = $this->warning('warning', 'No ERP order attribution snapshot matched this date range and filters.');
        }

        return [
            'schema_ready' => $schemaReady,
            'filters' => $safeFilters,
            'range' => ['from_date' => $range['from']->toDateString(), 'to_date' => $range['to']->toDateString(), 'days' => $range['days']],
            'campaign_options' => $campaignOptions->values(),
            'ad_set_options' => $adSetOptions->values(),
            'ad_options' => $adOptions->values(),
            'method_options' => $this->methodOptions(),
            'evidence_options' => $this->evidenceOptions(),
            'summary' => $summary,
            'reconciliation' => $reconciliation,
            'performance_rows' => $performanceRows,
            'method_breakdown' => $this->breakdown($attributionRows, 'attribution_method'),
            'status_breakdown' => $this->breakdown($attributionRows, 'evidence_state'),
            'freshness' => $freshness,
            'warnings' => $warnings,
        ];
    }

    private function schemaReady(): bool
    {
        foreach (['fbm_order_attributions', 'fbm_attribution_reconciliations', 'fbm_insight_daily_snapshots', 'fbm_campaigns', 'fbm_ad_sets', 'fbm_ads'] as $table) {
            if (!Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }

    private function snapshotRows(array $filters, array $range): Collection
    {
        $query = FbmInsightDailySnapshot::query()
            ->with(['campaign:id,name', 'adSet:id,name,fbm_campaign_id', 'ad:id,name,fbm_campaign_id,fbm_ad_set_id'])
            ->where('insight_level', 'ad')
            ->whereBetween('snapshot_date', [$range['from']->toDateString(), $range['to']->toDateString()]);

        foreach (['campaign_id' => 'fbm_campaign_id', 'ad_set_id' => 'fbm_ad_set_id', 'ad_id' => 'fbm_ad_id'] as $filter => $column) {
            if ($filters[$filter] !== null) {
                $query->where($column, (int) $filters[$filter]);
            }
        }

        return $query->orderBy('snapshot_date')->limit($this->maxRows())->get();
    }

    private function attributionRows(array $filters, array $range): Collection
    {
        $query = FbmOrderAttribution::query()
            ->with('reconciliations')
            ->whereBetween('snapshot_created_at', [$range['from']->startOfDay()->toDateTimeString(), $range['to']->endOfDay()->toDateTimeString()])
            ->orderByDesc('snapshot_created_at')
            ->limit($this->maxRows());

        if ($filters['evidence_state'] !== null) {
            $query->where('evidence_state', $filters['evidence_state']);
        }

        return $query->get();
    }

    private function projectAttributions(Collection $attributions, array $filters): Collection
    {
        $campaigns = FbmCampaign::query()->get(['id', 'name']);
        $adSets = FbmAdSet::query()->get(['id', 'name', 'fbm_campaign_id']);
        $ads = FbmAd::query()->get(['id', 'name', 'fbm_campaign_id', 'fbm_ad_set_id']);
        $campaignIndex = $this->entityIndex($campaigns);
        $adSetIndex = $this->entityIndex($adSets);
        $adIndex = $this->entityIndex($ads);
        $adSetParents = $adSets->mapWithKeys(fn(FbmAdSet $adSet): array => [(int) $adSet->id => $adSet->fbm_campaign_id ? (int) $adSet->fbm_campaign_id : null]);
        $adParents = $ads->mapWithKeys(fn(FbmAd $ad): array => [(int) $ad->id => [
            'campaign_id' => $ad->fbm_campaign_id ? (int) $ad->fbm_campaign_id : null,
            'ad_set_id' => $ad->fbm_ad_set_id ? (int) $ad->fbm_ad_set_id : null,
        ]]);
        $rows = collect();

        foreach ($attributions as $attribution) {
            $evidence = $this->decodeEvidence($attribution);
            $method = $this->attributionMethod((string) $attribution->evidence_state, $evidence);
            if ($filters['attribution_method'] !== null && $method !== $filters['attribution_method']) {
                continue;
            }

            $campaignId = $this->matchEntity($campaignIndex, [$evidence['latest_utm_campaign'] ?? null, $evidence['first_utm_campaign'] ?? null, $evidence['latest_utm_id'] ?? null, $evidence['first_utm_id'] ?? null]);
            $adSetId = $this->matchEntity($adSetIndex, [$evidence['latest_utm_content'] ?? null, $evidence['first_utm_content'] ?? null]);
            $adId = $this->matchEntity($adIndex, [$evidence['latest_utm_term'] ?? null, $evidence['first_utm_term'] ?? null, $evidence['latest_utm_content'] ?? null, $evidence['first_utm_content'] ?? null]);
            if ($adId !== null) {
                $parents = $adParents->get((int) $adId, []);
                $adSetId = $adSetId ?: ($parents['ad_set_id'] ?? null);
                $campaignId = $campaignId ?: ($parents['campaign_id'] ?? null);
            }
            if ($adSetId !== null) {
                $campaignId = $campaignId ?: $adSetParents->get((int) $adSetId);
            }

            if ($filters['campaign_id'] !== null && (int) $filters['campaign_id'] !== (int) $campaignId) {
                continue;
            }
            if ($filters['ad_set_id'] !== null && (int) $filters['ad_set_id'] !== (int) $adSetId) {
                continue;
            }
            if ($filters['ad_id'] !== null && (int) $filters['ad_id'] !== (int) $adId) {
                continue;
            }

            $revenue = (float) $attribution->order_total_snapshot
                + (float) $attribution->reconciliations->where('event_type', 'amount_adjusted')->sum('amount_delta');

            $rows->push([
                'id' => (int) $attribution->id,
                'campaign_id' => $campaignId,
                'ad_set_id' => $adSetId,
                'ad_id' => $adId,
                'currency' => $this->currency($attribution->currency),
                'evidence_state' => (string) $attribution->evidence_state,
                'attribution_method' => $method,
                'lifecycle_state' => (string) $attribution->lifecycle_state_current,
                'revenue' => round(max(0, $revenue), 2),
                'is_attributed_sale' => (string) $attribution->evidence_state === FbmOrderAttribution::EVIDENCE_ATTRIBUTED
                    && (string) $attribution->lifecycle_state_current === FbmOrderLifecycleClassifier::STATE_CONFIRMED,
                'snapshot_created_at' => optional($attribution->snapshot_created_at)->toDateTimeString(),
            ]);
        }

        return $rows;
    }

    private function performanceRows(Collection $snapshots, Collection $attributions): array
    {
        $groups = [];

        foreach ($snapshots as $snapshot) {
            $key = (int) $snapshot->fbm_campaign_id . '|' . (int) $snapshot->fbm_ad_set_id . '|' . (int) $snapshot->fbm_ad_id . '|' . $this->currency($snapshot->account_currency);
            if (!isset($groups[$key])) {
                $groups[$key] = $this->emptyPerformanceRow($snapshot);
            }
            $groups[$key]['spend'] += (float) $snapshot->spend;
            $groups[$key]['latest_snapshot_date'] = $this->maxDate($groups[$key]['latest_snapshot_date'], optional($snapshot->snapshot_date)->toDateString());
        }

        foreach ($attributions->where('is_attributed_sale', true) as $row) {
            $key = (int) $row['campaign_id'] . '|' . (int) $row['ad_set_id'] . '|' . (int) $row['ad_id'] . '|' . $this->currency($row['currency']);
            if (!isset($groups[$key])) {
                $groups[$key] = $this->unmatchedPerformanceRow($row);
            }
            $groups[$key]['attributed_orders']++;
            $groups[$key]['attributed_revenue'] += (float) $row['revenue'];
        }

        foreach ($groups as $key => $row) {
            $groups[$key] = $this->deriveMoneyMetrics($row);
        }

        usort($groups, fn(array $left, array $right): int => [$right['attributed_revenue'], $right['spend']] <=> [$left['attributed_revenue'], $left['spend']]);

        return array_slice($groups, 0, 250);
    }

    private function summary(Collection $snapshots, Collection $attributions): array
    {
        $firstAttribution = $attributions->first();
        $firstSnapshot = $snapshots->first();
        $row = [
            'currency' => $this->currency($firstAttribution['currency'] ?? optional($firstSnapshot)->account_currency ?? 'BDT'),
            'spend' => round($snapshots->sum('spend'), 2),
            'attributed_orders' => $attributions->where('is_attributed_sale', true)->count(),
            'attributed_revenue' => round($attributions->where('is_attributed_sale', true)->sum('revenue'), 2),
        ];

        return $this->deriveMoneyMetrics($row);
    }

    private function reconciliationSummary(Collection $attributions, array $range): array
    {
        $lastReconciliation = Schema::hasTable('fbm_attribution_reconciliations')
            ? FbmAttributionReconciliation::query()->orderByDesc('created_at')->first(['created_at'])
            : null;

        return [
            'total_considered_orders' => $attributions->count(),
            'attributed_orders' => $attributions->where('evidence_state', FbmOrderAttribution::EVIDENCE_ATTRIBUTED)->count(),
            'unattributed_orders' => $attributions->whereIn('evidence_state', [
                FbmOrderAttribution::EVIDENCE_MISSING_SESSION,
                FbmOrderAttribution::EVIDENCE_INVALID_SESSION,
                FbmOrderAttribution::EVIDENCE_EXPIRED_SESSION,
            ])->count(),
            'direct_or_organic_orders' => $attributions->where('attribution_method', 'unresolved')->count(),
            'pending_or_unresolved_orders' => $attributions->filter(fn(array $row): bool => in_array($row['lifecycle_state'], [FbmOrderLifecycleClassifier::STATE_PENDING, FbmOrderLifecycleClassifier::STATE_UNKNOWN], true))->count(),
            'last_reconciliation_at' => optional(optional($lastReconciliation)->created_at)->toDateTimeString(),
            'range_from' => $range['from']->toDateString(),
            'range_to' => $range['to']->toDateString(),
        ];
    }

    private function freshness(Collection $snapshots, Collection $attributions): array
    {
        return [
            'latest_snapshot_date' => $snapshots->map(fn($row) => optional($row->snapshot_date)->toDateString())->filter()->max(),
            'latest_fetched_at' => $snapshots->map(fn($row) => optional($row->fetched_at)->toDateTimeString())->filter()->max(),
            'freshness_watermark' => $snapshots->map(fn($row) => optional($row->freshness_watermark)->toDateString())->filter()->max(),
            'latest_order_snapshot_at' => $attributions->map(fn($row) => optional($row->snapshot_created_at)->toDateTimeString())->filter()->max(),
        ];
    }

    private function breakdown(Collection $rows, string $key): array
    {
        return $rows->groupBy($key)->map(function (Collection $group, string $value): array {
            return [
                'label' => $this->label($value),
                'count' => $group->count(),
                'revenue' => round($group->where('is_attributed_sale', true)->sum('revenue'), 2),
            ];
        })->values()->all();
    }

    private function campaignOptions(): Collection
    {
        return FbmCampaign::query()->where('is_available', true)->orderBy('name')->limit(500)->get(['id', 'name'])
            ->map(fn(FbmCampaign $campaign): array => ['id' => (int) $campaign->id, 'name' => $campaign->name ?: 'Unnamed Campaign']);
    }

    private function adSetOptions(array $filters): Collection
    {
        $query = FbmAdSet::query()->where('is_available', true);
        if (!empty($filters['campaign_id'])) {
            $query->where('fbm_campaign_id', (int) $filters['campaign_id']);
        }

        return $query->orderBy('name')->limit(500)->get(['id', 'name'])
            ->map(fn(FbmAdSet $adSet): array => ['id' => (int) $adSet->id, 'name' => $adSet->name ?: 'Unnamed Ad Set']);
    }

    private function adOptions(array $filters): Collection
    {
        $query = FbmAd::query()->where('is_available', true);
        if (!empty($filters['campaign_id'])) {
            $query->where('fbm_campaign_id', (int) $filters['campaign_id']);
        }
        if (!empty($filters['ad_set_id'])) {
            $query->where('fbm_ad_set_id', (int) $filters['ad_set_id']);
        }

        return $query->orderBy('name')->limit(500)->get(['id', 'name'])
            ->map(fn(FbmAd $ad): array => ['id' => (int) $ad->id, 'name' => $ad->name ?: 'Unnamed Ad']);
    }

    private function safeFilters(array $filters, array $range, Collection $campaigns, Collection $adSets, Collection $ads): array
    {
        return [
            'from_date' => $range['from']->toDateString(),
            'to_date' => $range['to']->toDateString(),
            'campaign_id' => $this->validId($filters['campaign_id'] ?? null, $campaigns),
            'ad_set_id' => $this->validId($filters['ad_set_id'] ?? null, $adSets),
            'ad_id' => $this->validId($filters['ad_id'] ?? null, $ads),
            'evidence_state' => in_array($filters['evidence_state'] ?? null, array_keys($this->evidenceOptions()), true) ? $filters['evidence_state'] : null,
            'attribution_method' => in_array($filters['attribution_method'] ?? null, array_keys($this->methodOptions()), true) ? $filters['attribution_method'] : null,
        ];
    }

    private function validId($value, Collection $options): ?int
    {
        $id = (int) $value;

        return $id > 0 && $options->contains('id', $id) ? $id : null;
    }

    private function deriveMoneyMetrics(array $row): array
    {
        $spend = round((float) ($row['spend'] ?? 0), 2);
        $orders = (int) ($row['attributed_orders'] ?? 0);
        $revenue = round((float) ($row['attributed_revenue'] ?? 0), 2);
        $row['spend'] = $spend;
        $row['attributed_orders'] = $orders;
        $row['attributed_revenue'] = $revenue;
        $row['average_order_value'] = $orders > 0 ? round($revenue / $orders, 2) : 0.0;
        $row['cost_per_attributed_order'] = $orders > 0 ? round($spend / $orders, 2) : 0.0;
        $row['roas'] = $spend > 0 ? round($revenue / $spend, 4) : 0.0;
        $row['revenue_minus_spend'] = round($revenue - $spend, 2);

        return $row;
    }

    private function emptyPerformanceRow($snapshot): array
    {
        return [
            'campaign_id' => (int) $snapshot->fbm_campaign_id,
            'ad_set_id' => (int) $snapshot->fbm_ad_set_id,
            'ad_id' => (int) $snapshot->fbm_ad_id,
            'campaign_name' => optional($snapshot->campaign)->name ?: 'Unmapped Campaign',
            'ad_set_name' => optional($snapshot->adSet)->name ?: 'Unmapped Ad Set',
            'ad_name' => optional($snapshot->ad)->name ?: 'Unmapped Ad',
            'currency' => $this->currency($snapshot->account_currency),
            'spend' => 0.0,
            'attributed_orders' => 0,
            'attributed_revenue' => 0.0,
            'latest_snapshot_date' => null,
        ];
    }

    private function unmatchedPerformanceRow(array $row): array
    {
        return [
            'campaign_id' => $row['campaign_id'],
            'ad_set_id' => $row['ad_set_id'],
            'ad_id' => $row['ad_id'],
            'campaign_name' => $row['campaign_id'] ? 'Matched Campaign #' . $row['campaign_id'] : 'Unmatched attribution',
            'ad_set_name' => $row['ad_set_id'] ? 'Matched Ad Set #' . $row['ad_set_id'] : 'Unmatched attribution',
            'ad_name' => $row['ad_id'] ? 'Matched Ad #' . $row['ad_id'] : 'Unmatched attribution',
            'currency' => $row['currency'],
            'spend' => 0.0,
            'attributed_orders' => 0,
            'attributed_revenue' => 0.0,
            'latest_snapshot_date' => null,
        ];
    }

    private function decodeEvidence(FbmOrderAttribution $attribution): array
    {
        try {
            $json = (string) $attribution->evidence_snapshot_ciphertext;
            $data = json_decode($json, true);

            return is_array($data) ? $data : [];
        } catch (Throwable $exception) {
            return [];
        }
    }

    private function attributionMethod(string $evidenceState, array $evidence): string
    {
        if ($evidenceState !== FbmOrderAttribution::EVIDENCE_ATTRIBUTED) {
            return 'unresolved';
        }
        if (!empty($evidence['fbc']) || !empty($evidence['fbclid'])) {
            return 'fbclid_based';
        }
        foreach (['first_utm_campaign', 'latest_utm_campaign', 'first_utm_id', 'latest_utm_id', 'first_utm_content', 'latest_utm_content'] as $key) {
            if (!empty($evidence[$key])) {
                return 'campaign_parameter_based';
            }
        }

        return 'landing_click_based';
    }

    private function entityIndex(Collection $entities): array
    {
        $index = [];
        foreach ($entities as $entity) {
            $index[(string) $entity->id] = (int) $entity->id;
            $normalized = $this->normalizeName($entity->name);
            if ($normalized !== '') {
                $index[$normalized] = (int) $entity->id;
            }
        }

        return $index;
    }

    private function matchEntity(array $index, array $candidates): ?int
    {
        foreach ($candidates as $candidate) {
            if (!is_scalar($candidate)) {
                continue;
            }
            $value = trim((string) $candidate);
            if ($value !== '' && isset($index[$value])) {
                return $index[$value];
            }
            $normalized = $this->normalizeName($value);
            if ($normalized !== '' && isset($index[$normalized])) {
                return $index[$normalized];
            }
        }

        return null;
    }

    private function methodOptions(): array
    {
        return [
            'landing_click_based' => 'Landing click based',
            'fbclid_based' => 'fbclid based',
            'campaign_parameter_based' => 'Campaign parameter based',
            'unresolved' => 'Unresolved',
        ];
    }

    private function evidenceOptions(): array
    {
        return [
            FbmOrderAttribution::EVIDENCE_ATTRIBUTED => 'Attributed',
            FbmOrderAttribution::EVIDENCE_MISSING_SESSION => 'Missing session',
            FbmOrderAttribution::EVIDENCE_INVALID_SESSION => 'Invalid session',
            FbmOrderAttribution::EVIDENCE_EXPIRED_SESSION => 'Expired session',
        ];
    }

    private function dateRange(array $filters): array
    {
        $today = CarbonImmutable::today();
        $from = $this->parseDate($filters['from_date'] ?? null) ?: $today->subDays(30);
        $to = $this->parseDate($filters['to_date'] ?? null) ?: $today;

        return ['from' => $from, 'to' => $to, 'days' => $from->diffInDays($to) + 1];
    }

    private function parseDate($value): ?CarbonImmutable
    {
        if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return CarbonImmutable::createFromFormat('Y-m-d', $value)->startOfDay();
        } catch (Throwable $exception) {
            return null;
        }
    }

    private function maxRows(): int
    {
        return max(100, min(5000, (int) config('fb_marketing.attribution_reports.max_rows', 1000)));
    }

    private function currency($value): string
    {
        $value = is_scalar($value) ? strtoupper(trim((string) $value)) : '';

        return $value !== '' && preg_match('/^[A-Z0-9_-]{1,20}$/', $value) ? $value : 'BDT';
    }

    private function normalizeName($value): string
    {
        $value = strtolower(trim((string) $value));
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? '';

        return trim(preg_replace('/\s+/', ' ', $value) ?? '');
    }

    private function label(string $value): string
    {
        return ucwords(str_replace('_', ' ', $value));
    }

    private function maxDate(?string $current, ?string $candidate): ?string
    {
        return $candidate !== null && ($current === null || $candidate > $current) ? $candidate : $current;
    }

    private function warning(string $type, string $message): array
    {
        return ['type' => $type, 'message' => $message];
    }
}

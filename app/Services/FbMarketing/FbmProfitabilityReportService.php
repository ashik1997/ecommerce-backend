<?php

namespace App\Services\FbMarketing;

use App\Models\FbMarketing\FbmAd;
use App\Models\FbMarketing\FbmAdSet;
use App\Models\FbMarketing\FbmCampaign;
use App\Models\FbMarketing\FbmCampaignCostAdjustment;
use App\Models\FbMarketing\FbmInsightDailySnapshot;
use App\Models\FbMarketing\FbmOrderAttribution;
use App\Models\FbMarketing\FbmOrderAttributionItem;
use App\Models\Product;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class FbmProfitabilityReportService
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
            $warnings[] = $this->warning('danger', 'FBM-08 Insights snapshots, FBM-15 attribution items, ERP products and FBM-18 cost adjustments are required before profitability reporting is available.');
        }
        foreach (['campaign_id' => 'Campaign', 'ad_set_id' => 'Ad Set', 'ad_id' => 'Ad'] as $key => $label) {
            if (!empty($filters[$key]) && $safeFilters[$key] === null) {
                $warnings[] = $this->warning('warning', "The requested {$label} is not available in this application. No unrelated profitability data was exposed.");
            }
        }

        $snapshots = $schemaReady ? $this->snapshotRows($safeFilters, $range) : collect();
        $attributions = $schemaReady ? $this->attributionRows($range) : collect();
        $items = $schemaReady ? $attributions->flatMap(fn(FbmOrderAttribution $attribution): Collection => $attribution->items) : collect();
        $sourceItemCosts = $schemaReady ? $this->sourceItemCosts($attributions) : collect();
        $products = $schemaReady ? $this->productsForItems($items) : collect();
        $salesRows = $schemaReady ? $this->projectAttributions($attributions, $safeFilters, $sourceItemCosts, $products) : collect();
        $adjustments = $schemaReady ? $this->adjustmentRows($safeFilters, $range) : collect();
        $rows = $this->profitabilityRows($snapshots, $salesRows, $adjustments);
        $rows = $this->applyDerivedFilters($rows, $safeFilters);

        if ($schemaReady && $snapshots->isEmpty()) {
            $warnings[] = $this->warning('warning', 'No stored ad-level Insights snapshot matched this date range. Meta ad spend is local-only and may show as zero until FBM sync runs.');
        }
        if ($schemaReady && $salesRows->isEmpty()) {
            $warnings[] = $this->warning('warning', 'No confirmed attributed ERP sale matched this date range and filters.');
        }
        if ($schemaReady && $adjustments->isEmpty()) {
            $warnings[] = $this->warning('info', 'No approved local campaign cost adjustment matched this date range. Profitability currently subtracts Meta ad spend only.');
        }
        if ($rows->where('missing_cost_count', '>', 0)->isNotEmpty()) {
            $warnings[] = $this->warning('warning', 'Some attributed sale rows have missing product cost. Contribution profit uses zero for those missing costs until ERP cost data is completed.');
        }

        return [
            'schema_ready' => $schemaReady,
            'filters' => $safeFilters,
            'range' => ['from_date' => $range['from']->toDateString(), 'to_date' => $range['to']->toDateString(), 'days' => $range['days']],
            'campaign_options' => $campaignOptions->values(),
            'ad_set_options' => $adSetOptions->values(),
            'ad_options' => $adOptions->values(),
            'profit_state_options' => $this->profitStateOptions(),
            'cost_type_options' => $this->costTypeOptions(),
            'summary' => $this->summary($rows),
            'rows' => $rows->values()->all(),
            'adjustments' => $this->safeAdjustmentRows($adjustments),
            'profit_breakdown' => $this->profitBreakdown($rows),
            'freshness' => $this->freshness($snapshots, $attributions, $adjustments),
            'warnings' => $warnings,
        ];
    }

    public function storeAdjustment(array $input, ?int $userId): void
    {
        if (!Schema::hasTable('fbm_campaign_cost_adjustments')) {
            return;
        }

        $base = round(max(0, (float) ($input['base_amount'] ?? 0)), 2);
        $vat = round(max(0, (float) ($input['vat_amount'] ?? 0)), 2);
        $tax = round(max(0, (float) ($input['tax_amount'] ?? 0)), 2);
        $serviceCharge = round(max(0, (float) ($input['service_charge_amount'] ?? 0)), 2);

        FbmCampaignCostAdjustment::query()->create([
            'adjustment_uuid' => (string) Str::uuid(),
            'effective_date' => $input['effective_date'],
            'fbm_campaign_id' => $this->existingId('fbm_campaigns', $input['campaign_id'] ?? null),
            'fbm_ad_set_id' => $this->existingId('fbm_ad_sets', $input['ad_set_id'] ?? null),
            'fbm_ad_id' => $this->existingId('fbm_ads', $input['ad_id'] ?? null),
            'currency' => $this->currency($input['currency'] ?? 'BDT'),
            'cost_type' => $this->safeCode($input['cost_type'] ?? 'other', 'other'),
            'label' => $this->safeString($input['label'] ?? 'Local cost adjustment', 160) ?: 'Local cost adjustment',
            'base_amount' => $base,
            'vat_amount' => $vat,
            'tax_amount' => $tax,
            'service_charge_amount' => $serviceCharge,
            'total_amount' => round($base + $vat + $tax + $serviceCharge, 2),
            'status' => in_array($input['status'] ?? 'approved', ['approved', 'pending', 'void'], true) ? $input['status'] : 'approved',
            'safe_note' => $this->safeString($input['safe_note'] ?? null, 500),
            'created_by' => $userId,
        ]);
    }

    private function schemaReady(): bool
    {
        foreach (['fbm_order_attributions', 'fbm_order_attribution_items', 'fbm_insight_daily_snapshots', 'fbm_campaigns', 'fbm_ad_sets', 'fbm_ads', 'products', 'fbm_campaign_cost_adjustments'] as $table) {
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

    private function attributionRows(array $range): Collection
    {
        return FbmOrderAttribution::query()
            ->with(['items', 'reconciliations'])
            ->where('evidence_state', FbmOrderAttribution::EVIDENCE_ATTRIBUTED)
            ->whereBetween('snapshot_created_at', [
                $range['from']->startOfDay()->toDateTimeString(),
                $range['to']->endOfDay()->toDateTimeString(),
            ])
            ->orderByDesc('snapshot_created_at')
            ->limit($this->maxRows())
            ->get();
    }

    private function projectAttributions(Collection $attributions, array $filters, Collection $sourceItemCosts, Collection $products): Collection
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
            if ((string) $attribution->lifecycle_state_current !== FbmOrderLifecycleClassifier::STATE_CONFIRMED) {
                continue;
            }

            $evidence = $this->decodeEvidence($attribution);
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
            if (!$this->matchesEntityFilters($filters, $campaignId, $adSetId, $adId)) {
                continue;
            }

            $purchaseCost = 0.0;
            $missingCostCount = 0;
            foreach ($attribution->items as $item) {
                $item->setRelation('attribution', $attribution);
                $cost = $this->lineCost($item, $products->get((int) $item->product_id), $sourceItemCosts);
                $purchaseCost += $cost['amount'];
                if ($cost['source'] === 'missing') {
                    $missingCostCount++;
                }
            }

            $revenue = (float) $attribution->order_total_snapshot
                + (float) $attribution->reconciliations->where('event_type', 'amount_adjusted')->sum('amount_delta');

            $rows->push([
                'campaign_id' => $campaignId,
                'ad_set_id' => $adSetId,
                'ad_id' => $adId,
                'currency' => $this->currency($attribution->currency),
                'attributed_orders' => 1,
                'attributed_revenue' => round(max(0, $revenue), 2),
                'purchase_cost' => round(max(0, $purchaseCost), 2),
                'missing_cost_count' => $missingCostCount,
            ]);
        }

        return $rows;
    }

    private function profitabilityRows(Collection $snapshots, Collection $salesRows, Collection $adjustments): Collection
    {
        $groups = [];

        foreach ($snapshots as $snapshot) {
            $key = $this->groupKey((int) $snapshot->fbm_campaign_id, (int) $snapshot->fbm_ad_set_id, (int) $snapshot->fbm_ad_id, $this->currency($snapshot->account_currency));
            if (!isset($groups[$key])) {
                $groups[$key] = $this->emptyRowFromSnapshot($snapshot);
            }
            $groups[$key]['meta_spend'] += (float) $snapshot->spend;
            $groups[$key]['latest_snapshot_date'] = $this->maxDate($groups[$key]['latest_snapshot_date'], optional($snapshot->snapshot_date)->toDateString());
        }

        foreach ($salesRows as $sale) {
            $key = $this->groupKey($sale['campaign_id'], $sale['ad_set_id'], $sale['ad_id'], $sale['currency']);
            if (!isset($groups[$key])) {
                $groups[$key] = $this->emptyRowFromSale($sale);
            }
            $groups[$key]['attributed_orders'] += (int) $sale['attributed_orders'];
            $groups[$key]['attributed_revenue'] += (float) $sale['attributed_revenue'];
            $groups[$key]['purchase_cost'] += (float) $sale['purchase_cost'];
            $groups[$key]['missing_cost_count'] += (int) $sale['missing_cost_count'];
        }

        foreach ($adjustments as $adjustment) {
            $key = $this->groupKey($adjustment->fbm_campaign_id, $adjustment->fbm_ad_set_id, $adjustment->fbm_ad_id, $this->currency($adjustment->currency));
            if (!isset($groups[$key])) {
                $groups[$key] = $this->emptyRowFromAdjustment($adjustment);
            }
            $groups[$key]['local_cost_adjustments'] += (float) $adjustment->total_amount;
            $groups[$key]['vat_tax_service_total'] += (float) $adjustment->vat_amount + (float) $adjustment->tax_amount + (float) $adjustment->service_charge_amount;
            $groups[$key]['adjustment_count']++;
        }

        $rows = collect(array_values($groups))->map(fn(array $row): array => $this->deriveRow($row));

        return $rows->sortByDesc('ad_adjusted_contribution_profit')->take(250)->values();
    }

    private function deriveRow(array $row): array
    {
        $row['meta_spend'] = round($row['meta_spend'], 2);
        $row['attributed_revenue'] = round($row['attributed_revenue'], 2);
        $row['purchase_cost'] = round($row['purchase_cost'], 2);
        $row['local_cost_adjustments'] = round($row['local_cost_adjustments'], 2);
        $row['vat_tax_service_total'] = round($row['vat_tax_service_total'], 2);
        $row['erp_contribution_profit'] = round($row['attributed_revenue'] - $row['purchase_cost'], 2);
        $row['total_marketing_cost'] = round($row['meta_spend'] + $row['local_cost_adjustments'], 2);
        $row['ad_adjusted_contribution_profit'] = round($row['erp_contribution_profit'] - $row['total_marketing_cost'], 2);
        $row['break_even_revenue'] = round($row['purchase_cost'] + $row['total_marketing_cost'], 2);
        $row['break_even_gap'] = round($row['attributed_revenue'] - $row['break_even_revenue'], 2);
        $row['contribution_margin_percent'] = $row['attributed_revenue'] > 0 ? round(($row['erp_contribution_profit'] / $row['attributed_revenue']) * 100, 2) : 0.0;
        $row['ad_adjusted_margin_percent'] = $row['attributed_revenue'] > 0 ? round(($row['ad_adjusted_contribution_profit'] / $row['attributed_revenue']) * 100, 2) : 0.0;
        $row['roas'] = $row['meta_spend'] > 0 ? round($row['attributed_revenue'] / $row['meta_spend'], 4) : 0.0;
        $row['cost_per_attributed_order'] = $row['attributed_orders'] > 0 ? round($row['total_marketing_cost'] / $row['attributed_orders'], 2) : 0.0;
        $row['profit_state'] = $this->profitState($row);

        return $row;
    }

    private function emptyBase(?int $campaignId, ?int $adSetId, ?int $adId, string $currency): array
    {
        return [
            'campaign_id' => $campaignId,
            'ad_set_id' => $adSetId,
            'ad_id' => $adId,
            'campaign_name' => $campaignId ? 'Matched Campaign #' . $campaignId : 'Unmatched attribution',
            'ad_set_name' => $adSetId ? 'Matched Ad Set #' . $adSetId : 'Unmatched attribution',
            'ad_name' => $adId ? 'Matched Ad #' . $adId : 'Unmatched attribution',
            'currency' => $currency,
            'meta_spend' => 0.0,
            'local_cost_adjustments' => 0.0,
            'vat_tax_service_total' => 0.0,
            'adjustment_count' => 0,
            'attributed_orders' => 0,
            'attributed_revenue' => 0.0,
            'purchase_cost' => 0.0,
            'missing_cost_count' => 0,
            'latest_snapshot_date' => null,
        ];
    }

    private function emptyRowFromSnapshot($snapshot): array
    {
        $row = $this->emptyBase((int) $snapshot->fbm_campaign_id, (int) $snapshot->fbm_ad_set_id, (int) $snapshot->fbm_ad_id, $this->currency($snapshot->account_currency));
        $row['campaign_name'] = optional($snapshot->campaign)->name ?: 'Unmapped Campaign';
        $row['ad_set_name'] = optional($snapshot->adSet)->name ?: 'Unmapped Ad Set';
        $row['ad_name'] = optional($snapshot->ad)->name ?: 'Unmapped Ad';

        return $row;
    }

    private function emptyRowFromSale(array $sale): array
    {
        return $this->emptyBase($sale['campaign_id'], $sale['ad_set_id'], $sale['ad_id'], $sale['currency']);
    }

    private function emptyRowFromAdjustment(FbmCampaignCostAdjustment $adjustment): array
    {
        $row = $this->emptyBase(
            $adjustment->fbm_campaign_id ? (int) $adjustment->fbm_campaign_id : null,
            $adjustment->fbm_ad_set_id ? (int) $adjustment->fbm_ad_set_id : null,
            $adjustment->fbm_ad_id ? (int) $adjustment->fbm_ad_id : null,
            $this->currency($adjustment->currency)
        );
        $row['campaign_name'] = optional($adjustment->campaign)->name ?: ($row['campaign_id'] ? $row['campaign_name'] : 'Unassigned local adjustment');
        $row['ad_set_name'] = optional($adjustment->adSet)->name ?: ($row['ad_set_id'] ? $row['ad_set_name'] : 'Unassigned local adjustment');
        $row['ad_name'] = optional($adjustment->ad)->name ?: ($row['ad_id'] ? $row['ad_name'] : 'Unassigned local adjustment');

        return $row;
    }

    private function sourceItemCosts(Collection $attributions): Collection
    {
        if (!Schema::hasTable('product_order_products')) {
            return collect();
        }

        $ids = $attributions
            ->filter(fn(FbmOrderAttribution $attribution): bool => (string) $attribution->source_order_type === FbmOrderAttribution::SOURCE_PRODUCT_ORDER)
            ->flatMap(fn(FbmOrderAttribution $attribution): Collection => $attribution->items)
            ->pluck('source_order_item_id')
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        $columns = ['id'];
        foreach (['purchase_price', 'net_profit', 'total_price'] as $column) {
            if (Schema::hasColumn('product_order_products', $column)) {
                $columns[] = $column;
            }
        }

        return DB::table('product_order_products')->whereIn('id', $ids->all())->get($columns)->keyBy('id');
    }

    private function productsForItems(Collection $items): Collection
    {
        $ids = $items->pluck('product_id')->filter()->map(fn($id) => (int) $id)->unique()->values();
        if ($ids->isEmpty()) {
            return collect();
        }

        $columns = ['id'];
        if (Schema::hasColumn('products', 'purchase_price')) {
            $columns[] = 'purchase_price';
        }

        return Product::query()->whereIn('id', $ids->all())->get($columns)->keyBy('id');
    }

    private function lineCost(FbmOrderAttributionItem $item, ?Product $product, Collection $sourceItemCosts): array
    {
        $quantity = max(0, (float) $item->quantity_snapshot);
        $source = optional($item->attribution)->source_order_type;

        if ($source === FbmOrderAttribution::SOURCE_PRODUCT_ORDER && $item->source_order_item_id) {
            $sourceItem = $sourceItemCosts->get((int) $item->source_order_item_id);
            if ($sourceItem) {
                if (isset($sourceItem->purchase_price)) {
                    return ['amount' => round(max(0, (float) $sourceItem->purchase_price) * $quantity, 2), 'source' => 'product_order_purchase_price'];
                }
                if (isset($sourceItem->net_profit) && isset($sourceItem->total_price)) {
                    return ['amount' => round(max(0, (float) $sourceItem->total_price - (float) $sourceItem->net_profit), 2), 'source' => 'product_order_net_profit'];
                }
            }
        }

        if ($product && Schema::hasColumn('products', 'purchase_price') && $product->purchase_price !== null) {
            return ['amount' => round(max(0, (float) $product->purchase_price) * $quantity, 2), 'source' => 'product_purchase_price'];
        }

        return ['amount' => 0.0, 'source' => 'missing'];
    }

    private function adjustmentRows(array $filters, array $range): Collection
    {
        $query = FbmCampaignCostAdjustment::query()
            ->with(['campaign:id,name', 'adSet:id,name', 'ad:id,name'])
            ->where('status', 'approved')
            ->whereBetween('effective_date', [$range['from']->toDateString(), $range['to']->toDateString()]);

        foreach (['campaign_id' => 'fbm_campaign_id', 'ad_set_id' => 'fbm_ad_set_id', 'ad_id' => 'fbm_ad_id'] as $filter => $column) {
            if ($filters[$filter] !== null) {
                $query->where($column, (int) $filters[$filter]);
            }
        }

        return $query->orderByDesc('effective_date')->limit($this->maxRows())->get();
    }

    private function safeAdjustmentRows(Collection $adjustments): array
    {
        return $adjustments->take(50)->map(fn(FbmCampaignCostAdjustment $row): array => [
            'effective_date' => optional($row->effective_date)->toDateString(),
            'campaign_name' => optional($row->campaign)->name ?: 'Unassigned',
            'ad_set_name' => optional($row->adSet)->name ?: 'Unassigned',
            'ad_name' => optional($row->ad)->name ?: 'Unassigned',
            'currency' => $this->currency($row->currency),
            'cost_type' => (string) $row->cost_type,
            'label' => (string) $row->label,
            'base_amount' => round((float) $row->base_amount, 2),
            'vat_amount' => round((float) $row->vat_amount, 2),
            'tax_amount' => round((float) $row->tax_amount, 2),
            'service_charge_amount' => round((float) $row->service_charge_amount, 2),
            'total_amount' => round((float) $row->total_amount, 2),
        ])->values()->all();
    }

    private function summary(Collection $rows): array
    {
        $firstRow = $rows->first();
        $revenue = round($rows->sum('attributed_revenue'), 2);
        $purchaseCost = round($rows->sum('purchase_cost'), 2);
        $erpProfit = round($rows->sum('erp_contribution_profit'), 2);
        $marketingCost = round($rows->sum('total_marketing_cost'), 2);
        $adjustedProfit = round($rows->sum('ad_adjusted_contribution_profit'), 2);

        return [
            'row_count' => $rows->count(),
            'currency' => $this->currency(is_array($firstRow) ? ($firstRow['currency'] ?? 'BDT') : 'BDT'),
            'meta_spend' => round($rows->sum('meta_spend'), 2),
            'local_cost_adjustments' => round($rows->sum('local_cost_adjustments'), 2),
            'total_marketing_cost' => $marketingCost,
            'attributed_orders' => (int) $rows->sum('attributed_orders'),
            'attributed_revenue' => $revenue,
            'purchase_cost' => $purchaseCost,
            'erp_contribution_profit' => $erpProfit,
            'ad_adjusted_contribution_profit' => $adjustedProfit,
            'break_even_revenue' => round($rows->sum('break_even_revenue'), 2),
            'break_even_gap' => round($rows->sum('break_even_gap'), 2),
            'contribution_margin_percent' => $revenue > 0 ? round(($erpProfit / $revenue) * 100, 2) : 0.0,
            'ad_adjusted_margin_percent' => $revenue > 0 ? round(($adjustedProfit / $revenue) * 100, 2) : 0.0,
            'roas' => $rows->sum('meta_spend') > 0 ? round($revenue / $rows->sum('meta_spend'), 4) : 0.0,
            'missing_cost_count' => (int) $rows->sum('missing_cost_count'),
        ];
    }

    private function profitBreakdown(Collection $rows): array
    {
        return $rows->groupBy('profit_state')->map(fn(Collection $group, string $state): array => [
            'state' => $state,
            'label' => $this->label($state),
            'row_count' => $group->count(),
            'revenue' => round($group->sum('attributed_revenue'), 2),
            'ad_adjusted_contribution_profit' => round($group->sum('ad_adjusted_contribution_profit'), 2),
        ])->values()->all();
    }

    private function freshness(Collection $snapshots, Collection $attributions, Collection $adjustments): array
    {
        return [
            'latest_snapshot_date' => $snapshots->map(fn($row) => optional($row->snapshot_date)->toDateString())->filter()->max(),
            'latest_fetched_at' => $snapshots->map(fn($row) => optional($row->fetched_at)->toDateTimeString())->filter()->max(),
            'latest_order_snapshot_at' => $attributions->map(fn($row) => optional($row->snapshot_created_at)->toDateTimeString())->filter()->max(),
            'latest_adjustment_date' => $adjustments->map(fn($row) => optional($row->effective_date)->toDateString())->filter()->max(),
        ];
    }

    private function applyDerivedFilters(Collection $rows, array $filters): Collection
    {
        if ($filters['profit_state'] !== null) {
            $rows = $rows->where('profit_state', $filters['profit_state']);
        }

        return $rows->values();
    }

    private function profitState(array $row): string
    {
        if ($row['attributed_revenue'] <= 0) {
            return 'no_sales';
        }
        if ($row['missing_cost_count'] > 0) {
            return 'missing_cost';
        }
        if ($row['ad_adjusted_contribution_profit'] < 0) {
            return 'loss';
        }
        if ($row['total_marketing_cost'] > 0 && $row['break_even_gap'] <= 0) {
            return 'break_even_risk';
        }

        return 'profitable';
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
            'profit_state' => array_key_exists((string) ($filters['profit_state'] ?? ''), $this->profitStateOptions()) ? (string) $filters['profit_state'] : null,
        ];
    }

    private function validId($value, Collection $options): ?int
    {
        $id = (int) $value;

        return $id > 0 && $options->contains('id', $id) ? $id : null;
    }

    private function existingId(string $table, $value): ?int
    {
        $id = (int) $value;
        if ($id <= 0 || !Schema::hasTable($table)) {
            return null;
        }

        return DB::table($table)->where('id', $id)->exists() ? $id : null;
    }

    private function matchesEntityFilters(array $filters, ?int $campaignId, ?int $adSetId, ?int $adId): bool
    {
        if ($filters['campaign_id'] !== null && (int) $filters['campaign_id'] !== (int) $campaignId) {
            return false;
        }
        if ($filters['ad_set_id'] !== null && (int) $filters['ad_set_id'] !== (int) $adSetId) {
            return false;
        }
        if ($filters['ad_id'] !== null && (int) $filters['ad_id'] !== (int) $adId) {
            return false;
        }

        return true;
    }

    private function decodeEvidence(FbmOrderAttribution $attribution): array
    {
        try {
            $data = json_decode((string) $attribution->evidence_snapshot_ciphertext, true);

            return is_array($data) ? $data : [];
        } catch (Throwable $exception) {
            return [];
        }
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

    private function profitStateOptions(): array
    {
        return [
            'profitable' => 'Profitable',
            'loss' => 'Loss',
            'break_even_risk' => 'Break-even risk',
            'missing_cost' => 'Missing cost',
            'no_sales' => 'No attributed sales',
        ];
    }

    private function costTypeOptions(): array
    {
        return [
            'agency_fee' => 'Agency fee',
            'creative_cost' => 'Creative cost',
            'influencer_cost' => 'Influencer cost',
            'boosting_service' => 'Boosting service',
            'vat_tax' => 'VAT / tax',
            'other' => 'Other',
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
        return max(100, min(5000, (int) config('fb_marketing.profitability.max_rows', 1000)));
    }

    private function groupKey(?int $campaignId, ?int $adSetId, ?int $adId, string $currency): string
    {
        return (int) $campaignId . '|' . (int) $adSetId . '|' . (int) $adId . '|' . $currency;
    }

    private function currency($value): string
    {
        $value = is_scalar($value) ? strtoupper(trim((string) $value)) : '';

        return $value !== '' && preg_match('/^[A-Z]{3}$/', $value) ? $value : 'BDT';
    }

    private function normalizeName($value): string
    {
        $value = strtolower(trim((string) $value));
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? '';

        return trim(preg_replace('/\s+/', ' ', $value) ?? '');
    }

    private function safeCode($value, string $fallback): string
    {
        $value = strtolower(trim((string) $value));

        return preg_match('/^[a-z0-9_]{1,60}$/', $value) ? $value : $fallback;
    }

    private function safeString($value, int $length): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : substr($value, 0, $length);
    }

    private function maxDate(?string $current, ?string $candidate): ?string
    {
        return $candidate !== null && ($current === null || $candidate > $current) ? $candidate : $current;
    }

    private function label(string $value): string
    {
        return ucwords(str_replace('_', ' ', $value));
    }

    private function warning(string $type, string $message): array
    {
        return ['type' => $type, 'message' => $message];
    }
}

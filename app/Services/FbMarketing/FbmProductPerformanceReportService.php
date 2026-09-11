<?php

namespace App\Services\FbMarketing;

use App\Models\FbMarketing\FbmCatalogProductMapping;
use App\Models\FbMarketing\FbmOrderAttribution;
use App\Models\FbMarketing\FbmOrderAttributionItem;
use App\Models\Product;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FbmProductPerformanceReportService
{
    public function build(array $filters): array
    {
        $range = $this->dateRange($filters);
        $schemaReady = $this->schemaReady();
        $productOptions = Schema::hasTable('products') ? $this->productOptions() : collect();
        $safeFilters = $this->safeFilters($filters, $range, $productOptions);
        $warnings = [];

        if (!$schemaReady) {
            $warnings[] = $this->warning('danger', 'FBM-15 attribution items and the ERP products table are required before product-level reporting is available.');
        }

        $items = $schemaReady ? $this->itemRows($safeFilters, $range) : collect();
        $products = $this->productsForItems($items, $productOptions, $safeFilters);
        $catalogMappings = $this->catalogMappings($products->pluck('id')->all());
        $sourceItemCosts = $this->sourceItemCosts($items);
        $rows = $this->productRows($items, $products, $catalogMappings, $sourceItemCosts);
        $rows = $this->applyDerivedFilters($rows, $safeFilters);

        if ($schemaReady && $items->isEmpty()) {
            $warnings[] = $this->warning('warning', 'No attributed order item snapshot matched the selected date range.');
        }
        if ($rows->where('cost_source', 'missing')->isNotEmpty()) {
            $warnings[] = $this->warning('warning', 'Some products have no usable purchase-cost source. Profit values for those rows use zero cost and must be reviewed before finance use.');
        }
        if (!Schema::hasTable('fbm_catalog_product_mappings')) {
            $warnings[] = $this->warning('warning', 'FBM-11 catalog mapping table is unavailable. Catalog mapping status is shown as unavailable.');
        }

        return [
            'schema_ready' => $schemaReady,
            'filters' => $safeFilters,
            'range' => ['from_date' => $range['from']->toDateString(), 'to_date' => $range['to']->toDateString(), 'days' => $range['days']],
            'product_options' => $productOptions->values(),
            'stock_risk_options' => $this->stockRiskOptions(),
            'catalog_status_options' => $this->catalogStatusOptions(),
            'summary' => $this->summary($rows),
            'rows' => $rows->values()->all(),
            'risk_breakdown' => $this->riskBreakdown($rows),
            'warnings' => $warnings,
        ];
    }

    private function schemaReady(): bool
    {
        foreach (['fbm_order_attributions', 'fbm_order_attribution_items', 'products'] as $table) {
            if (!Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }

    private function itemRows(array $filters, array $range): Collection
    {
        $query = FbmOrderAttributionItem::query()
            ->with('attribution:id,source_order_type,source_order_id,evidence_state,lifecycle_state_current,currency,snapshot_created_at')
            ->whereHas('attribution', function ($query) use ($range) {
                $query->where('evidence_state', FbmOrderAttribution::EVIDENCE_ATTRIBUTED)
                    ->whereBetween('snapshot_created_at', [
                        $range['from']->startOfDay()->toDateTimeString(),
                        $range['to']->endOfDay()->toDateTimeString(),
                    ]);
            })
            ->whereNotNull('product_id')
            ->orderByDesc('id')
            ->limit($this->maxRows());

        if ($filters['product_id'] !== null) {
            $query->where('product_id', (int) $filters['product_id']);
        }

        return $query->get();
    }

    private function productsForItems(Collection $items, Collection $productOptions, array $filters): Collection
    {
        $ids = $items->pluck('product_id')->filter()->map(fn($id) => (int) $id)->unique()->values();
        if ($filters['product_id'] !== null) {
            $ids->push((int) $filters['product_id']);
        }

        if ($ids->isEmpty()) {
            return collect();
        }

        return Product::query()
            ->whereIn('id', $ids->unique()->values()->all())
            ->get($this->productColumns())
            ->keyBy('id');
    }

    private function productColumns(): array
    {
        $columns = ['id', 'name'];
        foreach (['sku', 'code', 'status', 'purchase_price', 'stock', 'current_stock', 'low_stock', 'has_variant'] as $column) {
            if (Schema::hasColumn('products', $column)) {
                $columns[] = $column;
            }
        }

        return array_values(array_unique($columns));
    }

    private function catalogMappings(array $productIds): Collection
    {
        $productIds = array_values(array_unique(array_filter(array_map('intval', $productIds))));
        if ($productIds === [] || !Schema::hasTable('fbm_catalog_product_mappings')) {
            return collect();
        }

        return FbmCatalogProductMapping::query()
            ->whereIn('product_id', $productIds)
            ->orderByDesc('is_available')
            ->orderBy('mapping_status')
            ->get(['product_id', 'mapping_status', 'mapping_source', 'is_available'])
            ->groupBy('product_id');
    }

    private function sourceItemCosts(Collection $items): Collection
    {
        if (!Schema::hasTable('product_order_products')) {
            return collect();
        }

        $ids = $items
            ->filter(fn(FbmOrderAttributionItem $item): bool => optional($item->attribution)->source_order_type === FbmOrderAttribution::SOURCE_PRODUCT_ORDER)
            ->pluck('source_order_item_id')
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        $columns = ['id'];
        foreach (['purchase_price', 'unit_profit', 'net_profit', 'qty', 'total_price'] as $column) {
            if (Schema::hasColumn('product_order_products', $column)) {
                $columns[] = $column;
            }
        }

        return DB::table('product_order_products')
            ->whereIn('id', $ids->all())
            ->get($columns)
            ->keyBy('id');
    }

    private function productRows(Collection $items, Collection $products, Collection $catalogMappings, Collection $sourceItemCosts): Collection
    {
        $rows = [];

        foreach ($items as $item) {
            $productId = (int) $item->product_id;
            $product = $products->get($productId);
            $attribution = $item->attribution;
            if (!$product || !$attribution) {
                continue;
            }

            if (!isset($rows[$productId])) {
                $rows[$productId] = $this->emptyProductRow($product, $catalogMappings->get($productId, collect()));
            }

            $quantity = max(0, (float) $item->quantity_snapshot);
            $lineRevenue = max(0, (float) $item->line_total_snapshot);
            $cost = $this->lineCost($item, $product, $sourceItemCosts);
            $state = (string) $attribution->lifecycle_state_current;

            if ($state === FbmOrderLifecycleClassifier::STATE_CONFIRMED) {
                $rows[$productId]['confirmed_quantity'] += $quantity;
                $rows[$productId]['confirmed_revenue'] += $lineRevenue;
                $rows[$productId]['purchase_cost'] += $cost['amount'];
                $rows[$productId]['gross_profit'] += $lineRevenue - $cost['amount'];
                $rows[$productId]['order_count_keys'][$attribution->source_order_type . ':' . $attribution->source_order_id] = true;
                if ($cost['source'] !== 'missing') {
                    $rows[$productId]['costed_quantity'] += $quantity;
                }
            } elseif ($state === FbmOrderLifecycleClassifier::STATE_CANCELLED) {
                $rows[$productId]['cancelled_quantity'] += $quantity;
                $rows[$productId]['cancelled_revenue'] += $lineRevenue;
            } elseif ($state === FbmOrderLifecycleClassifier::STATE_RETURNED) {
                $rows[$productId]['returned_quantity'] += $quantity;
                $rows[$productId]['returned_revenue'] += $lineRevenue;
            } else {
                $rows[$productId]['pending_quantity'] += $quantity;
            }

            if ($cost['source'] !== 'missing') {
                $rows[$productId]['cost_sources'][$cost['source']] = true;
            } else {
                $rows[$productId]['missing_cost_count']++;
            }
        }

        foreach ($rows as $productId => $row) {
            $rows[$productId] = $this->deriveProductRow($row);
        }

        $rows = collect($rows)->sortByDesc('confirmed_revenue')->values();

        return $rows->take(250);
    }

    private function emptyProductRow(Product $product, Collection $mappings): array
    {
        $stock = $this->productStock($product);
        $lowStock = $this->lowStockThreshold($product);
        $catalogStatus = $this->catalogStatus($mappings);

        return [
            'product_id' => (int) $product->id,
            'product_name' => $product->name ?: 'Unnamed Product',
            'sku' => $this->safeString($product->sku ?? $product->code ?? null, 120),
            'status' => (string) ($product->status ?? 'unknown'),
            'catalog_status' => $catalogStatus,
            'stock_on_hand' => $stock,
            'low_stock_threshold' => $lowStock,
            'confirmed_quantity' => 0.0,
            'confirmed_revenue' => 0.0,
            'purchase_cost' => 0.0,
            'gross_profit' => 0.0,
            'gross_margin_percent' => 0.0,
            'average_unit_revenue' => 0.0,
            'estimated_stock_coverage' => null,
            'returned_quantity' => 0.0,
            'returned_revenue' => 0.0,
            'cancelled_quantity' => 0.0,
            'cancelled_revenue' => 0.0,
            'pending_quantity' => 0.0,
            'costed_quantity' => 0.0,
            'missing_cost_count' => 0,
            'cost_source' => 'missing',
            'cost_sources' => [],
            'order_count_keys' => [],
            'attributed_order_count' => 0,
            'stock_risk' => 'no_sales',
        ];
    }

    private function deriveProductRow(array $row): array
    {
        $row['attributed_order_count'] = count($row['order_count_keys']);
        $row['confirmed_quantity'] = round($row['confirmed_quantity'], 3);
        $row['confirmed_revenue'] = round($row['confirmed_revenue'], 2);
        $row['purchase_cost'] = round($row['purchase_cost'], 2);
        $row['gross_profit'] = round($row['gross_profit'], 2);
        $row['returned_revenue'] = round($row['returned_revenue'], 2);
        $row['cancelled_revenue'] = round($row['cancelled_revenue'], 2);
        $row['average_unit_revenue'] = $row['confirmed_quantity'] > 0 ? round($row['confirmed_revenue'] / $row['confirmed_quantity'], 2) : 0.0;
        $row['gross_margin_percent'] = $row['confirmed_revenue'] > 0 ? round(($row['gross_profit'] / $row['confirmed_revenue']) * 100, 2) : 0.0;
        $row['estimated_stock_coverage'] = $row['confirmed_quantity'] > 0 ? round($row['stock_on_hand'] / $row['confirmed_quantity'], 2) : null;
        $row['cost_source'] = empty($row['cost_sources']) ? 'missing' : implode(', ', array_keys($row['cost_sources']));
        $row['stock_risk'] = $this->stockRisk($row);
        unset($row['order_count_keys'], $row['cost_sources']);

        return $row;
    }

    private function lineCost(FbmOrderAttributionItem $item, Product $product, Collection $sourceItemCosts): array
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

        if (Schema::hasColumn('products', 'purchase_price') && $product->purchase_price !== null) {
            return ['amount' => round(max(0, (float) $product->purchase_price) * $quantity, 2), 'source' => 'product_purchase_price'];
        }

        return ['amount' => 0.0, 'source' => 'missing'];
    }

    private function productStock(Product $product): float
    {
        foreach (['current_stock', 'stock'] as $column) {
            if (Schema::hasColumn('products', $column) && $product->{$column} !== null) {
                return round((float) $product->{$column}, 3);
            }
        }

        return 0.0;
    }

    private function lowStockThreshold(Product $product): float
    {
        if (Schema::hasColumn('products', 'low_stock') && $product->low_stock !== null) {
            return max(0, (float) $product->low_stock);
        }

        return max(0, (float) config('fb_marketing.product_performance.low_stock_threshold', config('analytics.low_stock_threshold', 5)));
    }

    private function catalogStatus(Collection $mappings): string
    {
        if ($mappings->isEmpty()) {
            return Schema::hasTable('fbm_catalog_product_mappings') ? 'unmapped' : 'unavailable';
        }
        if ($mappings->contains(fn($mapping): bool => in_array((string) $mapping->mapping_status, ['automatic', 'manual'], true))) {
            return 'mapped';
        }

        return (string) optional($mappings->first())->mapping_status ?: 'unmapped';
    }

    private function stockRisk(array $row): string
    {
        if ($row['confirmed_quantity'] <= 0) {
            return 'no_sales';
        }
        if ($row['stock_on_hand'] <= 0) {
            return 'out_of_stock';
        }
        if ($row['stock_on_hand'] < $row['confirmed_quantity']) {
            return 'oversold_risk';
        }
        if ($row['stock_on_hand'] <= $row['low_stock_threshold']) {
            return 'low_stock';
        }
        if ($row['catalog_status'] !== 'mapped') {
            return 'catalog_unmapped';
        }
        if ($row['cost_source'] === 'missing') {
            return 'missing_cost';
        }

        return 'healthy';
    }

    private function applyDerivedFilters(Collection $rows, array $filters): Collection
    {
        if ($filters['stock_risk'] !== null) {
            $rows = $rows->where('stock_risk', $filters['stock_risk']);
        }
        if ($filters['catalog_status'] !== null) {
            $rows = $rows->where('catalog_status', $filters['catalog_status']);
        }

        return $rows->values();
    }

    private function summary(Collection $rows): array
    {
        $revenue = round($rows->sum('confirmed_revenue'), 2);
        $cost = round($rows->sum('purchase_cost'), 2);
        $profit = round($rows->sum('gross_profit'), 2);

        return [
            'product_count' => $rows->count(),
            'attributed_order_count' => $rows->sum('attributed_order_count'),
            'quantity_sold' => round($rows->sum('confirmed_quantity'), 3),
            'revenue' => $revenue,
            'purchase_cost' => $cost,
            'gross_profit' => $profit,
            'gross_margin_percent' => $revenue > 0 ? round(($profit / $revenue) * 100, 2) : 0.0,
            'returned_revenue' => round($rows->sum('returned_revenue'), 2),
            'cancelled_revenue' => round($rows->sum('cancelled_revenue'), 2),
            'risk_product_count' => $rows->whereNotIn('stock_risk', ['healthy', 'no_sales'])->count(),
        ];
    }

    private function riskBreakdown(Collection $rows): array
    {
        return $rows->groupBy('stock_risk')->map(function (Collection $group, string $risk): array {
            return [
                'risk' => $risk,
                'label' => $this->label($risk),
                'product_count' => $group->count(),
                'revenue' => round($group->sum('confirmed_revenue'), 2),
            ];
        })->values()->all();
    }

    private function productOptions(): Collection
    {
        return Product::query()
            ->orderBy('name')
            ->limit(500)
            ->get(['id', 'name'])
            ->map(fn(Product $product): array => ['id' => (int) $product->id, 'name' => $product->name ?: 'Unnamed Product']);
    }

    private function safeFilters(array $filters, array $range, Collection $products): array
    {
        return [
            'from_date' => $range['from']->toDateString(),
            'to_date' => $range['to']->toDateString(),
            'product_id' => $this->validId($filters['product_id'] ?? null, $products),
            'stock_risk' => array_key_exists((string) ($filters['stock_risk'] ?? ''), $this->stockRiskOptions()) ? (string) $filters['stock_risk'] : null,
            'catalog_status' => array_key_exists((string) ($filters['catalog_status'] ?? ''), $this->catalogStatusOptions()) ? (string) $filters['catalog_status'] : null,
        ];
    }

    private function validId($value, Collection $options): ?int
    {
        $id = (int) $value;

        return $id > 0 && $options->contains('id', $id) ? $id : null;
    }

    private function stockRiskOptions(): array
    {
        return [
            'healthy' => 'Healthy',
            'low_stock' => 'Low stock',
            'out_of_stock' => 'Out of stock',
            'oversold_risk' => 'Oversold risk',
            'catalog_unmapped' => 'Catalog unmapped',
            'missing_cost' => 'Missing cost',
            'no_sales' => 'No confirmed sales',
        ];
    }

    private function catalogStatusOptions(): array
    {
        return [
            'mapped' => 'Mapped',
            'unmapped' => 'Unmapped',
            'ambiguous' => 'Ambiguous',
            'unavailable' => 'Unavailable',
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
        } catch (\Throwable $exception) {
            return null;
        }
    }

    private function maxRows(): int
    {
        return max(100, min(5000, (int) config('fb_marketing.product_performance.max_rows', 1000)));
    }

    private function safeString($value, int $length): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : substr($value, 0, $length);
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

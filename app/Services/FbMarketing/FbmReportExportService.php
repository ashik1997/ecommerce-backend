<?php

namespace App\Services\FbMarketing;

use App\Models\FbMarketing\FbmReportExport;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class FbmReportExportService
{
    public function __construct(
        private FbmAttributionReportService $attributionReports,
        private FbmProductPerformanceReportService $productPerformance,
        private FbmProfitabilityReportService $profitability,
        private FbmBoostingJobLedgerService $boostingJobs
    ) {
    }

    public function reportOptions(): array
    {
        return [
            'attribution_reports' => 'Attribution reports',
            'product_performance' => 'Product performance',
            'profitability' => 'Profitability',
            'boosting_jobs' => 'Boosting jobs ledger',
        ];
    }

    public function recentExports(int $limit = 20): array
    {
        return FbmReportExport::query()
            ->orderByDesc('id')
            ->limit(max(1, min(100, $limit)))
            ->get()
            ->map(fn(FbmReportExport $export): array => $this->safeExportRow($export))
            ->all();
    }

    public function preview(array $filters): array
    {
        $reportType = $this->safeReportType($filters['report_type'] ?? null);
        $dataset = $this->dataset($reportType, $filters);

        return [
            'report_type' => $reportType,
            'report_options' => $this->reportOptions(),
            'filters' => $this->safeFilters($filters, $reportType),
            'summary' => $dataset['summary'],
            'columns' => $dataset['columns'],
            'rows' => array_slice($dataset['rows'], 0, 25),
            'row_count' => count($dataset['rows']),
            'warnings' => $dataset['warnings'],
            'recent_exports' => $this->recentExports(),
        ];
    }

    public function create(array $filters, ?int $userId): FbmReportExport
    {
        $reportType = $this->safeReportType($filters['report_type'] ?? null);
        $format = 'csv';
        $token = Str::random(48);
        $export = FbmReportExport::query()->create([
            'export_uuid' => (string) Str::uuid(),
            'report_type' => $reportType,
            'format' => $format,
            'status' => 'processing',
            'filter_snapshot' => $this->safeFilters($filters, $reportType),
            'file_disk' => 'local',
            'download_token_hash' => hash('sha256', $token),
            'requested_by' => $userId,
        ]);

        try {
            $dataset = $this->dataset($reportType, $filters);
            $csv = $this->csv($dataset['columns'], $dataset['rows']);
            $path = 'fb-marketing/exports/' . now()->format('Y/m/d') . '/' . $export->export_uuid . '.csv';
            Storage::disk('local')->put($path, $csv);
            $export->forceFill([
                'status' => 'completed',
                'row_count' => count($dataset['rows']),
                'file_path' => $path,
                'generated_at' => now(),
                'safe_error' => null,
            ])->save();
            $export->download_token = $token;
        } catch (Throwable $exception) {
            $export->forceFill([
                'status' => 'failed',
                'safe_error' => 'Report export failed safely. Review server logs for the redacted exception.',
            ])->save();
        }

        return $export;
    }

    public function downloadPath(FbmReportExport $export, string $token): ?string
    {
        if ((string) $export->status !== 'completed'
            || !$export->file_path
            || !hash_equals((string) $export->download_token_hash, hash('sha256', $token))
            || !Storage::disk('local')->exists($export->file_path)) {
            return null;
        }

        $export->forceFill(['downloaded_at' => now()])->save();

        return storage_path('app/' . $export->file_path);
    }

    public function safeExportRow(FbmReportExport $export): array
    {
        return [
            'id' => (int) $export->id,
            'export_uuid' => (string) $export->export_uuid,
            'report_type' => (string) $export->report_type,
            'report_label' => $this->reportOptions()[$export->report_type] ?? (string) $export->report_type,
            'format' => (string) $export->format,
            'status' => (string) $export->status,
            'row_count' => (int) $export->row_count,
            'generated_at' => optional($export->generated_at)->toDateTimeString(),
            'downloaded_at' => optional($export->downloaded_at)->toDateTimeString(),
            'safe_error' => (string) $export->safe_error,
        ];
    }

    private function dataset(string $reportType, array $filters): array
    {
        return match ($reportType) {
            'product_performance' => $this->productPerformanceDataset($filters),
            'profitability' => $this->profitabilityDataset($filters),
            'boosting_jobs' => $this->boostingJobsDataset($filters),
            default => $this->attributionDataset($filters),
        };
    }

    private function attributionDataset(array $filters): array
    {
        $report = $this->attributionReports->build($filters);
        $columns = ['campaign', 'ad_set', 'ad', 'currency', 'spend', 'attributed_orders', 'attributed_revenue', 'cost_per_order', 'roas', 'revenue_minus_spend', 'snapshot'];
        $rows = array_map(fn(array $row): array => [
            $row['campaign_name'] ?? '',
            $row['ad_set_name'] ?? '',
            $row['ad_name'] ?? '',
            $row['currency'] ?? '',
            $row['spend'] ?? 0,
            $row['attributed_orders'] ?? 0,
            $row['attributed_revenue'] ?? 0,
            $row['cost_per_attributed_order'] ?? 0,
            $row['roas'] ?? 0,
            $row['revenue_minus_spend'] ?? 0,
            $row['latest_snapshot_date'] ?? '',
        ], $report['performance_rows'] ?? []);

        return $this->datasetResult($report, $columns, $rows);
    }

    private function productPerformanceDataset(array $filters): array
    {
        $report = $this->productPerformance->build($filters);
        $columns = ['product_id', 'product_name', 'sku', 'catalog_status', 'stock_risk', 'stock_on_hand', 'quantity_sold', 'orders', 'revenue', 'purchase_cost', 'gross_profit', 'gross_margin_percent', 'cost_source'];
        $rows = array_map(fn(array $row): array => [
            $row['product_id'] ?? '',
            $row['product_name'] ?? '',
            $row['sku'] ?? '',
            $row['catalog_status'] ?? '',
            $row['stock_risk'] ?? '',
            $row['stock_on_hand'] ?? 0,
            $row['confirmed_quantity'] ?? 0,
            $row['attributed_order_count'] ?? 0,
            $row['confirmed_revenue'] ?? 0,
            $row['purchase_cost'] ?? 0,
            $row['gross_profit'] ?? 0,
            $row['gross_margin_percent'] ?? 0,
            $row['cost_source'] ?? '',
        ], $report['rows'] ?? []);

        return $this->datasetResult($report, $columns, $rows);
    }

    private function profitabilityDataset(array $filters): array
    {
        $report = $this->profitability->build($filters);
        $columns = ['campaign', 'ad_set', 'ad', 'currency', 'revenue', 'purchase_cost', 'meta_spend', 'local_cost', 'erp_profit', 'adjusted_profit', 'break_even_gap', 'roas', 'profit_state'];
        $rows = array_map(fn(array $row): array => [
            $row['campaign_name'] ?? '',
            $row['ad_set_name'] ?? '',
            $row['ad_name'] ?? '',
            $row['currency'] ?? '',
            $row['attributed_revenue'] ?? 0,
            $row['purchase_cost'] ?? 0,
            $row['meta_spend'] ?? 0,
            $row['local_cost_adjustments'] ?? 0,
            $row['erp_contribution_profit'] ?? 0,
            $row['ad_adjusted_contribution_profit'] ?? 0,
            $row['break_even_gap'] ?? 0,
            $row['roas'] ?? 0,
            $row['profit_state'] ?? '',
        ], $report['rows'] ?? []);

        return $this->datasetResult($report, $columns, $rows);
    }

    private function boostingJobsDataset(array $filters): array
    {
        $report = $this->boostingJobs->build($filters);
        $columns = ['job_code', 'title', 'mode', 'status', 'currency', 'client', 'planned_budget', 'actual_spend', 'service_fee', 'local_cost', 'received', 'refund', 'receivable', 'balance', 'campaign_links'];
        $rows = array_map(fn(array $row): array => [
            $row['job_code'] ?? '',
            $row['title'] ?? '',
            $row['mode'] ?? '',
            $row['status'] ?? '',
            $row['currency'] ?? '',
            $row['client_name'] ?? '',
            $row['planned_budget'] ?? 0,
            $row['actual_spend'] ?? 0,
            $row['service_fee'] ?? 0,
            $row['local_cost'] ?? 0,
            $row['received_amount'] ?? 0,
            $row['refund_amount'] ?? 0,
            $row['receivable_total'] ?? 0,
            $row['balance'] ?? 0,
            $row['campaign_link_count'] ?? 0,
        ], $report['rows'] ?? []);

        return $this->datasetResult($report, $columns, $rows);
    }

    private function datasetResult(array $report, array $columns, array $rows): array
    {
        return [
            'columns' => $columns,
            'rows' => $rows,
            'summary' => $report['summary'] ?? [],
            'warnings' => $report['warnings'] ?? [],
        ];
    }

    private function csv(array $columns, array $rows): string
    {
        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, $columns);
        foreach ($rows as $row) {
            fputcsv($stream, array_map(fn($value) => is_scalar($value) || $value === null ? $value : json_encode($value), $row));
        }
        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        return (string) $csv;
    }

    private function safeReportType($value): string
    {
        $value = is_scalar($value) ? strtolower(trim((string) $value)) : '';

        return array_key_exists($value, $this->reportOptions()) ? $value : 'attribution_reports';
    }

    private function safeFilters(array $filters, string $reportType): array
    {
        $allowed = [
            'report_type', 'from_date', 'to_date', 'campaign_id', 'ad_set_id', 'ad_id',
            'product_id', 'customer_id', 'evidence_state', 'attribution_method',
            'stock_risk', 'catalog_status', 'profit_state', 'mode', 'status',
        ];
        $safe = array_intersect_key($filters, array_flip($allowed));
        $safe['report_type'] = $reportType;

        return $safe;
    }
}

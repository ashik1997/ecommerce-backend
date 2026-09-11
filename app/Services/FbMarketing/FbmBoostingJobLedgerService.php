<?php

namespace App\Services\FbMarketing;

use App\Models\FbMarketing\FbmAd;
use App\Models\FbMarketing\FbmAdSet;
use App\Models\FbMarketing\FbmBoostingJob;
use App\Models\FbMarketing\FbmBoostingJobCampaign;
use App\Models\FbMarketing\FbmBoostingJobCostAdjustment;
use App\Models\FbMarketing\FbmBoostingJobPayment;
use App\Models\FbMarketing\FbmCampaign;
use App\Models\FbMarketing\FbmInsightDailySnapshot;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class FbmBoostingJobLedgerService
{
    public function build(array $filters): array
    {
        $range = $this->dateRange($filters);
        $schemaReady = $this->schemaReady();
        $campaignOptions = Schema::hasTable('fbm_campaigns') ? $this->campaignOptions() : collect();
        $adSetOptions = Schema::hasTable('fbm_ad_sets') ? $this->adSetOptions() : collect();
        $adOptions = Schema::hasTable('fbm_ads') ? $this->adOptions() : collect();
        $customerOptions = Schema::hasTable('customers') ? $this->customerOptions() : collect();
        $safeFilters = $this->safeFilters($filters, $range, $customerOptions);
        $warnings = [];

        if (!$schemaReady) {
            $warnings[] = $this->warning('danger', 'FBM-08 Insights snapshots and FBM-19 boosting job ledger tables are required before boosting jobs are available.');
        }

        $jobs = $schemaReady ? $this->jobs($safeFilters) : collect();
        $rows = $schemaReady ? $this->jobRows($jobs, $range) : collect();

        if ($schemaReady && $jobs->isEmpty()) {
            $warnings[] = $this->warning('info', 'No boosting job matched the current filters.');
        }
        if ($rows->where('campaign_link_count', 0)->isNotEmpty()) {
            $warnings[] = $this->warning('warning', 'Some boosting jobs have no linked local campaign/ad rows, so actual Meta spend is zero until a link is added.');
        }

        return [
            'schema_ready' => $schemaReady,
            'filters' => $safeFilters,
            'range' => ['from_date' => $range['from']->toDateString(), 'to_date' => $range['to']->toDateString(), 'days' => $range['days']],
            'mode_options' => $this->modeOptions(),
            'status_options' => $this->statusOptions(),
            'payment_type_options' => $this->paymentTypeOptions(),
            'cost_type_options' => $this->costTypeOptions(),
            'campaign_options' => $campaignOptions->values(),
            'ad_set_options' => $adSetOptions->values(),
            'ad_options' => $adOptions->values(),
            'customer_options' => $customerOptions->values(),
            'summary' => $this->summary($rows),
            'rows' => $rows->values()->all(),
            'warnings' => $warnings,
        ];
    }

    public function storeJob(array $input, ?int $userId): void
    {
        if (!Schema::hasTable('fbm_boosting_jobs')) {
            return;
        }

        FbmBoostingJob::query()->create([
            'job_uuid' => (string) Str::uuid(),
            'job_code' => $this->nextJobCode(),
            'title' => $this->safeString($input['title'] ?? 'Boosting job', 180) ?: 'Boosting job',
            'mode' => in_array($input['mode'] ?? 'own_store', array_keys($this->modeOptions()), true) ? $input['mode'] : 'own_store',
            'customer_id' => $this->existingId('customers', $input['customer_id'] ?? null),
            'client_name' => $this->safeString($input['client_name'] ?? null, 180),
            'currency' => $this->currency($input['currency'] ?? 'BDT'),
            'planned_budget' => round(max(0, (float) ($input['planned_budget'] ?? 0)), 2),
            'service_fee' => round(max(0, (float) ($input['service_fee'] ?? 0)), 2),
            'start_date' => $input['start_date'] ?? null,
            'end_date' => $input['end_date'] ?? null,
            'status' => in_array($input['status'] ?? 'draft', array_keys($this->statusOptions()), true) ? $input['status'] : 'draft',
            'safe_note' => $this->safeString($input['safe_note'] ?? null, 500),
            'created_by' => $userId,
        ]);
    }

    public function attachCampaign(array $input, ?int $userId): void
    {
        if (!Schema::hasTable('fbm_boosting_job_campaigns')) {
            return;
        }

        $jobId = $this->existingId('fbm_boosting_jobs', $input['job_id'] ?? null);
        if ($jobId === null) {
            return;
        }

        FbmBoostingJobCampaign::query()->create([
            'fbm_boosting_job_id' => $jobId,
            'fbm_campaign_id' => $this->existingId('fbm_campaigns', $input['campaign_id'] ?? null),
            'fbm_ad_set_id' => $this->existingId('fbm_ad_sets', $input['ad_set_id'] ?? null),
            'fbm_ad_id' => $this->existingId('fbm_ads', $input['ad_id'] ?? null),
            'allocation_percent' => round(max(0, min(100, (float) ($input['allocation_percent'] ?? 100))), 2),
            'safe_note' => $this->safeString($input['safe_note'] ?? null, 500),
            'created_by' => $userId,
        ]);
    }

    public function storePayment(array $input, ?int $userId): void
    {
        if (!Schema::hasTable('fbm_boosting_job_payments')) {
            return;
        }

        $jobId = $this->existingId('fbm_boosting_jobs', $input['job_id'] ?? null);
        if ($jobId === null) {
            return;
        }

        FbmBoostingJobPayment::query()->create([
            'fbm_boosting_job_id' => $jobId,
            'payment_date' => $input['payment_date'],
            'payment_type' => in_array($input['payment_type'] ?? 'client_payment', array_keys($this->paymentTypeOptions()), true) ? $input['payment_type'] : 'client_payment',
            'currency' => $this->currency($input['currency'] ?? 'BDT'),
            'amount' => round(max(0, (float) ($input['amount'] ?? 0)), 2),
            'method' => $this->safeString($input['method'] ?? null, 80),
            'safe_reference' => $this->safeString($input['safe_reference'] ?? null, 180),
            'status' => in_array($input['status'] ?? 'confirmed', ['confirmed', 'pending', 'void'], true) ? $input['status'] : 'confirmed',
            'safe_note' => $this->safeString($input['safe_note'] ?? null, 500),
            'created_by' => $userId,
        ]);
    }

    public function storeCost(array $input, ?int $userId): void
    {
        if (!Schema::hasTable('fbm_boosting_job_cost_adjustments')) {
            return;
        }

        $jobId = $this->existingId('fbm_boosting_jobs', $input['job_id'] ?? null);
        if ($jobId === null) {
            return;
        }

        $base = round(max(0, (float) ($input['base_amount'] ?? 0)), 2);
        $vat = round(max(0, (float) ($input['vat_amount'] ?? 0)), 2);
        $tax = round(max(0, (float) ($input['tax_amount'] ?? 0)), 2);
        $serviceCharge = round(max(0, (float) ($input['service_charge_amount'] ?? 0)), 2);

        FbmBoostingJobCostAdjustment::query()->create([
            'fbm_boosting_job_id' => $jobId,
            'cost_date' => $input['cost_date'],
            'cost_type' => $this->safeCode($input['cost_type'] ?? 'other', 'other'),
            'label' => $this->safeString($input['label'] ?? 'Boosting job cost', 160) ?: 'Boosting job cost',
            'currency' => $this->currency($input['currency'] ?? 'BDT'),
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
        foreach (['fbm_boosting_jobs', 'fbm_boosting_job_campaigns', 'fbm_boosting_job_payments', 'fbm_boosting_job_cost_adjustments', 'fbm_insight_daily_snapshots', 'fbm_campaigns', 'fbm_ad_sets', 'fbm_ads'] as $table) {
            if (!Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }

    private function jobs(array $filters): Collection
    {
        $query = FbmBoostingJob::query()
            ->with(['campaigns.campaign:id,name', 'campaigns.adSet:id,name', 'campaigns.ad:id,name', 'payments', 'costs'])
            ->orderByDesc('id')
            ->limit($this->maxRows());

        if ($filters['mode'] !== null) {
            $query->where('mode', $filters['mode']);
        }
        if ($filters['status'] !== null) {
            $query->where('status', $filters['status']);
        }
        if ($filters['customer_id'] !== null) {
            $query->where('customer_id', (int) $filters['customer_id']);
        }

        return $query->get();
    }

    private function jobRows(Collection $jobs, array $range): Collection
    {
        $customerNames = $this->customerNames($jobs->pluck('customer_id')->filter()->all());

        return $jobs->map(function (FbmBoostingJob $job) use ($range, $customerNames): array {
            $actualSpend = $this->actualSpend($job, $range);
            $payments = $job->payments->where('status', 'confirmed');
            $costs = $job->costs->where('status', 'approved');
            $received = round($payments->whereIn('payment_type', ['advance', 'client_payment', 'adjustment'])->sum('amount'), 2);
            $refund = round($payments->where('payment_type', 'refund')->sum('amount'), 2);
            $localCost = round($costs->sum('total_amount'), 2);
            $receivable = round($actualSpend + (float) $job->service_fee + $localCost, 2);
            $balance = round($receivable - $received + $refund, 2);

            return [
                'job_id' => (int) $job->id,
                'job_code' => (string) ($job->job_code ?: ('FBMJ-' . $job->id)),
                'title' => (string) $job->title,
                'mode' => (string) $job->mode,
                'status' => (string) $job->status,
                'currency' => $this->currency($job->currency),
                'client_name' => $job->customer_id ? ($customerNames[(int) $job->customer_id] ?? 'Customer #' . $job->customer_id) : ((string) $job->client_name ?: 'Own store'),
                'planned_budget' => round((float) $job->planned_budget, 2),
                'service_fee' => round((float) $job->service_fee, 2),
                'actual_spend' => $actualSpend,
                'local_cost' => $localCost,
                'received_amount' => $received,
                'refund_amount' => $refund,
                'receivable_total' => $receivable,
                'balance' => $balance,
                'net_margin' => round((float) $job->service_fee + $localCost - $refund, 2),
                'campaign_link_count' => $job->campaigns->count(),
                'payment_count' => $payments->count(),
                'cost_count' => $costs->count(),
                'start_date' => optional($job->start_date)->toDateString(),
                'end_date' => optional($job->end_date)->toDateString(),
                'linked_campaigns' => $this->linkedCampaigns($job),
            ];
        })->values();
    }

    private function actualSpend(FbmBoostingJob $job, array $range): float
    {
        $spend = 0.0;

        foreach ($job->campaigns as $link) {
            $query = FbmInsightDailySnapshot::query()
                ->where('insight_level', 'ad')
                ->whereBetween('snapshot_date', [$range['from']->toDateString(), $range['to']->toDateString()]);

            if ($link->fbm_campaign_id) {
                $query->where('fbm_campaign_id', (int) $link->fbm_campaign_id);
            }
            if ($link->fbm_ad_set_id) {
                $query->where('fbm_ad_set_id', (int) $link->fbm_ad_set_id);
            }
            if ($link->fbm_ad_id) {
                $query->where('fbm_ad_id', (int) $link->fbm_ad_id);
            }

            $spend += ((float) $query->sum('spend')) * (max(0, min(100, (float) $link->allocation_percent)) / 100);
        }

        return round($spend, 2);
    }

    private function linkedCampaigns(FbmBoostingJob $job): array
    {
        return $job->campaigns->take(5)->map(fn(FbmBoostingJobCampaign $link): array => [
            'campaign_name' => optional($link->campaign)->name ?: 'Unassigned campaign',
            'ad_set_name' => optional($link->adSet)->name ?: 'All ad sets',
            'ad_name' => optional($link->ad)->name ?: 'All ads',
            'allocation_percent' => round((float) $link->allocation_percent, 2),
        ])->values()->all();
    }

    private function summary(Collection $rows): array
    {
        $firstRow = $rows->first();

        return [
            'job_count' => $rows->count(),
            'client_job_count' => $rows->where('mode', 'client_boosting')->count(),
            'own_store_job_count' => $rows->where('mode', 'own_store')->count(),
            'planned_budget' => round($rows->sum('planned_budget'), 2),
            'actual_spend' => round($rows->sum('actual_spend'), 2),
            'service_fee' => round($rows->sum('service_fee'), 2),
            'local_cost' => round($rows->sum('local_cost'), 2),
            'received_amount' => round($rows->sum('received_amount'), 2),
            'balance' => round($rows->sum('balance'), 2),
            'currency' => $this->currency(is_array($firstRow) ? ($firstRow['currency'] ?? 'BDT') : 'BDT'),
        ];
    }

    private function customerOptions(): Collection
    {
        return DB::table('customers')
            ->orderBy('name')
            ->limit(500)
            ->get(['id', 'name', 'full_name', 'phone'])
            ->map(fn($row): array => [
                'id' => (int) $row->id,
                'name' => $this->customerLabel($row),
            ]);
    }

    private function customerNames(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if ($ids === [] || !Schema::hasTable('customers')) {
            return [];
        }

        return DB::table('customers')
            ->whereIn('id', $ids)
            ->get(['id', 'name', 'full_name', 'phone'])
            ->mapWithKeys(fn($row): array => [(int) $row->id => $this->customerLabel($row)])
            ->all();
    }

    private function campaignOptions(): Collection
    {
        return FbmCampaign::query()->where('is_available', true)->orderBy('name')->limit(500)->get(['id', 'name'])
            ->map(fn(FbmCampaign $campaign): array => ['id' => (int) $campaign->id, 'name' => $campaign->name ?: 'Unnamed Campaign']);
    }

    private function adSetOptions(): Collection
    {
        return FbmAdSet::query()->where('is_available', true)->orderBy('name')->limit(500)->get(['id', 'name'])
            ->map(fn(FbmAdSet $adSet): array => ['id' => (int) $adSet->id, 'name' => $adSet->name ?: 'Unnamed Ad Set']);
    }

    private function adOptions(): Collection
    {
        return FbmAd::query()->where('is_available', true)->orderBy('name')->limit(500)->get(['id', 'name'])
            ->map(fn(FbmAd $ad): array => ['id' => (int) $ad->id, 'name' => $ad->name ?: 'Unnamed Ad']);
    }

    private function safeFilters(array $filters, array $range, Collection $customers): array
    {
        return [
            'from_date' => $range['from']->toDateString(),
            'to_date' => $range['to']->toDateString(),
            'mode' => array_key_exists((string) ($filters['mode'] ?? ''), $this->modeOptions()) ? (string) $filters['mode'] : null,
            'status' => array_key_exists((string) ($filters['status'] ?? ''), $this->statusOptions()) ? (string) $filters['status'] : null,
            'customer_id' => $this->validId($filters['customer_id'] ?? null, $customers),
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

    private function nextJobCode(): string
    {
        return 'FBMJ-' . now()->format('ymdHis') . '-' . random_int(100, 999);
    }

    private function modeOptions(): array
    {
        return ['own_store' => 'Own store', 'client_boosting' => 'Client boosting'];
    }

    private function statusOptions(): array
    {
        return ['draft' => 'Draft', 'active' => 'Active', 'paused' => 'Paused', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];
    }

    private function paymentTypeOptions(): array
    {
        return ['advance' => 'Advance', 'client_payment' => 'Client payment', 'refund' => 'Refund', 'adjustment' => 'Adjustment'];
    }

    private function costTypeOptions(): array
    {
        return ['creative_cost' => 'Creative cost', 'agency_cost' => 'Agency cost', 'vat_tax' => 'VAT / tax', 'service_charge' => 'Service charge', 'other' => 'Other'];
    }

    private function customerLabel($row): string
    {
        $name = trim((string) ($row->full_name ?: $row->name ?: 'Customer #' . $row->id));
        $phone = trim((string) ($row->phone ?? ''));

        return $phone !== '' ? $name . ' (' . $phone . ')' : $name;
    }

    private function currency($value): string
    {
        $value = is_scalar($value) ? strtoupper(trim((string) $value)) : '';

        return $value !== '' && preg_match('/^[A-Z]{3}$/', $value) ? $value : 'BDT';
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

    private function maxRows(): int
    {
        return max(100, min(5000, (int) config('fb_marketing.boosting_jobs.max_rows', 1000)));
    }

    private function warning(string $type, string $message): array
    {
        return ['type' => $type, 'message' => $message];
    }
}

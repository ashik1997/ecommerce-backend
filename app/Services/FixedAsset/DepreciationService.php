<?php

namespace App\Services\FixedAsset;

use App\Models\FixedAsset\FixedAsset;
use App\Models\FixedAsset\FixedAssetDepreciationEntry;
use App\Models\FixedAsset\FixedAssetDepreciationRun;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DepreciationService
{
    public function __construct(private FixedAssetAccountingPostingService $postingService)
    {
    }

    public function preview(string $period, ?int $warehouseId = null): array
    {
        $query = FixedAsset::where('lifecycle_status', 'capitalized')
            ->whereNotIn('operational_status', ['retired'])
            ->where('depreciation_method', '!=', 'none')
            ->whereNotNull('useful_life_months')
            ->where('useful_life_months', '>', 0);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        $rows = [];
        $total = 0;

        foreach ($query->get() as $asset) {
            $opening = (float) $asset->carrying_amount;
            $monthly = max(0, ((float) $asset->capitalized_cost - (float) $asset->residual_value) / max(1, (int) $asset->useful_life_months));
            $amount = min($monthly, max(0, $opening - (float) $asset->residual_value));

            if ($amount <= 0) {
                continue;
            }

            $rows[] = [
                'asset' => $asset,
                'opening_book_value' => $opening,
                'depreciation_amount' => round($amount, 4),
                'closing_book_value' => round($opening - $amount, 4),
            ];

            $total += $amount;
        }

        return ['rows' => $rows, 'total' => round($total, 4)];
    }

    public function post(string $period, ?int $warehouseId = null): FixedAssetDepreciationRun
    {
        return DB::transaction(function () use ($period, $warehouseId) {
            $exists = FixedAssetDepreciationRun::where('period', $period)
                ->where('warehouse_id', $warehouseId)
                ->whereIn('status', ['posted', 'approved'])
                ->exists();

            if ($exists) {
                throw new RuntimeException('Depreciation already posted for this period and warehouse.');
            }

            $preview = $this->preview($period, $warehouseId);
            if ((float) $preview['total'] <= 0) {
                throw new RuntimeException('No depreciable asset found for this period.');
            }

            $run = FixedAssetDepreciationRun::create([
                'run_no' => 'DEP-' . now()->format('YmdHis'),
                'period' => $period,
                'warehouse_id' => $warehouseId,
                'run_date' => now()->toDateString(),
                'total_depreciation' => $preview['total'],
                'status' => 'posted',
                'created_by' => auth()->id(),
                'posted_by' => auth()->id(),
                'posted_at' => now(),
            ]);

            foreach ($preview['rows'] as $row) {
                $asset = $row['asset'];

                FixedAssetDepreciationEntry::create([
                    'depreciation_run_id' => $run->id,
                    'asset_id' => $asset->id,
                    'period' => $period,
                    'opening_book_value' => $row['opening_book_value'],
                    'depreciation_amount' => $row['depreciation_amount'],
                    'closing_book_value' => $row['closing_book_value'],
                ]);

                $asset->update([
                    'accumulated_depreciation' => (float) $asset->accumulated_depreciation + $row['depreciation_amount'],
                    'carrying_amount' => $row['closing_book_value'],
                ]);
            }

            $this->postingService->postDepreciationRun($run);

            return $run;
        });
    }

    public function reverse(FixedAssetDepreciationRun $run): void
    {
        DB::transaction(function () use ($run) {
            if ($run->status === 'reversed') {
                throw new RuntimeException('This depreciation run is already reversed.');
            }

            foreach (FixedAssetDepreciationEntry::where('depreciation_run_id', $run->id)->get() as $entry) {
                $asset = FixedAsset::find($entry->asset_id);
                if (! $asset) {
                    continue;
                }

                $asset->update([
                    'accumulated_depreciation' => max(0, (float) $asset->accumulated_depreciation - (float) $entry->depreciation_amount),
                    'carrying_amount' => (float) $asset->carrying_amount + (float) $entry->depreciation_amount,
                ]);
            }

            $this->postingService->reverseDepreciationRun($run);

            $run->update([
                'status' => 'reversed',
                'reversed_by' => auth()->id(),
                'reversed_at' => now(),
            ]);
        });
    }
}

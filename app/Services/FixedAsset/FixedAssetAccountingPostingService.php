<?php

namespace App\Services\FixedAsset;

use App\Http\Controllers\Account\Models\AcAccount;
use App\Http\Controllers\Account\Models\AcTransaction;
use App\Models\FixedAsset\FixedAsset;
use App\Models\FixedAsset\FixedAssetAccountMapping;
use App\Models\FixedAsset\FixedAssetDepreciationRun;
use App\Models\FixedAsset\FixedAssetDisposal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class FixedAssetAccountingPostingService
{
    public function __construct(private FixedAssetAccountHeadService $headService)
    {
    }

    public function postCapitalization(FixedAsset $asset): ?AcTransaction
    {
        $amount = (float) ($asset->capitalized_cost ?: $asset->purchase_cost);
        if ($amount <= 0) {
            return null;
        }

        $heads = $this->headService->ensureRequiredHeads();
        $assetAccountId = $this->assetAccountIdFor($asset, $heads);
        $clearingAccountId = $this->accountId($heads, 'fa_acquisition_clearing');

        return $this->postOnce(
            'fixed_asset_capitalization',
            $assetAccountId,
            $clearingAccountId,
            $amount,
            'Fixed asset capitalization: ' . $asset->asset_code,
            $asset,
            'asset_capitalization',
            (int) $asset->id
        );
    }

    public function postDepreciationRun(FixedAssetDepreciationRun $run): ?AcTransaction
    {
        $amount = (float) $run->total_depreciation;
        if ($amount <= 0) {
            return null;
        }

        $heads = $this->headService->ensureRequiredHeads();

        return $this->postOnce(
            'fixed_asset_depreciation',
            $this->accountId($heads, 'fa_depreciation_expense'),
            $this->accountId($heads, 'fa_accumulated_depreciation'),
            $amount,
            'Fixed asset depreciation for period ' . $run->period,
            null,
            'depreciation_run',
            (int) $run->id
        );
    }

    public function reverseDepreciationRun(FixedAssetDepreciationRun $run): ?AcTransaction
    {
        $original = AcTransaction::where('event_type', 'fixed_asset_depreciation')
            ->where('ref_fixed_asset_source_type', 'depreciation_run')
            ->where('ref_fixed_asset_source_id', $run->id)
            ->where('status', 'active')
            ->first();

        if (! $original) {
            return null;
        }

        $alreadyReversed = AcTransaction::where('ref_fixed_asset_reversal_of', $original->id)
            ->where('status', 'active')
            ->exists();

        if ($alreadyReversed) {
            return null;
        }

        return $this->postTransaction([
            'event_type' => 'fixed_asset_depreciation_reverse',
            'debit_account_id' => (int) $original->credit_account_id,
            'credit_account_id' => (int) $original->debit_account_id,
            'amount' => (float) $original->debit_amt,
            'note' => 'Reverse fixed asset depreciation run: ' . ($run->run_no ?: $run->period),
            'asset_id' => null,
            'source_type' => 'depreciation_run',
            'source_id' => (int) $run->id,
            'reversal_of' => (int) $original->id,
        ]);
    }

    public function postDisposal(FixedAssetDisposal $disposal): array
    {
        $asset = FixedAsset::find($disposal->asset_id);
        if (! $asset) {
            throw new RuntimeException('Disposal asset not found.');
        }

        $heads = $this->headService->ensureRequiredHeads();
        $assetAccountId = $this->assetAccountIdFor($asset, $heads);
        $proceeds = round((float) $disposal->proceeds_amount, 4);
        $cost = round((float) ($asset->capitalized_cost ?: $asset->purchase_cost), 4);
        $accDep = round((float) $asset->accumulated_depreciation, 4);
        $gainLoss = round((float) $disposal->gain_loss_amount, 4);
        $transactions = [];

        if ($cost <= 0) {
            return [];
        }

        DB::transaction(function () use (&$transactions, $asset, $disposal, $heads, $assetAccountId, $proceeds, $accDep, $gainLoss) {
            // Dr Accumulated Depreciation / Cr Fixed Asset
            if ($accDep > 0) {
                $transactions[] = $this->postOnce(
                    'fixed_asset_disposal_accumulated_depreciation',
                    $this->accountId($heads, 'fa_accumulated_depreciation'),
                    $assetAccountId,
                    $accDep,
                    'Clear accumulated depreciation on disposal: ' . $asset->asset_code,
                    $asset,
                    'disposal',
                    (int) $disposal->id
                );
            }

            // Dr Clearing/Receivable / Cr Fixed Asset
            if ($proceeds > 0) {
                $transactions[] = $this->postOnce(
                    'fixed_asset_disposal_proceeds',
                    $this->accountId($heads, 'fa_acquisition_clearing'),
                    $assetAccountId,
                    $proceeds,
                    'Record proceeds receivable/clearing on disposal: ' . $asset->asset_code,
                    $asset,
                    'disposal',
                    (int) $disposal->id
                );
            }

            // Loss: Dr Loss / Cr Fixed Asset. Gain: Dr Fixed Asset / Cr Gain.
            if ($gainLoss < 0) {
                $transactions[] = $this->postOnce(
                    'fixed_asset_disposal_loss',
                    $this->accountId($heads, 'fa_disposal_loss'),
                    $assetAccountId,
                    abs($gainLoss),
                    'Loss on fixed asset disposal: ' . $asset->asset_code,
                    $asset,
                    'disposal',
                    (int) $disposal->id
                );
            } elseif ($gainLoss > 0) {
                $transactions[] = $this->postOnce(
                    'fixed_asset_disposal_gain',
                    $assetAccountId,
                    $this->accountId($heads, 'fa_disposal_gain'),
                    $gainLoss,
                    'Gain on fixed asset disposal: ' . $asset->asset_code,
                    $asset,
                    'disposal',
                    (int) $disposal->id
                );
            }
        });

        return array_values(array_filter($transactions));
    }

    public function postMaintenanceExpense(FixedAsset $asset, float $amount, string $sourceType, int $sourceId, string $note = ''): ?AcTransaction
    {
        if ($amount <= 0) {
            return null;
        }

        $heads = $this->headService->ensureRequiredHeads();

        return $this->postOnce(
            'fixed_asset_maintenance_expense',
            $this->accountId($heads, 'fa_repairs_maintenance_expense'),
            $this->accountId($heads, 'fa_acquisition_clearing'),
            $amount,
            $note ?: 'Fixed asset maintenance expense: ' . $asset->asset_code,
            $asset,
            $sourceType,
            $sourceId
        );
    }

    public function sourceAlreadyPosted(string $eventType, string $sourceType, int $sourceId, ?int $assetId = null): bool
    {
        return $this->existingFor($eventType, $sourceType, $sourceId, $assetId)->exists();
    }

    public function fixedAssetLedgerQuery()
    {
        return AcTransaction::query()
            ->with(['debitAccount', 'creditAccount'])
            ->where('transaction_type', 'Fixed Asset');
    }

    private function assetAccountIdFor(FixedAsset $asset, array $heads): int
    {
        $mapping = FixedAssetAccountMapping::where('event_key', 'capitalization')
            ->where('category_id', $asset->category_id)
            ->where('is_active', true)
            ->first();

        if ($mapping && $mapping->debit_account_id) {
            $this->ensureAccountUsable((int) $mapping->debit_account_id, 'Mapped fixed asset account');
            return (int) $mapping->debit_account_id;
        }

        return $this->accountId($heads, 'fa_other_assets');
    }

    private function postOnce(string $eventType, int $debitAccountId, int $creditAccountId, float $amount, string $note, ?FixedAsset $asset, string $sourceType, int $sourceId): AcTransaction
    {
        return DB::transaction(function () use ($eventType, $debitAccountId, $creditAccountId, $amount, $note, $asset, $sourceType, $sourceId) {
            $existing = $this->existingFor($eventType, $sourceType, $sourceId, $asset?->id)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            return $this->postTransaction([
                'event_type' => $eventType,
                'debit_account_id' => $debitAccountId,
                'credit_account_id' => $creditAccountId,
                'amount' => $amount,
                'note' => $note,
                'asset_id' => $asset?->id,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
            ]);
        });
    }

    private function existingFor(string $eventType, string $sourceType, int $sourceId, ?int $assetId = null)
    {
        return AcTransaction::where('event_type', $eventType)
            ->where('ref_fixed_asset_source_type', $sourceType)
            ->where('ref_fixed_asset_source_id', $sourceId)
            ->where('status', 'active')
            ->when($assetId, fn ($query) => $query->where('ref_fixed_asset_id', $assetId));
    }

    private function postTransaction(array $payload): AcTransaction
    {
        $amount = round((float) $payload['amount'], 4);
        if ($amount <= 0) {
            throw new RuntimeException('Accounting amount must be greater than zero.');
        }

        $debitAccountId = (int) $payload['debit_account_id'];
        $creditAccountId = (int) $payload['credit_account_id'];

        if ($debitAccountId === $creditAccountId) {
            throw new RuntimeException('Debit and credit account cannot be same for fixed asset posting.');
        }

        $this->ensureAccountUsable($debitAccountId, 'Debit account');
        $this->ensureAccountUsable($creditAccountId, 'Credit account');

        $data = [
            'transaction_date' => now()->toDateString(),
            'transaction_type' => 'Fixed Asset',
            'event_type' => $payload['event_type'],
            'debit_account_id' => $debitAccountId,
            'credit_account_id' => $creditAccountId,
            'debit_amt' => $amount,
            'credit_amt' => $amount,
            'note' => $payload['note'] ?? null,
            'ref_fixed_asset_id' => $payload['asset_id'] ?? null,
            'ref_fixed_asset_source_type' => $payload['source_type'] ?? null,
            'ref_fixed_asset_source_id' => $payload['source_id'] ?? null,
            'ref_fixed_asset_reversal_of' => $payload['reversal_of'] ?? null,
            'created_by' => (string) (auth()->id() ?: ''),
            'created_date' => now()->toDateString(),
            'creator' => auth()->id() ?: null,
            'slug' => uniqid('fa-journal-', true),
            'status' => 'active',
        ];

        return AcTransaction::create($this->onlyExistingColumns('ac_transactions', $data));
    }

    private function ensureAccountUsable(int $accountId, string $label): void
    {
        $account = AcAccount::find($accountId);

        if (! $account) {
            throw new RuntimeException($label . ' not found. Please run Fixed Asset installer from settings.');
        }

        if (($account->status ?? 'active') !== 'active') {
            throw new RuntimeException($label . ' is inactive: ' . $account->account_name);
        }

        if (isset($account->delete_bit) && (int) $account->delete_bit === 1) {
            throw new RuntimeException($label . ' is deleted/disabled: ' . $account->account_name);
        }
    }

    private function accountId(array $heads, string $key): int
    {
        if (! isset($heads[$key]) || ! $heads[$key]) {
            throw new RuntimeException('Required fixed asset account head missing: ' . $key);
        }

        $id = (int) $heads[$key]->id;
        $this->ensureAccountUsable($id, 'Required account head ' . $key);

        return $id;
    }

    private function onlyExistingColumns(string $table, array $data): array
    {
        if (! Schema::hasTable($table)) {
            return $data;
        }

        return collect($data)
            ->filter(fn ($value, $column) => Schema::hasColumn($table, $column))
            ->all();
    }
}

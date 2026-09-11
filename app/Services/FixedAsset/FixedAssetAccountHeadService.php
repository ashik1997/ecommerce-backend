<?php

namespace App\Services\FixedAsset;

use App\Http\Controllers\Account\Models\AcAccount;
use Illuminate\Support\Str;

class FixedAssetAccountHeadService
{

    public function requiredHeadKeys(): array
    {
        return [
            'fa_land',
            'fa_buildings',
            'fa_furniture_fixtures',
            'fa_office_equipment',
            'fa_it_equipment',
            'fa_electrical_equipment',
            'fa_vehicles',
            'fa_machinery_equipment',
            'fa_cwip',
            'fa_other_assets',
            'fa_accumulated_depreciation',
            'fa_accumulated_impairment',
            'fa_acquisition_clearing',
            'fa_depreciation_expense',
            'fa_repairs_maintenance_expense',
            'fa_disposal_loss',
            'fa_impairment_loss',
            'fa_disposal_gain',
            'fa_revaluation_surplus',
        ];
    }

    public function installStatus(): array
    {
        $keys = $this->requiredHeadKeys();
        $existing = AcAccount::whereIn('account_selection_name', $keys)
            ->where(function ($query) {
                $query->whereNull('delete_bit')->orWhere('delete_bit', 0);
            })
            ->pluck('account_selection_name')
            ->filter()
            ->values()
            ->all();

        $missing = array_values(array_diff($keys, $existing));

        return [
            'installed' => count($missing) === 0,
            'total' => count($keys),
            'existing' => count($existing),
            'missing' => $missing,
            'missing_count' => count($missing),
        ];
    }

    public function isInstalled(): bool
    {
        return $this->installStatus()['installed'];
    }

    public function ensureRequiredHeads(): array
    {
        $assets = $this->ensure('assets', 'AC-1000', 'Assets', 'asset', 'debit', null, true, true);
        $currentAssets = $this->ensure('current_assets', 'AC-1100', 'Current Assets', 'asset', 'debit', $assets->id, true, false);
        $fixedAssets = $this->ensure('fixed_assets', 'AC-1200', 'Fixed Assets', 'asset', 'debit', $assets->id, true, true);
        $expenses = $this->ensure('expenses', 'AC-5000', 'Expenses', 'expense', 'debit', null, true, true);
        $operatingExpenses = $this->ensure(null, 'AC-5400', 'Operating Expenses', 'expense', 'debit', $expenses->id, true, false);
        $otherIncome = $this->ensure(null, 'AC-4200', 'Other Income', 'revenue', 'credit', null, true, false);
        $equity = $this->ensure('equity', 'AC-3000', "Owner's Equity", 'equity', 'credit', null, true, true);

        $heads = [
            'fa_land' => $this->ensure('fa_land', 'AC-1210', 'Land', 'asset', 'debit', $fixedAssets->id),
            'fa_buildings' => $this->ensure('fa_buildings', 'AC-1220', 'Buildings', 'asset', 'debit', $fixedAssets->id),
            'fa_furniture_fixtures' => $this->ensure('fa_furniture_fixtures', 'AC-1230', 'Furniture & Fixtures', 'asset', 'debit', $fixedAssets->id),
            'fa_office_equipment' => $this->ensure('fa_office_equipment', 'AC-1240', 'Office Equipment', 'asset', 'debit', $fixedAssets->id),
            'fa_it_equipment' => $this->ensure('fa_it_equipment', 'AC-1250', 'IT Equipment', 'asset', 'debit', $fixedAssets->id),
            'fa_electrical_equipment' => $this->ensure('fa_electrical_equipment', 'AC-1260', 'Electrical Equipment', 'asset', 'debit', $fixedAssets->id),
            'fa_vehicles' => $this->ensure('fa_vehicles', 'AC-1270', 'Vehicles', 'asset', 'debit', $fixedAssets->id),
            'fa_machinery_equipment' => $this->ensure('fa_machinery_equipment', 'AC-1280', 'Machinery & Equipment', 'asset', 'debit', $fixedAssets->id),
            'fa_cwip' => $this->ensure('fa_cwip', 'AC-1290', 'Capital Work in Progress', 'asset', 'debit', $fixedAssets->id),
            'fa_other_assets' => $this->ensure('fa_other_assets', 'AC-1295', 'Other Fixed Assets', 'asset', 'debit', $fixedAssets->id),
            'fa_accumulated_depreciation' => $this->ensure('fa_accumulated_depreciation', 'AC-1300', 'Accumulated Depreciation', 'asset', 'credit', $assets->id, true, true),
            'fa_accumulated_impairment' => $this->ensure('fa_accumulated_impairment', 'AC-1400', 'Accumulated Impairment Losses', 'asset', 'credit', $assets->id, true, true),
            'fa_acquisition_clearing' => $this->ensure('fa_acquisition_clearing', 'AC-1140', 'Asset Acquisition Clearing', 'asset', 'debit', $currentAssets->id),
            'fa_depreciation_expense' => $this->ensure('fa_depreciation_expense', 'AC-5490', 'Depreciation Expense', 'expense', 'debit', $operatingExpenses->id),
            'fa_repairs_maintenance_expense' => $this->ensure('fa_repairs_maintenance_expense', 'AC-5491', 'Repairs & Maintenance Expense', 'expense', 'debit', $operatingExpenses->id),
            'fa_disposal_loss' => $this->ensure('fa_disposal_loss', 'AC-5492', 'Loss on Disposal of Fixed Assets', 'expense', 'debit', $operatingExpenses->id),
            'fa_impairment_loss' => $this->ensure('fa_impairment_loss', 'AC-5493', 'Impairment Loss on Fixed Assets', 'expense', 'debit', $operatingExpenses->id),
            'fa_disposal_gain' => $this->ensure('fa_disposal_gain', 'AC-4210', 'Gain on Disposal of Fixed Assets', 'revenue', 'credit', $otherIncome->id),
            'fa_revaluation_surplus' => $this->ensure('fa_revaluation_surplus', 'AC-3500', 'Revaluation Surplus', 'equity', 'credit', $equity->id),
        ];

        return $heads;
    }

    private function ensure(?string $key, string $code, string $name, string $type, string $normalBalance, ?int $parentId = null, bool $system = true, bool $control = false): AcAccount
    {
        $account = null;
        if ($key) {
            $account = AcAccount::where('account_selection_name', $key)->first();
        }
        if (!$account) {
            $account = AcAccount::where('account_code', $code)->orWhere('sort_code', str_replace('AC-', '', $code))->first();
        }
        if ($account) {
            $updates = [];
            if ($key && empty($account->account_selection_name)) $updates['account_selection_name'] = $key;
            if ($parentId && (int)$account->parent_id === 0) $updates['parent_id'] = $parentId;
            if ($updates) $account->update($updates);
            return $account;
        }

        return AcAccount::create([
            'parent_id' => $parentId ?: 0,
            'account_type' => $type,
            'normal_balance' => $normalBalance,
            'is_system_account' => $system ? 1 : 0,
            'is_control_account' => $control ? 1 : 0,
            'sort_code' => str_replace('AC-', '', $code),
            'account_name' => $name,
            'account_code' => $code,
            'short_code' => str_replace('AC-', '', $code),
            'balance' => 0,
            'delete_bit' => 0,
            'account_selection_name' => $key,
            'creator' => auth()->id(),
            'slug' => Str::slug($name . '-' . time()),
            'status' => 'active',
        ]);
    }
}

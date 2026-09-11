<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CommissionAccountingSeeder extends Seeder
{
    public function run()
    {
        $expenseParentId = DB::table('ac_accounts')
            ->where('account_type', 'expense')
            ->where(function ($query) {
                $query->where('account_selection_name', 'expenses')
                    ->orWhere('account_name', 'Expenses')
                    ->orWhere('account_name', 'Expense');
            })
            ->value('id');

        if (! $expenseParentId) {
            $expenseParentId = DB::table('ac_accounts')->where('account_type', 'expense')->orderBy('id')->value('id');
        }

        $liabilityParentId = DB::table('ac_accounts')
            ->where('account_selection_name', 'current_liabilities')
            ->orWhere('account_name', 'Current Liabilities')
            ->value('id');

        if (! $liabilityParentId) {
            $liabilityParentId = DB::table('ac_accounts')->where('account_type', 'liability')->orderBy('id')->value('id');
        }

        $this->upsertAccount([
            'parent_id' => $expenseParentId,
            'account_type' => 'expense',
            'normal_balance' => 'debit',
            'account_name' => 'Sales Commission Expense',
            'account_code' => 'AC-COM-EXP',
            'short_code' => 'COMEXP',
            'account_selection_name' => 'sales_commission_expense',
            'is_system_account' => 1,
            'is_control_account' => 0,
            'status' => 'active',
        ]);

        $this->upsertAccount([
            'parent_id' => $liabilityParentId,
            'account_type' => 'liability',
            'normal_balance' => 'credit',
            'account_name' => 'Commission Payable',
            'account_code' => 'AC-COM-PAY',
            'short_code' => 'COMPAY',
            'account_selection_name' => 'commission_payable',
            'is_system_account' => 1,
            'is_control_account' => 0,
            'status' => 'active',
        ]);
    }

    protected function upsertAccount(array $data): void
    {
        $existing = DB::table('ac_accounts')
            ->where('account_selection_name', $data['account_selection_name'])
            ->first();

        $payload = array_merge($data, [
            'updated_at' => now(),
        ]);

        if ($existing) {
            DB::table('ac_accounts')->where('id', $existing->id)->update($payload);
            return;
        }

        DB::table('ac_accounts')->insert(array_merge($payload, [
            'balance' => 0,
            'creator' => auth()->id(),
            'created_at' => now(),
        ]));
    }
}

<?php

namespace App\Http\Controllers\Report\Actions;

use App\Models\AccountTransaction;
use App\Http\Controllers\Account\Models\AcAccount;
use Carbon\Carbon;

class ProfitLossReportAction extends ReportAction
{
    public function run(array $filters): array
    {
        $dateFrom = !empty($filters['date_from']) ? Carbon::parse($filters['date_from'])->toDateString() : null;
        $dateTo = !empty($filters['date_to']) ? Carbon::parse($filters['date_to'])->toDateString() : null;

        // ac_accounts.account_type uses "revenue", not "income".
        $revenueAccounts = AcAccount::where('account_type', 'revenue')
            ->where('status', 'active')
            ->pluck('id');

        $expenseAccounts = AcAccount::where('account_type', 'expense')
            ->where('status', 'active')
            ->pluck('id');

        /*
         * Accounting date should come from transaction_date.
         * Revenue has credit normal balance: net = credit - debit.
         * Expense has debit normal balance: net = debit - credit.
         */
        $totalRevenue = $this->netAmountForAccounts($revenueAccounts, $dateFrom, $dateTo, 'credit');
        $totalExpenses = $this->netAmountForAccounts($expenseAccounts, $dateFrom, $dateTo, 'debit');

        $profitLoss = $totalRevenue - $totalExpenses;

        $data = [
            [
                'item' => 'Revenue',
                'amount' => $totalRevenue,
            ],
            [
                'item' => 'Expenses',
                'amount' => $totalExpenses,
            ],
            [
                'item' => $profitLoss >= 0 ? 'Net Profit' : 'Net Loss',
                'amount' => $profitLoss,
            ],
        ];

        $summary = [
            'total_revenue' => $totalRevenue,
            'total_expenses' => $totalExpenses,
            'profit_loss' => $profitLoss,
            'is_profit' => $profitLoss >= 0,
        ];

        return [
            'data' => $data,
            'summary' => $summary,
        ];
    }

    public function getTitle(): string
    {
        return 'Profit Loss Report';
    }

    public function getCsvHeaders(): array
    {
        return ['Item', 'Amount'];
    }

    public function formatForCsv(array $data): array
    {
        $rows = [];
        foreach ($data as $item) {
            $rows[] = [
                $item['item'] ?? '',
                $item['amount'] ?? 0,
            ];
        }

        return $rows;
    }

    private function netAmountForAccounts($accountIds, ?string $dateFrom, ?string $dateTo, string $normalBalance): float
    {
        if ($accountIds->isEmpty()) {
            return 0;
        }

        $query = AccountTransaction::query()
            ->where('status', 'active')
            ->where(function ($query) use ($accountIds) {
                $query->whereIn('debit_account_id', $accountIds)
                    ->orWhereIn('credit_account_id', $accountIds);
            });

        if ($dateFrom) {
            $query->where('transaction_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('transaction_date', '<=', $dateTo);
        }

        $debitTotal = (float) (clone $query)
            ->whereIn('debit_account_id', $accountIds)
            ->sum('debit_amt');

        $creditTotal = (float) (clone $query)
            ->whereIn('credit_account_id', $accountIds)
            ->sum('credit_amt');

        return $normalBalance === 'credit'
            ? $creditTotal - $debitTotal
            : $debitTotal - $creditTotal;
    }
}

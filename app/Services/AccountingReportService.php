<?php

namespace App\Services;

use App\Http\Controllers\Account\Models\AcAccount;
use App\Http\Controllers\Account\Models\AcTransaction;
use App\Http\Controllers\Customer\Models\Customer;
use App\Http\Controllers\Inventory\Models\ProductSupplier;
use Carbon\Carbon;

class AccountingReportService
{
    public function activeAccountQuery()
    {
        return AcAccount::query()->where('status', 'active');
    }

    public function activeTransactionQuery(array $filters = [])
    {
        $query = AcTransaction::query()->where('status', 'active');

        $dateFrom = $filters['date_from'] ?? $filters['start_date'] ?? null;
        $dateTo = $filters['date_to'] ?? $filters['end_date'] ?? null;

        if ($dateFrom) {
            $query->where('transaction_date', '>=', Carbon::parse($dateFrom)->toDateString());
        }

        if ($dateTo) {
            $query->where('transaction_date', '<=', Carbon::parse($dateTo)->toDateString());
        }

        foreach (['store_id', 'customer_id', 'supplier_id', 'transaction_type', 'event_type'] as $field) {
            if (!empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }

        if (!empty($filters['account_id'])) {
            $accountId = (int) $filters['account_id'];
            $query->where(function ($query) use ($accountId) {
                $query->where('debit_account_id', $accountId)
                    ->orWhere('credit_account_id', $accountId);
            });
        }

        return $query;
    }

    public function normalBalanceForAccount(AcAccount $account): string
    {
        if ($account->normal_balance) {
            return $account->normal_balance;
        }

        return in_array($account->account_type, ['asset', 'expense'], true) ? 'debit' : 'credit';
    }

    public function accountIdsByType($types)
    {
        $types = is_array($types) ? $types : [$types];

        return $this->activeAccountQuery()
            ->whereIn('account_type', $types)
            ->pluck('id');
    }

    public function accountIdsBySelectionNames(array $selectionNames)
    {
        return $this->activeAccountQuery()
            ->whereIn('account_selection_name', $selectionNames)
            ->pluck('id');
    }

    public function debitCreditForAccount(int $accountId, array $filters = []): array
    {
        $debit = (float) (clone $this->activeTransactionQuery($filters))
            ->where('debit_account_id', $accountId)
            ->sum('debit_amt');

        $credit = (float) (clone $this->activeTransactionQuery($filters))
            ->where('credit_account_id', $accountId)
            ->sum('credit_amt');

        return [
            'debit' => $debit,
            'credit' => $credit,
        ];
    }

    public function balanceForAccount(AcAccount $account, array $filters = []): float
    {
        $totals = $this->debitCreditForAccount((int) $account->id, $filters);

        return $this->normalBalanceForAccount($account) === 'debit'
            ? $totals['debit'] - $totals['credit']
            : $totals['credit'] - $totals['debit'];
    }

    public function openingBalanceForAccount(AcAccount $account, ?string $beforeDate, array $filters = []): float
    {
        if (!$beforeDate) {
            return 0;
        }

        unset($filters['date_from'], $filters['start_date'], $filters['date_to'], $filters['end_date']);
        $filters['date_to'] = Carbon::parse($beforeDate)->subDay()->toDateString();

        return $this->balanceForAccount($account, $filters);
    }

    public function transactionRowsForAccount(int $accountId, array $filters = [])
    {
        return $this->activeTransactionQuery($filters)
            ->where(function ($query) use ($accountId) {
                $query->where('debit_account_id', $accountId)
                    ->orWhere('credit_account_id', $accountId);
            })
            ->with(['debitAccount', 'creditAccount'])
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();
    }

    public function trialBalance(array $filters = []): array
    {
        $rows = [];
        $totals = [
            'debit_total' => 0,
            'credit_total' => 0,
            'debit_balance' => 0,
            'credit_balance' => 0,
        ];

        $accounts = $this->activeAccountQuery()
            ->orderBy('sort_code')
            ->orderBy('account_name')
            ->get();

        foreach ($accounts as $account) {
            $dc = $this->debitCreditForAccount((int) $account->id, $filters);
            $normalBalance = $this->normalBalanceForAccount($account);
            $balance = $normalBalance === 'debit'
                ? $dc['debit'] - $dc['credit']
                : $dc['credit'] - $dc['debit'];

            $debitBalance = 0;
            $creditBalance = 0;

            if ($normalBalance === 'debit') {
                $debitBalance = $balance >= 0 ? $balance : 0;
                $creditBalance = $balance < 0 ? abs($balance) : 0;
            } else {
                $creditBalance = $balance >= 0 ? $balance : 0;
                $debitBalance = $balance < 0 ? abs($balance) : 0;
            }

            $rows[] = [
                'account' => $account,
                'debit_total' => $dc['debit'],
                'credit_total' => $dc['credit'],
                'debit_balance' => $debitBalance,
                'credit_balance' => $creditBalance,
            ];

            $totals['debit_total'] += $dc['debit'];
            $totals['credit_total'] += $dc['credit'];
            $totals['debit_balance'] += $debitBalance;
            $totals['credit_balance'] += $creditBalance;
        }

        $totals['is_matched'] = abs($totals['debit_total'] - $totals['credit_total']) < 0.01
            && abs($totals['debit_balance'] - $totals['credit_balance']) < 0.01;

        return [
            'rows' => $rows,
            'totals' => $totals,
        ];
    }

    public function profitLoss(array $filters = []): array
    {
        $revenueRows = $this->accountRowsByType('revenue', $filters);
        $expenseRows = $this->accountRowsByType('expense', $filters);

        $cogsRows = [];
        $operatingExpenseRows = [];

        foreach ($expenseRows as $row) {
            $account = $row['account'];
            $name = strtolower((string) $account->account_name);
            $selectionName = strtolower((string) $account->account_selection_name);
            $isCogs = $selectionName === 'cogs' || str_contains($name, 'cost of goods');

            if ($isCogs) {
                $cogsRows[] = $row;
            } else {
                $operatingExpenseRows[] = $row;
            }
        }

        $totalRevenue = array_sum(array_column($revenueRows, 'amount'));
        $totalCogs = array_sum(array_column($cogsRows, 'amount'));
        $grossProfit = $totalRevenue - $totalCogs;
        $totalOperatingExpense = array_sum(array_column($operatingExpenseRows, 'amount'));
        $netProfit = $grossProfit - $totalOperatingExpense;

        return compact(
            'revenueRows',
            'cogsRows',
            'operatingExpenseRows',
            'totalRevenue',
            'totalCogs',
            'grossProfit',
            'totalOperatingExpense',
            'netProfit'
        );
    }

    public function balanceSheet(array $filters = []): array
    {
        $dateTo = $filters['date_to'] ?? $filters['end_date'] ?? now()->toDateString();
        unset($filters['date_from'], $filters['start_date']);
        $filters['date_to'] = $dateTo;

        $assets = $this->accountRowsByType('asset', $filters);
        $liabilities = $this->accountRowsByType('liability', $filters);
        $equity = $this->accountRowsByType('equity', $filters);
        $profitLoss = $this->profitLoss($filters);

        $totalAssets = array_sum(array_column($assets, 'amount'));
        $totalLiabilities = array_sum(array_column($liabilities, 'amount'));
        $totalEquity = array_sum(array_column($equity, 'amount')) + $profitLoss['netProfit'];
        $totalLiabilitiesAndEquity = $totalLiabilities + $totalEquity;

        return [
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'net_profit' => $profitLoss['netProfit'],
            'total_assets' => $totalAssets,
            'total_liabilities' => $totalLiabilities,
            'total_equity' => $totalEquity,
            'total_liabilities_and_equity' => $totalLiabilitiesAndEquity,
            'difference' => $totalAssets - $totalLiabilitiesAndEquity,
        ];
    }

    public function cashBankBook(array $filters = []): array
    {
        $accountId = $filters['account_id'] ?? null;
        $cashAccounts = $accountId ? collect([(int) $accountId]) : $this->cashBankAccountIds();
        $rows = [];
        $openingBalance = 0;

        if (!empty($filters['date_from']) || !empty($filters['start_date'])) {
            $openingFilters = $filters;
            unset($openingFilters['date_from'], $openingFilters['start_date'], $openingFilters['date_to'], $openingFilters['end_date'], $openingFilters['account_id']);
            $openingFilters['date_to'] = Carbon::parse($filters['date_from'] ?? $filters['start_date'])->subDay()->toDateString();

            $openingDebit = (float) $this->activeTransactionQuery($openingFilters)
                ->whereIn('debit_account_id', $cashAccounts)
                ->sum('debit_amt');
            $openingCredit = (float) $this->activeTransactionQuery($openingFilters)
                ->whereIn('credit_account_id', $cashAccounts)
                ->sum('credit_amt');

            $openingBalance = $openingDebit - $openingCredit;
        }

        $runningBalance = $openingBalance;

        $transactions = $this->activeTransactionQuery($filters)
            ->where(function ($query) use ($cashAccounts) {
                $query->whereIn('debit_account_id', $cashAccounts)
                    ->orWhereIn('credit_account_id', $cashAccounts);
            })
            ->with(['debitAccount', 'creditAccount'])
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

        foreach ($transactions as $transaction) {
            $inflow = $cashAccounts->contains((int) $transaction->debit_account_id)
                ? (float) $transaction->debit_amt
                : 0;
            $outflow = $cashAccounts->contains((int) $transaction->credit_account_id)
                ? (float) $transaction->credit_amt
                : 0;
            $runningBalance += $inflow - $outflow;

            $rows[] = [
                'transaction' => $transaction,
                'account_name' => $inflow > 0
                    ? ($transaction->debitAccount->account_name ?? '')
                    : ($transaction->creditAccount->account_name ?? ''),
                'opposite_account_name' => $inflow > 0
                    ? ($transaction->creditAccount->account_name ?? '')
                    : ($transaction->debitAccount->account_name ?? ''),
                'inflow' => $inflow,
                'outflow' => $outflow,
                'running_balance' => $runningBalance,
            ];
        }

        return [
            'rows' => $rows,
            'opening_balance' => $openingBalance,
            'total_inflow' => array_sum(array_column($rows, 'inflow')),
            'total_outflow' => array_sum(array_column($rows, 'outflow')),
            'closing_balance' => $runningBalance,
        ];
    }

    public function cashBankAccountIds()
    {
        $paymentTypeAccounts = $this->activeAccountQuery()
            ->where('account_type', 'asset')
            ->whereNotNull('paymenttypes_id')
            ->pluck('id');

        $cashBankAccounts = $this->activeAccountQuery()
            ->where('account_type', 'asset')
            ->where(function ($query) {
                $query->where('account_selection_name', 'like', '%cash%')
                    ->orWhere('account_selection_name', 'like', '%bank%')
                    ->orWhere('account_name', 'like', '%Cash%')
                    ->orWhere('account_name', 'like', '%Bank%');
            })
            ->pluck('id');

        return $paymentTypeAccounts
            ->merge($cashBankAccounts)
            ->unique()
            ->values();
    }

    public function accountsPayableAccountIds()
    {
        $accountsPayable = $this->accountIdsBySelectionNames(['accounts_payable']);

        if ($accountsPayable->isNotEmpty()) {
            return $accountsPayable;
        }

        return $this->activeAccountQuery()
            ->where('account_type', 'liability')
            ->where(function ($query) {
                $query->where('account_name', 'like', '%Payable%')
                    ->orWhere('account_name', 'like', '%Supplier%');
            })
            ->pluck('id');
    }

    public function supplierLedger(array $filters = []): array
    {
        $payableAccountIds = $this->accountsPayableAccountIds();
        $openingBalance = 0;
        $rows = [];

        if (!empty($filters['date_from']) || !empty($filters['start_date'])) {
            $openingFilters = $filters;
            unset($openingFilters['date_from'], $openingFilters['start_date'], $openingFilters['date_to'], $openingFilters['end_date'], $openingFilters['account_id']);
            $openingFilters['date_to'] = Carbon::parse($filters['date_from'] ?? $filters['start_date'])->subDay()->toDateString();

            $openingDebit = (float) $this->activeTransactionQuery($openingFilters)
                ->whereIn('debit_account_id', $payableAccountIds)
                ->sum('debit_amt');
            $openingCredit = (float) $this->activeTransactionQuery($openingFilters)
                ->whereIn('credit_account_id', $payableAccountIds)
                ->sum('credit_amt');

            $openingBalance = $openingCredit - $openingDebit;
        }

        $runningBalance = $openingBalance;
        $transactions = $this->activeTransactionQuery($filters)
            ->where(function ($query) use ($payableAccountIds) {
                $query->whereIn('debit_account_id', $payableAccountIds)
                    ->orWhereIn('credit_account_id', $payableAccountIds);
            })
            ->with(['debitAccount', 'creditAccount'])
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();
        $supplierNames = ProductSupplier::whereIn('id', $transactions->pluck('supplier_id')->filter()->unique())
            ->pluck('name', 'id');

        foreach ($transactions as $transaction) {
            $debit = $payableAccountIds->contains((int) $transaction->debit_account_id)
                ? (float) $transaction->debit_amt
                : 0;
            $credit = $payableAccountIds->contains((int) $transaction->credit_account_id)
                ? (float) $transaction->credit_amt
                : 0;

            $runningBalance += $credit - $debit;

            $rows[] = [
                'transaction' => $transaction,
                'supplier_name' => $supplierNames[$transaction->supplier_id] ?? '',
                'debit' => $debit,
                'credit' => $credit,
                'running_balance' => $runningBalance,
                'payable_account_name' => $credit > 0
                    ? ($transaction->creditAccount->account_name ?? '')
                    : ($transaction->debitAccount->account_name ?? ''),
                'opposite_account_name' => $credit > 0
                    ? ($transaction->debitAccount->account_name ?? '')
                    : ($transaction->creditAccount->account_name ?? ''),
            ];
        }

        return [
            'rows' => $rows,
            'opening_balance' => $openingBalance,
            'total_debit' => array_sum(array_column($rows, 'debit')),
            'total_credit' => array_sum(array_column($rows, 'credit')),
            'closing_balance' => $runningBalance,
        ];
    }

    public function supplierDueSummary(array $filters = []): array
    {
        $payableAccountIds = $this->accountsPayableAccountIds();
        $rows = [];
        $totals = [
            'payable_credit' => 0,
            'paid_debit' => 0,
            'current_due' => 0,
        ];

        $supplierQuery = ProductSupplier::query()->where('status', 'active');
        if (!empty($filters['supplier_id'])) {
            $supplierQuery->where('id', $filters['supplier_id']);
        }

        $suppliers = $supplierQuery->orderBy('name')->get();

        foreach ($suppliers as $supplier) {
            $supplierFilters = $filters;
            $supplierFilters['supplier_id'] = $supplier->id;
            unset($supplierFilters['account_id']);

            $paidDebit = (float) $this->activeTransactionQuery($supplierFilters)
                ->whereIn('debit_account_id', $payableAccountIds)
                ->sum('debit_amt');
            $payableCredit = (float) $this->activeTransactionQuery($supplierFilters)
                ->whereIn('credit_account_id', $payableAccountIds)
                ->sum('credit_amt');
            $currentDue = $payableCredit - $paidDebit;

            if (abs($payableCredit) < 0.0001 && abs($paidDebit) < 0.0001 && abs($currentDue) < 0.0001) {
                continue;
            }

            $rows[] = [
                'supplier' => $supplier,
                'payable_credit' => $payableCredit,
                'paid_debit' => $paidDebit,
                'current_due' => $currentDue,
            ];

            $totals['payable_credit'] += $payableCredit;
            $totals['paid_debit'] += $paidDebit;
            $totals['current_due'] += $currentDue;
        }

        return [
            'rows' => $rows,
            'totals' => $totals,
        ];
    }

    public function accountsReceivableAccountIds()
    {
        $accountsReceivable = $this->accountIdsBySelectionNames(['accounts_receivable']);

        if ($accountsReceivable->isNotEmpty()) {
            return $accountsReceivable;
        }

        return $this->activeAccountQuery()
            ->where('account_type', 'asset')
            ->where(function ($query) {
                $query->where('account_name', 'like', '%Receivable%')
                    ->orWhere('account_name', 'like', '%Customer%');
            })
            ->pluck('id');
    }

    public function customerLedger(array $filters = []): array
    {
        $receivableAccountIds = $this->accountsReceivableAccountIds();
        $openingBalance = 0;
        $rows = [];

        if (!empty($filters['date_from']) || !empty($filters['start_date'])) {
            $openingFilters = $filters;
            unset($openingFilters['date_from'], $openingFilters['start_date'], $openingFilters['date_to'], $openingFilters['end_date'], $openingFilters['account_id']);
            $openingFilters['date_to'] = Carbon::parse($filters['date_from'] ?? $filters['start_date'])->subDay()->toDateString();

            $openingDebit = (float) $this->activeTransactionQuery($openingFilters)
                ->whereIn('debit_account_id', $receivableAccountIds)
                ->sum('debit_amt');
            $openingCredit = (float) $this->activeTransactionQuery($openingFilters)
                ->whereIn('credit_account_id', $receivableAccountIds)
                ->sum('credit_amt');

            $openingBalance = $openingDebit - $openingCredit;
        }

        $runningBalance = $openingBalance;
        $transactions = $this->activeTransactionQuery($filters)
            ->where(function ($query) use ($receivableAccountIds) {
                $query->whereIn('debit_account_id', $receivableAccountIds)
                    ->orWhereIn('credit_account_id', $receivableAccountIds);
            })
            ->with(['debitAccount', 'creditAccount'])
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();
        $customerNames = Customer::whereIn('id', $transactions->pluck('customer_id')->filter()->unique())
            ->pluck('name', 'id');

        foreach ($transactions as $transaction) {
            $debit = $receivableAccountIds->contains((int) $transaction->debit_account_id)
                ? (float) $transaction->debit_amt
                : 0;
            $credit = $receivableAccountIds->contains((int) $transaction->credit_account_id)
                ? (float) $transaction->credit_amt
                : 0;

            $runningBalance += $debit - $credit;

            $rows[] = [
                'transaction' => $transaction,
                'customer_name' => $customerNames[$transaction->customer_id] ?? '',
                'debit' => $debit,
                'credit' => $credit,
                'running_balance' => $runningBalance,
                'receivable_account_name' => $debit > 0
                    ? ($transaction->debitAccount->account_name ?? '')
                    : ($transaction->creditAccount->account_name ?? ''),
                'opposite_account_name' => $debit > 0
                    ? ($transaction->creditAccount->account_name ?? '')
                    : ($transaction->debitAccount->account_name ?? ''),
            ];
        }

        return [
            'rows' => $rows,
            'opening_balance' => $openingBalance,
            'total_debit' => array_sum(array_column($rows, 'debit')),
            'total_credit' => array_sum(array_column($rows, 'credit')),
            'closing_balance' => $runningBalance,
        ];
    }

    public function customerDueSummary(array $filters = []): array
    {
        $receivableAccountIds = $this->accountsReceivableAccountIds();
        $rows = [];
        $totals = [
            'receivable_debit' => 0,
            'received_credit' => 0,
            'current_due' => 0,
        ];

        $customerQuery = Customer::query()->where('status', 'active');
        if (!empty($filters['customer_id'])) {
            $customerQuery->where('id', $filters['customer_id']);
        }

        $customers = $customerQuery->orderBy('name')->get();

        foreach ($customers as $customer) {
            $customerFilters = $filters;
            $customerFilters['customer_id'] = $customer->id;
            unset($customerFilters['account_id']);

            $receivableDebit = (float) $this->activeTransactionQuery($customerFilters)
                ->whereIn('debit_account_id', $receivableAccountIds)
                ->sum('debit_amt');
            $receivedCredit = (float) $this->activeTransactionQuery($customerFilters)
                ->whereIn('credit_account_id', $receivableAccountIds)
                ->sum('credit_amt');
            $currentDue = $receivableDebit - $receivedCredit;

            if (abs($receivableDebit) < 0.0001 && abs($receivedCredit) < 0.0001 && abs($currentDue) < 0.0001) {
                continue;
            }

            $rows[] = [
                'customer' => $customer,
                'receivable_debit' => $receivableDebit,
                'received_credit' => $receivedCredit,
                'current_due' => $currentDue,
            ];

            $totals['receivable_debit'] += $receivableDebit;
            $totals['received_credit'] += $receivedCredit;
            $totals['current_due'] += $currentDue;
        }

        return [
            'rows' => $rows,
            'totals' => $totals,
        ];
    }

    public function expenseReport(array $filters = []): array
    {
        $accountId = $filters['account_id'] ?? null;
        $expenseAccountIds = $accountId ? collect([(int) $accountId]) : $this->accountIdsByType('expense');
        $rows = [];

        $transactions = $this->activeTransactionQuery($filters)
            ->where(function ($query) use ($expenseAccountIds) {
                $query->whereIn('debit_account_id', $expenseAccountIds)
                    ->orWhereIn('credit_account_id', $expenseAccountIds);
            })
            ->with(['debitAccount', 'creditAccount'])
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

        foreach ($transactions as $transaction) {
            $debit = $expenseAccountIds->contains((int) $transaction->debit_account_id)
                ? (float) $transaction->debit_amt
                : 0;
            $credit = $expenseAccountIds->contains((int) $transaction->credit_account_id)
                ? (float) $transaction->credit_amt
                : 0;

            $rows[] = [
                'transaction' => $transaction,
                'expense_account_name' => $debit > 0
                    ? ($transaction->debitAccount->account_name ?? '')
                    : ($transaction->creditAccount->account_name ?? ''),
                'opposite_account_name' => $debit > 0
                    ? ($transaction->creditAccount->account_name ?? '')
                    : ($transaction->debitAccount->account_name ?? ''),
                'debit' => $debit,
                'credit' => $credit,
                'net_expense' => $debit - $credit,
            ];
        }

        return [
            'rows' => $rows,
            'total_debit' => array_sum(array_column($rows, 'debit')),
            'total_credit' => array_sum(array_column($rows, 'credit')),
            'total_expense' => array_sum(array_column($rows, 'net_expense')),
        ];
    }

    public function dayBook(array $filters = []): array
    {
        $rows = [];
        $dailySummary = [];
        $totals = $this->emptyDayBookSummary();
        $cashBankAccountIds = $this->cashBankAccountIds();
        $receivableAccountIds = $this->accountsReceivableAccountIds();
        $payableAccountIds = $this->accountsPayableAccountIds();
        $expenseAccountIds = $this->accountIdsByType('expense');
        $revenueAccountIds = $this->accountIdsByType('revenue');
        $inventoryAccountIds = $this->activeAccountQuery()
            ->where(function ($query) {
                $query->where('account_selection_name', 'inventory')
                    ->orWhere('account_name', 'like', '%Inventory%');
            })
            ->pluck('id');

        $transactions = $this->activeTransactionQuery($filters)
            ->with(['debitAccount', 'creditAccount'])
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

        foreach ($transactions as $transaction) {
            $date = Carbon::parse($transaction->transaction_date)->toDateString();
            $debit = (float) ($transaction->debit_amt ?? 0);
            $credit = (float) ($transaction->credit_amt ?? 0);
            $debitAccountId = (int) $transaction->debit_account_id;
            $creditAccountId = (int) $transaction->credit_account_id;

            if (!isset($dailySummary[$date])) {
                $dailySummary[$date] = $this->emptyDayBookSummary();
            }

            $row = [
                'transaction' => $transaction,
                'debit_account_name' => $transaction->debitAccount->account_name ?? '',
                'credit_account_name' => $transaction->creditAccount->account_name ?? '',
                'debit' => $debit,
                'credit' => $credit,
            ];

            $rows[] = $row;
            $dailySummary[$date]['total_debit'] += $debit;
            $dailySummary[$date]['total_credit'] += $credit;
            $totals['total_debit'] += $debit;
            $totals['total_credit'] += $credit;

            if ($inventoryAccountIds->contains($debitAccountId)) {
                $dailySummary[$date]['purchase'] += $debit;
                $totals['purchase'] += $debit;
            }

            if ($revenueAccountIds->contains($creditAccountId)) {
                $dailySummary[$date]['sales'] += $credit;
                $totals['sales'] += $credit;
            }

            if ($expenseAccountIds->contains($debitAccountId)) {
                $dailySummary[$date]['expense'] += $debit;
                $totals['expense'] += $debit;
            }

            if ($expenseAccountIds->contains($creditAccountId)) {
                $dailySummary[$date]['expense'] -= $credit;
                $totals['expense'] -= $credit;
            }

            if ($cashBankAccountIds->contains($debitAccountId)) {
                $dailySummary[$date]['collection'] += $debit;
                $dailySummary[$date]['net_cash_movement'] += $debit;
                $totals['collection'] += $debit;
                $totals['net_cash_movement'] += $debit;
            }

            if ($cashBankAccountIds->contains($creditAccountId)) {
                $dailySummary[$date]['payment'] += $credit;
                $dailySummary[$date]['net_cash_movement'] -= $credit;
                $totals['payment'] += $credit;
                $totals['net_cash_movement'] -= $credit;
            }
        }

        ksort($dailySummary);

        return [
            'rows' => $rows,
            'daily_summary' => $dailySummary,
            'totals' => $totals,
        ];
    }

    private function accountRowsByType(string $accountType, array $filters = []): array
    {
        return $this->activeAccountQuery()
            ->where('account_type', $accountType)
            ->orderBy('sort_code')
            ->orderBy('account_name')
            ->get()
            ->map(function ($account) use ($filters) {
                return [
                    'account' => $account,
                    'amount' => $this->balanceForAccount($account, $filters),
                ];
            })
            ->filter(fn ($row) => abs($row['amount']) > 0.0001)
            ->values()
            ->all();
    }

    private function emptyDayBookSummary(): array
    {
        return [
            'purchase' => 0,
            'sales' => 0,
            'expense' => 0,
            'collection' => 0,
            'payment' => 0,
            'net_cash_movement' => 0,
            'total_debit' => 0,
            'total_credit' => 0,
        ];
    }
}

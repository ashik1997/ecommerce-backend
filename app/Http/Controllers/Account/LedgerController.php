<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Customer\Models\Customer;
use App\Http\Controllers\Inventory\Models\ProductSupplier;
use App\Services\AccountingReportService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class LedgerController extends Controller
{
    protected $accountingReports;

    public function __construct(AccountingReportService $accountingReports)
    {
        $this->accountingReports = $accountingReports;
    }

    /**
     * Ledger index: account-wise transactions or all accounts overview.
     */
    public function index(Request $request)
    {
        $request->validate([
            'account_id' => 'nullable|exists:ac_accounts,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'store_id' => 'nullable|integer',
            'customer_id' => 'nullable|integer',
            'supplier_id' => 'nullable|integer',
            'transaction_type' => 'nullable|string|max:100',
            'event_type' => 'nullable|string|max:100',
        ]);

        $startDate = $request->start_date ?? now()->subDays(30)->format('Y-m-d');
        $endDate = $request->end_date ?? now()->format('Y-m-d');
        $filters = $this->reportFilters($request, $startDate, $endDate);
        $accounts = $this->accountingReports->activeAccountQuery()
            ->orderBy('sort_code')
            ->orderBy('account_name')
            ->get();
        $account = null;
        $transactions = collect();
        $allTransactions = [];
        $openingBalance = 0;

        if ($request->account_id) {
            $account = $this->accountingReports->activeAccountQuery()->findOrFail($request->account_id);
            $openingBalance = $this->accountingReports->openingBalanceForAccount($account, $startDate, $filters);
            $transactions = $this->getTransactionsForAccount($account->id, $filters);
        } else {
            foreach ($accounts as $acc) {
                $accTransactions = $this->getTransactionsForAccount($acc->id, $filters);
                if ($accTransactions->isNotEmpty()) {
                    $allTransactions[$acc->id] = [
                        'account' => $acc,
                        'transactions' => $accTransactions,
                        'opening_balance' => $this->accountingReports->openingBalanceForAccount($acc, $startDate, $filters),
                    ];
                }
            }
        }

        return view('backend.ledger.index', compact('accounts', 'account', 'transactions', 'allTransactions', 'openingBalance'));
    }

    /**
     * Journal: structured by date and transaction_type with opening balance.
     * Payload: from, to, page. Response: [ opening_balance, ...days with grouped transactions ].
     */
    public function journal(Request $request)
    {
        $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
            'page' => 'nullable|integer|min:1',
            'account_id' => 'nullable|exists:ac_accounts,id',
            'store_id' => 'nullable|integer',
            'customer_id' => 'nullable|integer',
            'supplier_id' => 'nullable|integer',
            'transaction_type' => 'nullable|string|max:100',
            'event_type' => 'nullable|string|max:100',
        ]);

        $from = $request->input('from', now()->subDays(30)->format('Y-m-d'));
        $to = $request->input('to', now()->format('Y-m-d'));
        $filters = $this->reportFilters($request, $from, $to);
        $openingFilters = $filters;
        unset($openingFilters['date_from'], $openingFilters['date_to']);
        $page = (int) $request->input('page', 1);
        $perPage = 100;
        $accounts = $this->accountingReports->activeAccountQuery()
            ->orderBy('sort_code')
            ->orderBy('account_name')
            ->get();
        $selectedCustomer = $request->customer_id
            ? Customer::select('id', 'name', 'phone')->find($request->customer_id)
            : null;
        $selectedSupplier = $request->supplier_id
            ? ProductSupplier::leftJoin('product_supplier_contacts', 'product_suppliers.id', '=', 'product_supplier_contacts.product_supplier_id')
                ->where('product_suppliers.id', $request->supplier_id)
                ->select('product_suppliers.id', 'product_suppliers.name', 'product_supplier_contacts.contact_number')
                ->first()
            : null;
        $transactionTypes = $this->transactionFieldOptions('transaction_type');
        $eventTypes = $this->transactionFieldOptions('event_type');

        $journalData = [];

        // 1. Opening balance: dr/cr before from date + dr/cr from all previous pages in [from, to]
        $openingDr = $this->accountingReports->activeTransactionQuery($openingFilters)
            ->where('transaction_date', '<', $from)
            ->sum('debit_amt');
        $openingCr = $this->accountingReports->activeTransactionQuery($openingFilters)
            ->where('transaction_date', '<', $from)
            ->sum('credit_amt');

        if ($page > 1) {
            $offset = ($page - 1) * $perPage;
            $prevIds = $this->accountingReports->activeTransactionQuery($filters)
                ->orderBy('transaction_date')
                ->orderBy('id')
                ->limit($offset)
                ->pluck('id');
            if ($prevIds->isNotEmpty()) {
                $openingDr += $this->accountingReports->activeTransactionQuery($openingFilters)->whereIn('id', $prevIds)->sum('debit_amt');
                $openingCr += $this->accountingReports->activeTransactionQuery($openingFilters)->whereIn('id', $prevIds)->sum('credit_amt');
            }
        }

        $dayBeforeFrom = Carbon::parse($from)->subDay()->format('Y-m-d');
        $journalData[] = [
            'date' => $dayBeforeFrom,
            'dr' => (float) $openingDr,
            'cr' => (float) $openingCr,
        ];

        // 2. Transactions between from and to, limit 1000 (paginated by offset)
        $query = $this->accountingReports->activeTransactionQuery($filters)
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->with(['debitAccount', 'creditAccount']);

        $totalInRange = $query->count();
        $transactions = $query->paginate($perPage)->appends(request()->all());

        // 3. Group by date -> payment_code -> transaction_type
        $byDate = $transactions->groupBy('transaction_date');

        foreach ($byDate as $date => $dayTransactions) {
            $byPaymentCode = $dayTransactions->groupBy('payment_code');
            $paymentCodeBlocks = [];

            foreach ($byPaymentCode as $paymentCode => $paymentCodeTransactions) {
                $byType = $paymentCodeTransactions->groupBy('transaction_type');
                $typeBlocks = [];

                foreach ($byType as $type => $typeTransactions) {
                    $rows = $typeTransactions->map(function ($t) {
                        $debitAmount = (float) ($t->debit_amt ?? 0);
                        $creditAmount = (float) ($t->credit_amt ?? 0);
                        $accountHead = $debitAmount > 0
                            ? ($t->debitAccount->account_name ?? '')
                            : ($t->creditAccount->account_name ?? '');

                        return [
                            'debit_amount' => $debitAmount,
                            'credit_amount' => $creditAmount,
                            'note' => $t->note ?? '',
                            'event_type' => $t->event_type ?? '',
                            'account_head' => $accountHead,
                            'debit_account' => $t->debitAccount->account_name ?? '',
                            'credit_account' => $t->creditAccount->account_name ?? '',
                            'reference' => $this->transactionReference($t),
                        ];
                    })->values()->all();

                    $typeBlocks[] = [
                        'type' => $type,
                        'total_records' => count($rows),
                        'transactions' => $rows,
                    ];
                }

                $paymentCodeBlocks[] = [
                    'payment_code' => $paymentCode ?? '',
                    'transactions' => $typeBlocks,
                ];
            }

            $journalData[] = [
                'date' => $date,
                'total_records' => $dayTransactions->count(),
                'payment_codes' => $paymentCodeBlocks,
            ];
        }

        // Backward compatibility for view
        $totalDebit = $transactions->sum('debit_amt');
        $totalCredit = $transactions->sum('credit_amt');

        return view('backend.ledger.journal', [
            'journalData' => $journalData,
            'transactions' => $transactions,
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
            'startDate' => $from,
            'endDate' => $to,
            'from' => $from,
            'to' => $to,
            'page' => $page,
            'perPage' => $perPage,
            'totalInRange' => $totalInRange,
            'accounts' => $accounts,
            'selectedCustomer' => $selectedCustomer,
            'selectedSupplier' => $selectedSupplier,
            'transactionTypes' => $transactionTypes,
            'eventTypes' => $eventTypes,
        ]);
    }

    private function transactionFieldOptions(string $field)
    {
        return DB::table('ac_transactions')
            ->where('status', 'active')
            ->whereNotNull($field)
            ->where($field, '!=', '')
            ->distinct()
            ->orderBy($field)
            ->pluck($field);
    }

    public function searchSuppliers(Request $request)
    {
        $term = $request->input('q');

        $suppliers = ProductSupplier::leftJoin('product_supplier_contacts', 'product_suppliers.id', '=', 'product_supplier_contacts.product_supplier_id')
            ->where('product_suppliers.status', 'active')
            ->when($term, function ($query) use ($term) {
                $query->where(function ($searchQuery) use ($term) {
                    $searchQuery->where('product_suppliers.name', 'like', '%' . $term . '%')
                        ->orWhere('product_supplier_contacts.contact_number', 'like', '%' . $term . '%');
                });
            })
            ->select('product_suppliers.id', 'product_suppliers.name', 'product_supplier_contacts.contact_number')
            ->orderBy('product_suppliers.name')
            ->limit(10)
            ->get();

        return response()->json($suppliers);
    }

    private function transactionReference($transaction): array
    {
        if ($transaction->ref_sales_id) {
            $order = DB::table('product_orders')
                ->where('id', $transaction->ref_sales_id)
                ->select('id', 'order_code', 'slug')
                ->first();

            return [
                'label' => 'Sales Invoice',
                'text' => $order->order_code ?? ('Sale #' . $transaction->ref_sales_id),
                'url' => $order && $order->slug
                    ? url('order-invoice/' . $order->slug)
                    : url('pos/invoice/print/' . $transaction->ref_sales_id),
            ];
        }

        if ($transaction->ref_expense_id) {
            $expense = DB::table('db_expenses')
                ->where('id', $transaction->ref_expense_id)
                ->select('id', 'expense_code', 'slug')
                ->first();

            return [
                'label' => 'Expense',
                'text' => $expense->expense_code ?? ('Expense #' . $transaction->ref_expense_id),
                'url' => url('expenses/' . $transaction->ref_expense_id),
            ];
        }

        if ($transaction->ref_purchase_id) {
            $purchase = DB::table('product_purchase_orders')
                ->where('id', $transaction->ref_purchase_id)
                ->select('id', 'code', 'slug')
                ->first();

            return [
                'label' => 'Purchase Invoice',
                'text' => $purchase->code ?? ('Purchase #' . $transaction->ref_purchase_id),
                'url' => $purchase && $purchase->slug
                    ? url('purchase-invoice/' . $purchase->slug)
                    : url('view/all/purchase-product/order'),
            ];
        }

        if ($transaction->ref_purchase_return_id) {
            $purchaseReturn = DB::table('product_purchase_returns')
                ->where('id', $transaction->ref_purchase_return_id)
                ->select('id', 'code', 'slug')
                ->first();

            return [
                'label' => 'Purchase Return',
                'text' => $purchaseReturn->code ?? ('Purchase Return #' . $transaction->ref_purchase_return_id),
                'url' => $purchaseReturn && $purchaseReturn->slug
                    ? url('edit/purchase-return/order/' . $purchaseReturn->slug)
                    : url('view/all/purchase-return/order'),
            ];
        }

        if ($transaction->ref_customer_payment_id) {
            $customerPayment = DB::table('db_customer_payments')
                ->where('id', $transaction->ref_customer_payment_id)
                ->select('id', 'order_id', 'customer_id')
                ->first();

            if ($customerPayment && $customerPayment->order_id) {
                $order = DB::table('product_orders')
                    ->where('id', $customerPayment->order_id)
                    ->select('id', 'order_code', 'slug')
                    ->first();

                return [
                    'label' => 'Customer Payment',
                    'text' => $order->order_code ?? ('Payment #' . $transaction->ref_customer_payment_id),
                    'url' => $order && $order->slug
                        ? url('order-invoice/' . $order->slug)
                        : url('customer-payments'),
                ];
            }

            return [
                'label' => 'Customer Payment',
                'text' => 'Payment #' . $transaction->ref_customer_payment_id,
                'url' => $customerPayment && $customerPayment->customer_id
                    ? url('customer-payment-history/' . $customerPayment->customer_id)
                    : url('customer-payments'),
            ];
        }

        if ($transaction->supplier_payment_id) {
            return [
                'label' => 'Supplier Payment',
                'text' => 'Payment #' . $transaction->supplier_payment_id,
                'url' => url('supplier-payments'),
            ];
        }

        if ($transaction->ref_moneydeposits_id) {
            return [
                'label' => 'Money Deposit',
                'text' => 'Deposit #' . $transaction->ref_moneydeposits_id,
                'url' => url('print/deposit/' . $transaction->ref_moneydeposits_id),
            ];
        }

        if ($transaction->ref_moneytransfer_id) {
            return [
                'label' => 'Fund Transfer',
                'text' => 'Transfer #' . $transaction->ref_moneytransfer_id,
                'url' => url('print/fund-transfer/' . $transaction->ref_moneytransfer_id),
            ];
        }

        if ($transaction->ref_salespayments_id) {
            return [
                'label' => 'Sales Payment',
                'text' => 'Payment #' . $transaction->ref_salespayments_id,
                'url' => url('view/all/product-order/manage'),
            ];
        }

        if ($transaction->ref_salespaymentsreturn_id) {
            return [
                'label' => 'Sales Return Payment',
                'text' => 'Return Payment #' . $transaction->ref_salespaymentsreturn_id,
                'url' => url('view/all/product-order-returns'),
            ];
        }

        if ($transaction->ref_purchasepayments_id) {
            $purchasePayment = DB::table('db_purchasepayments')
                ->where('id', $transaction->ref_purchasepayments_id)
                ->select('id', 'purchase_id')
                ->first();
            $purchase = $purchasePayment && $purchasePayment->purchase_id
                ? DB::table('product_purchase_orders')->where('id', $purchasePayment->purchase_id)->select('code', 'slug')->first()
                : null;

            return [
                'label' => 'Purchase Payment',
                'text' => $purchase->code ?? ('Payment #' . $transaction->ref_purchasepayments_id),
                'url' => $purchase && $purchase->slug
                    ? url('purchase-invoice/' . $purchase->slug)
                    : url('view/all/purchase-product/order'),
            ];
        }

        if ($transaction->ref_purchasepaymentsreturn_id) {
            return [
                'label' => 'Purchase Return Payment',
                'text' => 'Return Payment #' . $transaction->ref_purchasepaymentsreturn_id,
                'url' => url('view/all/purchase-return/order'),
            ];
        }

        if ($transaction->ref_customer_opening_balance_id) {
            return [
                'label' => 'Customer Opening',
                'text' => 'Opening #' . $transaction->ref_customer_opening_balance_id,
                'url' => url('customer-opening-balance'),
            ];
        }

        if ($transaction->ref_supplier_opening_balance_id) {
            return [
                'label' => 'Supplier Opening',
                'text' => 'Opening #' . $transaction->ref_supplier_opening_balance_id,
                'url' => url('supplier-opening-balance'),
            ];
        }

        if ($transaction->ref_accounts_id) {
            return [
                'label' => 'Account Reference',
                'text' => 'Account Ref #' . $transaction->ref_accounts_id,
                'url' => url('ledger/journal'),
            ];
        }

        return [
            'label' => 'Journal Entry',
            'text' => $transaction->payment_code ?? ('Transaction #' . $transaction->id),
            'url' => '',
        ];
    }

    /**
     * Trial balance: debit/credit movement and balance for every active account.
     */
    public function trialBalance(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'store_id' => 'nullable|integer',
        ]);

        $startDate = $request->input('start_date', now('Asia/Dhaka')->subDays(30)->toDateString());
        $endDate = $request->input('end_date', now('Asia/Dhaka')->toDateString());
        $filters = [
            'date_from' => $startDate,
            'date_to' => $endDate,
            'store_id' => $request->input('store_id'),
        ];

        $trialBalance = $this->accountingReports->trialBalance($filters);

        return view('backend.ledger.trial_balance', compact('trialBalance', 'startDate', 'endDate'));
    }

    /**
     * Cash / Bank Book: inflow, outflow, and running balance for payment accounts.
     */
    public function cashBankBook(Request $request)
    {
        $request->validate([
            'account_id' => 'nullable|exists:ac_accounts,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'store_id' => 'nullable|integer',
        ]);

        $startDate = $request->input('start_date', now('Asia/Dhaka')->subDays(30)->toDateString());
        $endDate = $request->input('end_date', now('Asia/Dhaka')->toDateString());
        $cashBankAccountIds = $this->accountingReports->cashBankAccountIds();
        $cashBankAccounts = $this->accountingReports->activeAccountQuery()
            ->whereIn('id', $cashBankAccountIds)
            ->orderBy('sort_code')
            ->orderBy('account_name')
            ->get();
        $filters = [
            'account_id' => $request->input('account_id'),
            'date_from' => $startDate,
            'date_to' => $endDate,
            'store_id' => $request->input('store_id'),
        ];

        $cashBankBook = $this->accountingReports->cashBankBook($filters);

        return view('backend.ledger.cash_bank_book', compact('cashBankBook', 'cashBankAccounts', 'startDate', 'endDate'));
    }

    /**
     * Supplier ledger: Accounts Payable movement for one supplier or all suppliers.
     */
    public function supplierLedger(Request $request)
    {
        $request->validate([
            'supplier_id' => 'nullable|exists:product_suppliers,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'store_id' => 'nullable|integer',
        ]);

        $startDate = $request->input('start_date', now('Asia/Dhaka')->subDays(30)->toDateString());
        $endDate = $request->input('end_date', now('Asia/Dhaka')->toDateString());
        $suppliers = ProductSupplier::where('status', 'active')->orderBy('name')->get();
        $selectedSupplier = $request->supplier_id
            ? $suppliers->firstWhere('id', (int) $request->supplier_id)
            : null;
        $filters = [
            'supplier_id' => $request->input('supplier_id'),
            'date_from' => $startDate,
            'date_to' => $endDate,
            'store_id' => $request->input('store_id'),
        ];

        $supplierLedger = $this->accountingReports->supplierLedger($filters);

        return view('backend.ledger.supplier_ledger', compact('supplierLedger', 'suppliers', 'selectedSupplier', 'startDate', 'endDate'));
    }

    /**
     * Supplier due: Accounts Payable summary by supplier.
     */
    public function supplierDue(Request $request)
    {
        $request->validate([
            'supplier_id' => 'nullable|exists:product_suppliers,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'store_id' => 'nullable|integer',
        ]);

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date', now('Asia/Dhaka')->toDateString());
        $suppliers = ProductSupplier::where('status', 'active')->orderBy('name')->get();
        $filters = [
            'supplier_id' => $request->input('supplier_id'),
            'date_from' => $startDate,
            'date_to' => $endDate,
            'store_id' => $request->input('store_id'),
        ];

        $supplierDue = $this->accountingReports->supplierDueSummary($filters);

        return view('backend.ledger.supplier_due', compact('supplierDue', 'suppliers', 'startDate', 'endDate'));
    }

    /**
     * Customer ledger: Accounts Receivable movement for one customer or all customers.
     */
    public function customerLedger(Request $request)
    {
        $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'store_id' => 'nullable|integer',
        ]);

        $startDate = $request->input('start_date', now('Asia/Dhaka')->subDays(30)->toDateString());
        $endDate = $request->input('end_date', now('Asia/Dhaka')->toDateString());
        $customers = Customer::where('status', 'active')->orderBy('name')->get();
        $selectedCustomer = $request->customer_id
            ? $customers->firstWhere('id', (int) $request->customer_id)
            : null;
        $filters = [
            'customer_id' => $request->input('customer_id'),
            'date_from' => $startDate,
            'date_to' => $endDate,
            'store_id' => $request->input('store_id'),
        ];

        $customerLedger = $this->accountingReports->customerLedger($filters);

        return view('backend.ledger.customer_ledger', compact('customerLedger', 'customers', 'selectedCustomer', 'startDate', 'endDate'));
    }

    /**
     * Customer due: Accounts Receivable summary by customer.
     */
    public function customerDue(Request $request)
    {
        $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'store_id' => 'nullable|integer',
        ]);

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date', now('Asia/Dhaka')->toDateString());
        $customers = Customer::where('status', 'active')->orderBy('name')->get();
        $filters = [
            'customer_id' => $request->input('customer_id'),
            'date_from' => $startDate,
            'date_to' => $endDate,
            'store_id' => $request->input('store_id'),
        ];

        $customerDue = $this->accountingReports->customerDueSummary($filters);

        return view('backend.ledger.customer_due', compact('customerDue', 'customers', 'startDate', 'endDate'));
    }

    /**
     * Expense report: expense account movement with payment/source account.
     */
    public function expenseReport(Request $request)
    {
        $request->validate([
            'account_id' => 'nullable|exists:ac_accounts,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'store_id' => 'nullable|integer',
            'transaction_type' => 'nullable|string|max:100',
            'event_type' => 'nullable|string|max:100',
        ]);

        $startDate = $request->input('start_date', now('Asia/Dhaka')->subDays(30)->toDateString());
        $endDate = $request->input('end_date', now('Asia/Dhaka')->toDateString());
        $expenseAccounts = $this->accountingReports->activeAccountQuery()
            ->where('account_type', 'expense')
            ->orderBy('sort_code')
            ->orderBy('account_name')
            ->get();
        $filters = [
            'account_id' => $request->input('account_id'),
            'date_from' => $startDate,
            'date_to' => $endDate,
            'store_id' => $request->input('store_id'),
            'transaction_type' => $request->input('transaction_type'),
            'event_type' => $request->input('event_type'),
        ];

        $expenseReport = $this->accountingReports->expenseReport($filters);

        return view('backend.ledger.expense_report', compact('expenseReport', 'expenseAccounts', 'startDate', 'endDate'));
    }

    /**
     * Day book: every accounting row plus a daily business summary.
     */
    public function dayBook(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'store_id' => 'nullable|integer',
            'transaction_type' => 'nullable|string|max:100',
            'event_type' => 'nullable|string|max:100',
        ]);

        $startDate = $request->input('start_date', now('Asia/Dhaka')->toDateString());
        $endDate = $request->input('end_date', now('Asia/Dhaka')->toDateString());
        $filters = [
            'date_from' => $startDate,
            'date_to' => $endDate,
            'store_id' => $request->input('store_id'),
            'transaction_type' => $request->input('transaction_type'),
            'event_type' => $request->input('event_type'),
        ];

        $dayBook = $this->accountingReports->dayBook($filters);

        return view('backend.ledger.day_book', compact('dayBook', 'startDate', 'endDate'));
    }

    /**
     * Balance sheet: Assets, Liabilities, Equity for date range.
     */
    public function balanceSheet(Request $request)
    {
        $request->validate([
            'end_date' => 'nullable|date',
            'store_id' => 'nullable|integer',
        ]);

        $endDate = $request->input('end_date', now()->toDateString());
        $filters = [
            'date_to' => $endDate,
            'store_id' => $request->input('store_id'),
        ];

        $balanceSheet = $this->accountingReports->balanceSheet($filters);

        return view('backend.ledger.balance_sheet', compact('balanceSheet', 'endDate'));
    }

    /**
     * Income statement: Revenue, Expense, Net Income for date range.
     */
    public function incomeStatement(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'store_id' => 'nullable|integer',
        ]);

        $startDate = $request->input('start_date', now('Asia/Dhaka')->subDays(30)->toDateString());
        $endDate = $request->input('end_date', now('Asia/Dhaka')->toDateString());
        $filters = [
            'date_from' => $startDate,
            'date_to' => $endDate,
            'store_id' => $request->input('store_id'),
        ];

        $incomeStatement = $this->accountingReports->profitLoss($filters);

        return view('backend.ledger.income_statement', compact('incomeStatement', 'startDate', 'endDate'));
    }

    /**
     * Transactions for a single account in date range.
     */
    private function getTransactionsForAccount(int $accountId, array $filters)
    {
        $filters['account_id'] = $accountId;

        return $this->accountingReports->transactionRowsForAccount($accountId, [
            'date_from' => $filters['date_from'] ?? null,
            'date_to' => $filters['date_to'] ?? null,
            'store_id' => $filters['store_id'] ?? null,
            'customer_id' => $filters['customer_id'] ?? null,
            'supplier_id' => $filters['supplier_id'] ?? null,
            'transaction_type' => $filters['transaction_type'] ?? null,
            'event_type' => $filters['event_type'] ?? null,
        ]);
    }

    private function reportFilters(Request $request, string $dateFrom, string $dateTo): array
    {
        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'account_id' => $request->input('account_id'),
            'store_id' => $request->input('store_id'),
            'customer_id' => $request->input('customer_id'),
            'supplier_id' => $request->input('supplier_id'),
            'transaction_type' => $request->input('transaction_type'),
            'event_type' => $request->input('event_type'),
        ];
    }

}

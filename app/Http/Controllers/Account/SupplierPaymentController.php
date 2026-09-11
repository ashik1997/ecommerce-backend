<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Account\Models\DbSupplierPayment;
use App\Http\Controllers\Account\Models\DbPurchasePayment;
use App\Http\Controllers\Account\Models\DbPaymentType;
use App\Http\Controllers\Account\Models\AcAccount;
use App\Http\Controllers\Account\Models\AcTransaction;
use App\Http\Controllers\Account\Models\SupplierOpeningBalance;
use App\Http\Controllers\Inventory\Models\ProductPurchaseOrder;
use App\Models\ProductPurchaseReturn;
use App\Http\Controllers\Inventory\Models\ProductSupplier;
use App\Services\Supplier\SupplierTransactionService;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Brian2694\Toastr\Facades\Toastr;
use DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class SupplierPaymentController extends Controller
{
    protected $transactionService;

    public function __construct(SupplierTransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * Show supplier payments index page
     */
    public function index(Request $request)
    {
        $dashboard = $this->transactionService->supplierDashboardSummary();
        $summary = $dashboard['summary'];
        $supplierStats = $dashboard['supplier_stats'];
        $emptyStats = [
            'total_purchase' => 0,
            'return_amount' => 0,
            'paid' => 0,
            'old_due' => 0,
            'due' => 0,
            'advance' => 0,
            'net_payable' => 0,
        ];

        if ($request->ajax()) {
            $suppliers = ProductSupplier::where('status', 'active');

            return Datatables::of($suppliers)
                ->editColumn('id', function ($supplier) {
                    return $supplier->id;
                })
                ->editColumn('name', function ($supplier) {
                    return $supplier->name;
                })
                ->editColumn('total_purchase', function ($supplier) use ($supplierStats, $emptyStats) {
                    $stats = $supplierStats[$supplier->id] ?? $emptyStats;
                    return '৳' . number_format($stats['total_purchase'], 2);
                })
                ->editColumn('paid', function ($supplier) use ($supplierStats, $emptyStats) {
                    $stats = $supplierStats[$supplier->id] ?? $emptyStats;
                    return '৳' . number_format($stats['paid'], 2);
                })
                ->editColumn('return_amount', function ($supplier) use ($supplierStats, $emptyStats) {
                    $stats = $supplierStats[$supplier->id] ?? $emptyStats;
                    return '৳' . number_format($stats['return_amount'], 2);
                })
                ->editColumn('due', function ($supplier) use ($supplierStats, $emptyStats) {
                    $stats = $supplierStats[$supplier->id] ?? $emptyStats;
                    $due = $stats['due'];
                    $class = $due > 0 ? 'text-danger' : 'text-success';
                    return '<span class="' . $class . '">৳' . number_format($due, 2) . '</span>';
                })
                ->editColumn('advance', function ($supplier) use ($supplierStats, $emptyStats) {
                    $stats = $supplierStats[$supplier->id] ?? $emptyStats;
                    return '৳' . number_format($stats['advance'], 2);
                })
                ->addColumn('action', function ($supplier) use ($supplierStats, $emptyStats) {
                    $stats = $supplierStats[$supplier->id] ?? $emptyStats;
                    $due = $stats['due'];
                    $availableAdvance = $stats['advance'];

                    $btn = '<div class="dropdown">';
                    $btn .= '<button class="btn-sm btn-primary dropdown-toggle rounded" type="button" id="actionDropdown' . $supplier->id . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
                    $btn .= 'Action';
                    $btn .= '</button>';
                    $btn .= '<div class="dropdown-menu" aria-labelledby="actionDropdown' . $supplier->id . '">';
                    if ($due > 0) {
                        $btn .= '<a class="dropdown-item" href="' . route('CreateSupplierPaymentDue', $supplier->id) . '"><i class="fas fa-money-bill-wave"></i> Pay Due</a>';
                    }
                    $btn .= '<a class="dropdown-item" href="' . route('CreateSupplierPaymentAdvance', $supplier->id) . '"><i class="fas fa-hand-holding-usd"></i> Pay Advance</a>';
                    if ($availableAdvance > 0) {
                        $btn .= '<span class="dropdown-item text-muted">Advance Available: ৳' . number_format($availableAdvance, 2) . '</span>';
                        $btn .= '<a class="dropdown-item" href="' . route('CreateSupplierAdvanceRefund', $supplier->id) . '"><i class="fas fa-undo"></i> Receive Advance Refund</a>';
                    }
                    $btn .= '<a class="dropdown-item" href="' . route('ViewSupplierPayments', $supplier->id) . '"><i class="fas fa-list"></i> Payments</a>';
                    $btn .= '</div>';
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['due', 'action'])
                ->make(true);
        }

        return view('backend.supplier_payments.index', compact('summary'));
    }

    /**
     * Show payment form for paying due purchases
     */
    public function createDue($supplierId = null)
    {
        $this->transactionService->ensureAccountingSetup();
        $suppliers = ProductSupplier::where('status', 'active')->get();
        $selectedSupplier = $supplierId ? ProductSupplier::findOrFail($supplierId) : null;
        $paymentTypes = DbPaymentType::where('status', 'active')->get();

        return view('backend.supplier_payments.create_due', compact('suppliers', 'selectedSupplier', 'paymentTypes'));
    }

    /**
     * Show payment form for advance payment
     */
    public function createAdvance($supplierId = null)
    {
        $this->transactionService->ensureAccountingSetup();
        $suppliers = ProductSupplier::where('status', 'active')->get();
        $selectedSupplier = $supplierId ? ProductSupplier::findOrFail($supplierId) : null;
        $paymentTypes = DbPaymentType::where('status', 'active')->get();

        return view('backend.supplier_payments.create_advance', compact('suppliers', 'selectedSupplier', 'paymentTypes'));
    }

    public function createRefund($supplierId = null)
    {
        $this->transactionService->ensureAccountingSetup();
        $suppliers = ProductSupplier::where('status', 'active')->orderBy('name')->get();
        $selectedSupplier = $supplierId ? ProductSupplier::findOrFail($supplierId) : null;
        $paymentTypes = DbPaymentType::where('status', 'active')->get();

        return view('backend.supplier_payments.create_refund', compact('suppliers', 'selectedSupplier', 'paymentTypes'));
    }

    public function processRefund(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'supplier_id' => 'required|exists:product_suppliers,id',
            'refund_amount' => 'required|numeric|min:0.01',
            'payment_mode' => 'required|exists:db_paymenttypes,id',
            'refund_date' => 'required|date',
            'account_id' => 'nullable|exists:ac_accounts,id',
            'payment_note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $paymentMethod = DbPaymentType::findOrFail($request->payment_mode);
            $paymentAccount = $this->transactionService->ensurePaymentTypeAccount($paymentMethod);

            if ($request->account_id && (int) $request->account_id !== (int) $paymentAccount->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected account does not match the payment mode.',
                ], 422);
            }

            $supplierPayments = $this->transactionService->refundAdvance(
                (int) $request->supplier_id,
                $paymentMethod,
                (float) $request->refund_amount,
                $request->refund_date,
                $request->payment_note
            );
            $invoiceIds = collect($supplierPayments)->pluck('id')->implode(',');

            Toastr::success('Supplier advance refund received successfully!', 'Success');
            return response()->json([
                'success' => true,
                'message' => 'Supplier advance refund received successfully!',
                'redirect' => route('PrintSupplierAdvanceRefundInvoice', $invoiceIds),
            ]);
        } catch (\Exception $e) {
            Log::error('Supplier Advance Refund Error', [
                'message' => $e->getMessage(),
                'supplier_id' => $request->supplier_id ?? null,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong! ' . $e->getMessage(),
            ], 500);
        }
    }

    public function refundInvoice($ids)
    {
        $paymentIds = collect(explode(',', $ids))
            ->map(fn($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        abort_if($paymentIds->isEmpty(), 404);

        $payments = DbSupplierPayment::with('supplier')
            ->whereIn('id', $paymentIds)
            ->where('payment_type', 'refund')
            ->orderBy('id')
            ->get();

        abort_if($payments->isEmpty(), 404);

        $supplierIds = $payments->pluck('supplier_id')->unique();
        abort_if($supplierIds->count() !== 1, 404);

        $transactions = AcTransaction::with('debitAccount')
            ->whereIn('supplier_payment_id', $payments->pluck('id'))
            ->whereNotNull('debit_account_id')
            ->where('status', 'active')
            ->get();

        $supplier = $payments->first()->supplier;
        $totalRefund = $payments->sum(fn($payment) => abs((float) $payment->payment));
        $refundDate = $payments->first()->payment_date;
        $receiveAccount = optional($transactions->first())->debitAccount;

        return view('backend.supplier_payments.refund_invoice', compact('payments', 'supplier', 'totalRefund', 'refundDate', 'receiveAccount'));
    }

    public function createOpeningBalance()
    {
        $this->transactionService->ensureAccountingSetup();
        $suppliers = ProductSupplier::where('status', 'active')->orderBy('name')->get();
        $openingBalances = SupplierOpeningBalance::with('supplier')
            ->where('status', 'active')
            ->orderBy('id', 'desc')
            ->take(50)
            ->get();

        return view('backend.supplier_payments.opening_balance', compact('suppliers', 'openingBalances'));
    }

    public function storeOpeningBalance(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|exists:product_suppliers,id',
            'entry_type' => 'required|in:due,advance',
            'amount' => 'required|numeric|min:0.01',
            'opening_date' => 'required|date',
            'invoice_no' => 'nullable|string|max:100',
            'invoice_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'reference_no' => 'nullable|string|max:100',
            'note' => 'nullable|string',
        ]);

        try {
            $this->transactionService->createOpeningBalance($request->only([
                'supplier_id',
                'entry_type',
                'amount',
                'opening_date',
                'invoice_no',
                'invoice_date',
                'due_date',
                'reference_no',
                'note',
            ]));

            Toastr::success('Supplier opening balance saved successfully!', 'Success');
            return redirect()->route('CreateSupplierOpeningBalance');
        } catch (\Exception $e) {
            Toastr::error($e->getMessage(), 'Error');
            return back()->withInput();
        }
    }

    /**
     * Get account balance by payment type (AJAX)
     */
    public function getAccountBalance($paymentTypeId)
    {
        try {
            $paymentType = DbPaymentType::where('status', 'active')->findOrFail($paymentTypeId);
            $account = $this->transactionService->ensurePaymentTypeAccount($paymentType);

            if (!$account) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account not found for this payment type',
                    'balance' => 0
                ]);
            }

            $balance = $this->transactionService->accountBalance($account);

            return response()->json([
                'success' => true,
                'account_id' => $account->id,
                'account_name' => $account->account_name,
                'balance' => $balance,
                'account_type' => $account->account_type
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching account balance: ' . $e->getMessage(),
                'balance' => 0
            ], 500);
        }
    }

    /**
     * Get supplier due purchases data (helper method)
     */
    private function getSupplierDuePurchasesData($supplierId)
    {
        return $this->transactionService->getDueItems((int) $supplierId);
    }

    /**
     * Get supplier due purchases (AJAX)
     */
    public function getSupplierDuePurchases($supplierId)
    {
        try {
            $supplier = ProductSupplier::findOrFail($supplierId);

            $duePurchases = $this->getSupplierDuePurchasesData($supplierId);
            $availableAdvance = $this->transactionService->availableAdvance((int) $supplierId);

            return response()->json([
                'success' => true,
                'supplier' => $supplier,
                'due_purchases' => $duePurchases,
                'due_items' => $duePurchases,
                'available_advance' => $availableAdvance,
                'total_due' => collect($duePurchases)->sum('due_amount')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching supplier data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getSupplierAdvanceBalance($supplierId)
    {
        try {
            $supplier = ProductSupplier::findOrFail($supplierId);

            return response()->json([
                'success' => true,
                'supplier' => $supplier,
                'available_advance' => $this->transactionService->availableAdvance((int) $supplierId),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching supplier advance balance: ' . $e->getMessage(),
                'available_advance' => 0,
            ], 500);
        }
    }

    /**
     * Store payment (with FIFO allocation or advance)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'supplier_id' => 'required|exists:product_suppliers,id',
            'payment_amount' => 'nullable|numeric|min:0',
            'advance_amount' => 'nullable|numeric|min:0',
            'payment_type' => 'required|in:due,advance',
            'payment_mode' => 'nullable|exists:db_paymenttypes,id',
            'payment_date' => 'required|date',
            'account_id' => 'nullable|exists:ac_accounts,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $paymentAmount = floatval($request->payment_amount);
            $advanceAmount = floatval($request->advance_amount);
            $paymentMethod = null;
            $paymentAccount = null;

            if ($request->payment_type === 'advance') {
                if ($paymentAmount <= 0 || !$request->payment_mode) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Payment amount and payment mode are required for advance payment.',
                    ], 422);
                }
            }

            if ($request->payment_type === 'due' && ($paymentAmount + $advanceAmount) <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment amount or advance amount is required.',
                ], 422);
            }

            if ($paymentAmount > 0) {
                if (!$request->payment_mode) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Payment mode is required when cash/bank payment amount is greater than zero.',
                    ], 422);
                }

                $paymentMethod = DbPaymentType::findOrFail($request->payment_mode);
                $paymentAccount = $this->transactionService->ensurePaymentTypeAccount($paymentMethod);

                if ($request->account_id && (int) $request->account_id !== (int) $paymentAccount->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Selected account does not match the payment mode.',
                    ], 422);
                }
            }

            $allocations = [];
            if ($request->payment_allocations) {
                $allocations = json_decode($request->payment_allocations, true);
                if (!$allocations || !is_array($allocations)) {
                    throw new \Exception('Invalid payment allocation data');
                }
            }

            if ($request->payment_type === 'due') {
                $this->transactionService->payDue(
                    (int) $request->supplier_id,
                    $paymentMethod,
                    $paymentAmount,
                    $request->payment_date,
                    $request->payment_note,
                    $allocations,
                    $advanceAmount
                );
                Toastr::success('Supplier due payment recorded successfully!', 'Success');
                return response()->json([
                    'success' => true,
                    'message' => 'Supplier due payment recorded successfully!',
                    'redirect' => route('ViewAllSupplierPayments')
                ]);
            }

            $this->transactionService->payAdvance(
                (int) $request->supplier_id,
                $paymentMethod,
                $paymentAmount,
                $request->payment_date,
                $request->payment_note
            );

            Toastr::success('Supplier advance payment recorded successfully!', 'Success');
            return response()->json([
                'success' => true,
                'message' => 'Supplier advance payment recorded successfully!',
                'redirect' => route('ViewAllSupplierPayments')
            ]);
        } catch (\Exception $e) {
            Log::error('Supplier Payment Error', [
                'message' => $e->getMessage(),
                'supplier_id' => $request->supplier_id ?? null,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong! ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * View all payments for a supplier
     */
    public function viewPayments(Request $request, $supplierId = null)
    {
        $supplier = ($supplierId && $supplierId !== 'all') ? ProductSupplier::findOrFail($supplierId) : null;
        $suppliers = ProductSupplier::where('status', 'active')->get();

        if ($request->ajax()) {
            $query = DbSupplierPayment::with(['supplier', 'openingBalance'])
                ->orderBy('payment_date', 'desc')
                ->orderBy('id', 'desc');

            if ($supplier) {
                $query->where('supplier_id', $supplierId);
            }

            if ($request->date_from) {
                $query->where('payment_date', '>=', $request->date_from);
            }

            if ($request->date_to) {
                $query->where('payment_date', '<=', $request->date_to);
            }

            $data = $query->get();

            return Datatables::of($data)
                ->addIndexColumn()
                ->editColumn('supplier', function ($data) {
                    return $data->supplier ? $data->supplier->name : 'N/A';
                })
                ->editColumn('purchase_code', function ($data) {
                    if ($data->payment_type === 'refund') {
                        return '<span class="badge badge-danger">Advance Refund</span>';
                    }
                    if ($data->purchasepayment_id) {
                        $purchasePayment = DbPurchasePayment::find($data->purchasepayment_id);
                        if ($purchasePayment && $purchasePayment->purchase_id) {
                            $purchase = ProductPurchaseOrder::find($purchasePayment->purchase_id);
                            return $purchase ? $purchase->code : 'N/A';
                        }
                    }
                    if ($data->supplier_opening_balance_id && $data->openingBalance) {
                        return '<span class="badge badge-warning">Old Due: ' . ($data->openingBalance->invoice_no ?: $data->openingBalance->id) . '</span>';
                    }
                    return '<span class="badge badge-info">Advance Payment</span>';
                })
                ->editColumn('payment_type', function ($data) {
                    $badges = [
                        'due' => '<span class="badge badge-success">Due Payment</span>',
                        'advance' => '<span class="badge badge-info">Advance</span>',
                        'adjustment' => '<span class="badge badge-secondary">Adjustment</span>',
                        'refund' => '<span class="badge badge-danger">Refund</span>',
                    ];
                    return $badges[$data->payment_type] ?? '<span class="badge badge-light">' . ucfirst($data->payment_type) . '</span>';
                })
                ->editColumn('payment', function ($data) {
                    $amount = number_format(abs($data->payment), 2);
                    if ($data->payment < 0) {
                        return '<span class="text-danger">-৳' . $amount . '</span>';
                    }
                    return '<span class="text-success">৳' . $amount . '</span>';
                })
                ->editColumn('payment_date', function ($data) {
                    return date("Y-m-d", strtotime($data->payment_date));
                })
                ->addColumn('action', function ($data) {
                    if ($data->status !== 'active') {
                        return '<span class="badge badge-secondary">Voided</span>';
                    }

                    return '<button type="button" class="btn btn-sm btn-danger void-payment-btn" data-url="' . route('VoidSupplierPayment', $data->id) . '"><i class="fas fa-ban"></i> Void</button>';
                })
                ->rawColumns(['purchase_code', 'payment_type', 'payment', 'action'])
                ->make(true);
        }

        return view('backend.supplier_payments.payments', compact('supplier', 'suppliers'));
    }

    public function voidPayment(Request $request, $id)
    {
        $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);

        try {
            $this->transactionService->voidSupplierPayment((int) $id, $request->reason);

            Toastr::success('Supplier payment voided successfully!', 'Success');
            return response()->json([
                'success' => true,
                'message' => 'Supplier payment voided successfully!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Find the real cash/bank account linked with a payment method.
     * Prefer db_paymenttypes.debit_account_id, then fallback to paymenttypes_id.
     */
    private function resolvePaymentAccount($paymentTypeId)
    {
        $paymentType = DbPaymentType::where('status', 'active')->find($paymentTypeId);
        if (!$paymentType) {
            return null;
        }

        if ($paymentType->debit_account_id) {
            $account = AcAccount::where('id', $paymentType->debit_account_id)
                ->where('status', 'active')
                ->first();
            if ($account) {
                return $account;
            }
        }

        return AcAccount::where('paymenttypes_id', $paymentType->id)
            ->where('status', 'active')
            ->first();
    }

    /**
     * Current ledger balance for an account.
     */
    private function accountBalance(AcAccount $account)
    {
        $debits = (float) AcTransaction::where('debit_account_id', $account->id)
            ->where('status', 'active')
            ->sum('debit_amt');
        $credits = (float) AcTransaction::where('credit_account_id', $account->id)
            ->where('status', 'active')
            ->sum('credit_amt');

        if (in_array($account->account_type, ['liability', 'equity', 'revenue'])) {
            return $credits - $debits;
        }

        return $debits - $credits;
    }
}

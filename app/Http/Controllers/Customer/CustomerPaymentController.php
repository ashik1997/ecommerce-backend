<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Account\Models\AcTransaction;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Customer\Models\Customer;
use App\Http\Controllers\Customer\Models\CustomerOpeningBalance;
use App\Http\Controllers\Account\Models\DbCustomerPayment;
use App\Http\Controllers\Account\Models\DbPaymentType;
use App\Models\ProductOrder;
use App\Services\Customer\CustomerTransactionService;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Brian2694\Toastr\Facades\Toastr;
use DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CustomerPaymentController extends Controller
{
    protected $transactionService;

    public function __construct(CustomerTransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    public function dashboard(Request $request)
    {
        $this->transactionService->ensureAccountingSetup();

        $fromDate = $request->get('from_date', now()->startOfMonth()->toDateString());
        $toDate = $request->get('to_date', now()->toDateString());

        $payments = DbCustomerPayment::whereBetween('payment_date', [$fromDate, $toDate])
            ->where('status', 'active');

        $summary = [
            'collection' => (clone $payments)->where('payment_type', 'received')->sum('payment'),
            'advance' => (clone $payments)->where('payment_type', 'advance')->sum('payment'),
            'refund' => abs((clone $payments)->where('payment_type', 'refund')->sum('payment')),
            'net_collection' => (clone $payments)->sum('payment'),
            'order_due' => ProductOrder::where('status', 'active')->directCustomerReceivable()->sum('due_amount'),
            'old_due' => CustomerOpeningBalance::where('entry_type', 'due')->where('status', 'active')->sum('remaining_amount'),
            'advance_liability' => Customer::where('status', 'active')->sum('available_advance'),
        ];
        $summary['total_due'] = $summary['order_due'] + $summary['old_due'];

        $methodCollections = DbCustomerPayment::select('payment_mode_title', DB::raw('SUM(payment) as total'))
            ->whereBetween('payment_date', [$fromDate, $toDate])
            ->where('status', 'active')
            ->whereIn('payment_type', ['received', 'advance'])
            ->groupBy('payment_mode_title')
            ->orderByDesc('total')
            ->get();

        $recentTransactions = DbCustomerPayment::with(['customer', 'order', 'openingBalance'])
            ->where('status', 'active')
            ->orderBy('payment_date', 'desc')
            ->orderBy('id', 'desc')
            ->take(10)
            ->get();

        $topDueCustomers = Customer::where('status', 'active')
            ->where('due', '>', 0)
            ->orderByDesc('due')
            ->take(10)
            ->get();

        return view('backend.customer_payment.dashboard', compact(
            'fromDate',
            'toDate',
            'summary',
            'methodCollections',
            'recentTransactions',
            'topDueCustomers'
        ));
    }

    public function report(Request $request)
    {
        $this->transactionService->ensureAccountingSetup();

        $fromDate = $request->get('from_date', now()->startOfMonth()->toDateString());
        $toDate = $request->get('to_date', now()->toDateString());
        $customerId = $request->get('customer_id');
        $paymentType = $request->get('payment_type');
        $paymentMode = $request->get('payment_mode');

        $query = DbCustomerPayment::with(['customer', 'order', 'openingBalance'])
            ->whereBetween('payment_date', [$fromDate, $toDate])
            ->where('status', 'active');

        if ($customerId) {
            $query->where('customer_id', $customerId);
        }
        if ($paymentType) {
            $query->where('payment_type', $paymentType);
        }
        if ($paymentMode) {
            $query->where('payment_mode', $paymentMode);
        }

        $transactions = $query->orderBy('payment_date', 'desc')->orderBy('id', 'desc')->get();
        $transactionIds = $transactions->pluck('id')->all();

        $accountRows = AcTransaction::with(['debitAccount', 'creditAccount'])
            ->whereIn('ref_customer_payment_id', $transactionIds)
            ->get()
            ->groupBy('ref_customer_payment_id');

        $customers = Customer::where('status', 'active')->orderBy('name')->get();
        $paymentMethods = DbPaymentType::where('status', 'active')->orderBy('payment_type')->get();

        return view('backend.customer_payment.report', compact(
            'fromDate',
            'toDate',
            'customerId',
            'paymentType',
            'paymentMode',
            'transactions',
            'accountRows',
            'customers',
            'paymentMethods'
        ));
    }

    /**
     * Show payment form with order
     */
    public function createWithOrder($orderId)
    {
        $order = ProductOrder::with(['customer', 'order_products'])->findOrFail($orderId);

        if (($order->order_source ?? null) === 'ecommerce') {
            Toastr::warning('Ecommerce receivable is settled through courier settlement, not customer payment.', 'Warning');
            return redirect()->route('OrderListPage', ['order_source' => 'ecommerce']);
        }

        // Get all due orders for this customer
        $dueOrders = ProductOrder::where('customer_id', $order->customer_id)
            ->where('due_amount', '>', 0)
            ->where('status', 'active')
            ->directCustomerReceivable()
            ->orderBy('sale_date', 'asc')
            ->get();

        // Get customer's available advance
        $customer = Customer::find($order->customer_id);
        $availableAdvance = $customer->available_advance ?? 0;

        return view('backend.customer_payment.create_with_order', compact('order', 'dueOrders', 'availableAdvance'));
    }

    /**
     * Show payment form without order (advance payment)
     */
    public function create()
    {
        $this->transactionService->ensureAccountingSetup();
        $paymentMethods = DbPaymentType::where('status', 'active')->get();
        $customers = Customer::where('status', 'active')->get();
        $dueOnly = false;
        return view('backend.customer_payment.create', compact('customers', 'paymentMethods', 'dueOnly'));
    }

    public function createDue(Request $request)
    {
        $this->transactionService->ensureAccountingSetup();
        $paymentMethods = DbPaymentType::where('status', 'active')->get();
        $selectedCustomerId = $request->filled('customer_id') ? (int) $request->customer_id : null;
        $customers = Customer::where('status', 'active')
            ->where(function ($query) use ($selectedCustomerId) {
                $query->where('due', '>', 0)
                    ->orWhereHas('orders', function ($orderQuery) {
                        $orderQuery->where('status', 'active')
                            ->directCustomerReceivable()
                            ->where('due_amount', '>', 0);
                    })
                    ->orWhereHas('openingBalances', function ($openingQuery) {
                        $openingQuery->where('status', 'active')
                            ->where('entry_type', 'due')
                            ->where('remaining_amount', '>', 0);
                    });

                if ($selectedCustomerId) {
                    $query->orWhere('id', $selectedCustomerId);
                }
            })
            ->orderBy('name')
            ->get();
        $dueOnly = true;
        return view('backend.customer_payment.create', compact('customers', 'paymentMethods', 'dueOnly', 'selectedCustomerId'));
    }



    public function createOpeningBalance()
    {
        $this->transactionService->ensureAccountingSetup();
        $customers = Customer::where('status', 'active')->orderBy('name')->get();
        $openingBalances = CustomerOpeningBalance::with('customer')
            ->where('status', 'active')
            ->orderBy('id', 'desc')
            ->take(50)
            ->get();

        return view('backend.customer_payment.opening_balance', compact('customers', 'openingBalances'));
    }

    public function storeOpeningBalance(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'entry_type' => 'required|in:due,advance',
            'amount' => 'required|numeric|min:0.01',
            'opening_date' => 'required|date',
            'reference_no' => 'nullable|string|max:100',
            'note' => 'nullable|string',
        ]);

        try {
            $this->transactionService->createOpeningBalance($request->only([
                'customer_id',
                'entry_type',
                'amount',
                'opening_date',
                'reference_no',
                'note',
            ]));

            Toastr::success('Opening balance saved successfully!', 'Success');
            return redirect()->route('CreateCustomerOpeningBalance');
        } catch (\Exception $e) {
            Toastr::error($e->getMessage(), 'Error');
            return back()->withInput();
        }
    }

    /**
     * Get customer due orders (AJAX)
     */
    public function getCustomerDueOrders($customerId)
    {
        try {
            $customer = Customer::findOrFail($customerId);

            $dueOrders = ProductOrder::where('customer_id', $customerId)
                ->where('due_amount', '>', 0)
                ->where('status', 'active')
                ->directCustomerReceivable()
                ->orderBy('sale_date', 'asc')
                ->get(['id', 'order_code', 'sale_date', 'total', 'paid_amount', 'due_amount', 'order_status']);

            $oldDues = CustomerOpeningBalance::where('customer_id', $customerId)
                ->where('entry_type', 'due')
                ->where('remaining_amount', '>', 0)
                ->where('status', 'active')
                ->orderBy('opening_date', 'asc')
                ->get(['id', 'reference_no', 'opening_date', 'opening_amount', 'paid_amount', 'remaining_amount', 'note']);

            $dueItems = collect();

            foreach ($oldDues as $oldDue) {
                $dueItems->push([
                    'source_type' => 'opening_due',
                    'id' => $oldDue->id,
                    'opening_balance_id' => $oldDue->id,
                    'code' => $oldDue->reference_no ?: 'OLD-DUE-' . $oldDue->id,
                    'date' => optional($oldDue->opening_date)->format('Y-m-d'),
                    'total' => (float) $oldDue->opening_amount,
                    'paid_amount' => (float) $oldDue->paid_amount,
                    'due_amount' => (float) $oldDue->remaining_amount,
                    'label' => 'Old Due',
                ]);
            }

            foreach ($dueOrders as $order) {
                $dueItems->push([
                    'source_type' => 'order_due',
                    'id' => $order->id,
                    'order_id' => $order->id,
                    'code' => $order->order_code,
                    'date' => $order->sale_date,
                    'total' => (float) $order->total,
                    'paid_amount' => (float) $order->paid_amount,
                    'due_amount' => (float) $order->due_amount,
                    'label' => 'Order Due',
                ]);
            }
            return response()->json([
                'success' => true,
                'customer' => $customer,
                'due_orders' => $dueOrders,
                'old_dues' => $oldDues,
                'due_items' => $dueItems->values(),
                'available_advance' => $customer->available_advance ?? 0,
                'total_due' => $dueOrders->sum('due_amount') + $oldDues->sum('remaining_amount')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching customer data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store payment (with FIFO allocation or advance)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'payment_amount' => 'nullable|numeric|min:0',
            'advance_amount' => 'nullable|numeric|min:0',
            'payment_mode' => 'nullable|exists:db_paymenttypes,id',
            'payment_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $paymentAmount = floatval($request->payment_amount);
            $advanceAmount = floatval($request->advance_amount);
            $paymentMethod = $request->payment_mode ? DbPaymentType::findOrFail($request->payment_mode) : null;
            $allocations = [];

            if (($paymentAmount + $advanceAmount) <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment amount or advance amount is required.',
                ], 422);
            }

            if ($paymentAmount > 0 && !$paymentMethod) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment mode is required when cash/bank payment amount is greater than zero.',
                ], 422);
            }

            if ($request->payment_allocations) {
                $allocations = json_decode($request->payment_allocations, true);
                if (!$allocations || !is_array($allocations)) {
                    throw new \Exception('Invalid payment allocation data');
                }
            }

            $this->transactionService->collectPayment(
                (int) $request->customer_id,
                $paymentMethod,
                $paymentAmount,
                $request->payment_date,
                $request->payment_note,
                $allocations,
                $advanceAmount
            );

            Toastr::success('Customer transaction saved successfully!', 'Success');
            return response()->json([
                'success' => true,
                'message' => 'Customer transaction saved successfully!',
                'redirect' => route('ViewAllCustomerPayments')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong! ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show payment return form
     */
    public function createReturn()
    {
        $this->transactionService->ensureAccountingSetup();
        $customers = Customer::where('status', 'active')
            ->where('available_advance', '>', 0)
            ->get();
        $paymentMethods = DbPaymentType::where('status', 'active')->orderBy('payment_type')->get();

        return view('backend.customer_payment.create_return', compact('customers', 'paymentMethods'));
    }

    /**
     * Process payment return (refund)
     */
    public function processReturn(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'refund_amount' => 'required|numeric|min:0.01',
            'payment_mode' => 'required|exists:db_paymenttypes,id',
            'payment_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $refundAmount = floatval($request->refund_amount);
            $paymentMethod = DbPaymentType::findOrFail($request->payment_mode);

            $this->transactionService->refundAdvance(
                (int) $request->customer_id,
                $paymentMethod,
                $refundAmount,
                $request->payment_date,
                $request->payment_note
            );

            Toastr::success('Refund processed successfully!', 'Success');
            return response()->json([
                'success' => true,
                'message' => 'Refund processed successfully!',
                'redirect' => route('ViewAllCustomerPayments')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong! ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * View all customer payments
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = DbCustomerPayment::with(['customer', 'order', 'openingBalance'])
                ->orderBy('id', 'desc')
                ->get();

            return Datatables::of($data)
                ->editColumn('customer', function ($data) {
                    return $data->customer ? $data->customer->name : 'N/A';
                })
                ->editColumn('order_code', function ($data) {
                    if ($data->order_id && $data->order) {
                        return $data->order->order_code;
                    }
                    if ($data->customer_opening_balance_id && $data->openingBalance) {
                        return '<span class="badge badge-warning">Old Due: ' . ($data->openingBalance->reference_no ?: $data->openingBalance->id) . '</span>';
                    }
                    if ($data->payment_type == 'refund') {
                        return '<span class="badge badge-danger">Advance Refund</span>';
                    }
                    if ($data->payment_type == 'adjustment') {
                        return '<span class="badge badge-secondary">Advance Applied</span>';
                    }
                    return '<span class="badge badge-info">Advance Payment</span>';
                })
                ->editColumn('payment_type', function ($data) {
                    $badges = [
                        'received' => '<span class="badge badge-success">Received</span>',
                        'advance' => '<span class="badge badge-info">Advance</span>',
                        'refund' => '<span class="badge badge-danger">Refund</span>',
                        'credit' => '<span class="badge badge-warning">Credit</span>',
                        'adjustment' => '<span class="badge badge-secondary">Adjustment</span>',
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
                ->editColumn('payment_mode', function ($data) {
                    return $data->payment_mode_title ?: 'N/A';
                })
                ->addIndexColumn()
                ->addColumn('action', function ($data) {
                    if (empty($data->customer_id)) {
                        $message = 'Missing required parameter for [Route: ViewCustomerPaymentHistory] [URI: customer-payment-history/{customer_id}] [Missing parameter: customer_id].';

                        return '<span class="btn-sm btn-secondary rounded disabled" title="' . e($message) . '">'
                            . '<i class="fas fa-question-circle"></i> History unavailable</span>';
                    }

                    $btn = '<a href="' . route('ViewCustomerPaymentHistory', $data->customer_id) . '" class="btn-sm btn-info rounded">';
                    $btn .= '<i class="fas fa-history"></i> History</a>';
                    return $btn;
                })
                ->rawColumns(['order_code', 'payment_type', 'payment', 'action'])
                ->make(true);
        }
        return view('backend.customer_payment.index');
    }

    /**
     * View customer payment history
     */
    public function history($customerId)
    {
        $customer = Customer::findOrFail($customerId);

        $payments = DbCustomerPayment::with(['order', 'openingBalance'])
            ->where('customer_id', $customerId)
            ->orderBy('payment_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        // Calculate totals
        $totalPayments = $payments->where('payment', '>', 0)->sum('payment');
        $totalRefunds = abs($payments->where('payment', '<', 0)->sum('payment'));
        $netBalance = $totalPayments - $totalRefunds;

        return view('backend.customer_payment.history', compact('customer', 'payments', 'totalPayments', 'totalRefunds', 'netBalance'));
    }

    /**
     * Add relationship to DbCustomerPayment model
     */
    protected function addRelationships()
    {
        // This is just a note - actual relationship should be added to the model class
        // DbCustomerPayment::customer() -> belongsTo(Customer::class)
        // DbCustomerPayment::order() -> belongsTo(ProductOrder::class)
    }
}

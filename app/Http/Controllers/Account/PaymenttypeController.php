<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Account\Models\AcAccount;
use App\Http\Controllers\Account\Models\AcTransaction;
use App\Http\Controllers\Account\Models\DbPaymentType;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

use Yajra\DataTables\DataTables;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PaymenttypeController extends Controller
{
    public function addNewPaymentType()
    {
        // $payment_types = DbPaymentType::where('status', 'active')->get();
        // $users = User::where('status', 1)->get();
        // return view('backend.paymenttype.create', compact('customer_categories', 'customer_source_types', 'users'));
        return view('backend.paymenttype.create');
    }

    public function saveNewPaymentType(Request $request)
    {
        // dd(request()->all());
        $request->validate([
            'payment_type' => ['required', 'string', 'max:100'],
            'payment_category' => ['nullable', 'string', 'max:50'],
        ], [
            'payment_type.required' => 'Payment type is required.',
            'payment_type.max' => 'Payment type must not exceed 100 characters.',
            'payment_category.max' => 'Payment category must not exceed 50 characters.',
        ]);

        $clean = preg_replace('/[^a-zA-Z0-9\s]/', '', strtolower(request()->payment_type)); //remove all non alpha numeric
        $slug = preg_replace('!\s+!', '-', $clean);

        // $customer_category = CustomerCategory::where('id', request()->customer_category_id)->first();
        // $customer_source_type = CustomerSourceType::where('id', request()->customer_source_type_id)->first();
        // dd(5);

        $payment_type = DbPaymentType::create([
            'store_id' => 1,
            'payment_type' => request()->payment_type ?? '',
            'payment_category' => request()->payment_category ?? null,

            'creator' => auth()->user()->id,
            'slug' => $slug . time(),
            'status' => 'active',
            'created_at' => Carbon::now('Asia/Dhaka')
        ]);

        $payment_category = request()->payment_category ?? null;
        $parent_id = 3;
        $account = AcAccount::create([
            'store_id' => 1,
            'parent_id' => $parent_id,
            'account_type' => 'asset',
            'normal_balance' => 'debit',
            'is_system_account' => true,
            'is_control_account' => false,
            'sort_code' => '1110' . $payment_type->id,
            'account_code' => 'AC-1110' . $payment_type->id,
            'account_name' => request()->payment_type,
            'account_selection_name' => str_replace(' ', '_', strtolower(request()->payment_type)),
            'paymenttypes_id' => $payment_type->id,
            'balance' => 0,
            'status' => 'active',
            'created_at' => Carbon::now('Asia/Dhaka'),
            'updated_at' => Carbon::now('Asia/Dhaka'),
        ]);

        $payment_type->debit_account_id = $account->id;
        $payment_type->credit_account_id = $account->id;
        $payment_type->save();

        Toastr::success('Added successfully!', 'Success');
        return back();
    }

    // public function viewAllPaymentType(Request $request)
    // {
    //     if ($request->ajax()) {
    //         $data = DbPaymentType::with('user')
    //             ->where('status', 'active')
    //             ->orderBy('id', 'DESC')
    //             ->get();

    //         $col_data = Datatables::of($data)
    //             // ->editColumn('status', function ($data) {
    //             //     return $data->status == "active" ? 'Active' : 'Inactive';
    //             // })
    //             // ->editColumn('created_at', function ($data) {
    //             //     return date("Y-m-d", strtotime($data->created_at));
    //             // })
    //             // ->addIndexColumn()
    //             ->addColumn('payment_type', function ($data) {
    //                 return $data->payment_type ? $data->payment_type : 'N/A';
    //             });
    //         // ->addColumn('customer_source_type', function ($data) {
    //         //     return $data->customerSourceType ? $data->customerSourceType->title : 'N/A';
    //         // })
    //         // ->addColumn('reference_by', function ($data) {
    //         //     return $data->referenceBy ? $data->referenceBy->name : 'N/A';
    //         // })

    //             $col_data->addColumn('user', function ($data) {
    //                 return $data->user ? $data->user->name : 'N/A';
    //             });

    //             // return $col_data;
    //             $col_data->addColumn('action', function ($data) {
    //                 $btn = '<a href="' . url('edit/payment-type') . '/' . $data->slug . '" class="btn-sm btn-warning rounded editBtn"><i class="fas fa-edit"></i></a>';
    //                 $btn .= ' <a href="javascript:void(0)" data-toggle="tooltip" data-id="' . $data->slug . '" data-original-title="Delete" class="btn-sm btn-danger rounded deleteBtn"><i class="fas fa-trash-alt"></i></a>';
    //                 return $btn;
    //             })
    //             ;

    //         return $col_data->rawColumns(['action'])
    //         ->make(true);
    //     }
    //     return view('backend.paymenttype.view');
    // }



    public function viewAllPaymentType(Request $request)
    {
        if ($request->ajax()) {
            $data = DbPaymentType::with('user');
            // ->orderBy('id', 'desc');

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('user', function ($data) {
                    return $data->user ? $data->user->name : '';
                })
                ->addColumn('payment_category', function ($data) {
                    return $data->payment_category ? ucfirst($data->payment_category) : '';
                })
                ->addColumn('total_amount', function ($data) {
                    $amount = $data->total_amount ?? 0;
                    $formatted = number_format($amount, 2);
                    $color = $amount >= 0 ? 'text-success' : 'text-danger';
                    return '<span class="' . $color . ' font-weight-bold">৳ ' . $formatted . '</span>';
                })
                ->addColumn('action', function ($data) {
                    $btn = '<a href="' . route('PaymentTypeHistory', $data->slug) . '" class="btn-sm btn-info rounded" title="History"><i class="fas fa-history"></i></a>';
                    $btn .= ' <a href="' . route('CreateFundTransfer', ['from_payment_type_id' => $data->id]) . '" class="btn-sm btn-success rounded" title="Fund Transfer"><i class="fas fa-exchange-alt"></i></a>';
                    $btn .= ' <a href="' . url('edit/payment-type') . '/' . $data->slug . '" class="btn-sm btn-warning rounded editBtn"><i class="fas fa-edit"></i></a>';
                    $btn .= ' <a href="javascript:void(0)" data-toggle="tooltip" data-id="' . $data->slug . '" data-original-title="Delete" class="btn-sm btn-danger rounded deleteBtn"><i class="fas fa-trash-alt"></i></a>';
                    return $btn;
                })
                ->rawColumns(['total_amount', 'action'])
                ->make(true);
        }
        return view('backend.paymenttype.view');
    }

    public function paymentTypeHistory(Request $request, $slug)
    {
        $paymentType = DbPaymentType::where('slug', $slug)->firstOrFail();
        $accountIds = $this->paymentTypeAccountIds($paymentType);

        $defaultEndDate = Carbon::now('Asia/Dhaka')->toDateString();
        $defaultStartDate = Carbon::now('Asia/Dhaka')->subDays(4)->toDateString();

        if ($request->ajax()) {
            $startDate = $request->get('start_date', $defaultStartDate);
            $endDate = $request->get('end_date', $defaultEndDate);

            $request->validate([
                'start_date' => ['nullable', 'date', 'before_or_equal:end_date'],
                'end_date' => ['nullable', 'date'],
            ]);

            if ($accountIds->isEmpty()) {
                return Datatables::of(collect())->make(true);
            }

            $openingBalance = $this->paymentTypeBalanceBeforeDate($accountIds, $startDate);

            $rows = AcTransaction::query()
                ->where('status', 'active')
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->where(function ($query) use ($accountIds) {
                    $query->whereIn('debit_account_id', $accountIds->all())
                        ->orWhereIn('credit_account_id', $accountIds->all());
                })
                ->select('transaction_date')
                ->selectRaw('SUM(CASE WHEN debit_account_id IN (' . $accountIds->implode(',') . ') THEN COALESCE(debit_amt, 0) ELSE 0 END) as income')
                ->selectRaw('SUM(CASE WHEN credit_account_id IN (' . $accountIds->implode(',') . ') THEN COALESCE(credit_amt, 0) ELSE 0 END) as expense')
                ->groupBy('transaction_date')
                ->orderBy('transaction_date', 'asc')
                ->get();

            $runningBalance = $openingBalance;
            $formattedRows = $rows->map(function ($row) use (&$runningBalance, $paymentType) {
                $income = (float) $row->income;
                $expense = (float) $row->expense;
                $runningBalance += ($income - $expense);

                return [
                    'transaction_date' => Carbon::parse($row->transaction_date)->format('d M Y'),
                    'date_value' => $row->transaction_date,
                    'income' => $this->moneyHtml($income, 'text-success'),
                    'expense' => $this->moneyHtml($expense, 'text-danger'),
                    'balance' => $this->moneyHtml($runningBalance, $runningBalance >= 0 ? 'text-primary' : 'text-danger'),
                    'action' => '<button type="button" class="btn btn-sm btn-outline-info history-detail-btn" data-date="' . e($row->transaction_date) . '" data-title="' . e($paymentType->payment_type) . '"><i class="fas fa-list"></i> Details</button>',
                ];
            });

            return Datatables::of($formattedRows)
                ->rawColumns(['income', 'expense', 'balance', 'action'])
                ->make(true);
        }

        $currentBalance = $paymentType->total_amount;

        return view('backend.paymenttype.history', compact(
            'paymentType',
            'defaultStartDate',
            'defaultEndDate',
            'currentBalance'
        ));
    }

    public function paymentTypeHistoryDetails(Request $request, $slug)
    {
        $paymentType = DbPaymentType::where('slug', $slug)->firstOrFail();
        $accountIds = $this->paymentTypeAccountIds($paymentType);

        $request->validate([
            'date' => ['required', 'date'],
        ]);

        if ($accountIds->isEmpty()) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $transactions = AcTransaction::with(['debitAccount:id,account_name', 'creditAccount:id,account_name'])
            ->where('status', 'active')
            ->whereDate('transaction_date', $request->date)
            ->where(function ($query) use ($accountIds) {
                $query->whereIn('debit_account_id', $accountIds->all())
                    ->orWhereIn('credit_account_id', $accountIds->all());
            })
            ->orderBy('created_at')
            ->get()
            ->map(function ($transaction) use ($accountIds) {
                $isIncome = $transaction->debit_account_id && $accountIds->contains((int) $transaction->debit_account_id);
                $amount = $isIncome ? (float) $transaction->debit_amt : (float) $transaction->credit_amt;
                $reference = $this->transactionReference($transaction);

                return [
                    'date' => $transaction->transaction_date ? Carbon::parse($transaction->transaction_date)->format('Y-m-d') : '',
                    'payment_code' => $transaction->payment_code ?? '',
                    'type' => $isIncome ? 'Income' : 'Expense',
                    'account' => $isIncome
                        ? optional($transaction->debitAccount)->account_name
                        : optional($transaction->creditAccount)->account_name,
                    'amount' => number_format($amount, 2),
                    'note' => $transaction->note ?? '',
                    'reference' => $reference,
                ];
            });

        return response()->json([
            'success' => true,
            'payment_type' => $paymentType->payment_type,
            'date' => Carbon::parse($request->date)->format('d M Y'),
            'data' => $transactions,
        ]);
    }


    public function editPaymentType($slug)
    {
        $data = DbPaymentType::where('slug', $slug)->first();
        return view('backend.paymenttype.edit', compact('data'));
    }

    public function updatePaymentType(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'payment_type' => ['required', 'string', 'max:100'],
            'payment_category' => ['nullable', 'string', 'max:50'],
        ], [
            'payment_type.required' => 'Payment type is required.',
            'payment_type.max' => 'Payment type must not exceed 100 characters.',
            'payment_category.max' => 'Payment category must not exceed 50 characters.',
        ]);

        // Check if the selected product_warehouse_room_id exists for the selected product_warehouse_id        
        $data = DbPaymentType::where('id', request()->paymenttype_id)->first();

        $clean = preg_replace('/[^a-zA-Z0-9\s]/', '', strtolower($data->payment_type)); //remove all non alpha numeric
        $slug = preg_replace('!\s+!', '-', $clean);

        $data->store_id = request()->store_id ?? $data->store_id;
        $data->payment_type = request()->payment_type ?? $data->payment_type;
        $data->payment_category = request()->payment_category ?? $data->payment_category;
        $data->status = request()->status ?? $data->status;

        $data->creator = auth()->user()->id;
        $data->status = request()->status ?? $data->status;
        $data->updated_at = Carbon::now();
        $data->save();

        $payment_category = request()->payment_category ?? null;
        $parent_id = 3;

        $check_exist = AcAccount::where('paymenttypes_id', $data->id)->first();
        if(!$check_exist){
            $account = AcAccount::create([
                'store_id' => 1,
                'parent_id' => $parent_id,
                'account_type' => 'asset',
                'normal_balance' => 'debit',
                'is_system_account' => true,
                'is_control_account' => false,
                'sort_code' => '1110' . $data->id,
                'account_code' => 'AC-1110' . $data->id,
                'account_name' => request()->payment_type,
                'account_selection_name' => str_replace(' ', '_', strtolower(request()->payment_type)),
                'paymenttypes_id' => $data->id,
                'balance' => 0,
                'status' => 'active',
                'created_at' => Carbon::now('Asia/Dhaka'),
                'updated_at' => Carbon::now('Asia/Dhaka'),
            ]);
    
            $data->debit_account_id = $account->id;
            $data->credit_account_id = $account->id;
            $data->save();
        }

        Toastr::success('Successfully Updated', 'Success!');
        return redirect()->route('ViewAllPaymentType');
    }


    public function deletePaymentType($slug)
    {
        $data = DbPaymentType::where('slug', $slug)->first();

        $data->delete();
        // $data->status = 'inactive';
        // $data->save();

        return response()->json([
            'success' => 'Deleted successfully!',
            'data' => 1
        ]);
    }

    private function paymentTypeAccountIds(DbPaymentType $paymentType)
    {
        $directIds = collect([$paymentType->debit_account_id, $paymentType->credit_account_id])
            ->filter()
            ->map(fn ($id) => (int) $id);

        $linkedIds = AcAccount::where('paymenttypes_id', $paymentType->id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id);

        return $directIds->merge($linkedIds)->unique()->values();
    }

    private function paymentTypeBalanceBeforeDate($accountIds, string $date): float
    {
        $debits = (float) AcTransaction::whereIn('debit_account_id', $accountIds->all())
            ->where('status', 'active')
            ->whereDate('transaction_date', '<', $date)
            ->sum('debit_amt');

        $credits = (float) AcTransaction::whereIn('credit_account_id', $accountIds->all())
            ->where('status', 'active')
            ->whereDate('transaction_date', '<', $date)
            ->sum('credit_amt');

        return $debits - $credits;
    }

    private function moneyHtml(float $amount, string $class): string
    {
        return '<span class="' . $class . ' font-weight-bold">৳ ' . number_format($amount, 2) . '</span>';
    }

    private function transactionReference(AcTransaction $transaction): array
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
}

<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Account\Models\DbExpense;
use App\Http\Controllers\Account\Models\DbExpenseCategory;
use App\Http\Controllers\Account\Models\DbExpensePayment;
use App\Http\Controllers\Account\Models\DbPaymentType;
use App\Http\Controllers\Account\Models\DbTax;
use App\Http\Controllers\Account\Models\AcAccount;
use App\Http\Controllers\Account\Models\AcTransaction;
use App\Http\Controllers\Outlet\Models\Outlet;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Yajra\DataTables\DataTables;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ExpenseController extends Controller
{
    public function addNewExpense()
    {
        $expense_categories = DbExpenseCategory::where('status', 'active')
            ->with(['debitAccount', 'creditAccount'])
            ->get();
        $payment_types = DbPaymentType::where('status', 'active')->get();
        $accounts = AcAccount::where('status', 'active')->get();
        $taxes = DbTax::where('status', 'active')->get();
        $stores = Outlet::where('status', 'active')->get();

        return view('backend.expense.create', compact(
            'expense_categories',
            'payment_types',
            'accounts',
            'taxes',
            'stores'
        ));
    }

    public function create()
    {
        return $this->addNewExpense();
    }

    public function edit($id)
    {
        $expense = DbExpense::findOrFail($id);

        return $this->editExpense($expense->slug);
    }

    public function update(Request $request, $id)
    {
        $expense = DbExpense::with('payments')->findOrFail($id);

        $hasPayment = $expense->payments()->where('status', 'active')->exists();

        $rules = [
            'category_id' => ['nullable', 'exists:db_expense_categories,id'],
            'expense_date' => ['nullable', 'date'],
            'reference_no' => ['nullable', 'string', 'max:255'],
            'expense_for' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
        ];

        if (! $hasPayment) {
            $rules['expense_amt'] = ['nullable', 'numeric', 'min:1'];
            $rules['tax_id'] = ['nullable'];
        }

        $request->validate($rules);

        DB::beginTransaction();
        try {
            $category = $request->category_id ? DbExpenseCategory::findOrFail($request->category_id) : $expense->expense_category;

            $data = [
                'category_id' => $request->category_id ?? $expense->category_id,
                'expense_date' => $request->expense_date ?? $expense->expense_date,
                'reference_no' => $request->reference_no ?? $expense->reference_no,
                'expense_for' => $request->expense_for ?? $expense->expense_for,
                'note' => $request->note ?? $expense->note,
                'debit_account_id' => $category->debit_id ?? $expense->debit_account_id,
                'credit_account_id' => $category->credit_id ?? $expense->credit_account_id,
                'payment_type_id' => $category->credit_id ? ($this->paymentTypeIdForAccount((int) $category->credit_id) ?? $expense->payment_type_id) : $expense->payment_type_id,
                'updated_at' => now('Asia/Dhaka'),
            ];

            if (! $hasPayment && $request->filled('expense_amt')) {
                $expenseAmount = (float) $request->expense_amt;
                $taxAmount = $this->calculateTaxAmount($expenseAmount, $request->tax_id, $request->tax_amount);
                $finalAmount = $expenseAmount + $taxAmount;
                [$paymentStatus, $expenseStatus, $dueAmount] = $this->paymentState($finalAmount, 0);

                $data += [
                    'tax_id' => $request->tax_id,
                    'expense_amt' => $expenseAmount,
                    'tax_amount' => $taxAmount,
                    'final_amount' => $finalAmount,
                    'paid_amount' => 0,
                    'due_amount' => $dueAmount,
                    'payment_status' => $paymentStatus,
                    'expense_status' => $expenseStatus,
                ];
            }

            $expense->update($data);
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Expense updated successfully.',
                'data' => $expense->fresh(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get expense category details including account mappings and balance
     */
    public function getExpenseCategoryDetails(Request $request)
    {
        try {
            $categoryId = $request->category_id;

            if (!$categoryId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category ID is required'
                ]);
            }

            $category = DbExpenseCategory::findOrFail($categoryId);

            if (!$category->credit_id || !$category->debit_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Expense category account mapping is incomplete'
                ]);
            }

            // Get credit account and calculate balance
            $creditAccount = AcAccount::findOrFail($category->credit_id);
            $debitAccount = AcAccount::findOrFail($category->debit_id);
            $paymentType = DbPaymentType::where('credit_account_id', $creditAccount->id)
                ->orWhere('debit_account_id', $creditAccount->id)
                ->orWhereHas('accounts', function ($query) use ($creditAccount) {
                    $query->where('ac_accounts.id', $creditAccount->id);
                })
                ->first();

            // Calculate balance for credit account
            $debits = AcTransaction::where('debit_account_id', $creditAccount->id)
                ->where('status', 'active')
                ->sum('debit_amt') ?? 0;
            $credits = AcTransaction::where('credit_account_id', $creditAccount->id)
                ->where('status', 'active')
                ->sum('credit_amt') ?? 0;

            $balance = 0;
            if ($creditAccount->account_type === 'asset' || $creditAccount->account_type === 'expense') {
                $balance = $debits - $credits;
            } elseif ($creditAccount->account_type === 'liability' || $creditAccount->account_type === 'equity' || $creditAccount->account_type === 'revenue') {
                $balance = $credits - $debits;
            } else {
                $balance = $debits - $credits;
            }

            return response()->json([
                'success' => true,
                'credit_account' => [
                    'id' => $creditAccount->id,
                    'name' => $creditAccount->account_name,
                    'balance' => $balance,
                    'formatted_balance' => number_format($balance, 2)
                ],
                'debit_account' => [
                    'id' => $debitAccount->id,
                    'name' => $debitAccount->account_name
                ],
                'payment_type' => [
                    'id' => $paymentType->id ?? null,
                    'name' => $paymentType->payment_type ?? null,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get account balance by account_id (for Paid From balance display)
     */
    public function getAccountBalance(Request $request)
    {
        try {
            $accountId = $request->account_id;
            if (!$accountId) {
                return response()->json(['success' => false, 'message' => 'Account ID is required', 'balance' => 0, 'formatted_balance' => '0.00']);
            }
            $account = AcAccount::findOrFail($accountId);
            $debits = AcTransaction::where('debit_account_id', $account->id)->where('status', 'active')->sum('debit_amt') ?? 0;
            $credits = AcTransaction::where('credit_account_id', $account->id)->where('status', 'active')->sum('credit_amt') ?? 0;
            $balance = 0;
            if ($account->account_type === 'asset' || $account->account_type === 'expense') {
                $balance = $debits - $credits;
            } elseif ($account->account_type === 'liability' || $account->account_type === 'equity' || $account->account_type === 'revenue') {
                $balance = $credits - $debits;
            } else {
                $balance = $debits - $credits;
            }
            return response()->json([
                'success' => true,
                'balance' => $balance,
                'formatted_balance' => number_format($balance, 2),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'balance' => 0, 'formatted_balance' => '0.00']);
        }
    }

    public function saveNewExpense(Request $request)
    {
        return $this->store($request);
    }

    public function store(Request $request)
    {
        $request->merge([
            'category_id' => $request->input('category_id', $request->input('expense_category_id')),
            'store_id' => $request->input('store_id', auth()->user()->store_id ?? 1),
        ]);

        $rules = [
            'store_id' => ['required'],
            'category_id' => ['required', 'exists:db_expense_categories,id'],
            'expense_date' => ['required', 'date'],
            'expense_amt' => ['required', 'numeric', 'min:1'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'reference_no' => ['nullable', 'string', 'max:255'],
            'expense_for' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
        ];

        if ((float) $request->input('paid_amount', 0) > 0) {
            $rules['payment_type_id'] = ['required', 'exists:db_paymenttypes,id'];
            $rules['account_id'] = ['required', 'exists:ac_accounts,id'];
            $rules['paid_on'] = ['required', 'date'];
        }

        $request->validate($rules);

        DB::beginTransaction();
        try {
            $category = DbExpenseCategory::findOrFail($request->category_id);
            $expenseAmount = (float) $request->expense_amt;
            $taxAmount = $this->calculateTaxAmount($expenseAmount, $request->tax_id, $request->tax_amount);
            $finalAmount = $expenseAmount + $taxAmount;
            $paidAmount = (float) ($request->paid_amount ?? 0);
            $paymentAccountId = null;

            if ($paidAmount > $finalAmount) {
                throw new \Exception('Paid amount cannot be greater than final amount.');
            }

            [$paymentStatus, $expenseStatus, $dueAmount] = $this->paymentState($finalAmount, $paidAmount);

            if ($paidAmount > 0) {
                $paymentAccountId = $this->expensePaymentSourceAccountId(
                    $category,
                    (int) $request->payment_type_id,
                    $request->filled('account_id') ? (int) $request->account_id : null
                );

                if (!$paymentAccountId) {
                    throw new \Exception('Source account was not found for the selected payment method.');
                }

                if ($this->accountBalance($paymentAccountId) < $paidAmount) {
                    throw new \Exception('Insufficient account balance.');
                }
            }

            $storeId = $request->store_id;
            $countId = ((int) DbExpense::where('store_id', $storeId)->max('count_id')) + 1;
            $expenseCode = 'EXP-' . str_pad((string) $countId, 6, '0', STR_PAD_LEFT);

            $expense = DbExpense::create([
                'product_website_id' => $request->product_website_id,
                'store_id' => $storeId,
                'count_id' => $countId,
                'category_id' => $category->id,
                'payment_type_id' => $paidAmount > 0 ? $request->payment_type_id : null,
                'account_id' => $paymentAccountId,
                'debit_account_id' => $category->debit_id,
                'credit_account_id' => $paidAmount > 0 ? $paymentAccountId : ($category->credit_id ?? null),
                'expense_code' => $expenseCode,
                'expense_date' => $request->expense_date,
                'reference_no' => $request->reference_no,
                'expense_for' => $request->expense_for,
                'tax_id' => $request->tax_id,
                'expense_amt' => $expenseAmount,
                'tax_amount' => $taxAmount,
                'final_amount' => $finalAmount,
                'paid_amount' => $paidAmount,
                'due_amount' => $dueAmount,
                'payment_status' => $paymentStatus,
                'expense_status' => $expenseStatus,
                'paid_on' => $paidAmount > 0 ? $request->paid_on : null,
                'payment_note' => $paidAmount > 0 ? $request->payment_note : null,
                'note' => $request->note,
                'created_time' => now('Asia/Dhaka')->format('H:i:s'),
                'system_ip' => $request->ip(),
                'system_name' => gethostname(),
                'creator' => auth()->id(),
                'slug' => Str::slug($request->expense_for ?: $expenseCode) . '-' . time() . rand(1000, 9999),
                'status' => 'active',
                'created_at' => now('Asia/Dhaka'),
            ]);

            if ($paidAmount > 0) {
                $this->createExpensePayment($expense, $paidAmount, $request->paid_on, $request->payment_type_id, $paymentAccountId, $request->payment_note);
            }

            DB::commit();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Expense saved successfully.',
                    'data' => $expense->load(['expense_category', 'payments']),
                ]);
            }

            Toastr::success('Expense added successfully!', 'Success');
            return redirect()->route('ViewAllExpense');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Expense Create Error: ' . $e->getMessage(), [
                'exception' => $e,
                'request' => $request->all(),
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            Toastr::error('Error: ' . $e->getMessage(), 'Error');
            return back()->withInput();
        }
    }

    public function viewAllExpense(Request $request)
    {
        // dd(5);
        if ($request->ajax()) {
            $data = DbExpense::with([
                'user',
                'expense_category.debitAccount',
                'expense_category.creditAccount',
                'payment_type',
                'debitAccount',
                'creditAccount',
            ]);

            return Datatables::of($data)
                ->addColumn('expense_code', function ($data) {
                    return $data->expense_code ?: ('EXP-' . str_pad((string) $data->id, 6, '0', STR_PAD_LEFT));
                })
                ->addColumn('expense_date', function ($data) {
                    return $data->expense_date ? date('Y-m-d', strtotime($data->expense_date)) : 'N/A';
                })
                ->addColumn('category', function ($data) {
                    return $data->expense_category ? $data->expense_category->category_name : 'N/A';
                })
                ->addColumn('from_account', function ($data) {
                    if ($data->creditAccount) {
                        return $data->creditAccount->account_name;
                    }
                    if ($data->expense_category && $data->expense_category->creditAccount) {
                        return $data->expense_category->creditAccount->account_name;
                    }
                    return 'N/A';
                })
                ->addColumn('to_account', function ($data) {
                    if ($data->debitAccount) {
                        return $data->debitAccount->account_name;
                    }
                    if ($data->expense_category && $data->expense_category->debitAccount) {
                        return $data->expense_category->debitAccount->account_name;
                    }
                    return 'N/A';
                })
                ->addColumn('payment_type', function ($data) {
                    return $data->payment_type ? $data->payment_type->payment_type : 'N/A';
                })
                ->addColumn('user', function ($data) {
                    return $data->user ? $data->user->name : 'N/A';
                })
                ->editColumn('expense_amt', function ($data) {
                    return '৳ ' . number_format($data->expense_amt, 2);
                })
                ->addColumn('paid_amount', function ($data) {
                    return '৳ ' . number_format((float) ($data->paid_amount ?? 0), 2);
                })
                ->addColumn('due_amount', function ($data) {
                    $due = $data->due_amount ?? max(0, (float) ($data->final_amount ?: $data->expense_amt) - (float) ($data->paid_amount ?? 0));
                    return '৳ ' . number_format((float) $due, 2);
                })
                ->addColumn('payment_status_badge', function ($data) {
                    $status = $data->payment_status ?: ((float) ($data->paid_amount ?? 0) > 0 ? 'paid' : 'due');
                    $classes = [
                        'paid' => 'success',
                        'partial' => 'warning',
                        'due' => 'danger',
                        'cancelled' => 'secondary',
                    ];
                    $class = $classes[$status] ?? 'secondary';
                    return '<span class="badge badge-' . $class . '">' . ucfirst($status) . '</span>';
                })
                ->editColumn('created_at', function ($data) {
                    return date("Y-m-d h:i", strtotime($data->created_at));
                })
                ->addColumn('action', function ($data) {
                    $btn = '<a href="' . route('ViewExpenseDetails', $data->id) . '" class="btn-sm btn-info rounded" title="View Details"><i class="fas fa-eye"></i></a>';
                    if ($data->status === 'active' && $data->slug) {
                        $btn .= ' <a href="' . route('EditExpense', $data->slug) . '" class="btn-sm btn-warning rounded" title="Edit"><i class="fas fa-edit"></i></a>';
                    }
                    $btn .= ' <a href="' . route('PrintExpense', $data->id) . '" target="_blank" class="btn-sm btn-primary rounded" title="Print Voucher"><i class="fas fa-print"></i></a>';
                    if (in_array($data->payment_status, ['due', 'partial', null], true)) {
                        $btn .= ' <a href="' . route('ViewExpenseDetails', $data->id) . '#add-payment" class="btn-sm btn-success rounded" title="Add Payment"><i class="fas fa-money-bill"></i></a>';
                    }
                    if ($data->payment_status !== 'cancelled') {
                        $btn .= ' <form method="POST" action="' . route('expenses.cancel', $data->id) . '" style="display:inline;" onsubmit="return confirm(\'Cancel this expense?\')">'
                            . csrf_field()
                            . '<button type="submit" class="btn-sm btn-danger rounded border-0" title="Cancel"><i class="fas fa-ban"></i></button>'
                            . '</form>';
                    }
                    return $btn;
                })
                ->rawColumns(['action', 'payment_status_badge'])
                ->make(true);
        }
        return view('backend.expense.view');
    }

    public function editExpense($slug)
    {
        $data = DbExpense::where('status', 'active')->where('slug', $slug)->first();
        $accounts = AcAccount::where('status', 'active')->get();
        $expense_categories = DbExpenseCategory::where('status', 'active')->get();
        $payment_types = DbPaymentType::where('status', 'active')->get();
        // $nestedData = $this->buildTree($accounts);
        $nestedDataAll = $this->buildTree($accounts);
        // $nestedDataAll =AcAccount::where('status', 'active')
        //                                 ->where('account_name', '!=', 'Expense')
        //                                 ->get();
        $nestedData = AcAccount::where('account_name', 'Expense')->with('inc')->where('status', 'active')->get();

        // $transaction = AcTransaction::where('status', 'active')->where('payment_code', $data->expense_code)->get();;


        return view(
            'backend.expense.edit',
            compact(
                'data',
                'accounts',
                'expense_categories',
                'payment_types',
                'nestedData',
                'nestedDataAll',
            )
        );
    }

    private function buildTree($accounts, $parentId = null)
    {
        $tree = [];

        foreach ($accounts as $account) {
            // Skip 'Expense' account and its children
            if ($account->account_name === 'Expense') {
                continue;  // Skip this account and its children
            }

            if ($account->parent_id == $parentId) {
                // Recursively build the tree for children
                $children = $this->buildTree($accounts, $account->id);

                // Build the node for the current account
                $node = [
                    'id' => $account->id,
                    'text' => $account->account_name,
                ];

                // If there are children, add them to the node
                if (!empty($children)) {
                    $node['inc'] = $children;
                }

                // Add the current node to the tree
                $tree[] = $node;
            }
        }

        return $tree;
    }

    public function updateExpense(Request $request)
    {
        // dd(request()->all());
        $request->validate([
            'expense_for' => ['required', 'string', 'max:255'],
            'expense_amt' => ['required'],
            'expense_date' => ['required'],
            'expense_category_id' => ['required', 'exists:db_expense_categories,id'],
        ], [
            'expense_for.required' => 'expense for is required.',
            'expense_for.max' => 'expense for must not exceed 100 characters.',
            'expense_amt.required' => 'expense amount is required',
            'expense_date.required' => 'expense date is required',
            'expense_category_id.required' => 'expense category is required',
        ]);

        // Check if the selected product_warehouse_room_id exists for the selected product_warehouse_id        
        $data = DbExpense::where('id', request()->expense_id)->first();
        $hasPayment = $data->payments()->where('status', 'active')->exists();
        $category = request()->expense_category_id
            ? DbExpenseCategory::find(request()->expense_category_id)
            : null;
        $debitAccountId = $category->debit_id ?? $data->debit_account_id;
        $paymentAccountId = $category->credit_id ?? $data->credit_account_id;
        $paymentTypeId = $paymentAccountId ? $this->paymentTypeIdForAccount((int) $paymentAccountId) : null;

        if (!$paymentAccountId) {
            Toastr::error('Payment source account was not found for this expense category.', 'Error');
            return back()->withInput();
        }

        $clean = preg_replace('/[^a-zA-Z0-9\s]/', '', strtolower($data->expense_for)); //remove all non alpha numeric
        $slug = preg_replace('!\s+!', '-', $clean);

        $data->store_id = request()->expense_store_id ?? $data->expense_store_id;
        $data->category_id = request()->expense_category_id ?? $data->expense_category_id;
        $data->account_id = $paymentAccountId;
        $data->debit_account_id = $debitAccountId;
        $data->credit_account_id = $paymentAccountId;
        $data->payment_type_id = $paymentTypeId ?? $data->payment_type_id;
        $data->expense_for = request()->expense_for ?? $data->expense_for;
        $data->expense_code = $data->expense_code ?? '';
        $data->expense_date = request()->expense_date ?? $data->expense_date;
        if (!$hasPayment) {
            $expenseAmount = (float) request()->expense_amt;
            $taxAmount = $this->calculateTaxAmount($expenseAmount, request()->tax_id, request()->tax_amount);
            $finalAmount = $expenseAmount + $taxAmount;
            [$paymentStatus, $expenseStatus, $dueAmount] = $this->paymentState($finalAmount, 0);

            $data->expense_amt = $expenseAmount;
            $data->tax_id = request()->tax_id ?? $data->tax_id;
            $data->tax_amount = $taxAmount;
            $data->final_amount = $finalAmount;
            $data->paid_amount = 0;
            $data->due_amount = $dueAmount;
            $data->payment_status = $paymentStatus;
            $data->expense_status = $expenseStatus;
        }
        $data->reference_no = request()->reference_no ?? $data->reference_no;
        $data->note = request()->note ?? $data->note;


        if ($data->expense_for != $request->expense_for) {
            $data->slug = $slug . time() . rand();
        }

        $data->creator = auth()->user()->id;
        $data->status = request()->status ?? $data->status;
        $data->updated_at = Carbon::now('Asia/Dhaka');
        $data->save();






        // $request->validate([
        //     'deposit_date' => ['required'],
        //     'debit_credit_amount' => ['required'],
        // ], [
        //     'deposit_date.required' => 'deposit date is required.',
        //     'debit_credit_amount.required' => 'amount is required',
        // ]);



        // Check if the selected product_warehouse_room_id exists for the selected product_warehouse_id        
        // $data = AcTransaction::where('id', request()->deposit_id)->first();
        // $data_two = AcTransaction::where('payment_code', $data->payment_code)->get();
        // dd($data_two);


        // Fetch all transactions with the same payment_code
        $data_two = AcTransaction::where('payment_code', $data->expense_code)->get();

        // Loop through the transactions and update based on conditions
        foreach ($data_two as $item) {
            $transactionAmount = $hasPayment
                ? (float) ($item->debit_amt ?: $item->credit_amt ?: $data->expense_amt)
                : (float) request()->expense_amt;

            if ($item->credit_account_id != 0) {
                $item->credit_account_id = $paymentAccountId;
                $item->debit_amt = 0.0000;
                $item->credit_amt = $transactionAmount;
            }

            if ($item->debit_account_id != 0) {
                $item->debit_account_id = $debitAccountId;
                $item->credit_amt = 0.0000;
                $item->debit_amt = $transactionAmount;
            }

            if ($item->credit_account_id == 0) {
                $item->debit_amt = $transactionAmount;
            }
            if ($item->debit_account_id == 0) {
                $item->credit_amt = $transactionAmount;
            }


            $item->transaction_date = request()->deposit_date ?? $item->transaction_date;
            $item->note = request()->note ?? $item->note;
            $item->creator = auth()->user()->id;
            $item->status = request()->status ?? $item->status;
            $item->updated_at = Carbon::now('Asia/Dhaka');
            $item->save();
            // dd($item);
        }

        Toastr::success('Successfully Updated', 'Success!');
        return redirect()->route('ViewAllExpense');
    }

    /**
     * Show expense details
     */
    public function showExpense($id)
    {
        $expense = DbExpense::with([
            'user',
            'expense_category.debitAccount',
            'expense_category.creditAccount',
            'payment_type',
            'payments.payment_type',
            'payments.account',
            'payments.user',
        ])->findOrFail($id);

        $payment_types = DbPaymentType::where('status', 'active')->get();
        $accounts = AcAccount::where('status', 'active')->get();

        return view('backend.expense.details', compact('expense', 'payment_types', 'accounts'));
    }

    /**
     * Print expense voucher
     */
    public function printExpense($id)
    {
        $expense = DbExpense::with([
            'user',
            'expense_category.debitAccount',
            'expense_category.creditAccount',
            'payment_type'
        ])->findOrFail($id);

        // Get general info for company details
        $generalInfo = \App\Models\GeneralInfo::first();

        return view('backend.expense.print', compact('expense', 'generalInfo'));
    }

    public function cancel($id)
    {
        DB::beginTransaction();
        try {
            $expense = DbExpense::with('payments')->findOrFail($id);

            $expense->payments()->update(['status' => 'cancelled']);

            AcTransaction::where('ref_expense_id', $expense->id)
                ->where('transaction_type', 'EXPENSE')
                ->update(['status' => 'inactive']);

            $expense->update([
                'payment_status' => 'cancelled',
                'expense_status' => 'cancelled',
                'status' => 'inactive',
                'updated_at' => now('Asia/Dhaka'),
            ]);

            DB::commit();
            Toastr::success('Expense cancelled successfully.', 'Success');
            return back();
        } catch (\Exception $e) {
            DB::rollBack();
            Toastr::error($e->getMessage(), 'Error');
            return back();
        }
    }

    private function calculateTaxAmount(float $expenseAmount, ?int $taxId, $requestTaxAmount = null): float
    {
        if ($taxId) {
            $tax = DbTax::where('status', 'active')->find($taxId);
            if ($tax) {
                return round(($expenseAmount * (float) $tax->tax) / 100, 4);
            }
        }

        return round((float) ($requestTaxAmount ?? 0), 4);
    }

    private function paymentState(float $finalAmount, float $paidAmount): array
    {
        $dueAmount = $finalAmount - $paidAmount;
        if ($dueAmount < 0) {
            $dueAmount = 0;
        }

        if ($paidAmount <= 0) {
            return ['due', 'unresolved', $dueAmount];
        }

        if ($paidAmount < $finalAmount) {
            return ['partial', 'partial', $dueAmount];
        }

        return ['paid', 'resolved', 0.0];
    }

    private function accountBalance(int $accountId): float
    {
        $account = AcAccount::findOrFail($accountId);
        $debits = (float) AcTransaction::where('debit_account_id', $account->id)->where('status', 'active')->sum('debit_amt');
        $credits = (float) AcTransaction::where('credit_account_id', $account->id)->where('status', 'active')->sum('credit_amt');

        if (in_array($account->account_type, ['liability', 'equity', 'revenue'], true)) {
            return $credits - $debits;
        }

        return $debits - $credits;
    }

    private function paymentTypeSourceAccountId(int $paymentTypeId): ?int
    {
        $paymentType = DbPaymentType::find($paymentTypeId);

        if (!$paymentType) {
            return null;
        }

        return $paymentType->credit_account_id
            ?: $paymentType->debit_account_id
            ?: optional(AcAccount::where('paymenttypes_id', $paymentTypeId)->where('status', 'active')->first())->id;
    }

    private function paymentTypeIdForAccount(int $accountId): ?int
    {
        $paymentType = DbPaymentType::where('credit_account_id', $accountId)
            ->orWhere('debit_account_id', $accountId)
            ->orWhereHas('accounts', function ($query) use ($accountId) {
                $query->where('ac_accounts.id', $accountId);
            })
            ->first();

        return $paymentType->id ?? null;
    }

    private function expensePaymentSourceAccountId(?DbExpenseCategory $category, int $paymentTypeId, ?int $selectedAccountId = null): ?int
    {
        if ($selectedAccountId) {
            $accountExists = AcAccount::where('id', $selectedAccountId)
                ->where('status', 'active')
                ->exists();

            if (!$accountExists) {
                throw new \Exception('Selected payment account was not found.');
            }

            return $selectedAccountId;
        }

        return $category->credit_id
            ? (int) $category->credit_id
            : $this->paymentTypeSourceAccountId($paymentTypeId);
    }

    private function createExpensePayment(DbExpense $expense, float $amount, string $paymentDate, int $paymentTypeId, int $accountId, ?string $note = null): DbExpensePayment
    {
        $payment = DbExpensePayment::create([
            'product_website_id' => $expense->product_website_id,
            'store_id' => $expense->store_id,
            'expense_id' => $expense->id,
            'payment_date' => $paymentDate,
            'payment_amount' => $amount,
            'payment_type_id' => $paymentTypeId,
            'account_id' => $accountId,
            'payment_note' => $note,
            'created_by' => auth()->user()->name ?? null,
            'created_date' => now('Asia/Dhaka')->toDateString(),
            'created_time' => now('Asia/Dhaka')->format('H:i:s'),
            'system_ip' => request()->ip(),
            'system_name' => gethostname(),
            'creator' => auth()->id(),
            'slug' => Str::slug('expense-payment-' . $expense->expense_code) . '-' . time() . rand(1000, 9999),
            'status' => 'active',
        ]);

        $paymentCode = generate_payment_code('EXP');
        $this->createExpenseTransactions($expense, $payment, $paymentCode);

        return $payment;
    }

    private function createExpenseTransactions(DbExpense $expense, DbExpensePayment $payment, string $paymentCode): void
    {
        $date = Carbon::parse($payment->payment_date)->toDateString();
        $note = $payment->payment_note ?: 'Expense payment for ' . $expense->expense_code;

        AcTransaction::create([
            'store_id' => $expense->store_id,
            'payment_code' => $paymentCode,
            'transaction_date' => $date,
            'transaction_type' => 'EXPENSE',
            'debit_account_id' => $expense->debit_account_id,
            'debit_amt' => $payment->payment_amount,
            'credit_account_id' => null,
            'credit_amt' => null,
            'ref_expense_id' => $expense->id,
            'note' => $note,
            'creator' => auth()->id(),
            'slug' => Str::slug('expense-dr-' . $expense->expense_code) . '-' . time() . rand(1000, 9999),
            'status' => 'active',
            'created_at' => now('Asia/Dhaka'),
        ]);

        AcTransaction::create([
            'store_id' => $expense->store_id,
            'payment_code' => $paymentCode,
            'transaction_date' => $date,
            'transaction_type' => 'EXPENSE',
            'debit_account_id' => null,
            'debit_amt' => null,
            'credit_account_id' => $payment->account_id,
            'credit_amt' => $payment->payment_amount,
            'ref_expense_id' => $expense->id,
            'note' => $note,
            'creator' => auth()->id(),
            'slug' => Str::slug('expense-cr-' . $expense->expense_code) . '-' . time() . rand(1000, 9999),
            'status' => 'active',
            'created_at' => now('Asia/Dhaka'),
        ]);
    }
}

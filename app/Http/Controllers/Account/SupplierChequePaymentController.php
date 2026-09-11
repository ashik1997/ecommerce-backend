<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Account\Models\AcAccount;
use App\Http\Controllers\Account\Models\AcTransaction;
use App\Http\Controllers\Account\Models\DbPaymentType;
use App\Http\Controllers\Account\Models\DbPurchasePayment;
use App\Http\Controllers\Account\Models\DbSupplierPayment;
use App\Http\Controllers\Account\Models\SupplierChequePayment;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Inventory\Models\ProductPurchaseOrder;
use App\Http\Controllers\Inventory\Models\ProductSupplier;
use App\Models\AcEventMapping;
use App\Models\ProductPurchaseReturn;
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SupplierChequePaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = SupplierChequePayment::with(['supplier', 'purchase', 'paymentType', 'sourceAccount'])
            ->latest('id');

        $this->applyFilters($query, $request);

        $cheques = $query->paginate(20)->appends($request->query());
        $suppliers = ProductSupplier::where('status', 'active')->orderBy('name')->get();
        $paymentTypes = DbPaymentType::where('status', 'active')->orderBy('payment_type')->get();
        $cards = $this->summaryCards();

        return view('backend.supplier_cheque_payments.index', compact('cheques', 'suppliers', 'paymentTypes', 'cards'));
    }

    public function create()
    {
        $suppliers = ProductSupplier::where('status', 'active')->orderBy('name')->get();
        $paymentTypes = DbPaymentType::where('status', 'active')->orderBy('payment_type')->get();
        $paymentTypeAccounts = $this->paymentTypeAccounts();
        $cheque = new SupplierChequePayment([
            'issue_date' => now()->toDateString(),
            'execution_date' => now()->toDateString(),
        ]);

        return view('backend.supplier_cheque_payments.form', compact('suppliers', 'paymentTypes', 'paymentTypeAccounts', 'cheque'));
    }

    public function store(Request $request)
    {
        $data = $this->validateCheque($request);
        $purchase = ProductPurchaseOrder::where('product_supplier_id', $data['supplier_id'])->findOrFail($data['purchase_id']);
        $dueAmount = $this->purchaseDueAmount($purchase);

        if ($data['amount'] > $dueAmount) {
            return back()->withErrors(['amount' => 'Payment amount cannot exceed purchase due amount.'])->withInput();
        }

        $sourceAccountId = $this->sourceAccountId($data['payment_type_id']);
        if (!$sourceAccountId) {
            return back()->withErrors(['payment_type_id' => 'Source account was not found for this payment type.'])->withInput();
        }

        $exists = SupplierChequePayment::where('cheque_number', $data['cheque_number'])
            ->where('source_account_id', $sourceAccountId)
            ->whereIn('status', ['pending', 'cleared'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['cheque_number' => 'This cheque number already exists for the selected source account.'])->withInput();
        }

        $attachment = $this->storeAttachment($request);

        SupplierChequePayment::create($data + [
            'source_account_id' => $sourceAccountId,
            'account_head_id' => 12,
            'status' => 'pending',
            'attachment' => $attachment,
            'created_by' => auth()->id(),
        ]);

        Toastr::success('Supplier cheque payment saved as pending.', 'Success');
        return redirect()->route('supplier-cheque-payments.index');
    }

    public function edit($id)
    {
        $cheque = SupplierChequePayment::findOrFail($id);
        if ($cheque->status !== 'pending') {
            Toastr::error('Only pending cheques can be edited.', 'Error');
            return redirect()->route('supplier-cheque-payments.index');
        }

        $suppliers = ProductSupplier::where('status', 'active')->orderBy('name')->get();
        $paymentTypes = DbPaymentType::where('status', 'active')->orderBy('payment_type')->get();
        $paymentTypeAccounts = $this->paymentTypeAccounts();

        return view('backend.supplier_cheque_payments.form', compact('suppliers', 'paymentTypes', 'paymentTypeAccounts', 'cheque'));
    }

    public function update(Request $request, $id)
    {
        $cheque = SupplierChequePayment::findOrFail($id);
        if ($cheque->status !== 'pending') {
            Toastr::error('Only pending cheques can be updated.', 'Error');
            return redirect()->route('supplier-cheque-payments.index');
        }

        $data = $this->validateCheque($request, $cheque->id);
        $purchase = ProductPurchaseOrder::where('product_supplier_id', $data['supplier_id'])->findOrFail($data['purchase_id']);
        $dueAmount = $this->purchaseDueAmount($purchase);

        if ($data['amount'] > $dueAmount) {
            return back()->withErrors(['amount' => 'Payment amount cannot exceed purchase due amount.'])->withInput();
        }

        $sourceAccountId = $this->sourceAccountId($data['payment_type_id']);
        if (!$sourceAccountId) {
            return back()->withErrors(['payment_type_id' => 'Source account was not found for this payment type.'])->withInput();
        }

        $exists = SupplierChequePayment::where('cheque_number', $data['cheque_number'])
            ->where('source_account_id', $sourceAccountId)
            ->whereIn('status', ['pending', 'cleared'])
            ->where('id', '!=', $cheque->id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['cheque_number' => 'This cheque number already exists for the selected source account.'])->withInput();
        }

        $updateData = $data + [
            'source_account_id' => $sourceAccountId,
            'account_head_id' => 12,
            'updated_by' => auth()->id(),
        ];

        if ($request->hasFile('attachment')) {
            $updateData['attachment'] = $this->storeAttachment($request);
        }

        $cheque->update($updateData);

        Toastr::success('Supplier cheque payment updated.', 'Success');
        return redirect()->route('supplier-cheque-payments.index');
    }

    public function destroy($id)
    {
        $cheque = SupplierChequePayment::findOrFail($id);
        if ($cheque->status === 'cleared' || $cheque->transaction_id) {
            Toastr::error('Cleared cheques cannot be deleted.', 'Error');
            return back();
        }

        $cheque->delete();
        Toastr::success('Supplier cheque payment deleted.', 'Success');
        return back();
    }

    public function getDuePurchases($supplierId)
    {
        $items = collect($this->duePurchases($supplierId))->map(function ($purchase) {
            return [
                'id' => $purchase['id'],
                'text' => "{$purchase['code']} | Total: ৳" . number_format($purchase['total'], 2) .
                    ($purchase['reference'] ? " | Ref: {$purchase['reference']}" : '') .
                    " | Paid: ৳" . number_format($purchase['paid_amount'], 2) .
                    " | Due: ৳" . number_format($purchase['due_amount'], 2) .
                    " | Date: " . ($purchase['purchase_date'] ?: 'N/A'),
                'total_amount' => $purchase['total'],
                'paid_amount' => $purchase['paid_amount'],
                'due_amount' => $purchase['due_amount'],
                'purchase_date' => $purchase['purchase_date'],
            ];
        })->values();

        return response()->json($items);
    }

    public function getPurchaseSummary($purchaseId)
    {
        $purchase = ProductPurchaseOrder::with('supplier')->findOrFail($purchaseId);
        $total = (float) $purchase->total;
        $return = (float) ProductPurchaseReturn::where('purchase_code', $purchase->code)
            ->where('status', 'active')
            ->sum('total');
        $paid = (float) DbPurchasePayment::where('purchase_id', $purchase->id)
            ->where('status', 'active')
            ->sum('payment');
        $due = max(($total - $return) - $paid, 0);

        return response()->json([
            'success' => true,
            'purchase_no' => $purchase->code,
            'reference' => $purchase->reference,
            'supplier' => $purchase->supplier->name ?? null,
            'purchase_date' => $purchase->date,
            'total_amount' => $total,
            'return_amount' => $return,
            'paid_amount' => $paid,
            'due_amount' => $due,
            'formatted_total_amount' => '৳' . number_format($total, 2),
            'formatted_paid_amount' => '৳' . number_format($paid, 2),
            'formatted_due_amount' => '৳' . number_format($due, 2),
        ]);
    }

    public function markCleared(Request $request, $id)
    {
        $request->validate(['cleared_date' => ['nullable', 'date']]);

        $cheque = SupplierChequePayment::with(['supplier', 'purchase'])->findOrFail($id);
        if ($cheque->status !== 'pending' || $cheque->transaction_id) {
            Toastr::error('This cheque is already processed.', 'Error');
            return back();
        }

        DB::beginTransaction();
        try {
            $purchase = $cheque->purchase;
            $dueAmount = $this->purchaseDueAmount($purchase);
            if ($cheque->amount > $dueAmount) {
                throw new \Exception('Cheque amount is greater than current purchase due amount.');
            }

            $sourceAccount = AcAccount::findOrFail($cheque->source_account_id);
            $balance = $this->accountBalance($sourceAccount);
            if ($cheque->amount > $balance) {
                throw new \Exception('Insufficient balance in source account. Available: ৳' . number_format($balance, 2));
            }

            $clearedDate = $request->cleared_date ?: now()->toDateString();
            $paymentNote = 'Supplier cheque cleared. Cheque No: ' . $cheque->cheque_number;

            $purchasePayment = DbPurchasePayment::create([
                'purchase_id' => $cheque->purchase_id,
                'supplier_id' => $cheque->supplier_id,
                'payment_date' => $clearedDate,
                'payment_type' => $cheque->payment_type_id,
                'payment' => $cheque->amount,
                'payment_note' => $paymentNote,
                'account_id' => $cheque->source_account_id,
                'creator' => auth()->id(),
                'slug' => Str::orderedUuid() . uniqid(),
                'status' => 'active',
            ]);

            $supplierPayment = DbSupplierPayment::create([
                'purchasepayment_id' => $purchasePayment->id,
                'supplier_id' => $cheque->supplier_id,
                'payment_date' => $clearedDate,
                'payment_type' => 'due',
                'payment' => $cheque->amount,
                'payment_note' => $paymentNote,
                'creator' => auth()->id(),
                'slug' => Str::orderedUuid() . uniqid(),
                'status' => 'active',
            ]);

            $transactionId = $this->recordClearedAccounting($cheque, $supplierPayment, $clearedDate);

            $cheque->update([
                'status' => 'cleared',
                'cleared_date' => $clearedDate,
                'transaction_id' => $transactionId,
                'purchase_payment_id' => $purchasePayment->id,
                'supplier_payment_id' => $supplierPayment->id,
                'updated_by' => auth()->id(),
            ]);

            DB::commit();
            Toastr::success('Cheque marked as cleared and accounting entries posted.', 'Success');
        } catch (\Exception $e) {
            DB::rollBack();
            Toastr::error($e->getMessage(), 'Error');
        }

        return back();
    }

    public function markCancelled($id)
    {
        return $this->setPendingStatus($id, 'cancelled', 'Cheque marked as cancelled.');
    }

    public function markBounced($id)
    {
        return $this->setPendingStatus($id, 'bounced', 'Cheque marked as bounced.');
    }

    private function validateCheque(Request $request, $ignoreId = null)
    {
        return $request->validate([
            'supplier_id' => ['required', 'exists:product_suppliers,id'],
            'purchase_id' => ['required', 'exists:product_purchase_orders,id'],
            'payment_type_id' => ['required', 'exists:db_paymenttypes,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'cheque_number' => ['required', 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'branch_name' => ['nullable', 'string', 'max:255'],
            'account_name' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:255'],
            'cheque_type' => ['nullable', 'string', 'max:255'],
            'issue_date' => ['required', 'date'],
            'execution_date' => ['required', 'date', 'after_or_equal:issue_date'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
            'note' => ['nullable', 'string'],
        ]);
    }

    private function duePurchases($supplierId)
    {
        return ProductPurchaseOrder::where('product_supplier_id', $supplierId)
            ->where('status', 'active')
            ->orderBy('date')
            ->orderBy('id')
            ->get()
            ->map(function ($purchase) {
                $dueAmount = $this->purchaseDueAmount($purchase);

                return [
                    'id' => $purchase->id,
                    'code' => $purchase->code,
                    'reference' => $purchase->reference,
                    'purchase_date' => $purchase->date,
                    'total' => (float) $purchase->total,
                    'paid_amount' => (float) DbPurchasePayment::where('purchase_id', $purchase->id)->where('status', 'active')->sum('payment'),
                    'due_amount' => $dueAmount,
                ];
            })
            ->filter(fn ($purchase) => $purchase['due_amount'] > 0)
            ->values()
            ->all();
    }

    private function purchaseDueAmount(ProductPurchaseOrder $purchase)
    {
        $totalPurchase = (float) $purchase->total;
        $totalReturn = (float) ProductPurchaseReturn::where('purchase_code', $purchase->code)
            ->where('status', 'active')
            ->sum('total');
        $totalPaid = (float) DbPurchasePayment::where('purchase_id', $purchase->id)
            ->where('status', 'active')
            ->sum('payment');

        return max(($totalPurchase - $totalReturn) - $totalPaid, 0);
    }

    private function sourceAccountId($paymentTypeId)
    {
        $paymentType = DbPaymentType::find($paymentTypeId);
        if (!$paymentType) {
            return null;
        }

        return $paymentType->debit_account_id
            ?: $paymentType->credit_account_id
            ?: optional(AcAccount::where('paymenttypes_id', $paymentTypeId)->where('status', 'active')->first())->id;
    }

    private function accountBalance(AcAccount $account)
    {
        $debits = (float) AcTransaction::where('debit_account_id', $account->id)->where('status', 'active')->sum('debit_amt');
        $credits = (float) AcTransaction::where('credit_account_id', $account->id)->where('status', 'active')->sum('credit_amt');

        if (in_array($account->account_type, ['liability', 'equity', 'revenue'])) {
            return $credits - $debits;
        }

        return $debits - $credits;
    }

    private function paymentTypeAccounts()
    {
        return DbPaymentType::where('status', 'active')->get()->mapWithKeys(function ($type) {
            $accountId = $this->sourceAccountId($type->id);
            $account = $accountId ? AcAccount::find($accountId) : null;

            return [$type->id => [
                'account_id' => $accountId,
                'account_name' => $account->account_name ?? 'Source account not found',
            ]];
        });
    }

    private function recordClearedAccounting(SupplierChequePayment $cheque, DbSupplierPayment $supplierPayment, $clearedDate)
    {
        $event = AcEventMapping::where('event_name', 'supplier_payment')->first();
        $debitAccountId = $event->debit_account_id ?? $cheque->account_head_id ?? 12;
        $creditAccountId = $cheque->source_account_id;
        $paymentCode = function_exists('generate_payment_code') ? generate_payment_code('SC') : 'SC-' . date('ymdHis');
        $user = auth()->user();
        $note = 'Supplier cheque payment cleared. Cheque No: ' . $cheque->cheque_number .
            ', Supplier: ' . ($cheque->supplier->name ?? 'N/A') .
            ', Purchase: ' . ($cheque->purchase->code ?? 'N/A');

        if (!$debitAccountId || !$creditAccountId) {
            throw new \Exception('Debit or credit account is missing for cheque clearing.');
        }

        $debit = AcTransaction::create([
            'store_id' => $user->store_id ?? null,
            'payment_code' => $paymentCode,
            'transaction_date' => $clearedDate,
            'transaction_type' => 'SUPPLIER_CHEQUE_PAYMENT',
            'debit_account_id' => $debitAccountId,
            'credit_account_id' => null,
            'debit_amt' => $cheque->amount,
            'credit_amt' => null,
            'note' => $note . ' - Debit Entry',
            'ref_purchase_id' => $cheque->purchase_id,
            'supplier_id' => $cheque->supplier_id,
            'supplier_payment_id' => $supplierPayment->id,
            'created_by' => $user ? substr($user->name, 0, 50) : null,
            'created_date' => now()->toDateString(),
            'creator' => auth()->id(),
            'slug' => uniqid() . time(),
            'status' => 'active',
        ]);

        AcTransaction::create([
            'store_id' => $user->store_id ?? null,
            'payment_code' => $paymentCode,
            'transaction_date' => $clearedDate,
            'transaction_type' => 'SUPPLIER_CHEQUE_PAYMENT',
            'debit_account_id' => null,
            'credit_account_id' => $creditAccountId,
            'debit_amt' => null,
            'credit_amt' => $cheque->amount,
            'note' => $note . ' - Credit Entry',
            'ref_purchase_id' => $cheque->purchase_id,
            'supplier_id' => $cheque->supplier_id,
            'supplier_payment_id' => $supplierPayment->id,
            'created_by' => $user ? substr($user->name, 0, 50) : null,
            'created_date' => now()->toDateString(),
            'creator' => auth()->id(),
            'slug' => uniqid() . time(),
            'status' => 'active',
        ]);

        return $debit->id;
    }

    private function setPendingStatus($id, $status, $message)
    {
        $cheque = SupplierChequePayment::findOrFail($id);
        if ($cheque->status !== 'pending') {
            Toastr::error('Only pending cheques can be changed to ' . $status . '.', 'Error');
            return back();
        }

        $cheque->update(['status' => $status, 'updated_by' => auth()->id()]);
        Toastr::success($message, 'Success');
        return back();
    }

    private function applyFilters($query, Request $request)
    {
        if ($request->supplier_id) {
            $query->where('supplier_id', $request->supplier_id);
        }
        if ($request->payment_type_id) {
            $query->where('payment_type_id', $request->payment_type_id);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->cheque_number) {
            $query->where('cheque_number', 'like', '%' . $request->cheque_number . '%');
        }
        if ($request->date_from) {
            $query->whereDate('execution_date', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('execution_date', '<=', $request->date_to);
        }

        if ($request->filter === 'upcoming') {
            $query->where('status', 'pending')
                ->whereBetween('execution_date', [now()->toDateString(), now()->addDays(7)->toDateString()]);
        } elseif ($request->filter === 'due_today') {
            $query->where('status', 'pending')->whereDate('execution_date', now()->toDateString());
        } elseif ($request->filter === 'overdue') {
            $query->where('status', 'pending')->whereDate('execution_date', '<', now()->toDateString());
        } elseif (in_array($request->filter, ['pending', 'cleared', 'cancelled', 'bounced'])) {
            $query->where('status', $request->filter);
        }
    }

    private function summaryCards()
    {
        $today = now()->toDateString();
        $nextWeek = now()->addDays(7)->toDateString();

        return [
            'pending' => SupplierChequePayment::where('status', 'pending')->sum('amount'),
            'upcoming' => SupplierChequePayment::where('status', 'pending')->whereBetween('execution_date', [$today, $nextWeek])->sum('amount'),
            'due_today' => SupplierChequePayment::where('status', 'pending')->whereDate('execution_date', $today)->sum('amount'),
            'overdue' => SupplierChequePayment::where('status', 'pending')->whereDate('execution_date', '<', $today)->sum('amount'),
            'cleared' => SupplierChequePayment::where('status', 'cleared')->sum('amount'),
        ];
    }

    private function storeAttachment(Request $request)
    {
        if (!$request->hasFile('attachment')) {
            return null;
        }

        return $request->file('attachment')->store('uploads/supplier_cheques', 'public');
    }
}

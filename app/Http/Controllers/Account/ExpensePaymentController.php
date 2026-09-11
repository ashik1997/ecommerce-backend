<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Account\Models\AcAccount;
use App\Http\Controllers\Account\Models\AcTransaction;
use App\Http\Controllers\Account\Models\DbExpense;
use App\Http\Controllers\Account\Models\DbExpensePayment;
use App\Http\Controllers\Account\Models\DbPaymentType;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ExpensePaymentController extends Controller
{
    public function index($id)
    {
        $expense = DbExpense::with(['payments.payment_type', 'payments.account', 'payments.user'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $expense->payments,
        ]);
    }

    public function store(Request $request, $id)
    {
        $expense = DbExpense::findOrFail($id);

        $request->validate([
            'payment_amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['required', 'date'],
            'payment_type_id' => ['required', 'exists:db_paymenttypes,id'],
            'account_id' => ['required', 'exists:ac_accounts,id'],
            'payment_note' => ['nullable', 'string'],
        ]);

        DB::beginTransaction();
        try {
            $currentDue = (float) ($expense->due_amount ?? 0);
            $paymentAmount = (float) $request->payment_amount;

            if ($currentDue <= 0 || in_array($expense->payment_status, ['paid', 'cancelled'], true)) {
                throw new \Exception('This expense has no payable due amount.');
            }

            if ($paymentAmount > $currentDue) {
                throw new \Exception('Payment amount cannot be greater than due amount.');
            }

            $paymentAccountId = $this->expensePaymentSourceAccountId(
                $expense,
                (int) $request->payment_type_id,
                $request->filled('account_id') ? (int) $request->account_id : null
            );

            if (!$paymentAccountId) {
                throw new \Exception('Source account was not found for the selected payment method.');
            }

            if ($this->accountBalance($paymentAccountId) < $paymentAmount) {
                throw new \Exception('Insufficient account balance.');
            }

            $payment = DbExpensePayment::create([
                'product_website_id' => $expense->product_website_id,
                'store_id' => $expense->store_id,
                'expense_id' => $expense->id,
                'payment_date' => $request->payment_date,
                'payment_amount' => $paymentAmount,
                'payment_type_id' => $request->payment_type_id,
                'account_id' => $paymentAccountId,
                'payment_note' => $request->payment_note,
                'created_by' => auth()->user()->name ?? null,
                'created_date' => now('Asia/Dhaka')->toDateString(),
                'created_time' => now('Asia/Dhaka')->format('H:i:s'),
                'system_ip' => $request->ip(),
                'system_name' => gethostname(),
                'creator' => auth()->id(),
                'slug' => Str::slug('expense-payment-' . $expense->expense_code) . '-' . time() . rand(1000, 9999),
                'status' => 'active',
            ]);

            $newPaid = (float) ($expense->paid_amount ?? 0) + $paymentAmount;
            $newDue = max(0, (float) $expense->final_amount - $newPaid);
            $paymentStatus = $newDue <= 0 ? 'paid' : 'partial';
            $expenseStatus = $newDue <= 0 ? 'resolved' : 'partial';

            $expense->update([
                'paid_amount' => $newPaid,
                'due_amount' => $newDue,
                'payment_status' => $paymentStatus,
                'expense_status' => $expenseStatus,
                'payment_type_id' => $request->payment_type_id,
                'account_id' => $paymentAccountId,
                'credit_account_id' => $paymentAccountId,
                'paid_on' => $request->payment_date,
                'payment_note' => $request->payment_note,
            ]);

            $this->createExpenseTransactions($expense, $payment, generate_payment_code('EXP'));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Expense payment saved successfully.',
                'data' => $expense->fresh(['payments.payment_type', 'payments.account']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Expense Payment Error: ' . $e->getMessage(), [
                'exception' => $e,
                'request' => $request->all(),
                'expense_id' => $id,
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
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

    private function expensePaymentSourceAccountId(DbExpense $expense, int $paymentTypeId, ?int $selectedAccountId = null): ?int
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

        $expense->loadMissing('expense_category');
        $categoryCreditId = $expense->expense_category->credit_id ?? $expense->credit_account_id;

        return $categoryCreditId
            ? (int) $categoryCreditId
            : $this->paymentTypeSourceAccountId($paymentTypeId);
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

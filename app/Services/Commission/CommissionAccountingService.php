<?php

namespace App\Services\Commission;

use App\Http\Controllers\Account\Models\AcAccount;
use App\Http\Controllers\Account\Models\AcTransaction;
use App\Models\CommissionSettlement;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CommissionAccountingService
{
    public function postSettlementApproval(CommissionSettlement $settlement): ?AcTransaction
    {
        $settlement->refresh();

        if ((float) $settlement->payable_amount <= 0) {
            return null;
        }

        if ($settlement->accounting_transaction_id) {
            return AcTransaction::find($settlement->accounting_transaction_id);
        }

        $expenseAccount = $this->accountBySelection('sales_commission_expense');
        $payableAccount = $this->accountBySelection('commission_payable');

        if (! $expenseAccount || ! $payableAccount) {
            $this->markAccountingStatus($settlement, 'account_missing');
            return null;
        }

        $transaction = AcTransaction::create([
            'payment_code' => $settlement->settlement_no,
            'transaction_date' => Carbon::parse($settlement->settled_at ?: now())->toDateString(),
            'transaction_type' => 'commission_settlement_approved',
            'event_type' => 'commission_settlement_approved',
            'debit_account_id' => $expenseAccount->id,
            'credit_account_id' => $payableAccount->id,
            'debit_amt' => $settlement->payable_amount,
            'credit_amt' => $settlement->payable_amount,
            'note' => 'Commission payable recognized for settlement ' . $settlement->settlement_no,
            'short_code' => 'COM-APPROVE',
            'created_by' => optional(Auth::user())->name,
            'created_date' => now()->toDateString(),
            'creator' => Auth::id(),
            'slug' => 'commission-settlement-approved-' . $settlement->id . '-' . Str::random(6),
            'status' => 'active',
        ]);

        $settlement->forceFill([
            'accounting_transaction_id' => $transaction->id,
            'accounting_status' => 'posted',
        ])->save();

        return $transaction;
    }

    public function postSettlementPayment(CommissionSettlement $settlement, float $paymentAmount = null): ?AcTransaction
    {
        $settlement->refresh();
        $amount = $paymentAmount !== null ? $paymentAmount : (float) $settlement->paid_amount;

        if ($amount <= 0) {
            return null;
        }

        if (! $settlement->accounting_transaction_id) {
            $this->postSettlementApproval($settlement);
            $settlement->refresh();
        }

        // To keep v9 safe and idempotent, one payment transaction is posted only when the settlement becomes fully paid.
        // Partial payment remains tracked operationally; accounting can be posted when paid in full or extended later to a payment-ledger table.
        if ($settlement->status !== 'paid') {
            $this->markAccountingStatus($settlement, 'partial_pending');
            return null;
        }

        if ($settlement->payment_transaction_id) {
            return AcTransaction::find($settlement->payment_transaction_id);
        }

        $payableAccount = $this->accountBySelection('commission_payable');
        $cashAccount = $this->resolvePaymentAccount($settlement->payment_method_id);

        if (! $payableAccount || ! $cashAccount) {
            $this->markAccountingStatus($settlement, 'account_missing');
            return null;
        }

        $transaction = AcTransaction::create([
            'payment_code' => $settlement->settlement_no . '-PAY',
            'transaction_date' => Carbon::parse($settlement->settled_at ?: now())->toDateString(),
            'transaction_type' => 'commission_settlement_paid',
            'event_type' => 'commission_settlement_paid',
            'debit_account_id' => $payableAccount->id,
            'credit_account_id' => $cashAccount->id,
            'debit_amt' => $settlement->paid_amount,
            'credit_amt' => $settlement->paid_amount,
            'note' => 'Commission settlement payment for ' . $settlement->settlement_no,
            'short_code' => 'COM-PAID',
            'created_by' => optional(Auth::user())->name,
            'created_date' => now()->toDateString(),
            'creator' => Auth::id(),
            'slug' => 'commission-settlement-paid-' . $settlement->id . '-' . Str::random(6),
            'status' => 'active',
        ]);

        $settlement->forceFill([
            'payment_transaction_id' => $transaction->id,
            'accounting_status' => 'paid_posted',
        ])->save();

        return $transaction;
    }

    public function reverseSettlementAccounting(CommissionSettlement $settlement): void
    {
        if ($settlement->payment_transaction_id) {
            AcTransaction::whereKey($settlement->payment_transaction_id)->update(['status' => 'inactive']);
        }

        if ($settlement->accounting_transaction_id) {
            AcTransaction::whereKey($settlement->accounting_transaction_id)->update(['status' => 'inactive']);
        }

        $settlement->forceFill(['accounting_status' => 'reversed'])->save();
    }

    protected function accountBySelection(string $selection): ?AcAccount
    {
        return AcAccount::where('account_selection_name', $selection)->where('status', 'active')->first();
    }

    protected function resolvePaymentAccount(?int $paymentMethodId): ?AcAccount
    {
        if ($paymentMethodId) {
            $account = AcAccount::where('paymenttypes_id', $paymentMethodId)->where('status', 'active')->first();
            if ($account) {
                return $account;
            }
        }

        return AcAccount::whereIn('account_selection_name', ['cash_in_hand', 'cash', 'bank_accounts', 'bank'])
            ->where('status', 'active')
            ->orderBy('id')
            ->first();
    }

    protected function markAccountingStatus(CommissionSettlement $settlement, string $status): void
    {
        $settlement->forceFill(['accounting_status' => $status])->save();
    }
}

<?php

namespace App\Services\ServiceManagement;

use App\Http\Controllers\Account\Models\AcAccount;
use App\Http\Controllers\Account\Models\AcTransaction;
use App\Http\Controllers\Account\Models\DbPaymentType;
use App\Models\ServiceManagement\ServiceInstance;
use App\Models\ServiceManagement\ServicePayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ServiceTransactionService
{
    public function ensureAccountingSetup(): array
    {
        $accounts = $this->ensureServiceAccounts();

        DbPaymentType::where('status', 'active')->get()->each(function ($paymentType) {
            $this->ensurePaymentTypeAccount($paymentType);
        });

        return $accounts;
    }

    public function collectPayment(ServiceInstance $instance, DbPaymentType $paymentType, float $amount, string $paymentDate, ?string $note = null): ServicePayment
    {
        $this->ensureAccountingSetup();

        return DB::transaction(function () use ($instance, $paymentType, $amount, $paymentDate, $note) {
            $instance = ServiceInstance::with(['service', 'customer'])->lockForUpdate()->findOrFail($instance->id);
            $amount = round(max(0, $amount), 2);

            if ($amount <= 0) {
                throw new \Exception('Payment amount is required.');
            }

            if ($instance->status !== 'billed') {
                throw new \Exception('Please bill this service instance before receiving payment.');
            }

            if ((float) $instance->due_amount <= 0) {
                throw new \Exception('This service instance has no due amount.');
            }

            if ($amount > (float) $instance->due_amount) {
                throw new \Exception('Payment amount cannot exceed due amount.');
            }

            $this->postInstanceAccountingIfMissing($instance);

            $paymentAccount = $this->ensurePaymentTypeAccount($paymentType);
            $payment = ServicePayment::create([
                'payment_no' => $this->generatePaymentNo(),
                'service_instance_id' => $instance->id,
                'customer_id' => $instance->customer_id,
                'payment_type_id' => $paymentType->id,
                'account_id' => $paymentAccount->id,
                'amount' => $amount,
                'payment_date' => $paymentDate,
                'note' => $note,
                'created_by' => auth()->id(),
            ]);

            $accounts = $this->ensureServiceAccounts();

            AcTransaction::create([
                'store_id' => auth()->user()->store_id ?? 1,
                'payment_code' => $payment->payment_no,
                'transaction_date' => $paymentDate,
                'transaction_type' => 'service_payment',
                'event_type' => 'service_payment',
                'debit_account_id' => $paymentAccount->id,
                'credit_account_id' => $accounts['service_accounts_receivable']->id,
                'debit_amt' => $amount,
                'credit_amt' => $amount,
                'note' => $note ?: 'Service payment for ' . $instance->instance_no,
                'customer_id' => $instance->customer_id,
                'ref_service_instance_id' => $instance->id,
                'ref_service_payment_id' => $payment->id,
                'creator' => auth()->id(),
                'slug' => Str::orderedUuid() . uniqid(),
                'status' => 'active',
            ]);

            $paidAmount = round((float) $instance->paid_amount + $amount, 2);
            $dueAmount = round(max(0, (float) $instance->total_amount - $paidAmount), 2);

            $instance->update([
                'paid_amount' => $paidAmount,
                'due_amount' => $dueAmount,
                'status' => $dueAmount <= 0 ? 'paid' : 'billed',
                'updated_by' => auth()->id(),
            ]);

            return $payment;
        });
    }

    public function postInstanceAccountingIfMissing(ServiceInstance $instance): void
    {
        if ($instance->accounting_posted_at) {
            return;
        }

        if (!in_array($instance->status, ['confirmed', 'billed', 'paid'], true)) {
            throw new \Exception('Only confirmed service instances can be billed.');
        }

        $accounts = $this->ensureServiceAccounts();
        $revenueAccount = $instance->service?->type === 'rental'
            ? $accounts['rental_revenue']
            : $accounts['service_revenue'];

        AcTransaction::create([
            'store_id' => auth()->user()->store_id ?? 1,
            'payment_code' => $instance->instance_no,
            'transaction_date' => $instance->start_date ?? now()->toDateString(),
            'transaction_type' => 'service_invoice',
            'event_type' => 'service_invoice',
            'debit_account_id' => $accounts['service_accounts_receivable']->id,
            'credit_account_id' => $revenueAccount->id,
            'debit_amt' => $instance->total_amount,
            'credit_amt' => $instance->total_amount,
            'note' => 'Service invoice posted for ' . $instance->instance_no,
            'customer_id' => $instance->customer_id,
            'ref_service_instance_id' => $instance->id,
            'creator' => auth()->id(),
            'slug' => Str::orderedUuid() . uniqid(),
            'status' => 'active',
        ]);

        $instance->forceFill(['accounting_posted_at' => now()])->save();
    }

    public function ensurePaymentTypeAccount(DbPaymentType $paymentType): AcAccount
    {
        if ($paymentType->debit_account_id && ($existing = AcAccount::find($paymentType->debit_account_id))) {
            return $existing;
        }

        $account = AcAccount::where('paymenttypes_id', $paymentType->id)->first();

        if (!$account) {
            $cashBank = AcAccount::where('account_selection_name', 'cash_bank')->first()
                ?: $this->ensureServiceAccounts()['cash_bank'];

            $account = AcAccount::create([
                'store_id' => $paymentType->store_id ?? 1,
                'parent_id' => $cashBank->id,
                'account_type' => 'asset',
                'normal_balance' => 'debit',
                'is_system_account' => false,
                'is_control_account' => false,
                'sort_code' => '1110' . $paymentType->id,
                'account_code' => 'AC-1110' . $paymentType->id,
                'account_name' => $paymentType->payment_type,
                'account_selection_name' => $this->selectionName($paymentType->payment_type),
                'paymenttypes_id' => $paymentType->id,
                'balance' => 0,
                'status' => 'active',
            ]);
        }

        $paymentType->debit_account_id = $account->id;
        $paymentType->credit_account_id = $account->id;
        $paymentType->save();

        return $account;
    }

    private function ensureServiceAccounts(): array
    {
        $assets = $this->ensureAccount('assets', 'Assets', 'asset', 'debit', null, '1000', 'AC-1000');
        $currentAssets = $this->ensureAccount('current_assets', 'Current Assets', 'asset', 'debit', $assets->id, '1100', 'AC-1100');
        $cashBank = $this->ensureAccount('cash_bank', 'Cash & Bank', 'asset', 'debit', $currentAssets->id, '1110', 'AC-1110');
        $serviceReceivable = $this->ensureAccount('service_accounts_receivable', 'Service Accounts Receivable', 'asset', 'debit', $currentAssets->id, '1125', 'AC-1125', true);

        $revenue = $this->ensureAccount('revenue', 'Revenue', 'revenue', 'credit', null, '4000', 'AC-4000');
        $serviceRevenue = $this->ensureAccount('service_revenue', 'Service Revenue', 'revenue', 'credit', $revenue->id, '4300', 'AC-4300');
        $rentalRevenue = $this->ensureAccount('rental_revenue', 'Rental Revenue', 'revenue', 'credit', $revenue->id, '4310', 'AC-4310');

        return [
            'cash_bank' => $cashBank,
            'service_accounts_receivable' => $serviceReceivable,
            'service_revenue' => $serviceRevenue,
            'rental_revenue' => $rentalRevenue,
        ];
    }

    private function ensureAccount(string $selectionName, string $accountName, string $type, string $normalBalance, ?int $parentId, string $sortCode, string $accountCode, bool $isControl = false): AcAccount
    {
        $account = AcAccount::where('account_selection_name', $selectionName)->first()
            ?: AcAccount::where('account_name', $accountName)->first();

        if (!$account) {
            return AcAccount::create([
                'store_id' => auth()->user()->store_id ?? 1,
                'parent_id' => $parentId ?? 0,
                'account_type' => $type,
                'normal_balance' => $normalBalance,
                'is_system_account' => true,
                'is_control_account' => $isControl,
                'sort_code' => $sortCode,
                'account_code' => $accountCode,
                'account_name' => $accountName,
                'account_selection_name' => $selectionName,
                'balance' => 0,
                'status' => 'active',
                'creator' => auth()->id(),
                'slug' => Str::slug($accountName) . '-' . time(),
            ]);
        }

        $account->fill([
            'parent_id' => $parentId ?? $account->parent_id,
            'account_type' => $type,
            'normal_balance' => $normalBalance,
            'is_control_account' => $isControl,
            'account_selection_name' => $selectionName,
            'status' => 'active',
        ]);
        $account->save();

        return $account;
    }

    private function generatePaymentNo(): string
    {
        do {
            $paymentNo = 'SRP-' . now()->format('ymd') . '-' . Str::upper(Str::random(5));
        } while (ServicePayment::where('payment_no', $paymentNo)->exists());

        return $paymentNo;
    }

    private function selectionName(string $value): string
    {
        $clean = preg_replace('/[^a-zA-Z0-9\s]/', '', strtolower($value));
        return trim(preg_replace('/\s+/', '_', $clean), '_');
    }
}

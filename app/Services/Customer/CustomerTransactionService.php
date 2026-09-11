<?php

namespace App\Services\Customer;

use App\Http\Controllers\Account\Models\AcAccount;
use App\Http\Controllers\Account\Models\AcTransaction;
use App\Http\Controllers\Account\Models\DbCustomerPayment;
use App\Http\Controllers\Account\Models\DbPaymentType;
use App\Http\Controllers\Customer\Models\Customer;
use App\Http\Controllers\Customer\Models\CustomerOpeningBalance;
use App\Models\AcEventMapping;
use App\Models\ProductOrder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CustomerTransactionService
{
    /**
     * Keep customer accounting setup self-healing.
     *
     * This method is intentionally simple: before customer due/advance work,
     * it makes sure required heads, payment accounts, and event mappings exist.
     */
    public function ensureAccountingSetup(): array
    {
        $accounts = $this->ensureCustomerDueAccounts();

        DbPaymentType::where('status', 'active')->get()->each(function ($paymentType) {
            $this->ensurePaymentTypeAccount($paymentType);
        });

        $this->ensureCustomerDueEventMappings($accounts);

        return $accounts;
    }

    public function ensureCustomerDueAccounts(): array
    {
        $assets = $this->ensureAccount('assets', 'Assets', 'asset', 'debit', null, '1000', 'AC-1000');
        $currentAssets = $this->ensureAccount('current_assets', 'Current Assets', 'asset', 'debit', $assets->id, '1100', 'AC-1100');
        $cashBank = $this->ensureAccount('cash_bank', 'Cash & Bank', 'asset', 'debit', $currentAssets->id, '1110', 'AC-1110');

        $cashOnHand = $this->ensureAccount('cash_on_hand', 'Cash on Hand', 'asset', 'debit', $cashBank->id, '1111', 'AC-1111');
        $accountsReceivable = $this->ensureAccount('accounts_receivable', 'Accounts Receivable', 'asset', 'debit', $currentAssets->id, '1120', 'AC-1120', true);

        $liabilities = $this->ensureAccount('liabilities', 'Liabilities', 'liability', 'credit', null, '2000', 'AC-2000');
        $currentLiabilities = $this->ensureAccount('current_liabilities', 'Current Liabilities', 'liability', 'credit', $liabilities->id, '2100', 'AC-2100');
        $customerAdvance = $this->ensureAccount('customer_advance', 'Customer Advance', 'liability', 'credit', $currentLiabilities->id, '2120', 'AC-2120', true);

        $equity = $this->ensureAccount('equity', "Owner's Equity", 'equity', 'credit', null, '3000', 'AC-3000');
        $openingBalanceEquity = $this->ensureAccount('opening_balance_equity', 'Opening Balance Equity', 'equity', 'credit', $equity->id, '3400', 'AC-3400');

        $revenue = $this->ensureAccount('revenue', 'Revenue', 'revenue', 'credit', null, '4000', 'AC-4000');
        $salesRevenue = $this->ensureAccount('sales_revenue', 'Sales Revenue', 'revenue', 'credit', $revenue->id, '4100', 'AC-4100');
        $extraChargeIncome = $this->ensureAccount('extra_charge_income', 'Extra Charge Income', 'revenue', 'credit', $revenue->id, '4201', 'AC-4201');
        $deliveryChargeIncome = $this->ensureAccount('delivery_charge_income', 'Delivery Charge Income', 'revenue', 'credit', $revenue->id, '4202', 'AC-4202');

        $expenses = $this->ensureAccount('expenses', 'Expenses', 'expense', 'debit', null, '5000', 'AC-5000');
        $salesDiscount = $this->ensureAccount('sales_discount', 'Sales Discount', 'expense', 'debit', $expenses->id, '5470', 'AC-5470');
        $roundOff = $this->ensureAccount('round_off', 'Round Off', 'expense', 'debit', $expenses->id, '5480', 'AC-5480');

        return [
            'cash_on_hand' => $cashOnHand,
            'accounts_receivable' => $accountsReceivable,
            'customer_advance' => $customerAdvance,
            'opening_balance_equity' => $openingBalanceEquity,
            'sales_revenue' => $salesRevenue,
            'extra_charge_income' => $extraChargeIncome,
            'delivery_charge_income' => $deliveryChargeIncome,
            'sales_discount' => $salesDiscount,
            'round_off' => $roundOff,
        ];
    }

    public function ensurePaymentTypeAccount(DbPaymentType $paymentType): AcAccount
    {
        if ($paymentType->debit_account_id) {
            $existing = AcAccount::find($paymentType->debit_account_id);
            if ($existing) {
                return $existing;
            }
        }

        $account = AcAccount::where('paymenttypes_id', $paymentType->id)->first();

        if (!$account) {
            $cashBank = AcAccount::where('account_selection_name', 'cash_bank')->first()
                ?: AcAccount::where('account_name', 'Cash & Bank')->first();

            $account = AcAccount::create([
                'store_id' => $paymentType->store_id ?? 1,
                'parent_id' => $cashBank?->id ?? 0,
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
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $paymentType->debit_account_id = $account->id;
        $paymentType->credit_account_id = $account->id;
        $paymentType->save();

        return $account;
    }

    public function createOpeningBalance(array $data): CustomerOpeningBalance
    {
        $this->ensureAccountingSetup();

        return DB::transaction(function () use ($data) {
            $customer = Customer::lockForUpdate()->findOrFail($data['customer_id']);
            $amount = (float) $data['amount'];

            $opening = CustomerOpeningBalance::create([
                'store_id' => auth()->user()->store_id ?? 1,
                'customer_id' => $customer->id,
                'entry_type' => $data['entry_type'],
                'opening_date' => $data['opening_date'],
                'reference_no' => $data['reference_no'] ?? null,
                'opening_amount' => $amount,
                'paid_amount' => 0,
                'remaining_amount' => $amount,
                'note' => $data['note'] ?? null,
                'posted_to_accounts' => true,
                'creator' => auth()->id(),
                'slug' => Str::orderedUuid() . uniqid(),
                'status' => 'active',
            ]);

            if ($opening->entry_type === 'due') {
                $this->postAccounting('customer_opening_due', $amount, $opening->opening_date, $customer, 'Customer old due opening balance', null, null, null, $opening);
            } else {
                $this->postAccounting('customer_opening_advance', $amount, $opening->opening_date, $customer, 'Customer old advance opening balance', null, null, null, $opening);
            }

            $this->recalculateCustomerBalance($customer->id);

            return $opening;
        });
    }

    public function collectPayment(int $customerId, ?DbPaymentType $paymentType, float $paymentAmount, string $paymentDate, ?string $note, array $allocations, float $advanceAmount = 0): void
    {
        $this->ensureAccountingSetup();

        DB::transaction(function () use ($customerId, $paymentType, $paymentAmount, $paymentDate, $note, $allocations, $advanceAmount) {
            $customer = Customer::lockForUpdate()->findOrFail($customerId);
            $cashAmount = max(0, $paymentAmount);
            $advanceAmount = max(0, $advanceAmount);
            $settlementAmount = $cashAmount + $advanceAmount;
            $paymentAccount = null;

            if ($settlementAmount <= 0) {
                throw new \Exception('Payment amount or advance amount is required.');
            }

            if ($cashAmount > 0) {
                if (!$paymentType) {
                    throw new \Exception('Payment mode is required for cash/bank payment.');
                }

                $paymentAccount = $this->ensurePaymentTypeAccount($paymentType);
            }

            $this->recalculateCustomerBalance($customer->id);
            $customer->refresh();

            if ($advanceAmount > (float) $customer->available_advance) {
                throw new \Exception('Advance amount exceeds customer available advance.');
            }

            $totalAllocated = 0;
            $cashRemaining = $cashAmount;
            $advanceRemaining = $advanceAmount;

            if (empty($allocations)) {
                $allocations[] = ['is_advance' => true, 'payment_amount' => $settlementAmount];
            }

            foreach ($allocations as $allocation) {
                $amount = (float) ($allocation['payment_amount'] ?? 0);
                if ($amount <= 0) {
                    continue;
                }

                if (!empty($allocation['is_advance'])) {
                    if ($advanceRemaining > 0) {
                        throw new \Exception('Customer advance can only be applied to due items.');
                    }

                    $cashRemaining -= $amount;
                    $this->receiveAdvance($customer, $paymentType, $paymentAccount, $amount, $paymentDate, $note);
                } else {
                    $advancePart = min($advanceRemaining, $amount);
                    $cashPart = $amount - $advancePart;
                    $advanceRemaining -= $advancePart;
                    $cashRemaining -= $cashPart;

                    if (!empty($allocation['opening_balance_id'])) {
                        if ($advancePart > 0) {
                            $this->applyAdvanceToOpeningDue($customer, $advancePart, $paymentDate, $note, (int) $allocation['opening_balance_id']);
                        }
                        if ($cashPart > 0) {
                            $this->collectOpeningDue($customer, $paymentType, $paymentAccount, $cashPart, $paymentDate, $note, (int) $allocation['opening_balance_id']);
                        }
                    } elseif (!empty($allocation['order_id'])) {
                        if ($advancePart > 0) {
                            $this->applyAdvanceToOrderDue($customer, $advancePart, $paymentDate, $note, (int) $allocation['order_id']);
                        }
                        if ($cashPart > 0) {
                            $this->collectOrderDue($customer, $paymentType, $paymentAccount, $cashPart, $paymentDate, $note, (int) $allocation['order_id']);
                        }
                    }
                }

                $totalAllocated += $amount;
            }

            if (abs($totalAllocated - $settlementAmount) > 0.01) {
                throw new \Exception('Payment allocation mismatch.');
            }

            if ($cashRemaining > 0.01 || $advanceRemaining > 0.01) {
                throw new \Exception('Payment allocation did not use the full amount.');
            }

            $this->recalculateCustomerBalance($customer->id);
        });
    }

    public function refundAdvance(int $customerId, DbPaymentType $paymentType, float $amount, string $paymentDate, ?string $note): void
    {
        $this->ensureAccountingSetup();

        DB::transaction(function () use ($customerId, $paymentType, $amount, $paymentDate, $note) {
            $customer = Customer::lockForUpdate()->findOrFail($customerId);
            $this->recalculateCustomerBalance($customer->id);
            $customer->refresh();

            if ($amount > (float) $customer->available_advance) {
                throw new \Exception('Refund amount exceeds available advance balance.');
            }

            $paymentAccount = $this->ensurePaymentTypeAccount($paymentType);

            $customerPayment = DbCustomerPayment::create([
                'customer_id' => $customer->id,
                'payment_date' => $paymentDate,
                'payment_type' => 'refund',
                'payment' => -$amount,
                'payment_mode' => $paymentType->id,
                'payment_mode_title' => $paymentType->payment_type,
                'payment_note' => $note ?? 'Advance payment refund',
                'creator' => auth()->id(),
                'slug' => Str::orderedUuid() . uniqid(),
                'status' => 'active',
            ]);

            $this->postAccounting('customer_advance_refund', $amount, $paymentDate, $customer, $note ?? 'Customer advance refund', $paymentAccount, $customerPayment);

            $this->recalculateCustomerBalance($customer->id);
        });
    }

    public function recalculateCustomerBalance(int $customerId): void
    {
        $customer = Customer::find($customerId);
        if (!$customer) {
            return;
        }

        $orderDue = (float) ProductOrder::where('customer_id', $customerId)
            ->where('status', 'active')
            ->directCustomerReceivable()
            ->sum('due_amount');

        $openingDue = (float) CustomerOpeningBalance::where('customer_id', $customerId)
            ->where('entry_type', 'due')
            ->where('status', 'active')
            ->sum('remaining_amount');

        $openingAdvance = (float) CustomerOpeningBalance::where('customer_id', $customerId)
            ->where('entry_type', 'advance')
            ->where('status', 'active')
            ->sum('remaining_amount');

        $advancePayments = (float) DbCustomerPayment::where('customer_id', $customerId)
            ->where('status', 'active')
            ->where('payment_type', 'advance')
            ->sum('payment');

        $advanceOut = abs((float) DbCustomerPayment::where('customer_id', $customerId)
            ->where('status', 'active')
            ->whereIn('payment_type', ['adjustment', 'refund'])
            ->sum('payment'));

        $paid = (float) DbCustomerPayment::where('customer_id', $customerId)
            ->where('status', 'active')
            ->where('payment_type', 'received')
            ->sum('payment');

        $due = $orderDue + $openingDue;
        $advance = max(0, $openingAdvance + $advancePayments - $advanceOut);

        $customer->due = $due;
        $customer->paid = $paid;
        $customer->available_advance = $advance;
        $customer->balance = $advance - $due;
        $customer->last_transaction = now();
        $customer->save();
    }

    private function collectOpeningDue(Customer $customer, DbPaymentType $paymentType, AcAccount $paymentAccount, float $amount, string $paymentDate, ?string $note, int $openingId): void
    {
        $opening = CustomerOpeningBalance::where('customer_id', $customer->id)
            ->where('entry_type', 'due')
            ->where('status', 'active')
            ->lockForUpdate()
            ->findOrFail($openingId);

        if ($amount > (float) $opening->remaining_amount) {
            throw new \Exception('Payment amount exceeds old due balance.');
        }

        $opening->paid_amount = (float) $opening->paid_amount + $amount;
        $opening->remaining_amount = (float) $opening->remaining_amount - $amount;
        $opening->save();

        $customerPayment = $this->createPaymentRow($customer, $paymentType, $amount, $paymentDate, $note, null, $opening->id, 'received');

        $this->postAccounting('customer_due_payment', $amount, $paymentDate, $customer, $note ?? 'Old due payment received', $paymentAccount, $customerPayment, null, $opening);
    }

    private function collectOrderDue(Customer $customer, DbPaymentType $paymentType, AcAccount $paymentAccount, float $amount, string $paymentDate, ?string $note, int $orderId): void
    {
        $order = ProductOrder::where('customer_id', $customer->id)
            ->where('status', 'active')
            ->directCustomerReceivable()
            ->lockForUpdate()
            ->findOrFail($orderId);

        if ($amount > (float) $order->due_amount) {
            throw new \Exception("Payment amount exceeds due amount for order {$order->order_code}.");
        }

        $payments = is_array($order->payments) ? $order->payments : (json_decode($order->payments, true) ?: []);
        $paymentKey = (string) $paymentType->id;

        $order->paid_amount = (float) $order->paid_amount + $amount;
        $order->due_amount = max(0, (float) $order->due_amount - $amount);
        $payments[$paymentKey] = (float) ($payments[$paymentKey] ?? 0) + $amount;
        $payments['total_paid'] = $order->paid_amount;
        $payments['total_due'] = $order->due_amount;
        $order->payments = $payments;
        $order->save();

        $customerPayment = $this->createPaymentRow($customer, $paymentType, $amount, $paymentDate, $note, $order->id, null, 'received');

        $this->postAccounting('customer_due_payment', $amount, $paymentDate, $customer, $note ?? "Payment for order {$order->order_code}", $paymentAccount, $customerPayment, $order);
    }

    private function receiveAdvance(Customer $customer, DbPaymentType $paymentType, AcAccount $paymentAccount, float $amount, string $paymentDate, ?string $note): void
    {
        $customerPayment = $this->createPaymentRow($customer, $paymentType, $amount, $paymentDate, $note, null, null, 'advance');

        $this->postAccounting('customer_advance_payment', $amount, $paymentDate, $customer, $note ?? 'Advance payment received', $paymentAccount, $customerPayment);
    }

    private function applyAdvanceToOpeningDue(Customer $customer, float $amount, string $paymentDate, ?string $note, int $openingId): void
    {
        $opening = CustomerOpeningBalance::where('customer_id', $customer->id)
            ->where('entry_type', 'due')
            ->where('status', 'active')
            ->lockForUpdate()
            ->findOrFail($openingId);

        if ($amount > (float) $opening->remaining_amount) {
            throw new \Exception('Advance amount exceeds old due balance.');
        }

        $opening->paid_amount = (float) $opening->paid_amount + $amount;
        $opening->remaining_amount = (float) $opening->remaining_amount - $amount;
        $opening->save();

        $customerPayment = $this->createAdvanceAdjustmentRow($customer, $amount, $paymentDate, $note, null, $opening->id);

        $this->postAccounting('customer_advance_applied', $amount, $paymentDate, $customer, $note ?? 'Advance applied to old due', null, $customerPayment, null, $opening);
    }

    private function applyAdvanceToOrderDue(Customer $customer, float $amount, string $paymentDate, ?string $note, int $orderId): void
    {
        $order = ProductOrder::where('customer_id', $customer->id)
            ->where('status', 'active')
            ->directCustomerReceivable()
            ->lockForUpdate()
            ->findOrFail($orderId);

        if ($amount > (float) $order->due_amount) {
            throw new \Exception("Advance amount exceeds due amount for order {$order->order_code}.");
        }

        $payments = is_array($order->payments) ? $order->payments : (json_decode($order->payments, true) ?: []);

        $order->paid_amount = (float) $order->paid_amount + $amount;
        $order->due_amount = max(0, (float) $order->due_amount - $amount);
        $payments['advance'] = (float) ($payments['advance'] ?? 0) + $amount;
        $payments['total_paid'] = $order->paid_amount;
        $payments['total_due'] = $order->due_amount;
        $order->payments = $payments;
        $order->save();

        $customerPayment = $this->createAdvanceAdjustmentRow($customer, $amount, $paymentDate, $note, $order->id, null);

        $this->postAccounting('customer_advance_applied', $amount, $paymentDate, $customer, $note ?? "Advance applied to order {$order->order_code}", null, $customerPayment, $order);
    }

    private function createPaymentRow(Customer $customer, DbPaymentType $paymentType, float $amount, string $paymentDate, ?string $note, ?int $orderId, ?int $openingId, string $type): DbCustomerPayment
    {
        return DbCustomerPayment::create([
            'customer_id' => $customer->id,
            'order_id' => $orderId,
            'customer_opening_balance_id' => $openingId,
            'payment_date' => $paymentDate,
            'payment_type' => $type,
            'payment' => $amount,
            'payment_mode' => $paymentType->id,
            'payment_mode_title' => $paymentType->payment_type,
            'payment_note' => $note,
            'creator' => auth()->id(),
            'slug' => Str::orderedUuid() . uniqid(),
            'status' => 'active',
        ]);
    }

    private function createAdvanceAdjustmentRow(Customer $customer, float $amount, string $paymentDate, ?string $note, ?int $orderId, ?int $openingId): DbCustomerPayment
    {
        return DbCustomerPayment::create([
            'customer_id' => $customer->id,
            'order_id' => $orderId,
            'customer_opening_balance_id' => $openingId,
            'payment_date' => $paymentDate,
            'payment_type' => 'adjustment',
            'payment' => -$amount,
            'payment_mode' => null,
            'payment_mode_title' => 'Advance',
            'payment_note' => $note,
            'creator' => auth()->id(),
            'slug' => Str::orderedUuid() . uniqid(),
            'status' => 'active',
        ]);
    }

    private function postAccounting(string $eventName, float $amount, $date, Customer $customer, ?string $note = null, ?AcAccount $paymentAccount = null, ?DbCustomerPayment $customerPayment = null, ?ProductOrder $order = null, ?CustomerOpeningBalance $openingBalance = null): void
    {
        $this->ensureAccountingSetup();
        $mapping = AcEventMapping::getByEventName($eventName);

        if (!$mapping) {
            throw new \Exception("Accounting mapping missing for {$eventName}.");
        }

        $debitAccountId = $paymentAccount?->id ?? $mapping->debit_account_id;
        $creditAccountId = $mapping->credit_account_id;

        if ($eventName === 'customer_advance_refund') {
            $debitAccountId = $mapping->debit_account_id;
            $creditAccountId = $paymentAccount?->id;
        }

        if (!$debitAccountId || !$creditAccountId) {
            throw new \Exception("Accounting account missing for {$eventName}.");
        }

        $paymentCode = generate_payment_code('CT');
        $base = [
            'store_id' => auth()->user()->store_id ?? null,
            'payment_code' => $paymentCode,
            'transaction_date' => Carbon::parse($date)->format('Y-m-d'),
            'transaction_type' => strtoupper($eventName),
            'event_type' => $eventName,
            'note' => $note,
            'customer_id' => $customer->id,
            'ref_customer_payment_id' => $customerPayment?->id,
            'ref_customer_opening_balance_id' => $openingBalance?->id,
            'ref_sales_id' => $order?->id,
            'created_by' => auth()->user() ? substr(auth()->user()->name, 0, 50) : null,
            'creator' => auth()->id(),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        AcTransaction::create(array_merge($base, [
            'debit_account_id' => $debitAccountId,
            'debit_amt' => $amount,
            'credit_account_id' => null,
            'credit_amt' => null,
            'slug' => Str::orderedUuid() . uniqid(),
        ]));

        AcTransaction::create(array_merge($base, [
            'debit_account_id' => null,
            'debit_amt' => null,
            'credit_account_id' => $creditAccountId,
            'credit_amt' => $amount,
            'slug' => Str::orderedUuid() . uniqid(),
        ]));
    }

    private function ensureCustomerDueEventMappings(array $accounts): void
    {
        $this->upsertMapping('customer_due_payment', 'Customer due payment collection', null, $accounts['accounts_receivable']->id);
        $this->upsertMapping('customer_advance_payment', 'Customer advance received', null, $accounts['customer_advance']->id);
        $this->upsertMapping('customer_advance_refund', 'Customer advance refunded', $accounts['customer_advance']->id, null);
        $this->upsertMapping('customer_advance_applied', 'Customer advance applied to due', $accounts['customer_advance']->id, $accounts['accounts_receivable']->id);
        $this->upsertMapping('customer_opening_due', 'Customer old due opening balance', $accounts['accounts_receivable']->id, $accounts['opening_balance_equity']->id);
        $this->upsertMapping('customer_opening_advance', 'Customer old advance opening balance', $accounts['opening_balance_equity']->id, $accounts['customer_advance']->id);
        $this->upsertMapping('customer_due_order', 'Customer order with due payment', $accounts['accounts_receivable']->id, $accounts['sales_revenue']->id);
    }

    private function upsertMapping(string $eventName, string $description, ?int $debitAccountId, ?int $creditAccountId): void
    {
        AcEventMapping::updateOrCreate(
            ['event_name' => $eventName],
            [
                'event_description' => $description,
                'debit_account_id' => $debitAccountId,
                'credit_account_id' => $creditAccountId,
                'is_active' => true,
                'status' => 'active',
            ]
        );
    }

    private function ensureAccount(string $selectionName, string $accountName, string $type, string $normalBalance, ?int $parentId, string $sortCode, string $accountCode, bool $isControl = false): AcAccount
    {
        $account = AcAccount::where('account_selection_name', $selectionName)->first();

        if (!$account) {
            $account = AcAccount::where('account_name', $accountName)->first();
        }

        if (!$account && $selectionName === 'customer_advance') {
            $account = AcAccount::where('account_name', 'customer advance')->orWhere('account_name', 'Customer Advance')->first();
        }

        if (!$account) {
            $account = AcAccount::create([
                'store_id' => 1,
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
            ]);
        } else {
            $account->fill([
                'parent_id' => $parentId ?? $account->parent_id,
                'account_type' => $type,
                'normal_balance' => $normalBalance,
                'is_control_account' => $isControl,
                'account_selection_name' => $selectionName,
                'status' => 'active',
            ]);
            $account->save();
        }

        return $account;
    }

    private function selectionName(string $value): string
    {
        $clean = preg_replace('/[^a-zA-Z0-9\s]/', '', strtolower($value));
        return trim(preg_replace('/\s+/', '_', $clean), '_');
    }
}

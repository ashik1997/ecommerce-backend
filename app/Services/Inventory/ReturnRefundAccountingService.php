<?php

namespace App\Services\Inventory;

use App\Http\Controllers\Account\Models\AcAccount;
use App\Http\Controllers\Account\Models\AcTransaction;
use App\Http\Controllers\Account\Models\DbCustomerPayment;
use App\Http\Controllers\Account\Models\DbPaymentType;
use App\Http\Controllers\Customer\Models\Customer;
use App\Models\AcEventMapping;
use App\Models\ManualProductReturn;
use App\Models\ProductOrder;
use App\Models\ProductOrderRefund;
use App\Models\ProductOrderReturn;
use App\Services\Customer\CustomerTransactionService;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ReturnRefundAccountingService
{
    public function __construct(private CustomerTransactionService $customerTransactions)
    {
    }

    public function postReturn(ProductOrderReturn $return): void
    {
        $return->loadMissing(['return_products', 'originalOrder']);

        if ((int) ($return->is_accounting_posted ?? 0) === 1) {
            return;
        }

        $order = $return->originalOrder ?: ProductOrder::find($return->product_order_id);
        if (!$order || !$return->customer_id || (float) $return->total <= 0) {
            return;
        }

        $accounts = $this->ensureAccountsAndMappings();
        $date = Carbon::parse($return->return_date ?: now())->format('Y-m-d');
        $amount = (float) $return->total;
        $code = $return->return_invoice_no ?: $return->return_code;
        $note = "Product return {$return->return_code} for order {$order->order_code}";

        $customerPayment = DbCustomerPayment::create([
            'customer_id' => $return->customer_id,
            'order_id' => $order->id,
            'payment_date' => $date,
            'payment_type' => 'advance',
            'payment' => $amount,
            'payment_mode' => null,
            'payment_mode_title' => 'Return Payable',
            'payment_note' => $note,
            'creator' => auth()->id(),
            'slug' => Str::orderedUuid() . uniqid(),
            'status' => 'active',
        ]);

        $this->transactionLine($return, null, $customerPayment->id, $date, 'sales_return', $code, $accounts['sales_returns']->id, $accounts['customer_advance']->id, $amount, $note);

        $inventoryValue = $this->returnedInventoryValue($return);
        if ($inventoryValue > 0) {
            $this->transactionLine($return, null, null, $date, 'sales_return_inventory', $code, $accounts['inventory']->id, $accounts['cogs']->id, $inventoryValue, "Inventory restock for {$return->return_code}");
        }

        $return->fill([
            'is_accounting_posted' => 1,
            'accounting_posted_at' => now(),
            'accounting_reference' => $code,
            'refund_due_amount' => max(0, $amount - (float) ($return->refunded_amount ?? 0)),
            'refund_status' => ((float) ($return->refunded_amount ?? 0) >= $amount) ? 'completed' : 'pending',
        ])->save();

        $this->refreshOrderReturnSummary($order);
        $this->customerTransactions->recalculateCustomerBalance((int) $return->customer_id);
    }

    public function createRefund(ProductOrderReturn $return, DbPaymentType $paymentType, float $amount, ?string $refundDate = null, ?string $note = null): ProductOrderRefund
    {
        $return->loadMissing('originalOrder');
        $order = $return->originalOrder ?: ProductOrder::find($return->product_order_id);
        $remaining = max(0, (float) $return->total - (float) ($return->refunded_amount ?? 0));

        if (!$order || !$return->customer_id) {
            throw new \Exception('Return order or customer missing.');
        }

        if ($amount <= 0 || $amount > $remaining) {
            throw new \Exception('Refund amount exceeds payable return balance.');
        }

        $paymentAccount = $this->ensurePaymentTypeAccount($paymentType);
        $refund = ProductOrderRefund::create([
            'product_website_id' => $return->product_website_id,
            'store_id' => auth()->user()->store_id ?? $order->store_id ?? null,
            'product_order_return_id' => $return->id,
            'product_order_id' => $order->id,
            'customer_id' => $return->customer_id,
            'refund_code' => $this->generateRefundCode(),
            'refund_date' => $refundDate ?: now()->toDateString(),
            'refund_amount' => $amount,
            'payment_type_id' => $paymentType->id,
            'account_id' => $paymentAccount->id,
            'payment_type_snapshot' => $paymentType->payment_type,
            'refund_status' => 'completed',
            'note' => $note,
            'creator' => auth()->id(),
            'slug' => Str::orderedUuid() . uniqid(),
            'status' => 'active',
        ]);

        $this->postRefund($refund);

        return $refund;
    }

    public function postRefund(ProductOrderRefund $refund): void
    {
        if ((int) ($refund->is_accounting_posted ?? 0) === 1) {
            return;
        }

        $refund->loadMissing(['return', 'order']);
        $return = $refund->return;
        $order = $refund->order;

        if (!$return || !$order || !$refund->customer_id || (float) $refund->refund_amount <= 0) {
            return;
        }

        $accounts = $this->ensureAccountsAndMappings();
        $paymentType = DbPaymentType::findOrFail($refund->payment_type_id);
        $paymentAccount = $this->ensurePaymentTypeAccount($paymentType);
        $date = Carbon::parse($refund->refund_date ?: now())->format('Y-m-d');
        $amount = (float) $refund->refund_amount;
        $note = $refund->note ?: "Refund {$refund->refund_code} for return {$return->return_code}";

        $customerPayment = DbCustomerPayment::create([
            'customer_id' => $refund->customer_id,
            'order_id' => $order->id,
            'payment_date' => $date,
            'payment_type' => 'refund',
            'payment' => -$amount,
            'payment_mode' => $paymentType->id,
            'payment_mode_title' => $paymentType->payment_type,
            'payment_note' => $note,
            'creator' => auth()->id(),
            'slug' => Str::orderedUuid() . uniqid(),
            'status' => 'active',
        ]);

        $this->transactionLine($return, $refund, $customerPayment->id, $date, 'product_order_refund', $refund->refund_code, $accounts['customer_advance']->id, $paymentAccount->id, $amount, $note);

        $refund->fill([
            'account_id' => $paymentAccount->id,
            'is_accounting_posted' => 1,
            'accounting_posted_at' => now(),
            'accounting_reference' => $refund->refund_code,
        ])->save();

        $return->refunded_amount = (float) ($return->refunded_amount ?? 0) + $amount;
        $return->refund_due_amount = max(0, (float) $return->total - (float) $return->refunded_amount);
        $return->refund_status = $return->refund_due_amount > 0 ? 'partial' : 'completed';
        $return->refund_payment_type_id = $paymentType->id;
        $return->refund_account_id = $paymentAccount->id;
        $return->save();

        $this->refreshOrderReturnSummary($order);
        $this->customerTransactions->recalculateCustomerBalance((int) $refund->customer_id);
    }

    public function reverseRefund(ProductOrderRefund $refund, ?string $note = null): void
    {
        $refund->loadMissing(['return', 'order']);
        if ($refund->status !== 'active') {
            return;
        }

        $return = $refund->return;
        $order = $refund->order;
        if (!$return || !$order) {
            throw new \Exception('Refund reference missing.');
        }

        $amount = (float) $refund->refund_amount;
        $date = now()->toDateString();
        $memo = $note ?: "Reverse refund {$refund->refund_code}";

        DbCustomerPayment::create([
            'customer_id' => $refund->customer_id,
            'order_id' => $refund->product_order_id,
            'payment_date' => $date,
            'payment_type' => 'refund',
            'payment' => $amount,
            'payment_mode' => $refund->payment_type_id,
            'payment_mode_title' => $refund->payment_type_snapshot,
            'payment_note' => $memo,
            'creator' => auth()->id(),
            'slug' => Str::orderedUuid() . uniqid(),
            'status' => 'active',
        ]);

        $this->reverseTransactionsFor($return, $refund, $memo);

        $refund->status = 'inactive';
        $refund->refund_status = 'reversed';
        $refund->note = trim(($refund->note ? $refund->note . "\n" : '') . $memo);
        $refund->reversal_refund_id = $refund->id;
        $refund->save();

        $return->refunded_amount = max(0, (float) $return->refunded_amount - $amount);
        $return->refund_due_amount = max(0, (float) $return->total - (float) $return->refunded_amount);
        $return->refund_status = $return->refunded_amount <= 0 ? 'pending' : ($return->refund_due_amount > 0 ? 'partial' : 'completed');
        $return->save();

        $this->refreshOrderReturnSummary($order);
        $this->customerTransactions->recalculateCustomerBalance((int) $refund->customer_id);
    }

    public function reverseReturn(ProductOrderReturn $return, ?string $note = null): void
    {
        $return->loadMissing(['return_products', 'originalOrder', 'refunds']);
        if ($return->status !== 'active') {
            return;
        }

        if ($return->refunds()->where('status', 'active')->exists()) {
            throw new \Exception('Reverse active refunds before reversing this return.');
        }

        $order = $return->originalOrder ?: ProductOrder::find($return->product_order_id);
        if (!$order) {
            throw new \Exception('Original order missing.');
        }

        $amount = (float) $return->total;
        $date = now()->toDateString();
        $memo = $note ?: "Reverse return {$return->return_code}";

        foreach ($return->return_products as $item) {
            $product = \App\Models\Product::find($item->product_id);
            if ($product) {
                $product->stock = max(0, (float) $product->stock - (float) $item->qty);
                $product->save();
            }

            if (function_exists('insert_stock_log')) {
                insert_stock_log([
                    'warehouse_id' => $return->product_warehouse_id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'product_return_id' => $return->id,
                    'quantity' => -abs((float) $item->qty),
                    'type' => 'return_reverse',
                ]);
            }
        }

        DbCustomerPayment::create([
            'customer_id' => $return->customer_id,
            'order_id' => $return->product_order_id,
            'payment_date' => $date,
            'payment_type' => 'advance',
            'payment' => -$amount,
            'payment_mode_title' => 'Return Reversal',
            'payment_note' => $memo,
            'creator' => auth()->id(),
            'slug' => Str::orderedUuid() . uniqid(),
            'status' => 'active',
        ]);

        $this->reverseTransactionsFor($return, null, $memo);

        $return->status = 'inactive';
        $return->return_status = 'rejected';
        $return->refund_status = 'pending';
        $return->refund_due_amount = 0;
        $return->reversal_return_id = $return->id;
        $return->note = trim(($return->note ? $return->note . "\n" : '') . $memo);
        $return->save();

        $this->refreshOrderReturnSummary($order);
        $this->customerTransactions->recalculateCustomerBalance((int) $return->customer_id);
    }

    public function postManualReturn(ManualProductReturn $return): void
    {
        $return->loadMissing(['return_items', 'customer']);

        if ((int) ($return->is_accounting_posted ?? 0) === 1) {
            return;
        }

        if (!$return->customer_id || (float) $return->total <= 0) {
            return;
        }

        $accounts = $this->ensureAccountsAndMappings();
        $date = Carbon::parse($return->return_date ?: now())->format('Y-m-d');
        $amount = (float) $return->total;
        $note = "Manual product return {$return->return_code}";

        $customerPayment = DbCustomerPayment::create([
            'customer_id' => $return->customer_id,
            'order_id' => null,
            'payment_date' => $date,
            'payment_type' => 'advance',
            'payment' => $amount,
            'payment_mode' => null,
            'payment_mode_title' => 'Manual Return Payable',
            'payment_note' => $note,
            'creator' => auth()->id(),
            'slug' => Str::orderedUuid() . uniqid(),
            'status' => 'active',
        ]);

        $this->manualTransactionLine($return, $customerPayment->id, $date, 'manual_product_return', $return->return_code, $accounts['sales_returns']->id, $accounts['customer_advance']->id, $amount, $note);

        $inventoryValue = $this->manualReturnedInventoryValue($return);
        if ($inventoryValue > 0) {
            $this->manualTransactionLine($return, null, $date, 'manual_product_return_inventory', $return->return_code, $accounts['inventory']->id, $accounts['cogs']->id, $inventoryValue, "Inventory restock for manual return {$return->return_code}");
        }

        $return->fill([
            'is_accounting_posted' => 1,
            'accounting_posted_at' => now(),
            'accounting_reference' => $return->return_code,
        ])->save();

        $this->customerTransactions->recalculateCustomerBalance((int) $return->customer_id);
    }

    public function reverseManualReturn(ManualProductReturn $return, ?string $note = null): void
    {
        $return->loadMissing('return_items');
        if ($return->status !== 'active') {
            return;
        }

        $amount = (float) $return->total;
        $date = now()->toDateString();
        $memo = $note ?: "Reverse manual return {$return->return_code}";

        foreach ($return->return_items as $item) {
            $product = \App\Models\Product::find($item->product_id);
            if ($product) {
                $product->stock = max(0, (float) $product->stock - (float) $item->qty);
                $product->save();
            }

            if (function_exists('insert_stock_log')) {
                insert_stock_log([
                    'warehouse_id' => null,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'product_return_id' => $return->id,
                    'quantity' => -abs((float) $item->qty),
                    'type' => 'manual_return_reverse',
                ]);
            }
        }

        DbCustomerPayment::create([
            'customer_id' => $return->customer_id,
            'order_id' => null,
            'payment_date' => $date,
            'payment_type' => 'advance',
            'payment' => -$amount,
            'payment_mode_title' => 'Manual Return Reversal',
            'payment_note' => $memo,
            'creator' => auth()->id(),
            'slug' => Str::orderedUuid() . uniqid(),
            'status' => 'active',
        ]);

        $this->reverseManualTransactionsFor($return, $memo);

        $return->status = 'inactive';
        $return->return_status = 'rejected';
        $return->reversal_return_id = $return->id;
        $return->note = trim(($return->note ? $return->note . "\n" : '') . $memo);
        $return->save();

        $this->customerTransactions->recalculateCustomerBalance((int) $return->customer_id);
    }

    public function ensurePaymentTypeAccount(DbPaymentType $paymentType): AcAccount
    {
        return $this->customerTransactions->ensurePaymentTypeAccount($paymentType);
    }

    public function refreshOrderReturnSummary(ProductOrder $order): void
    {
        $returnedAmount = (float) ProductOrderReturn::where('product_order_id', $order->id)
            ->where('status', 'active')
            ->sum('total');

        $refundedAmount = (float) ProductOrderRefund::where('product_order_id', $order->id)
            ->where('status', 'active')
            ->sum('refund_amount');

        $order->returned_amount = $returnedAmount;
        $order->refunded_amount = $refundedAmount;
        $order->refund_due_amount = max(0, $returnedAmount - $refundedAmount);
        $order->is_returned = $returnedAmount > 0 ? 1 : 0;
        $order->return_ids = ProductOrderReturn::where('product_order_id', $order->id)
            ->where('status', 'active')
            ->pluck('id')
            ->implode(',');
        $order->save();
    }

    private function ensureAccountsAndMappings(): array
    {
        $customerAccounts = $this->customerTransactions->ensureAccountingSetup();

        $assets = $this->ensureAccount('assets', 'Assets', 'asset', 'debit', null, '1000', 'AC-1000');
        $currentAssets = $this->ensureAccount('current_assets', 'Current Assets', 'asset', 'debit', $assets->id, '1100', 'AC-1100');
        $inventory = $this->ensureAccount('inventory', 'Inventory', 'asset', 'debit', $currentAssets->id, '1130', 'AC-1130');

        $expenses = $this->ensureAccount('expenses', 'Expenses', 'expense', 'debit', null, '5000', 'AC-5000');
        $cogs = $this->ensureAccount('cogs', 'Cost of Goods Sold (COGS)', 'expense', 'debit', $expenses->id, '5100', 'AC-5100');
        $salesReturns = $this->ensureAccount('sales_returns', 'Sales Returns & Allowances', 'expense', 'debit', $expenses->id, '5200', 'AC-5200');

        $accounts = array_merge($customerAccounts, [
            'inventory' => $inventory,
            'cogs' => $cogs,
            'sales_returns' => $salesReturns,
        ]);

        AcEventMapping::updateOrCreate(
            ['event_name' => 'product_order_return'],
            [
                'event_description' => 'Product order return payable',
                'debit_account_id' => $salesReturns->id,
                'credit_account_id' => $accounts['customer_advance']->id,
                'secondary_debit_account_id' => $inventory->id,
                'secondary_credit_account_id' => $cogs->id,
                'is_active' => true,
            ]
        );

        AcEventMapping::updateOrCreate(
            ['event_name' => 'product_order_refund'],
            [
                'event_description' => 'Product return refund paid',
                'debit_account_id' => $accounts['customer_advance']->id,
                'credit_account_id' => null,
                'is_active' => true,
            ]
        );

        return $accounts;
    }

    private function transactionLine(ProductOrderReturn $return, ?ProductOrderRefund $refund, ?int $customerPaymentId, string $date, string $event, ?string $code, int $debitAccountId, int $creditAccountId, float $amount, string $note): void
    {
        AcTransaction::create([
            'store_id' => auth()->user()->store_id ?? null,
            'payment_code' => $code,
            'transaction_date' => $date,
            'transaction_type' => strtoupper($event),
            'event_type' => $event,
            'debit_account_id' => $debitAccountId,
            'credit_account_id' => $creditAccountId,
            'debit_amt' => $amount,
            'credit_amt' => $amount,
            'note' => $note,
            'ref_salespaymentsreturn_id' => $return->id,
            'ref_product_order_refund_id' => $refund?->id,
            'ref_customer_payment_id' => $customerPaymentId,
            'ref_sales_id' => $return->product_order_id,
            'customer_id' => $return->customer_id,
            'created_by' => auth()->user() ? substr(auth()->user()->name, 0, 50) : null,
            'created_date' => $date,
            'creator' => auth()->id(),
            'slug' => Str::orderedUuid() . uniqid(),
            'status' => 'active',
        ]);
    }

    private function manualTransactionLine(ManualProductReturn $return, ?int $customerPaymentId, string $date, string $event, ?string $code, int $debitAccountId, int $creditAccountId, float $amount, string $note): void
    {
        AcTransaction::create([
            'store_id' => auth()->user()->store_id ?? null,
            'payment_code' => $code,
            'transaction_date' => $date,
            'transaction_type' => strtoupper($event),
            'event_type' => $event,
            'debit_account_id' => $debitAccountId,
            'credit_account_id' => $creditAccountId,
            'debit_amt' => $amount,
            'credit_amt' => $amount,
            'note' => $note,
            'ref_manual_product_return_id' => $return->id,
            'ref_customer_payment_id' => $customerPaymentId,
            'customer_id' => $return->customer_id,
            'created_by' => auth()->user() ? substr(auth()->user()->name, 0, 50) : null,
            'created_date' => $date,
            'creator' => auth()->id(),
            'slug' => Str::orderedUuid() . uniqid(),
            'status' => 'active',
        ]);
    }

    private function reverseTransactionsFor(ProductOrderReturn $return, ?ProductOrderRefund $refund, string $note): void
    {
        $query = AcTransaction::where('ref_salespaymentsreturn_id', $return->id)
            ->where('status', 'active')
            ->whereNull('reversal_of_transaction_id')
            ->where('event_type', 'not like', '%_reverse');

        if ($refund) {
            $query->where('ref_product_order_refund_id', $refund->id);
        } else {
            $query->whereNull('ref_product_order_refund_id');
        }

        foreach ($query->get() as $tx) {
            AcTransaction::create([
                'store_id' => $tx->store_id,
                'payment_code' => $tx->payment_code,
                'transaction_date' => now()->toDateString(),
                'transaction_type' => $tx->transaction_type . '_REVERSE',
                'event_type' => $tx->event_type . '_reverse',
                'debit_account_id' => $tx->credit_account_id,
                'credit_account_id' => $tx->debit_account_id,
                'debit_amt' => $tx->credit_amt,
                'credit_amt' => $tx->debit_amt,
                'note' => $note,
                'ref_salespaymentsreturn_id' => $return->id,
                'ref_product_order_refund_id' => $refund?->id,
                'ref_sales_id' => $return->product_order_id,
                'customer_id' => $return->customer_id,
                'reversal_of_transaction_id' => $tx->id,
                'creator' => auth()->id(),
                'slug' => Str::orderedUuid() . uniqid(),
                'status' => 'active',
            ]);
        }
    }

    private function reverseManualTransactionsFor(ManualProductReturn $return, string $note): void
    {
        $query = AcTransaction::where('ref_manual_product_return_id', $return->id)
            ->where('status', 'active')
            ->whereNull('reversal_of_transaction_id')
            ->where('event_type', 'not like', '%_reverse');

        foreach ($query->get() as $tx) {
            AcTransaction::create([
                'store_id' => $tx->store_id,
                'payment_code' => $tx->payment_code,
                'transaction_date' => now()->toDateString(),
                'transaction_type' => $tx->transaction_type . '_REVERSE',
                'event_type' => $tx->event_type . '_reverse',
                'debit_account_id' => $tx->credit_account_id,
                'credit_account_id' => $tx->debit_account_id,
                'debit_amt' => $tx->credit_amt,
                'credit_amt' => $tx->debit_amt,
                'note' => $note,
                'ref_manual_product_return_id' => $return->id,
                'customer_id' => $return->customer_id,
                'reversal_of_transaction_id' => $tx->id,
                'creator' => auth()->id(),
                'slug' => Str::orderedUuid() . uniqid(),
                'status' => 'active',
            ]);
        }
    }

    private function returnedInventoryValue(ProductOrderReturn $return): float
    {
        return (float) $return->return_products->sum(function ($item) {
            return (float) $item->product_price * (float) $item->qty;
        });
    }

    private function manualReturnedInventoryValue(ManualProductReturn $return): float
    {
        return (float) $return->return_items->sum(function ($item) {
            return (float) $item->total_price;
        });
    }

    private function ensureAccount(string $selectionName, string $accountName, string $type, string $normalBalance, ?int $parentId, string $sortCode, string $accountCode): AcAccount
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
                'is_control_account' => in_array($selectionName, ['customer_advance'], true),
                'sort_code' => $sortCode,
                'account_code' => $accountCode,
                'account_name' => $accountName,
                'account_selection_name' => $selectionName,
                'balance' => 0,
                'creator' => auth()->id(),
                'slug' => Str::slug($accountName) . '-' . time(),
                'status' => 'active',
            ]);
        }

        $account->fill([
            'parent_id' => $parentId ?? $account->parent_id,
            'account_type' => $type,
            'normal_balance' => $normalBalance,
            'account_selection_name' => $selectionName,
            'status' => 'active',
        ])->save();

        return $account;
    }

    private function generateRefundCode(): string
    {
        $prefix = 'RFD-' . now()->format('ym');
        $last = ProductOrderRefund::where('refund_code', 'like', $prefix . '%')->orderByDesc('id')->value('refund_code');
        $number = $last ? ((int) substr($last, -5)) + 1 : 1;

        return $prefix . str_pad((string) $number, 5, '0', STR_PAD_LEFT);
    }
}

<?php

namespace App\Services\Supplier;

use App\Http\Controllers\Account\Models\AcAccount;
use App\Http\Controllers\Account\Models\AcTransaction;
use App\Http\Controllers\Account\Models\DbPaymentType;
use App\Http\Controllers\Account\Models\DbPurchasePayment;
use App\Http\Controllers\Account\Models\DbSupplierPayment;
use App\Http\Controllers\Account\Models\SupplierOpeningBalance;
use App\Http\Controllers\Inventory\Models\ProductPurchaseOrder;
use App\Http\Controllers\Inventory\Models\ProductSupplier;
use App\Models\AcEventMapping;
use App\Models\ProductPurchaseReturn;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SupplierTransactionService
{
    /**
     * Make supplier accounting self-healing before posting supplier transactions.
     */
    public function ensureAccountingSetup(): array
    {
        $accounts = $this->ensureSupplierAccounts();

        DbPaymentType::where('status', 'active')->get()->each(function ($paymentType) {
            $this->ensurePaymentTypeAccount($paymentType);
        });

        $this->ensureSupplierEventMappings($accounts);

        return $accounts;
    }

    public function ensureSupplierAccounts(): array
    {
        $assets = $this->ensureAccount('assets', 'Assets', 'asset', 'debit', null, '1000', 'AC-1000');
        $currentAssets = $this->ensureAccount('current_assets', 'Current Assets', 'asset', 'debit', $assets->id, '1100', 'AC-1100');
        $cashBank = $this->ensureAccount('cash_bank', 'Cash & Bank', 'asset', 'debit', $currentAssets->id, '1110', 'AC-1110');

        $cashOnHand = $this->ensureAccount('cash_on_hand', 'Cash on Hand', 'asset', 'debit', $cashBank->id, '1111', 'AC-1111');
        $inventory = $this->ensureAccount('inventory', 'Inventory', 'asset', 'debit', $currentAssets->id, '1130', 'AC-1130');
        $supplierAdvance = $this->ensureAccount('supplier_advance', 'Supplier Advance', 'asset', 'debit', $currentAssets->id, '1125', 'AC-1125', true);

        $liabilities = $this->ensureAccount('liabilities', 'Liabilities', 'liability', 'credit', null, '2000', 'AC-2000');
        $currentLiabilities = $this->ensureAccount('current_liabilities', 'Current Liabilities', 'liability', 'credit', $liabilities->id, '2100', 'AC-2100');
        $accountsPayable = $this->ensureAccount('accounts_payable', 'Accounts Payable', 'liability', 'credit', $currentLiabilities->id, '2110', 'AC-2110', true);

        $equity = $this->ensureAccount('equity', "Owner's Equity", 'equity', 'credit', null, '3000', 'AC-3000');
        $openingBalanceEquity = $this->ensureAccount('opening_balance_equity', 'Opening Balance Equity', 'equity', 'credit', $equity->id, '3400', 'AC-3400');

        return [
            'cash_on_hand' => $cashOnHand,
            'inventory' => $inventory,
            'supplier_advance' => $supplierAdvance,
            'accounts_payable' => $accountsPayable,
            'opening_balance_equity' => $openingBalanceEquity,
        ];
    }

    /**
     * Resolve/create the real cash/bank account for a payment method.
     */
    public function ensurePaymentTypeAccount(DbPaymentType $paymentType): AcAccount
    {
        if ($paymentType->debit_account_id) {
            $existing = AcAccount::where('id', $paymentType->debit_account_id)
                ->where('status', 'active')
                ->first();
            if ($existing) {
                return $existing;
            }
        }

        $account = AcAccount::where('paymenttypes_id', $paymentType->id)
            ->where('status', 'active')
            ->first();

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

    public function accountBalance(AcAccount $account): float
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

    public function supplierDashboardSummary(): array
    {
        $purchaseBySupplier = ProductPurchaseOrder::select('product_supplier_id', DB::raw('COALESCE(SUM(total), 0) as total'))
            ->where('status', 'active')
            ->where('order_status', 'received')
            ->groupBy('product_supplier_id')
            ->pluck('total', 'product_supplier_id');

        $returnBySupplier = ProductPurchaseReturn::select('product_supplier_id', DB::raw('COALESCE(SUM(total), 0) as total'))
            ->where('status', 'active')
            ->groupBy('product_supplier_id')
            ->pluck('total', 'product_supplier_id');

        $paidBySupplier = DbPurchasePayment::select('supplier_id', DB::raw('COALESCE(SUM(payment), 0) as total'))
            ->where('status', 'active')
            ->groupBy('supplier_id')
            ->pluck('total', 'supplier_id');

        $oldDueBySupplier = SupplierOpeningBalance::select('supplier_id', DB::raw('COALESCE(SUM(remaining_amount), 0) as total'))
            ->where('entry_type', 'due')
            ->where('status', 'active')
            ->groupBy('supplier_id')
            ->pluck('total', 'supplier_id');

        $openingAdvanceBySupplier = SupplierOpeningBalance::select('supplier_id', DB::raw('COALESCE(SUM(remaining_amount), 0) as total'))
            ->where('entry_type', 'advance')
            ->where('status', 'active')
            ->groupBy('supplier_id')
            ->pluck('total', 'supplier_id');

        $advancePaymentBySupplier = DbSupplierPayment::select('supplier_id', DB::raw('COALESCE(SUM(payment), 0) as total'))
            ->where('status', 'active')
            ->where('payment_type', 'advance')
            ->groupBy('supplier_id')
            ->pluck('total', 'supplier_id');

        $advanceOutBySupplier = DbSupplierPayment::select('supplier_id', DB::raw('COALESCE(SUM(payment), 0) as total'))
            ->where('status', 'active')
            ->whereIn('payment_type', ['adjustment', 'refund'])
            ->whereNull('advance_opening_balance_id')
            ->groupBy('supplier_id')
            ->pluck('total', 'supplier_id');

        $supplierIds = collect()
            ->merge($purchaseBySupplier->keys())
            ->merge($returnBySupplier->keys())
            ->merge($paidBySupplier->keys())
            ->merge($oldDueBySupplier->keys())
            ->merge($openingAdvanceBySupplier->keys())
            ->merge($advancePaymentBySupplier->keys())
            ->merge($advanceOutBySupplier->keys())
            ->filter()
            ->unique();

        $stats = [];
        $summary = [
            'total_purchase' => 0,
            'total_return' => 0,
            'total_paid' => 0,
            'total_old_due' => 0,
            'total_payable' => 0,
            'total_advance' => 0,
            'total_net_payable' => 0,
        ];

        foreach ($supplierIds as $supplierId) {
            $supplierId = (int) $supplierId;
            $purchase = (float) ($purchaseBySupplier[$supplierId] ?? 0);
            $return = (float) ($returnBySupplier[$supplierId] ?? 0);
            $paid = (float) ($paidBySupplier[$supplierId] ?? 0);
            $oldDue = (float) ($oldDueBySupplier[$supplierId] ?? 0);
            $advance = max(
                0,
                (float) ($openingAdvanceBySupplier[$supplierId] ?? 0)
                    + (float) ($advancePaymentBySupplier[$supplierId] ?? 0)
                    - abs((float) ($advanceOutBySupplier[$supplierId] ?? 0))
            );
            $payable = $purchase - $return - $paid + $oldDue;
            $netPayable = $payable - $advance;

            $stats[$supplierId] = [
                'total_purchase' => $purchase,
                'return_amount' => $return,
                'paid' => $paid,
                'old_due' => $oldDue,
                'due' => $payable,
                'advance' => $advance,
                'net_payable' => $netPayable,
            ];

            $summary['total_purchase'] += $purchase;
            $summary['total_return'] += $return;
            $summary['total_paid'] += $paid;
            $summary['total_old_due'] += $oldDue;
            $summary['total_payable'] += $payable;
            $summary['total_advance'] += $advance;
            $summary['total_net_payable'] += $netPayable;
        }

        return [
            'summary' => $summary,
            'supplier_stats' => $stats,
        ];
    }

    public function createOpeningBalance(array $data): SupplierOpeningBalance
    {
        $this->ensureAccountingSetup();

        return DB::transaction(function () use ($data) {
            $supplier = ProductSupplier::lockForUpdate()->findOrFail($data['supplier_id']);
            $amount = (float) $data['amount'];

            $opening = SupplierOpeningBalance::create([
                'store_id' => auth()->user()->store_id ?? 1,
                'supplier_id' => $supplier->id,
                'entry_type' => $data['entry_type'],
                'invoice_no' => $data['invoice_no'] ?? null,
                'invoice_date' => $data['invoice_date'] ?? null,
                'due_date' => $data['due_date'] ?? null,
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

            $event = $opening->entry_type === 'due' ? 'supplier_opening_due' : 'supplier_opening_advance';
            $this->postAccounting($event, $amount, $opening->opening_date, $supplier, $opening->note, null, null, null, $opening);

            return $opening;
        });
    }

    public function payAdvance(int $supplierId, DbPaymentType $paymentType, float $amount, string $paymentDate, ?string $note): void
    {
        $this->ensureAccountingSetup();

        DB::transaction(function () use ($supplierId, $paymentType, $amount, $paymentDate, $note) {
            $supplier = ProductSupplier::lockForUpdate()->findOrFail($supplierId);
            $paymentAccount = $this->ensurePaymentTypeAccount($paymentType);
            $this->ensureAccountHasEnoughBalance($paymentAccount, $amount);

            $supplierPayment = DbSupplierPayment::create([
                'purchasepayment_id' => null,
                'supplier_opening_balance_id' => null,
                'supplier_id' => $supplier->id,
                'payment_date' => $paymentDate,
                'payment_type' => 'advance',
                'payment' => $amount,
                'payment_note' => $note ?? 'Supplier advance payment',
                'creator' => auth()->id(),
                'slug' => Str::orderedUuid() . uniqid(),
                'status' => 'active',
            ]);

            $this->postAccounting('supplier_advance_payment', $amount, $paymentDate, $supplier, $supplierPayment->payment_note, $paymentAccount, $supplierPayment);
        });
    }

    public function refundAdvance(int $supplierId, DbPaymentType $paymentType, float $amount, string $refundDate, ?string $note): array
    {
        $this->ensureAccountingSetup();

        return DB::transaction(function () use ($supplierId, $paymentType, $amount, $refundDate, $note) {
            $supplier = ProductSupplier::lockForUpdate()->findOrFail($supplierId);
            $refundAccount = $this->ensurePaymentTypeAccount($paymentType);
            $amount = max(0, $amount);

            if ($amount <= 0) {
                throw new \Exception('Refund amount is required.');
            }

            if ($amount > $this->availableAdvance($supplier->id)) {
                throw new \Exception('Refund amount exceeds supplier available advance.');
            }

            $supplierPayments = [];
            $advanceSources = $this->consumeAdvanceSources($supplier, $amount);

            foreach ($advanceSources as $source) {
                $sourceAmount = (float) ($source['amount'] ?? 0);
                if ($sourceAmount <= 0) {
                    continue;
                }

                $supplierPayment = $this->createSupplierPayment(
                    $supplier,
                    -$sourceAmount,
                    $refundDate,
                    $note ?? 'Supplier advance refund received',
                    'refund',
                    null,
                    null,
                    $source['opening_balance_id'] ?? null
                );

                $this->postAccounting('supplier_advance_refund', $sourceAmount, $refundDate, $supplier, $supplierPayment->payment_note, $refundAccount, $supplierPayment);
                $supplierPayments[] = $supplierPayment;
            }

            return $supplierPayments;
        });
    }

    public function payDue(int $supplierId, ?DbPaymentType $paymentType, float $paymentAmount, string $paymentDate, ?string $note, array $allocations, float $advanceAmount = 0): void
    {
        $accounts = $this->ensureAccountingSetup();

        DB::transaction(function () use ($supplierId, $paymentType, $paymentAmount, $paymentDate, $note, $allocations, $advanceAmount, $accounts) {
            $supplier = ProductSupplier::lockForUpdate()->findOrFail($supplierId);
            $paymentAccount = null;
            $cashAmount = max(0, $paymentAmount);
            $advanceAmount = max(0, $advanceAmount);
            $settlementAmount = $cashAmount + $advanceAmount;

            if ($settlementAmount <= 0) {
                throw new \Exception('Payment amount or advance amount is required.');
            }

            if ($cashAmount > 0) {
                if (!$paymentType) {
                    throw new \Exception('Payment mode is required for cash/bank payment.');
                }
                $paymentAccount = $this->ensurePaymentTypeAccount($paymentType);
                $this->ensureAccountHasEnoughBalance($paymentAccount, $cashAmount);
            }

            if ($advanceAmount > $this->availableAdvance($supplier->id)) {
                throw new \Exception('Advance amount exceeds supplier available advance.');
            }

            if (empty($allocations)) {
                $allocations = $this->buildFifoAllocations($supplier->id, $settlementAmount);
            }

            $totalAllocated = 0;
            $cashRemaining = $cashAmount;
            $advanceRemaining = $advanceAmount;

            foreach ($allocations as $allocation) {
                $amount = (float) ($allocation['payment_amount'] ?? 0);
                if ($amount <= 0) {
                    continue;
                }

                $advancePart = min($advanceRemaining, $amount);
                $cashPart = $amount - $advancePart;
                $advanceSources = $advancePart > 0 ? $this->consumeAdvanceSources($supplier, $advancePart) : [];
                $advanceRemaining -= $advancePart;
                $cashRemaining -= $cashPart;

                if (!empty($allocation['opening_balance_id'])) {
                    $this->payOpeningDue($supplier, $paymentAccount, $cashPart, $advanceSources, $paymentDate, $note, (int) $allocation['opening_balance_id']);
                } elseif (!empty($allocation['purchase_id'])) {
                    $this->payPurchaseDue($supplier, $paymentType, $paymentAccount, $accounts['supplier_advance'], $cashPart, $advanceSources, $paymentDate, $note, (int) $allocation['purchase_id']);
                } elseif (!empty($allocation['is_advance'])) {
                    throw new \Exception('Extra advance is not allowed on due payment page. Use Supplier Advance Payment.');
                }

                $totalAllocated += $amount;
            }

            if (abs($totalAllocated - $settlementAmount) > 0.01) {
                throw new \Exception('Payment allocation total does not match payment amount.');
            }

            if ($cashRemaining > 0.01 || $advanceRemaining > 0.01) {
                throw new \Exception('Payment allocation did not use the full amount.');
            }
        });
    }

    public function voidSupplierPayment(int $supplierPaymentId, ?string $reason = null): void
    {
        DB::transaction(function () use ($supplierPaymentId, $reason) {
            $supplierPayment = DbSupplierPayment::whereKey($supplierPaymentId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($supplierPayment->status !== 'active') {
                throw new \Exception('This supplier payment is already voided.');
            }

            if (
                Schema::hasTable('supplier_cheque_payments')
                && DB::table('supplier_cheque_payments')
                ->where('supplier_payment_id', $supplierPayment->id)
                ->where('status', 'cleared')
                ->exists()
            ) {
                throw new \Exception('Void cleared cheque payments from the Supplier Cheque Payments module.');
            }

            $amount = abs((float) $supplierPayment->payment);
            if ($amount <= 0) {
                throw new \Exception('Invalid supplier payment amount.');
            }

            if ($supplierPayment->supplier_opening_balance_id) {
                $this->restoreOpeningDue((int) $supplierPayment->supplier_opening_balance_id, $amount);
            }

            if ($supplierPayment->purchasepayment_id) {
                DbPurchasePayment::whereKey($supplierPayment->purchasepayment_id)
                    ->lockForUpdate()
                    ->update(['status' => 'inactive', 'updated_at' => now()]);
            }

            if (in_array($supplierPayment->payment_type, ['adjustment', 'refund']) && $supplierPayment->advance_opening_balance_id) {
                $this->restoreOpeningAdvanceSource((int) $supplierPayment->advance_opening_balance_id, $amount);
            }

            AcTransaction::where('supplier_payment_id', $supplierPayment->id)
                ->where('status', 'active')
                ->update(['status' => 'inactive', 'updated_at' => now()]);

            $voidNote = 'Voided on ' . now()->format('Y-m-d H:i:s');
            if ($reason) {
                $voidNote .= '. Reason: ' . $reason;
            }

            $supplierPayment->payment_note = trim(($supplierPayment->payment_note ? $supplierPayment->payment_note . "\n" : '') . $voidNote);
            $supplierPayment->status = 'inactive';
            $supplierPayment->save();
        });
    }

    public function getDueItems(int $supplierId): array
    {
        $oldDues = SupplierOpeningBalance::where('supplier_id', $supplierId)
            ->where('entry_type', 'due')
            ->where('remaining_amount', '>', 0)
            ->where('status', 'active')
            ->orderBy('opening_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $items = [];

        foreach ($oldDues as $oldDue) {
            $items[] = [
                'source_type' => 'opening_due',
                'id' => $oldDue->id,
                'opening_balance_id' => $oldDue->id,
                'purchase_id' => null,
                'code' => $oldDue->invoice_no ?: ($oldDue->reference_no ?: 'OLD-DUE-' . $oldDue->id),
                'purchase_date' => optional($oldDue->invoice_date ?: $oldDue->opening_date)->format('Y-m-d'),
                'total' => (float) $oldDue->opening_amount,
                'return_amount' => 0,
                'paid_amount' => (float) $oldDue->paid_amount,
                'due_amount' => (float) $oldDue->remaining_amount,
                'label' => 'Old Due',
            ];
        }

        foreach ($this->getPurchaseDueRows($supplierId) as $purchase) {
            $items[] = [
                'source_type' => 'purchase_due',
                'id' => $purchase['id'],
                'opening_balance_id' => null,
                'purchase_id' => $purchase['id'],
                'code' => $purchase['code'],
                'purchase_date' => $purchase['purchase_date'],
                'total' => $purchase['total'],
                'return_amount' => $purchase['return_amount'],
                'paid_amount' => $purchase['paid_amount'],
                'due_amount' => $purchase['due_amount'],
                'label' => 'Purchase Due',
            ];
        }

        return $items;
    }

    public function availableAdvance(int $supplierId): float
    {
        $openingAdvance = (float) SupplierOpeningBalance::where('supplier_id', $supplierId)
            ->where('entry_type', 'advance')
            ->where('status', 'active')
            ->sum('remaining_amount');

        $advancePayments = (float) DbSupplierPayment::where('supplier_id', $supplierId)
            ->where('status', 'active')
            ->where('payment_type', 'advance')
            ->sum('payment');

        $advanceOut = abs((float) DbSupplierPayment::where('supplier_id', $supplierId)
            ->where('status', 'active')
            ->whereIn('payment_type', ['adjustment', 'refund'])
            ->whereNull('advance_opening_balance_id')
            ->sum('payment'));

        return max(0, $openingAdvance + $advancePayments - $advanceOut);
    }

    private function consumeAdvanceSources(ProductSupplier $supplier, float $amount): array
    {
        $remaining = $amount;
        $sources = [];

        $openingAdvances = SupplierOpeningBalance::where('supplier_id', $supplier->id)
            ->where('entry_type', 'advance')
            ->where('remaining_amount', '>', 0)
            ->where('status', 'active')
            ->orderBy('opening_date', 'asc')
            ->orderBy('id', 'asc')
            ->lockForUpdate()
            ->get();

        foreach ($openingAdvances as $openingAdvance) {
            if ($remaining <= 0) {
                break;
            }

            $sourceAmount = min($remaining, (float) $openingAdvance->remaining_amount);
            if ($sourceAmount <= 0) {
                continue;
            }

            $openingAdvance->paid_amount = (float) $openingAdvance->paid_amount + $sourceAmount;
            $openingAdvance->remaining_amount = max(0, (float) $openingAdvance->remaining_amount - $sourceAmount);
            $openingAdvance->save();

            $sources[] = [
                'opening_balance_id' => $openingAdvance->id,
                'amount' => $sourceAmount,
            ];

            $remaining -= $sourceAmount;
        }

        if ($remaining > 0.01) {
            $sources[] = [
                'opening_balance_id' => null,
                'amount' => $remaining,
            ];
        }

        return $sources;
    }

    private function sumAdvanceSources(array $advanceSources): float
    {
        return array_reduce($advanceSources, function ($total, $source) {
            return $total + (float) ($source['amount'] ?? 0);
        }, 0.0);
    }

    private function restoreOpeningDue(int $openingId, float $amount): void
    {
        $opening = SupplierOpeningBalance::where('entry_type', 'due')
            ->where('status', 'active')
            ->lockForUpdate()
            ->findOrFail($openingId);

        $opening->paid_amount = max(0, (float) $opening->paid_amount - $amount);
        $opening->remaining_amount = min((float) $opening->opening_amount, (float) $opening->remaining_amount + $amount);
        $opening->save();
    }

    private function restoreOpeningAdvanceSource(int $openingId, float $amount): void
    {
        $opening = SupplierOpeningBalance::where('entry_type', 'advance')
            ->where('status', 'active')
            ->lockForUpdate()
            ->findOrFail($openingId);

        $opening->paid_amount = max(0, (float) $opening->paid_amount - $amount);
        $opening->remaining_amount = min((float) $opening->opening_amount, (float) $opening->remaining_amount + $amount);
        $opening->save();
    }

    private function payOpeningDue(ProductSupplier $supplier, ?AcAccount $paymentAccount, float $cashAmount, array $advanceSources, string $paymentDate, ?string $note, int $openingId): void
    {
        $opening = SupplierOpeningBalance::where('supplier_id', $supplier->id)
            ->where('entry_type', 'due')
            ->where('status', 'active')
            ->lockForUpdate()
            ->findOrFail($openingId);

        $advanceAmount = $this->sumAdvanceSources($advanceSources);
        $amount = $cashAmount + $advanceAmount;
        if ($amount > (float) $opening->remaining_amount) {
            throw new \Exception('Payment amount exceeds old supplier due balance.');
        }

        $opening->paid_amount = (float) $opening->paid_amount + $amount;
        $opening->remaining_amount = max(0, (float) $opening->remaining_amount - $amount);
        $opening->save();

        if ($cashAmount > 0) {
            $supplierPayment = $this->createSupplierPayment($supplier, $cashAmount, $paymentDate, $note ?? 'Old supplier due payment', 'due', null, $opening->id);
            $this->postAccounting('supplier_payment', $cashAmount, $paymentDate, $supplier, $supplierPayment->payment_note, $paymentAccount, $supplierPayment, null, $opening);
        }

        foreach ($advanceSources as $source) {
            $sourceAmount = (float) ($source['amount'] ?? 0);
            if ($sourceAmount <= 0) {
                continue;
            }

            $supplierPayment = $this->createSupplierPayment($supplier, -$sourceAmount, $paymentDate, $note ?? 'Supplier advance adjusted with old due', 'adjustment', null, $opening->id, $source['opening_balance_id'] ?? null);
            $this->postAccounting('supplier_advance_applied', $sourceAmount, $paymentDate, $supplier, $supplierPayment->payment_note, null, $supplierPayment, null, $opening);
        }
    }

    private function payPurchaseDue(ProductSupplier $supplier, ?DbPaymentType $paymentType, ?AcAccount $paymentAccount, AcAccount $supplierAdvanceAccount, float $cashAmount, array $advanceSources, string $paymentDate, ?string $note, int $purchaseId): void
    {
        $purchase = ProductPurchaseOrder::where('product_supplier_id', $supplier->id)
            ->where('status', 'active')
            ->where('order_status', 'received')
            ->lockForUpdate()
            ->findOrFail($purchaseId);

        $advanceAmount = $this->sumAdvanceSources($advanceSources);
        $amount = $cashAmount + $advanceAmount;
        $dueAmount = $this->purchaseDueAmount($purchase);
        if ($amount > $dueAmount) {
            throw new \Exception("Payment amount exceeds due amount for purchase {$purchase->code}.");
        }

        if ($cashAmount > 0) {
            $purchasePayment = $this->createPurchasePayment($purchase, $supplier, $paymentType?->id, $cashAmount, $paymentDate, $note ?? "Payment for purchase {$purchase->code}", $paymentAccount?->id);
            $supplierPayment = $this->createSupplierPayment($supplier, $cashAmount, $paymentDate, $note ?? "Payment for purchase {$purchase->code}", 'due', $purchasePayment->id, null);
            $this->postAccounting('supplier_payment', $cashAmount, $paymentDate, $supplier, $supplierPayment->payment_note, $paymentAccount, $supplierPayment, $purchase);
        }

        foreach ($advanceSources as $source) {
            $sourceAmount = (float) ($source['amount'] ?? 0);
            if ($sourceAmount <= 0) {
                continue;
            }

            $purchasePayment = $this->createPurchasePayment($purchase, $supplier, 'advance', $sourceAmount, $paymentDate, $note ?? "Advance adjusted for purchase {$purchase->code}", $supplierAdvanceAccount->id);
            $supplierPayment = $this->createSupplierPayment($supplier, -$sourceAmount, $paymentDate, $note ?? "Advance adjusted for purchase {$purchase->code}", 'adjustment', $purchasePayment->id, null, $source['opening_balance_id'] ?? null);
            $this->postAccounting('supplier_advance_applied', $sourceAmount, $paymentDate, $supplier, $supplierPayment->payment_note, null, $supplierPayment, $purchase);
        }
    }

    private function createPurchasePayment(ProductPurchaseOrder $purchase, ProductSupplier $supplier, $paymentType, float $amount, string $paymentDate, ?string $note, ?int $accountId): DbPurchasePayment
    {
        return DbPurchasePayment::create([
            'purchase_id' => $purchase->id,
            'supplier_id' => $supplier->id,
            'payment_date' => $paymentDate,
            'payment_type' => $paymentType,
            'payment' => $amount,
            'payment_note' => $note,
            'account_id' => $accountId,
            'creator' => auth()->id(),
            'slug' => Str::orderedUuid() . uniqid(),
            'status' => 'active',
        ]);
    }

    private function createSupplierPayment(ProductSupplier $supplier, float $amount, string $paymentDate, ?string $note, string $type, ?int $purchasePaymentId, ?int $openingId, ?int $advanceOpeningId = null): DbSupplierPayment
    {
        return DbSupplierPayment::create([
            'purchasepayment_id' => $purchasePaymentId,
            'supplier_opening_balance_id' => $openingId,
            'advance_opening_balance_id' => $advanceOpeningId,
            'supplier_id' => $supplier->id,
            'payment_date' => $paymentDate,
            'payment_type' => $type,
            'payment' => $amount,
            'payment_note' => $note,
            'creator' => auth()->id(),
            'slug' => Str::orderedUuid() . uniqid(),
            'status' => 'active',
        ]);
    }

    private function postAccounting(string $eventName, float $amount, $date, ProductSupplier $supplier, ?string $note = null, ?AcAccount $paymentAccount = null, ?DbSupplierPayment $supplierPayment = null, ?ProductPurchaseOrder $purchase = null, ?SupplierOpeningBalance $openingBalance = null): void
    {
        $mapping = AcEventMapping::getByEventName($eventName);
        if (!$mapping) {
            throw new \Exception("Accounting mapping missing for {$eventName}.");
        }

        $debitAccountId = $mapping->debit_account_id;
        $creditAccountId = $mapping->credit_account_id;

        if (in_array($eventName, ['supplier_payment', 'supplier_advance_payment'])) {
            $creditAccountId = $paymentAccount?->id;
        }

        if ($eventName === 'supplier_advance_refund') {
            $debitAccountId = $paymentAccount?->id;
        }

        if (!$debitAccountId || !$creditAccountId) {
            throw new \Exception("Accounting account missing for {$eventName}.");
        }

        $paymentCode = generate_payment_code('ST');
        $base = [
            'store_id' => auth()->user()->store_id ?? null,
            'payment_code' => $paymentCode,
            'transaction_date' => Carbon::parse($date)->format('Y-m-d'),
            'transaction_type' => strtoupper($eventName),
            'event_type' => $eventName,
            'note' => $note,
            'supplier_id' => $supplier->id,
            'supplier_payment_id' => $supplierPayment?->id,
            'ref_purchase_id' => $purchase?->id,
            'ref_supplier_opening_balance_id' => $openingBalance?->id,
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

    private function ensureSupplierEventMappings(array $accounts): void
    {
        $this->upsertMapping('purchase', 'Purchase of inventory on credit', $accounts['inventory']->id, $accounts['accounts_payable']->id);
        $this->upsertMapping('purchase_cash', 'Purchase of inventory with cash', $accounts['inventory']->id, $accounts['cash_on_hand']->id);
        $this->upsertMapping('purchase_return', 'Return of purchased inventory', $accounts['accounts_payable']->id, $accounts['inventory']->id);
        $this->upsertMapping('supplier_payment', 'Payment to supplier', $accounts['accounts_payable']->id, $accounts['cash_on_hand']->id);
        $this->upsertMapping('supplier_advance_payment', 'Advance payment to supplier', $accounts['supplier_advance']->id, $accounts['cash_on_hand']->id);
        $this->upsertMapping('supplier_advance_applied', 'Supplier advance applied to due', $accounts['accounts_payable']->id, $accounts['supplier_advance']->id);
        $this->upsertMapping('supplier_advance_refund', 'Supplier advance refunded by supplier', $accounts['cash_on_hand']->id, $accounts['supplier_advance']->id);
        $this->upsertMapping('supplier_opening_due', 'Supplier old due opening balance', $accounts['opening_balance_equity']->id, $accounts['accounts_payable']->id);
        $this->upsertMapping('supplier_opening_advance', 'Supplier old advance opening balance', $accounts['supplier_advance']->id, $accounts['opening_balance_equity']->id);
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

    private function getPurchaseDueRows(int $supplierId): array
    {
        $purchases = ProductPurchaseOrder::where('product_supplier_id', $supplierId)
            ->where('status', 'active')
            ->where('order_status', 'received')
            ->orderBy('date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $rows = [];
        foreach ($purchases as $purchase) {
            $dueAmount = $this->purchaseDueAmount($purchase);
            if ($dueAmount > 0) {
                $rows[] = [
                    'id' => $purchase->id,
                    'code' => $purchase->code,
                    'purchase_date' => $purchase->date,
                    'total' => (float) $purchase->total,
                    'return_amount' => $this->purchaseReturnAmount($purchase),
                    'paid_amount' => $this->purchasePaidAmount($purchase),
                    'due_amount' => $dueAmount,
                ];
            }
        }

        return $rows;
    }

    private function buildFifoAllocations(int $supplierId, float $paymentAmount): array
    {
        $allocations = [];
        $remaining = $paymentAmount;

        foreach ($this->getDueItems($supplierId) as $item) {
            if ($remaining <= 0) {
                break;
            }

            $amount = min($remaining, (float) $item['due_amount']);
            $allocations[] = [
                'purchase_id' => $item['purchase_id'],
                'opening_balance_id' => $item['opening_balance_id'],
                'payment_amount' => $amount,
            ];
            $remaining -= $amount;
        }

        if ($remaining > 0.01) {
            throw new \Exception('Payment amount exceeds supplier due.');
        }

        return $allocations;
    }

    private function purchaseDueAmount(ProductPurchaseOrder $purchase): float
    {
        return max(((float) $purchase->total - $this->purchaseReturnAmount($purchase)) - $this->purchasePaidAmount($purchase), 0);
    }

    private function purchaseReturnAmount(ProductPurchaseOrder $purchase): float
    {
        return (float) ProductPurchaseReturn::where('purchase_code', $purchase->code)
            ->where('status', 'active')
            ->sum('total');
    }

    private function purchasePaidAmount(ProductPurchaseOrder $purchase): float
    {
        return (float) DbPurchasePayment::where('purchase_id', $purchase->id)
            ->where('status', 'active')
            ->sum('payment');
    }

    private function ensureAccountHasEnoughBalance(AcAccount $account, float $amount): void
    {
        if ($amount > $this->accountBalance($account)) {
            throw new \Exception('Insufficient balance in account. Available: ' . number_format($this->accountBalance($account), 2));
        }
    }

    private function selectionName(string $value): string
    {
        $clean = preg_replace('/[^a-zA-Z0-9\s]/', '', strtolower($value));
        return trim(preg_replace('/\s+/', '_', $clean), '_');
    }
}

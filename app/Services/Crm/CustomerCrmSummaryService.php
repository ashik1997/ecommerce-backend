<?php

namespace App\Services\Crm;

use App\Http\Controllers\Customer\Models\Customer;
use App\Models\ProductOrder;
use App\Services\Customer\CustomerTransactionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustomerCrmSummaryService
{
    protected CustomerTransactionService $customerTransactionService;

    public function __construct(CustomerTransactionService $customerTransactionService)
    {
        $this->customerTransactionService = $customerTransactionService;
    }

    /**
     * Refresh CRM-only summary fields from the current accounting snapshot.
     *
     * CustomerTransactionService remains the owner of due, paid, advance, and
     * balance calculations. This method deliberately consumes those existing
     * customer fields instead of rebuilding accounting rules in the CRM layer.
     */
    public function refresh(int $customerId): ?Customer
    {
        try {
            return DB::transaction(function () use ($customerId) {
                $customer = Customer::lockForUpdate()->find($customerId);

                if (!$customer) {
                    return null;
                }

                return $this->refreshLockedCustomer($customer);
            });
        } catch (\Throwable $exception) {
            Log::error('CRM customer summary refresh failed', [
                'customer_id' => $customerId,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Recalculate the established customer accounting snapshot first, then
     * refresh CRM-only summary fields. Use this explicit method only where the
     * caller intentionally needs an up-to-date due/paid/advance snapshot.
     */
    public function refreshAfterBalanceRecalculation(int $customerId): ?Customer
    {
        try {
            return DB::transaction(function () use ($customerId) {
                $customer = Customer::lockForUpdate()->find($customerId);

                if (!$customer) {
                    return null;
                }

                $this->customerTransactionService->recalculateCustomerBalance($customer->id);
                $customer->refresh();

                return $this->refreshLockedCustomer($customer);
            });
        } catch (\Throwable $exception) {
            Log::error('CRM customer summary refresh after balance recalculation failed', [
                'customer_id' => $customerId,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    protected function refreshLockedCustomer(Customer $customer): Customer
    {
        $activeOrders = ProductOrder::query()
            ->where('customer_id', $customer->id)
            ->where('status', 'active');

        $receivableOrders = (clone $activeOrders)->directCustomerReceivable();

        $lastOrder = (clone $activeOrders)
            ->orderByDesc('sale_date')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first(['sale_date', 'created_at']);

        $currentDue = (float) ($customer->due ?? 0);

        $customer->forceFill([
            'last_order_at' => $lastOrder ? ($lastOrder->sale_date ?: $lastOrder->created_at) : null,
            'total_order_value' => (float) (clone $activeOrders)->sum('total'),
            'total_paid' => (float) ($customer->paid ?? 0),
            'current_due' => $currentDue,
            'overdue_amount' => (float) (clone $receivableOrders)
                ->where('due_amount', '>', 0)
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<', now()->toDateString())
                ->sum('due_amount'),
            'credit_status' => $this->resolveCreditStatus($customer, $currentDue),
        ])->save();

        return $customer->refresh();
    }

    protected function resolveCreditStatus(Customer $customer, float $currentDue): string
    {
        $creditLimit = (float) ($customer->credit_limit ?? 0);

        if ($currentDue <= 0) {
            return 'clear';
        }

        if ($creditLimit > 0 && $currentDue > $creditLimit) {
            return 'over_limit';
        }

        return 'due';
    }
}

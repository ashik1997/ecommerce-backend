<?php

namespace App\Http\Controllers\Report\Actions;

use App\Http\Controllers\Customer\Models\Customer;
use App\Http\Controllers\Customer\Models\CustomerOpeningBalance;
use App\Models\ProductOrder;

class DueCustomerReportAction extends ReportAction
{
    public function run(array $filters): array
    {
        $customersQuery = Customer::where('status', 'active');

        if (!empty($filters['customer_id'])) {
            $customersQuery->where('id', $filters['customer_id']);
        }

        $customers = $customersQuery->orderBy('name')->get();

        $data = [];
        foreach ($customers as $customer) {
            $orderDue = (float) ProductOrder::where('customer_id', $customer->id)
                ->where('due_amount', '>', 0)
                ->where('status', 'active')
                ->directCustomerReceivable()
                ->sum('due_amount');

            $orderCount = ProductOrder::where('customer_id', $customer->id)
                ->where('due_amount', '>', 0)
                ->where('status', 'active')
                ->directCustomerReceivable()
                ->count();

            $oldDue = (float) CustomerOpeningBalance::where('customer_id', $customer->id)
                ->where('entry_type', 'due')
                ->where('remaining_amount', '>', 0)
                ->where('status', 'active')
                ->sum('remaining_amount');

            $oldDueCount = CustomerOpeningBalance::where('customer_id', $customer->id)
                ->where('entry_type', 'due')
                ->where('remaining_amount', '>', 0)
                ->where('status', 'active')
                ->count();

            $totalDue = $orderDue + $oldDue;

            if ($totalDue <= 0) {
                continue;
            }

            $data[] = [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'phone' => $customer->phone,
                'order_due' => $orderDue,
                'old_due' => $oldDue,
                'total_due' => $totalDue,
                'order_count' => $orderCount,
                'old_due_count' => $oldDueCount,
            ];
        }

        $summary = [
            'total_customers' => count($data),
            'total_due' => array_sum(array_column($data, 'total_due')),
            'order_due' => array_sum(array_column($data, 'order_due')),
            'old_due' => array_sum(array_column($data, 'old_due')),
        ];

        return [
            'data' => $data,
            'summary' => $summary,
        ];
    }

    public function getTitle(): string
    {
        return 'Due Customer Report';
    }

    public function getCsvHeaders(): array
    {
        return ['Customer', 'Phone', 'Order Due', 'Old Due', 'Total Due', 'Order Count', 'Old Due Count'];
    }

    public function formatForCsv(array $data): array
    {
        $rows = [];
        foreach ($data as $item) {
            $rows[] = [
                $item['customer_name'] ?? '',
                $item['phone'] ?? '',
                $item['order_due'] ?? 0,
                $item['old_due'] ?? 0,
                $item['total_due'] ?? 0,
                $item['order_count'] ?? 0,
                $item['old_due_count'] ?? 0,
            ];
        }
        return $rows;
    }

    public function getFiltersConfig(): array
    {
        return [
            [
                'type' => 'select',
                'name' => 'customer_id',
                'label' => 'Customer',
                'required' => false,
            ],
        ];
    }
}

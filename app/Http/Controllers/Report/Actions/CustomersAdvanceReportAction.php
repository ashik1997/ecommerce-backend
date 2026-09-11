<?php

namespace App\Http\Controllers\Report\Actions;

use App\Http\Controllers\Account\Models\DbCustomerPayment;
use App\Http\Controllers\Customer\Models\Customer;
use App\Http\Controllers\Customer\Models\CustomerOpeningBalance;

class CustomersAdvanceReportAction extends ReportAction
{
    public function run(array $filters): array
    {
        $customers = Customer::where('status', 'active')->orderBy('name')->get();

        $data = [];
        foreach ($customers as $customer) {
            $openingAdvance = (float) CustomerOpeningBalance::where('customer_id', $customer->id)
                ->where('entry_type', 'advance')
                ->where('status', 'active')
                ->sum('remaining_amount');

            $advanceReceived = (float) DbCustomerPayment::where('customer_id', $customer->id)
                ->where('status', 'active')
                ->where('payment_type', 'advance')
                ->sum('payment');

            $advanceOut = abs((float) DbCustomerPayment::where('customer_id', $customer->id)
                ->where('status', 'active')
                ->whereIn('payment_type', ['adjustment', 'refund'])
                ->sum('payment'));

            $availableAdvance = max(0, $openingAdvance + $advanceReceived - $advanceOut);

            if ($availableAdvance > 0) {
                $data[] = [
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->name,
                    'phone' => $customer->phone,
                    'email' => $customer->email,
                    'opening_advance' => $openingAdvance,
                    'advance_received' => $advanceReceived,
                    'advance_used_or_refunded' => $advanceOut,
                    'available_advance' => $availableAdvance,
                ];
            }
        }

        $summary = [
            'total_customers' => count($data),
            'opening_advance' => array_sum(array_column($data, 'opening_advance')),
            'advance_received' => array_sum(array_column($data, 'advance_received')),
            'advance_used_or_refunded' => array_sum(array_column($data, 'advance_used_or_refunded')),
            'total_advance' => array_sum(array_column($data, 'available_advance')),
        ];

        return [
            'data' => $data,
            'summary' => $summary,
        ];
    }

    public function getTitle(): string
    {
        return 'Customers Advance Report';
    }

    public function getCsvHeaders(): array
    {
        return ['Customer', 'Phone', 'Email', 'Opening Advance', 'Advance Received', 'Used/Refunded', 'Available Advance'];
    }

    public function formatForCsv(array $data): array
    {
        $rows = [];
        foreach ($data as $item) {
            $rows[] = [
                $item['customer_name'] ?? '',
                $item['phone'] ?? '',
                $item['email'] ?? '',
                $item['opening_advance'] ?? 0,
                $item['advance_received'] ?? 0,
                $item['advance_used_or_refunded'] ?? 0,
                $item['available_advance'] ?? 0,
            ];
        }
        return $rows;
    }

    public function getFiltersConfig(): array
    {
        return [];
    }
}

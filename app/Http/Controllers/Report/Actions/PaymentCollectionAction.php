<?php

namespace App\Http\Controllers\Report\Actions;

use App\Http\Controllers\Account\Models\DbCustomerPayment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PaymentCollectionAction extends ReportAction
{
    public function run(array $filters): array
    {
        $query = DbCustomerPayment::query()
            ->from('db_customer_payments as payments')
            ->leftJoin('db_paymenttypes as payment_types', 'payments.payment_mode', '=', 'payment_types.id')
            ->where('payments.status', 'active')
            // Only real money received is collection. Advance adjustment/refund is not new collection.
            ->whereIn('payments.payment_type', ['received', 'advance']);

        if (!empty($filters['date_from'])) {
            $query->where('payments.payment_date', '>=', Carbon::parse($filters['date_from'])->toDateString());
        }
        if (!empty($filters['date_to'])) {
            $query->where('payments.payment_date', '<=', Carbon::parse($filters['date_to'])->toDateString());
        }
        if (!empty($filters['customer_id'])) {
            $query->where('payments.customer_id', $filters['customer_id']);
        }

        $data = $query
            ->select([
                'payments.payment_mode',
                DB::raw("COALESCE(NULLIF(MAX(payments.payment_mode_title), ''), payment_types.payment_type, 'N/A') as payment_mode_name"),
                DB::raw('COUNT(payments.id) as count'),
                DB::raw('COALESCE(SUM(payments.payment), 0) as total_amount'),
                DB::raw("COALESCE(SUM(CASE WHEN payments.payment_type = 'received' THEN payments.payment ELSE 0 END), 0) as due_collection"),
                DB::raw("COALESCE(SUM(CASE WHEN payments.payment_type = 'advance' THEN payments.payment ELSE 0 END), 0) as advance_collection"),
            ])
            ->groupBy('payments.payment_mode', 'payment_types.payment_type')
            ->orderByDesc('total_amount')
            ->get()
            ->map(function ($row) {
                return [
                    'payment_mode_id' => $row->payment_mode,
                    'payment_mode' => $row->payment_mode_name,
                    'count' => (int) $row->count,
                    'due_collection' => (float) $row->due_collection,
                    'advance_collection' => (float) $row->advance_collection,
                    'total_amount' => (float) $row->total_amount,
                ];
            })
            ->toArray();

        $summary = [
            'total_payments' => array_sum(array_column($data, 'count')),
            'due_collection' => array_sum(array_column($data, 'due_collection')),
            'advance_collection' => array_sum(array_column($data, 'advance_collection')),
            'total_collected' => array_sum(array_column($data, 'total_amount')),
        ];

        return [
            'data' => $data,
            'summary' => $summary,
        ];
    }

    public function getTitle(): string
    {
        return 'Payment Collection Report';
    }

    public function getCsvHeaders(): array
    {
        return ['Payment Mode', 'Count', 'Due Collection', 'Advance Collection', 'Total Amount'];
    }

    public function formatForCsv(array $data): array
    {
        $rows = [];
        foreach ($data as $item) {
            $rows[] = [
                $item['payment_mode'] ?? '',
                $item['count'] ?? 0,
                $item['due_collection'] ?? 0,
                $item['advance_collection'] ?? 0,
                $item['total_amount'] ?? 0,
            ];
        }
        return $rows;
    }

    public function getFiltersConfig(): array
    {
        return array_merge(parent::getFiltersConfig(), [
            [
                'type' => 'select',
                'name' => 'customer_id',
                'label' => 'Customer',
                'required' => false,
            ],
        ]);
    }
}

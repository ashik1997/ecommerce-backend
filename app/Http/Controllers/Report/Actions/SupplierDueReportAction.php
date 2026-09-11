<?php

namespace App\Http\Controllers\Report\Actions;

use App\Http\Controllers\Inventory\Models\ProductPurchaseOrder;
use App\Http\Controllers\Account\Models\SupplierOpeningBalance;
use App\Models\ProductPurchaseReturn;
use App\Http\Controllers\Account\Models\DbPurchasePayment;
use Illuminate\Support\Facades\DB;

class SupplierDueReportAction extends ReportAction
{
    public function run(array $filters): array
    {
        $suppliers = DB::table('product_suppliers')->where('status', 'active')->get();

        $data = [];
        foreach ($suppliers as $supplier) {
            // Only received purchases create supplier payable.
            $totalPurchase = (float) ProductPurchaseOrder::where('product_supplier_id', $supplier->id)
                ->where('status', 'active')
                ->where('order_status', 'received')
                ->sum('total');

            $totalReturn = (float) ProductPurchaseReturn::where('product_supplier_id', $supplier->id)
                ->where('status', 'active')
                ->sum('total');

            $totalPaid = (float) DbPurchasePayment::where('supplier_id', $supplier->id)
                ->where('status', 'active')
                ->sum('payment');

            // Old supplier due is tracked separately and reduced as payments are made.
            $oldDue = (float) SupplierOpeningBalance::where('supplier_id', $supplier->id)
                ->where('entry_type', 'due')
                ->where('remaining_amount', '>', 0)
                ->where('status', 'active')
                ->sum('remaining_amount');

            $due = ($totalPurchase - $totalReturn) - $totalPaid + $oldDue;

            if ($due > 0 || empty($filters['show_only_due'])) {
                $data[] = [
                    'supplier_id' => $supplier->id,
                    'supplier_name' => $supplier->name,
                    'total_purchase' => $totalPurchase,
                    'total_return' => $totalReturn,
                    'total_paid' => $totalPaid,
                    'old_due' => $oldDue,
                    'due' => $due,
                ];
            }
        }

        $summary = [
            'total_suppliers' => count($data),
            'total_purchase' => array_sum(array_column($data, 'total_purchase')),
            'total_return' => array_sum(array_column($data, 'total_return')),
            'total_paid' => array_sum(array_column($data, 'total_paid')),
            'old_due' => array_sum(array_column($data, 'old_due')),
            'total_due' => array_sum(array_column($data, 'due')),
        ];

        return [
            'data' => $data,
            'summary' => $summary,
        ];
    }

    public function getTitle(): string
    {
        return 'Supplier Due Report';
    }

    public function getCsvHeaders(): array
    {
        return ['Supplier', 'Received Purchase', 'Total Return', 'Total Paid', 'Old Due', 'Due'];
    }

    public function formatForCsv(array $data): array
    {
        $rows = [];
        foreach ($data as $item) {
            $rows[] = [
                $item['supplier_name'] ?? '',
                $item['total_purchase'] ?? 0,
                $item['total_return'] ?? 0,
                $item['total_paid'] ?? 0,
                $item['old_due'] ?? 0,
                $item['due'] ?? 0,
            ];
        }
        return $rows;
    }

    public function getFiltersConfig(): array
    {
        return [
            [
                'type' => 'checkbox',
                'name' => 'show_only_due',
                'label' => 'Show Only Suppliers with Due',
                'required' => false,
            ],
        ];
    }
}

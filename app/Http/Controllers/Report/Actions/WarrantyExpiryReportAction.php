<?php

namespace App\Http\Controllers\Report\Actions;

use App\Models\ProductPurchaseOrderProductUnit;
use Carbon\Carbon;

class WarrantyExpiryReportAction extends ReportAction
{
    public function run(array $filters): array
    {
        $basis = $filters['warranty_basis'] ?? 'effective';
        $period = $filters['period'] ?? 'next_30';
        $today = Carbon::today();

        $query = ProductPurchaseOrderProductUnit::with(['product', 'productPurchaseOrder.supplier', 'saleOrder.customer']);

        $query->where(function ($q) {
            $q->whereNotNull('supplier_warranty_end_date')
                ->orWhereNotNull('customer_warranty_end_date');
        });

        if (!empty($filters['supplier_id'])) {
            $query->whereHas('productPurchaseOrder', function ($q) use ($filters) {
                $q->where('product_supplier_id', $filters['supplier_id']);
            });
        }

        $units = $query->orderBy('supplier_warranty_end_date')->orderBy('customer_warranty_end_date')->get();

        $data = $units->map(function ($unit) use ($basis, $today) {
            $supplierEnd = $unit->supplier_warranty_end_date;
            $customerEnd = $unit->customer_warranty_end_date;
            $effectiveEnd = $basis === 'customer'
                ? $customerEnd
                : ($basis === 'supplier' ? $supplierEnd : ($customerEnd ?: $supplierEnd));

            if (!$effectiveEnd) {
                return null;
            }

            return [
                'supplier' => optional(optional($unit->productPurchaseOrder)->supplier)->name ?? 'N/A',
                'product' => optional($unit->product)->name ?? 'N/A',
                'code' => $unit->code,
                'serial' => $unit->serial_no,
                'imei' => collect([$unit->imei_1, $unit->imei_2])->filter()->implode(' / '),
                'status' => $unit->unit_status,
                'customer' => optional(optional($unit->saleOrder)->customer)->name ?? '',
                'supplier_warranty_end' => optional($supplierEnd)->format('Y-m-d'),
                'customer_warranty_end' => optional($customerEnd)->format('Y-m-d'),
                'effective_warranty_end' => $effectiveEnd->format('Y-m-d'),
                'days_left' => $today->diffInDays($effectiveEnd, false),
            ];
        })->filter()->values();

        $data = $this->filterByPeriod($data, $period);

        $supplierWise = $data->groupBy('supplier')->map->count();

        return [
            'data' => $data->values()->all(),
            'summary' => [
                'total_units' => $data->count(),
                'expired_units' => $data->where('days_left', '<', 0)->count(),
                'next_7_days' => $data->whereBetween('days_left', [0, 7])->count(),
                'next_15_days' => $data->whereBetween('days_left', [0, 15])->count(),
                'next_30_days' => $data->whereBetween('days_left', [0, 30])->count(),
                'supplier_wise' => $supplierWise->map(fn($count, $supplier) => "{$supplier}: {$count}")->implode(', '),
            ],
        ];
    }

    private function filterByPeriod($data, string $period)
    {
        return match ($period) {
            'expired' => $data->where('days_left', '<', 0),
            'next_7' => $data->whereBetween('days_left', [0, 7]),
            'next_15' => $data->whereBetween('days_left', [0, 15]),
            'next_30' => $data->whereBetween('days_left', [0, 30]),
            default => $data,
        };
    }

    public function getTitle(): string
    {
        return 'Warranty Expiry Report';
    }

    public function getCsvHeaders(): array
    {
        return ['Supplier', 'Product', 'Code', 'Serial', 'IMEI', 'Status', 'Customer', 'Supplier Warranty End', 'Customer Warranty End', 'Effective Warranty End', 'Days Left'];
    }

    public function formatForCsv(array $data): array
    {
        return array_map(fn($item) => [
            $item['supplier'] ?? '',
            $item['product'] ?? '',
            $item['code'] ?? '',
            $item['serial'] ?? '',
            $item['imei'] ?? '',
            $item['status'] ?? '',
            $item['customer'] ?? '',
            $item['supplier_warranty_end'] ?? '',
            $item['customer_warranty_end'] ?? '',
            $item['effective_warranty_end'] ?? '',
            $item['days_left'] ?? '',
        ], $data);
    }

    public function getFiltersConfig(): array
    {
        return [
            [
                'type' => 'select',
                'name' => 'period',
                'label' => 'Expiry Period',
                'default' => 'next_30',
                'options' => [
                    ['value' => 'next_7', 'label' => 'Next 7 days'],
                    ['value' => 'next_15', 'label' => 'Next 15 days'],
                    ['value' => 'next_30', 'label' => 'Next 30 days'],
                    ['value' => 'expired', 'label' => 'Expired'],
                    ['value' => 'all', 'label' => 'All warranties'],
                ],
            ],
            [
                'type' => 'select',
                'name' => 'warranty_basis',
                'label' => 'Warranty Type',
                'default' => 'effective',
                'options' => [
                    ['value' => 'effective', 'label' => 'Customer first, otherwise supplier'],
                    ['value' => 'customer', 'label' => 'Customer warranty'],
                    ['value' => 'supplier', 'label' => 'Supplier warranty'],
                ],
            ],
        ];
    }
}

<?php

namespace App\Http\Controllers\Delivery;

use App\Http\Controllers\Controller;
use App\Models\Delivery\DeliveryEmployee;
use App\Models\Delivery\DeliveryProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DeliveryReportController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->filters($request);

        return view('backend.delivery_management.reports.index', [
            'filters' => $filters,
            'statuses' => $this->statuses(),
            'statusSummary' => $this->statusSummary($filters),
            'providerSummary' => $this->providerSummary($filters),
            'employeeSummary' => $this->employeeSummary($filters),
            'financeSummary' => $this->financeSummary($filters),
            'agingSummary' => $this->agingSummary($filters),
            'delayedShipments' => $this->delayedShipments($filters),
            'providers' => Schema::hasTable('delivery_providers') ? DeliveryProvider::orderBy('name')->get(['id', 'name']) : collect(),
            'employees' => Schema::hasTable('delivery_employees') ? DeliveryEmployee::orderBy('name')->get(['id', 'name']) : collect(),
        ]);
    }

    public function export(Request $request)
    {
        $filters = $this->filters($request);
        $reportType = $request->input('report_type', 'shipments');
        $filename = 'delivery-' . str_replace('_', '-', $reportType) . '-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($filters, $reportType) {
            $handle = fopen('php://output', 'w');

            if ($reportType === 'status') {
                $this->writeStatusCsv($handle, $filters);
            } elseif ($reportType === 'provider') {
                $this->writeProviderCsv($handle, $filters);
            } elseif ($reportType === 'employee') {
                $this->writeEmployeeCsv($handle, $filters);
            } elseif ($reportType === 'aging') {
                $this->writeAgingCsv($handle, $filters);
            } else {
                $this->writeShipmentCsv($handle, $filters);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function filters(Request $request): array
    {
        return [
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'provider_id' => $request->input('provider_id'),
            'delivery_employee_id' => $request->input('delivery_employee_id'),
            'current_status' => $request->input('current_status'),
        ];
    }

    private function statusSummary(array $filters)
    {
        if (!Schema::hasTable('delivery_shipments')) {
            return collect();
        }

        return $this->shipmentQuery($filters)
            ->select(
                'current_status',
                DB::raw('COUNT(*) as total_shipments'),
                DB::raw('SUM(cod_amount) as cod_amount'),
                DB::raw('SUM(customer_delivery_charge) as customer_delivery_charge'),
                DB::raw('SUM(provider_cost) as provider_cost')
            )
            ->groupBy('current_status')
            ->orderByDesc('total_shipments')
            ->get();
    }

    private function writeStatusCsv($handle, array $filters): void
    {
        fputcsv($handle, ['Status', 'Total Shipments', 'COD Amount', 'Customer Delivery Charge', 'Provider Cost']);

        foreach ($this->statusSummary($filters) as $row) {
            fputcsv($handle, [
                ucwords(str_replace('_', ' ', $row->current_status ?: 'N/A')),
                $row->total_shipments,
                $row->cod_amount,
                $row->customer_delivery_charge,
                $row->provider_cost,
            ]);
        }
    }

    private function providerSummary(array $filters)
    {
        if (!Schema::hasTable('delivery_shipments')) {
            return collect();
        }

        return $this->shipmentQuery($filters)
            ->leftJoin('delivery_providers', 'delivery_providers.id', '=', 'delivery_shipments.provider_id')
            ->select(
                DB::raw('COALESCE(delivery_providers.name, "Unassigned") as provider_name'),
                DB::raw('COUNT(delivery_shipments.id) as total_shipments'),
                DB::raw("SUM(CASE WHEN delivery_shipments.current_status = 'delivered' THEN 1 ELSE 0 END) as delivered_shipments"),
                DB::raw("SUM(CASE WHEN delivery_shipments.current_status IN ('returned','returned_to_store') THEN 1 ELSE 0 END) as returned_shipments"),
                DB::raw("SUM(CASE WHEN delivery_shipments.current_status = 'delivery_failed' THEN 1 ELSE 0 END) as failed_shipments"),
                DB::raw('SUM(delivery_shipments.customer_delivery_charge) as customer_delivery_charge'),
                DB::raw('SUM(delivery_shipments.provider_cost) as provider_cost')
            )
            ->groupBy('delivery_shipments.provider_id', 'delivery_providers.name')
            ->orderByDesc('total_shipments')
            ->get();
    }

    private function writeProviderCsv($handle, array $filters): void
    {
        fputcsv($handle, ['Provider', 'Total Shipments', 'Delivered', 'Returned', 'Failed', 'Customer Delivery Charge', 'Provider Cost', 'Margin']);

        foreach ($this->providerSummary($filters) as $row) {
            fputcsv($handle, [
                $row->provider_name,
                $row->total_shipments,
                $row->delivered_shipments,
                $row->returned_shipments,
                $row->failed_shipments,
                $row->customer_delivery_charge,
                $row->provider_cost,
                (float) $row->customer_delivery_charge - (float) $row->provider_cost,
            ]);
        }
    }

    private function employeeSummary(array $filters)
    {
        if (!Schema::hasTable('delivery_shipments')) {
            return collect();
        }

        return $this->shipmentQuery($filters)
            ->leftJoin('delivery_employees', 'delivery_employees.id', '=', 'delivery_shipments.delivery_employee_id')
            ->leftJoin('delivery_cod_collections', 'delivery_cod_collections.delivery_shipment_id', '=', 'delivery_shipments.id')
            ->select(
                DB::raw('COALESCE(delivery_employees.name, "Unassigned") as employee_name'),
                DB::raw('COUNT(DISTINCT delivery_shipments.id) as assigned_shipments'),
                DB::raw("COUNT(DISTINCT CASE WHEN delivery_shipments.current_status = 'delivered' THEN delivery_shipments.id END) as delivered_shipments"),
                DB::raw('SUM(COALESCE(delivery_cod_collections.collected_amount, 0)) as collected_amount'),
                DB::raw('SUM(COALESCE(delivery_cod_collections.submitted_amount, 0)) as submitted_amount'),
                DB::raw('SUM(GREATEST(COALESCE(delivery_cod_collections.expected_amount, 0) - COALESCE(delivery_cod_collections.submitted_amount, 0), 0)) as pending_amount')
            )
            ->groupBy('delivery_shipments.delivery_employee_id', 'delivery_employees.name')
            ->orderByDesc('assigned_shipments')
            ->get();
    }

    private function writeEmployeeCsv($handle, array $filters): void
    {
        fputcsv($handle, ['Employee', 'Assigned Shipments', 'Delivered Shipments', 'Collected Amount', 'Submitted Amount', 'Pending Amount']);

        foreach ($this->employeeSummary($filters) as $row) {
            fputcsv($handle, [
                $row->employee_name,
                $row->assigned_shipments,
                $row->delivered_shipments,
                $row->collected_amount,
                $row->submitted_amount,
                $row->pending_amount,
            ]);
        }
    }

    private function financeSummary(array $filters): array
    {
        if (!Schema::hasTable('delivery_shipments')) {
            return [
                'cod_amount' => 0,
                'customer_delivery_charge' => 0,
                'provider_cost' => 0,
                'delivery_margin' => 0,
                'settlement_pending' => 0,
                'total_shipments' => 0,
                'delivered_shipments' => 0,
            ];
        }

        $shipments = $this->shipmentQuery($filters)
            ->selectRaw("COUNT(*) as total_shipments, SUM(CASE WHEN current_status = 'delivered' THEN 1 ELSE 0 END) as delivered_shipments, SUM(cod_amount) as cod_amount, SUM(customer_delivery_charge) as customer_delivery_charge, SUM(provider_cost) as provider_cost")
            ->first();

        $settlementPending = Schema::hasTable('delivery_settlements')
            ? (float) DB::table('delivery_settlements')->whereIn('status', ['draft', 'approved'])->sum('net_amount')
            : 0;

        $customerCharge = (float) ($shipments->customer_delivery_charge ?? 0);
        $providerCost = (float) ($shipments->provider_cost ?? 0);

        return [
            'cod_amount' => (float) ($shipments->cod_amount ?? 0),
            'customer_delivery_charge' => $customerCharge,
            'provider_cost' => $providerCost,
            'delivery_margin' => $customerCharge - $providerCost,
            'settlement_pending' => $settlementPending,
            'total_shipments' => (int) ($shipments->total_shipments ?? 0),
            'delivered_shipments' => (int) ($shipments->delivered_shipments ?? 0),
        ];
    }

    private function agingSummary(array $filters)
    {
        if (!Schema::hasTable('delivery_shipments')) {
            return collect();
        }

        return $this->shipmentQuery($filters)
            ->whereNotIn('delivery_shipments.current_status', $this->terminalStatuses())
            ->selectRaw("
                CASE
                    WHEN DATEDIFF(CURDATE(), DATE(delivery_shipments.created_at)) <= 1 THEN '0-1 Days'
                    WHEN DATEDIFF(CURDATE(), DATE(delivery_shipments.created_at)) BETWEEN 2 AND 3 THEN '2-3 Days'
                    WHEN DATEDIFF(CURDATE(), DATE(delivery_shipments.created_at)) BETWEEN 4 AND 7 THEN '4-7 Days'
                    ELSE '8+ Days'
                END as aging_bucket,
                COUNT(*) as total_shipments,
                SUM(cod_amount) as cod_amount
            ")
            ->groupBy('aging_bucket')
            ->orderByRaw("FIELD(aging_bucket, '0-1 Days', '2-3 Days', '4-7 Days', '8+ Days')")
            ->get();
    }

    private function writeAgingCsv($handle, array $filters): void
    {
        fputcsv($handle, ['Aging Bucket', 'Total Shipments', 'COD Amount']);

        foreach ($this->agingSummary($filters) as $row) {
            fputcsv($handle, [
                $row->aging_bucket,
                $row->total_shipments,
                $row->cod_amount,
            ]);
        }
    }

    private function writeShipmentCsv($handle, array $filters): void
    {
        fputcsv($handle, [
            'Shipment Code',
            'Order ID',
            'Provider',
            'Employee',
            'Recipient',
            'Phone',
            'Status',
            'COD Amount',
            'Customer Delivery Charge',
            'Provider Cost',
            'Created At',
            'Pending Days',
        ]);

        if (!Schema::hasTable('delivery_shipments')) {
            return;
        }

        $this->shipmentQuery($filters)
            ->leftJoin('delivery_providers', 'delivery_providers.id', '=', 'delivery_shipments.provider_id')
            ->leftJoin('delivery_employees', 'delivery_employees.id', '=', 'delivery_shipments.delivery_employee_id')
            ->select(
                'delivery_shipments.shipment_code',
                'delivery_shipments.product_order_id',
                'delivery_shipments.recipient_name',
                'delivery_shipments.recipient_phone',
                'delivery_shipments.current_status',
                'delivery_shipments.cod_amount',
                'delivery_shipments.customer_delivery_charge',
                'delivery_shipments.provider_cost',
                'delivery_shipments.created_at',
                DB::raw('DATEDIFF(CURDATE(), DATE(delivery_shipments.created_at)) as pending_days'),
                DB::raw('COALESCE(delivery_providers.name, "Unassigned") as provider_name'),
                DB::raw('COALESCE(delivery_employees.name, "Unassigned") as employee_name')
            )
            ->orderByDesc('delivery_shipments.id')
            ->chunk(500, function ($shipments) use ($handle) {
                foreach ($shipments as $shipment) {
                    fputcsv($handle, [
                        $shipment->shipment_code,
                        $shipment->product_order_id,
                        $shipment->provider_name,
                        $shipment->employee_name,
                        $shipment->recipient_name,
                        $shipment->recipient_phone,
                        $shipment->current_status,
                        $shipment->cod_amount,
                        $shipment->customer_delivery_charge,
                        $shipment->provider_cost,
                        $shipment->created_at,
                        $shipment->pending_days,
                    ]);
                }
            });
    }

    private function delayedShipments(array $filters)
    {
        if (!Schema::hasTable('delivery_shipments')) {
            return collect();
        }

        return $this->shipmentQuery($filters)
            ->leftJoin('delivery_providers', 'delivery_providers.id', '=', 'delivery_shipments.provider_id')
            ->leftJoin('delivery_employees', 'delivery_employees.id', '=', 'delivery_shipments.delivery_employee_id')
            ->whereNotIn('delivery_shipments.current_status', $this->terminalStatuses())
            ->whereRaw('DATEDIFF(CURDATE(), DATE(delivery_shipments.created_at)) >= 3')
            ->select(
                'delivery_shipments.id',
                'delivery_shipments.shipment_code',
                'delivery_shipments.recipient_name',
                'delivery_shipments.recipient_phone',
                'delivery_shipments.current_status',
                'delivery_shipments.cod_amount',
                'delivery_shipments.created_at',
                DB::raw('DATEDIFF(CURDATE(), DATE(delivery_shipments.created_at)) as pending_days'),
                DB::raw('COALESCE(delivery_providers.name, "Unassigned") as provider_name'),
                DB::raw('COALESCE(delivery_employees.name, "Unassigned") as employee_name')
            )
            ->orderByDesc('pending_days')
            ->orderBy('delivery_shipments.created_at')
            ->limit(25)
            ->get();
    }

    private function shipmentQuery(array $filters)
    {
        $query = DB::table('delivery_shipments');

        if (!empty($filters['date_from'])) {
            $query->whereDate('delivery_shipments.created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('delivery_shipments.created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['provider_id'])) {
            $query->where('delivery_shipments.provider_id', $filters['provider_id']);
        }

        if (!empty($filters['delivery_employee_id'])) {
            $query->where('delivery_shipments.delivery_employee_id', $filters['delivery_employee_id']);
        }

        if (!empty($filters['current_status'])) {
            $query->where('delivery_shipments.current_status', $filters['current_status']);
        }

        return $query;
    }

    private function terminalStatuses(): array
    {
        return ['delivered', 'returned', 'returned_to_store', 'cancelled', 'closed'];
    }

    private function statuses()
    {
        if (Schema::hasTable('delivery_statuses')) {
            return DB::table('delivery_statuses')
                ->where('status', 'active')
                ->orderBy('sort_order')
                ->pluck('label', 'slug');
        }

        if (Schema::hasTable('delivery_shipments')) {
            return DB::table('delivery_shipments')
                ->whereNotNull('current_status')
                ->orderBy('current_status')
                ->pluck('current_status', 'current_status')
                ->map(function ($status) {
                    return ucwords(str_replace('_', ' ', $status));
                });
        }

        return collect();
    }
}

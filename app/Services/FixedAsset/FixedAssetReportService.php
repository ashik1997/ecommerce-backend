<?php

namespace App\Services\FixedAsset;

use App\Models\FixedAsset\FixedAsset;
use App\Models\FixedAsset\FixedAssetAssignment;
use App\Models\FixedAsset\FixedAssetDepreciationEntry;
use App\Models\FixedAsset\FixedAssetDisposal;
use App\Models\FixedAsset\FixedAssetMaintenanceJob;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FixedAssetReportService
{
    public function filters(Request $request): array
    {
        return [
            'warehouse_id' => $request->filled('warehouse_id') ? $request->warehouse_id : null,
            'category_id' => $request->filled('category_id') ? $request->category_id : null,
            'status' => $request->filled('status') ? $request->status : null,
            'date_from' => $request->filled('date_from') ? $request->date_from : null,
            'date_to' => $request->filled('date_to') ? $request->date_to : null,
            'period' => $request->filled('period') ? $request->period : null,
            'report' => $request->get('report', 'warehouse'),
        ];
    }

    public function dashboard(array $filters): array
    {
        return [
            'cards' => $this->summaryCards($filters),
            'warehouseSummary' => $this->warehouseSummary($filters),
            'categorySummary' => $this->categorySummary($filters),
            'assignedAssets' => $this->assignedAssets($filters),
            'maintenanceSummary' => $this->maintenanceSummary($filters),
            'depreciationSummary' => $this->depreciationSummary($filters),
            'disposals' => $this->disposals($filters),
        ];
    }

    public function summaryCards(array $filters): array
    {
        $assetQuery = $this->assetBaseQuery($filters);

        return [
            'total_assets' => (clone $assetQuery)->count('fa_assets.id'),
            'capitalized_cost' => (clone $assetQuery)->sum('fa_assets.capitalized_cost'),
            'book_value' => (clone $assetQuery)->sum('fa_assets.carrying_amount'),
            'active_assignments' => FixedAssetAssignment::query()
                ->where('fa_assignments.status', 'active')
                ->when($filters['warehouse_id'] ?? null, fn ($q, $v) => $q->where('fa_assignments.warehouse_id', $v))
                ->count(),
            'maintenance_cost' => $this->maintenanceBaseQuery($filters)->sum('fa_maintenance_jobs.total_cost'),
            'depreciation_amount' => $this->depreciationFilteredQuery($filters)->sum('fa_depreciation_entries.depreciation_amount'),
            'disposal_gain_loss' => $this->disposalBaseQuery($filters)->sum('fa_disposals.gain_loss_amount'),
        ];
    }

    public function warehouseSummary(array $filters)
    {
        return $this->assetBaseQuery($filters)
            ->leftJoin('product_warehouses', 'fa_assets.warehouse_id', '=', 'product_warehouses.id')
            ->select(
                'fa_assets.warehouse_id',
                'product_warehouses.title as warehouse_name',
                DB::raw('COUNT(fa_assets.id) as total_assets'),
                DB::raw('COALESCE(SUM(fa_assets.capitalized_cost), 0) as capitalized_cost'),
                DB::raw('COALESCE(SUM(fa_assets.accumulated_depreciation), 0) as accumulated_depreciation'),
                DB::raw('COALESCE(SUM(fa_assets.carrying_amount), 0) as book_value'),
                DB::raw("SUM(CASE WHEN fa_assets.operational_status = 'assigned' THEN 1 ELSE 0 END) as assigned_assets"),
                DB::raw("SUM(CASE WHEN fa_assets.operational_status = 'available' THEN 1 ELSE 0 END) as available_assets"),
                DB::raw("SUM(CASE WHEN fa_assets.operational_status = 'under_maintenance' THEN 1 ELSE 0 END) as maintenance_assets")
            )
            ->groupBy('fa_assets.warehouse_id', 'product_warehouses.title')
            ->orderBy('product_warehouses.title')
            ->get();
    }

    public function categorySummary(array $filters)
    {
        return $this->assetBaseQuery($filters)
            ->leftJoin('fa_categories', 'fa_assets.category_id', '=', 'fa_categories.id')
            ->select(
                'fa_assets.category_id',
                'fa_categories.name as category_name',
                DB::raw('COUNT(fa_assets.id) as total_assets'),
                DB::raw('COALESCE(SUM(fa_assets.capitalized_cost), 0) as capitalized_cost'),
                DB::raw('COALESCE(SUM(fa_assets.accumulated_depreciation), 0) as accumulated_depreciation'),
                DB::raw('COALESCE(SUM(fa_assets.carrying_amount), 0) as book_value')
            )
            ->groupBy('fa_assets.category_id', 'fa_categories.name')
            ->orderBy('fa_categories.name')
            ->get();
    }

    public function assignedAssets(array $filters)
    {
        return FixedAssetAssignment::with(['asset.category', 'asset.warehouse', 'employee', 'department'])
            ->where('fa_assignments.status', 'active')
            ->when($filters['warehouse_id'] ?? null, fn ($q, $v) => $q->where('fa_assignments.warehouse_id', $v))
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('fa_assignments.assigned_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('fa_assignments.assigned_at', '<=', $v))
            ->latest('fa_assignments.created_at')
            ->limit(300)
            ->get();
    }

    public function maintenanceSummary(array $filters)
    {
        return $this->maintenanceBaseQuery($filters)
            ->leftJoin('product_warehouses', 'fa_maintenance_jobs.warehouse_id', '=', 'product_warehouses.id')
            ->select(
                'fa_maintenance_jobs.warehouse_id',
                'product_warehouses.title as warehouse_name',
                DB::raw('COUNT(fa_maintenance_jobs.id) as total_jobs'),
                DB::raw('COALESCE(SUM(fa_maintenance_jobs.total_cost), 0) as total_cost'),
                DB::raw("SUM(CASE WHEN fa_maintenance_jobs.status = 'completed' THEN 1 ELSE 0 END) as completed_jobs"),
                DB::raw("SUM(CASE WHEN fa_maintenance_jobs.status IN ('open','in_progress') THEN 1 ELSE 0 END) as open_jobs")
            )
            ->groupBy('fa_maintenance_jobs.warehouse_id', 'product_warehouses.title')
            ->orderBy('product_warehouses.title')
            ->get();
    }

    public function depreciationSummary(array $filters)
    {
        return $this->depreciationFilteredQuery($filters)
            ->leftJoin('product_warehouses', 'fa_assets.warehouse_id', '=', 'product_warehouses.id')
            ->select(
                'fa_depreciation_entries.period',
                'fa_assets.warehouse_id',
                'product_warehouses.title as warehouse_name',
                DB::raw('COUNT(fa_depreciation_entries.id) as total_entries'),
                DB::raw('COALESCE(SUM(fa_depreciation_entries.opening_book_value), 0) as opening_book_value'),
                DB::raw('COALESCE(SUM(fa_depreciation_entries.depreciation_amount), 0) as depreciation_amount'),
                DB::raw('COALESCE(SUM(fa_depreciation_entries.closing_book_value), 0) as closing_book_value')
            )
            ->groupBy('fa_depreciation_entries.period', 'fa_assets.warehouse_id', 'product_warehouses.title')
            ->orderByDesc('fa_depreciation_entries.period')
            ->limit(100)
            ->get();
    }

    public function disposals(array $filters)
    {
        return FixedAssetDisposal::with(['asset.category', 'asset.warehouse', 'warehouse', 'reason'])
            ->when($filters['warehouse_id'] ?? null, fn ($q, $v) => $q->where('fa_disposals.warehouse_id', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('fa_disposals.status', $v))
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('fa_disposals.disposal_date', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('fa_disposals.disposal_date', '<=', $v))
            ->latest('fa_disposals.created_at')
            ->limit(300)
            ->get();
    }

    public function exportRows(array $filters): array
    {
        $report = $filters['report'] ?? 'warehouse';

        if ($report === 'category') {
            return [
                ['Category', 'Total Assets', 'Capitalized Cost', 'Accumulated Depreciation', 'Book Value'],
                $this->categorySummary($filters)->map(fn ($row) => [
                    $row->category_name ?: 'N/A', $row->total_assets, $row->capitalized_cost, $row->accumulated_depreciation, $row->book_value,
                ])->all(),
            ];
        }

        if ($report === 'assigned') {
            return [
                ['Assignment No', 'Asset Code', 'Asset Name', 'Warehouse', 'Assigned Type', 'Employee', 'Department', 'Assigned At'],
                $this->assignedAssets($filters)->map(fn ($row) => [
                    $row->assignment_no,
                    optional($row->asset)->asset_code,
                    optional($row->asset)->asset_name,
                    optional(optional($row->asset)->warehouse)->title,
                    $row->assigned_to_type,
                    optional($row->employee)->name ?? optional($row->employee)->employee_name ?? $row->employee_id,
                    optional($row->department)->name ?? optional($row->department)->department_name ?? $row->department_id,
                    optional($row->assigned_at)->format('Y-m-d') ?: $row->assigned_at,
                ])->all(),
            ];
        }

        if ($report === 'maintenance') {
            return [
                ['Warehouse', 'Total Jobs', 'Open Jobs', 'Completed Jobs', 'Total Cost'],
                $this->maintenanceSummary($filters)->map(fn ($row) => [
                    $row->warehouse_name ?: 'N/A', $row->total_jobs, $row->open_jobs, $row->completed_jobs, $row->total_cost,
                ])->all(),
            ];
        }

        if ($report === 'depreciation') {
            return [
                ['Period', 'Warehouse', 'Entries', 'Opening Book Value', 'Depreciation Amount', 'Closing Book Value'],
                $this->depreciationSummary($filters)->map(fn ($row) => [
                    $row->period, $row->warehouse_name ?: 'N/A', $row->total_entries, $row->opening_book_value, $row->depreciation_amount, $row->closing_book_value,
                ])->all(),
            ];
        }

        if ($report === 'disposal') {
            return [
                ['Disposal No', 'Asset Code', 'Asset Name', 'Warehouse', 'Disposal Date', 'Carrying Amount', 'Proceeds', 'Gain/Loss', 'Status'],
                $this->disposals($filters)->map(fn ($row) => [
                    $row->disposal_no,
                    optional($row->asset)->asset_code,
                    optional($row->asset)->asset_name,
                    optional($row->warehouse)->title ?? optional(optional($row->asset)->warehouse)->title,
                    optional($row->disposal_date)->format('Y-m-d') ?: $row->disposal_date,
                    $row->carrying_amount,
                    $row->proceeds_amount,
                    $row->gain_loss_amount,
                    $row->status,
                ])->all(),
            ];
        }

        return [
            ['Warehouse', 'Total Assets', 'Capitalized Cost', 'Accumulated Depreciation', 'Book Value', 'Assigned', 'Available', 'Under Maintenance'],
            $this->warehouseSummary($filters)->map(fn ($row) => [
                $row->warehouse_name ?: 'N/A', $row->total_assets, $row->capitalized_cost, $row->accumulated_depreciation, $row->book_value, $row->assigned_assets, $row->available_assets, $row->maintenance_assets,
            ])->all(),
        ];
    }

    private function assetBaseQuery(array $filters): Builder
    {
        return FixedAsset::query()
            ->when($filters['warehouse_id'] ?? null, fn ($q, $v) => $q->where('fa_assets.warehouse_id', $v))
            ->when($filters['category_id'] ?? null, fn ($q, $v) => $q->where('fa_assets.category_id', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('fa_assets.operational_status', $v))
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('fa_assets.purchase_date', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('fa_assets.purchase_date', '<=', $v));
    }

    private function maintenanceBaseQuery(array $filters): Builder
    {
        return FixedAssetMaintenanceJob::query()
            ->when($filters['warehouse_id'] ?? null, fn ($q, $v) => $q->where('fa_maintenance_jobs.warehouse_id', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('fa_maintenance_jobs.status', $v))
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('fa_maintenance_jobs.reported_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('fa_maintenance_jobs.reported_at', '<=', $v));
    }

    private function depreciationBaseQuery(array $filters): Builder
    {
        return FixedAssetDepreciationEntry::query()
            ->when($filters['period'] ?? null, fn ($q, $v) => $q->where('fa_depreciation_entries.period', $v));
    }

    private function depreciationFilteredQuery(array $filters): Builder
    {
        return $this->depreciationBaseQuery($filters)
            ->join('fa_assets', 'fa_depreciation_entries.asset_id', '=', 'fa_assets.id')
            ->when($filters['warehouse_id'] ?? null, fn ($q, $v) => $q->where('fa_assets.warehouse_id', $v))
            ->when($filters['category_id'] ?? null, fn ($q, $v) => $q->where('fa_assets.category_id', $v))
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('fa_depreciation_entries.created_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('fa_depreciation_entries.created_at', '<=', $v));
    }

    private function disposalBaseQuery(array $filters): Builder
    {
        return FixedAssetDisposal::query()
            ->when($filters['warehouse_id'] ?? null, fn ($q, $v) => $q->where('fa_disposals.warehouse_id', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('fa_disposals.status', $v))
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('fa_disposals.disposal_date', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('fa_disposals.disposal_date', '<=', $v));
    }
}

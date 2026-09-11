@extends('backend.master')
@section('page_title','Fixed Asset Reports')
@section('page_heading','Fixed Asset Reports')
@section('header_css')@include('backend.fixed_asset._style')@endsection
@section('content')
@php
    $money = fn($v) => number_format((float) ($v ?? 0), 2);
    $reportOptions = [
        'warehouse' => 'Warehouse Summary',
        'category' => 'Category Summary',
        'assigned' => 'Assigned Assets',
        'maintenance' => 'Maintenance Cost',
        'depreciation' => 'Depreciation',
        'disposal' => 'Disposal',
    ];
@endphp
<div class="fa-page-wrap">
    @include('backend.fixed_asset.partials.nav')

    @include('backend.fixed_asset.partials.page-header', [
        'title' => 'Fixed Asset Reports',
        'subtitle' => 'Warehouse-wise asset value, assignment, depreciation, maintenance and disposal overview.',
        'actions' => '<a class="btn btn-fa-soft" href="'.route('fixed-assets.reports.export', request()->query()).'">Export Current Report CSV</a>'
    ])

    <div class="fa-filter-card">
        <form method="get" class="row align-items-end">
            <div class="col-md-2">
                <label>Report</label>
                <select name="report" class="form-control">
                    @foreach($reportOptions as $key => $label)
                        <option value="{{ $key }}" {{ ($filters['report'] ?? 'warehouse') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label>Warehouse / Branch</label>
                <select name="warehouse_id" class="form-control">
                    <option value="">All</option>
                    @foreach($warehouses as $w)
                        <option value="{{ $w->id }}" {{ ($filters['warehouse_id'] ?? null) == $w->id ? 'selected' : '' }}>{{ $w->title }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label>Category</label>
                <select name="category_id" class="form-control">
                    <option value="">All</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ ($filters['category_id'] ?? null) == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label>Status</label>
                <input type="text" name="status" class="form-control" placeholder="optional" value="{{ $filters['status'] ?? '' }}">
            </div>
            <div class="col-md-1">
                <label>Period</label>
                <input type="month" name="period" class="form-control" value="{{ $filters['period'] ?? '' }}">
            </div>
            <div class="col-md-1">
                <label>From</label>
                <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
            </div>
            <div class="col-md-1">
                <label>To</label>
                <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
            </div>
            <div class="col-md-1">
                <button class="btn btn-fa-primary btn-block">Filter</button>
            </div>
        </form>
    </div>

    <div class="row">
        <div class="col-md-2"><div class="fa-kpi"><div class="label">Assets</div><div class="value">{{ number_format($cards['total_assets'] ?? 0) }}</div></div></div>
        <div class="col-md-2"><div class="fa-kpi"><div class="label">Capitalized</div><div class="value">{{ $money($cards['capitalized_cost'] ?? 0) }}</div></div></div>
        <div class="col-md-2"><div class="fa-kpi"><div class="label">Book Value</div><div class="value">{{ $money($cards['book_value'] ?? 0) }}</div></div></div>
        <div class="col-md-2"><div class="fa-kpi"><div class="label">Assigned</div><div class="value">{{ number_format($cards['active_assignments'] ?? 0) }}</div></div></div>
        <div class="col-md-2"><div class="fa-kpi"><div class="label">Maint. Cost</div><div class="value">{{ $money($cards['maintenance_cost'] ?? 0) }}</div></div></div>
        <div class="col-md-2"><div class="fa-kpi"><div class="label">Depreciation</div><div class="value">{{ $money($cards['depreciation_amount'] ?? 0) }}</div></div></div>
    </div>

    @if(($filters['report'] ?? 'warehouse') === 'warehouse')
        <div class="fa-card"><div class="fa-card-header"><h4 class="fa-card-title">Warehouse-wise Asset Summary</h4></div><div class="fa-card-body"><div class="fa-table table-responsive"><table class="table table-bordered"><thead><tr><th>Warehouse</th><th>Total</th><th class="fa-money">Capitalized</th><th class="fa-money">Accum. Dep.</th><th class="fa-money">Book Value</th><th>Assigned</th><th>Available</th><th>Maintenance</th></tr></thead><tbody>@forelse($warehouseSummary as $row)<tr><td>{{ $row->warehouse_name ?? 'N/A' }}</td><td>{{ $row->total_assets }}</td><td class="fa-money">{{ $money($row->capitalized_cost) }}</td><td class="fa-money">{{ $money($row->accumulated_depreciation) }}</td><td class="fa-money">{{ $money($row->book_value) }}</td><td>{{ $row->assigned_assets }}</td><td>{{ $row->available_assets }}</td><td>{{ $row->maintenance_assets }}</td></tr>@empty<tr><td colspan="8">@include('backend.fixed_asset.partials.empty-state', ['title' => 'No warehouse report data found'])</td></tr>@endforelse</tbody></table></div></div></div>
    @endif

    @if(($filters['report'] ?? '') === 'category')
        <div class="fa-card"><div class="fa-card-header"><h4 class="fa-card-title">Category-wise Asset Summary</h4></div><div class="fa-card-body"><div class="fa-table table-responsive"><table class="table table-bordered"><thead><tr><th>Category</th><th>Total</th><th class="fa-money">Capitalized</th><th class="fa-money">Accum. Dep.</th><th class="fa-money">Book Value</th></tr></thead><tbody>@forelse($categorySummary as $row)<tr><td>{{ $row->category_name ?? 'N/A' }}</td><td>{{ $row->total_assets }}</td><td class="fa-money">{{ $money($row->capitalized_cost) }}</td><td class="fa-money">{{ $money($row->accumulated_depreciation) }}</td><td class="fa-money">{{ $money($row->book_value) }}</td></tr>@empty<tr><td colspan="5">@include('backend.fixed_asset.partials.empty-state', ['title' => 'No category report data found'])</td></tr>@endforelse</tbody></table></div></div></div>
    @endif

    @if(($filters['report'] ?? '') === 'assigned')
        <div class="fa-card"><div class="fa-card-header"><h4 class="fa-card-title">Employee / Department Assigned Assets</h4></div><div class="fa-card-body"><div class="fa-table table-responsive"><table class="table table-bordered"><thead><tr><th>Assignment No</th><th>Asset</th><th>Warehouse</th><th>Type</th><th>Employee</th><th>Department</th><th>Assigned At</th></tr></thead><tbody>@forelse($assignedAssets as $row)<tr><td>{{ $row->assignment_no }}</td><td>{{ optional($row->asset)->asset_code }} — {{ optional($row->asset)->asset_name }}</td><td>{{ optional(optional($row->asset)->warehouse)->title }}</td><td>@include('backend.fixed_asset.partials.status-badge', ['value' => $row->assigned_to_type])</td><td>{{ optional($row->employee)->name ?? optional($row->employee)->employee_name ?? $row->employee_id }}</td><td>{{ optional($row->department)->name ?? optional($row->department)->department_name ?? $row->department_id }}</td><td>{{ optional($row->assigned_at)->format('Y-m-d') ?: $row->assigned_at }}</td></tr>@empty<tr><td colspan="7">@include('backend.fixed_asset.partials.empty-state', ['title' => 'No active assignments found'])</td></tr>@endforelse</tbody></table></div></div></div>
    @endif

    @if(($filters['report'] ?? '') === 'maintenance')
        <div class="fa-card"><div class="fa-card-header"><h4 class="fa-card-title">Maintenance Cost Summary</h4></div><div class="fa-card-body"><div class="fa-table table-responsive"><table class="table table-bordered"><thead><tr><th>Warehouse</th><th>Total Jobs</th><th>Open</th><th>Completed</th><th class="fa-money">Total Cost</th></tr></thead><tbody>@forelse($maintenanceSummary as $row)<tr><td>{{ $row->warehouse_name ?? 'N/A' }}</td><td>{{ $row->total_jobs }}</td><td>{{ $row->open_jobs }}</td><td>{{ $row->completed_jobs }}</td><td class="fa-money">{{ $money($row->total_cost) }}</td></tr>@empty<tr><td colspan="5">@include('backend.fixed_asset.partials.empty-state', ['title' => 'No maintenance report data found'])</td></tr>@endforelse</tbody></table></div></div></div>
    @endif

    @if(($filters['report'] ?? '') === 'depreciation')
        <div class="fa-card"><div class="fa-card-header"><h4 class="fa-card-title">Depreciation Summary</h4></div><div class="fa-card-body"><div class="fa-table table-responsive"><table class="table table-bordered"><thead><tr><th>Period</th><th>Warehouse</th><th>Entries</th><th class="fa-money">Opening</th><th class="fa-money">Depreciation</th><th class="fa-money">Closing</th></tr></thead><tbody>@forelse($depreciationSummary as $row)<tr><td>{{ $row->period }}</td><td>{{ $row->warehouse_name ?? 'N/A' }}</td><td>{{ $row->total_entries }}</td><td class="fa-money">{{ $money($row->opening_book_value) }}</td><td class="fa-money">{{ $money($row->depreciation_amount) }}</td><td class="fa-money">{{ $money($row->closing_book_value) }}</td></tr>@empty<tr><td colspan="6">@include('backend.fixed_asset.partials.empty-state', ['title' => 'No depreciation report data found'])</td></tr>@endforelse</tbody></table></div></div></div>
    @endif

    @if(($filters['report'] ?? '') === 'disposal')
        <div class="fa-card"><div class="fa-card-header"><h4 class="fa-card-title">Disposal Report</h4></div><div class="fa-card-body"><div class="fa-table table-responsive"><table class="table table-bordered"><thead><tr><th>No</th><th>Asset</th><th>Warehouse</th><th>Date</th><th class="fa-money">Carrying</th><th class="fa-money">Proceeds</th><th class="fa-money">Gain/Loss</th><th>Status</th></tr></thead><tbody>@forelse($disposals as $row)<tr><td>{{ $row->disposal_no }}</td><td>{{ optional($row->asset)->asset_code }} — {{ optional($row->asset)->asset_name }}</td><td>{{ optional($row->warehouse)->title ?? optional(optional($row->asset)->warehouse)->title }}</td><td>{{ optional($row->disposal_date)->format('Y-m-d') ?: $row->disposal_date }}</td><td class="fa-money">{{ $money($row->carrying_amount) }}</td><td class="fa-money">{{ $money($row->proceeds_amount) }}</td><td class="fa-money">{{ $money($row->gain_loss_amount) }}</td><td>@include('backend.fixed_asset.partials.status-badge', ['value' => $row->status])</td></tr>@empty<tr><td colspan="8">@include('backend.fixed_asset.partials.empty-state', ['title' => 'No disposal report data found'])</td></tr>@endforelse</tbody></table></div></div></div>
    @endif
</div>
@endsection

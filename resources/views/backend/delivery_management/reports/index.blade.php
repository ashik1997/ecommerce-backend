@extends('backend.master')
@section('page_title', 'Delivery Reports')
@section('page_heading', 'Delivery Reports')
@section('content')
    @include('backend.delivery_management.partials.nav')

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('delivery-management.reports.index') }}">
                <div class="row align-items-end">
                    <div class="col-md-2 mb-2">
                        <label class="mb-1">Date From</label>
                        <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="mb-1">Date To</label>
                        <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="mb-1">Provider</label>
                        <select name="provider_id" class="form-control">
                            <option value="">All Providers</option>
                            @foreach ($providers as $provider)
                                <option value="{{ $provider->id }}" {{ (string) ($filters['provider_id'] ?? '') === (string) $provider->id ? 'selected' : '' }}>
                                    {{ $provider->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="mb-1">Employee</label>
                        <select name="delivery_employee_id" class="form-control">
                            <option value="">All Employees</option>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}" {{ (string) ($filters['delivery_employee_id'] ?? '') === (string) $employee->id ? 'selected' : '' }}>
                                    {{ $employee->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="mb-1">Status</label>
                        <select name="current_status" class="form-control">
                            <option value="">All Status</option>
                            @foreach ($statuses as $slug => $label)
                                <option value="{{ $slug }}" {{ (string) ($filters['current_status'] ?? '') === (string) $slug ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="feather-filter"></i> Filter
                        </button>
                        <a href="{{ route('delivery-management.reports.index') }}" class="btn btn-outline-secondary btn-block mt-1">
                            <i class="feather-refresh-cw"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
            <form method="GET" action="{{ route('delivery-management.reports.export') }}" class="mt-2">
                <input type="hidden" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
                <input type="hidden" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
                <input type="hidden" name="provider_id" value="{{ $filters['provider_id'] ?? '' }}">
                <input type="hidden" name="delivery_employee_id" value="{{ $filters['delivery_employee_id'] ?? '' }}">
                <input type="hidden" name="current_status" value="{{ $filters['current_status'] ?? '' }}">
                <div class="row align-items-end">
                    <div class="col-md-3 mb-2">
                        <label class="mb-1">CSV Report</label>
                        <select name="report_type" class="form-control">
                            <option value="shipments">Shipment Details</option>
                            <option value="status">Status Summary</option>
                            <option value="provider">Provider Summary</option>
                            <option value="employee">Employee Summary</option>
                            <option value="aging">Pending Aging</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <button type="submit" class="btn btn-success btn-block">
                            <i class="feather-download"></i> Export CSV
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-3 mb-2"><div class="card h-100"><div class="card-body"><div class="text-muted">Total Shipments</div><h4>{{ number_format((int) $financeSummary['total_shipments']) }}</h4></div></div></div>
        <div class="col-md-3 mb-2"><div class="card h-100"><div class="card-body"><div class="text-muted">Delivered Shipments</div><h4 class="text-success">{{ number_format((int) $financeSummary['delivered_shipments']) }}</h4></div></div></div>
        <div class="col-md-3 mb-2"><div class="card h-100"><div class="card-body"><div class="text-muted">COD Amount</div><h4>{{ number_format((float) $financeSummary['cod_amount'], 2) }}</h4></div></div></div>
        <div class="col-md-3 mb-2"><div class="card h-100"><div class="card-body"><div class="text-muted">Customer Delivery Charge</div><h4>{{ number_format((float) $financeSummary['customer_delivery_charge'], 2) }}</h4></div></div></div>
        <div class="col-md-3 mb-2"><div class="card h-100"><div class="card-body"><div class="text-muted">Provider Cost</div><h4>{{ number_format((float) $financeSummary['provider_cost'], 2) }}</h4></div></div></div>
        <div class="col-md-3 mb-2"><div class="card h-100"><div class="card-body"><div class="text-muted">Delivery Margin</div><h4 class="{{ (float) $financeSummary['delivery_margin'] < 0 ? 'text-danger' : 'text-success' }}">{{ number_format((float) $financeSummary['delivery_margin'], 2) }}</h4></div></div></div>
        <div class="col-md-3 mb-2"><div class="card h-100"><div class="card-body"><div class="text-muted">Settlement Pending</div><h4 class="text-info">{{ number_format((float) $financeSummary['settlement_pending'], 2) }}</h4></div></div></div>
    </div>

    <div class="row">
        <div class="col-lg-4 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="mb-3">Pending Aging</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead><tr><th>Age</th><th>Shipments</th><th>COD</th></tr></thead>
                            <tbody>
                                @forelse ($agingSummary as $row)
                                    <tr>
                                        <td>{{ $row->aging_bucket }}</td>
                                        <td>{{ number_format($row->total_shipments) }}</td>
                                        <td>{{ number_format((float) $row->cod_amount, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted">No pending aging data.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="mb-3">Delayed Shipment Watchlist</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead><tr><th>Shipment</th><th>Recipient</th><th>Provider</th><th>Employee</th><th>Status</th><th>Days</th><th>COD</th></tr></thead>
                            <tbody>
                                @forelse ($delayedShipments as $shipment)
                                    <tr>
                                        <td>
                                            <a href="{{ route('delivery-management.shipments.show', $shipment->id) }}">
                                                {{ $shipment->shipment_code }}
                                            </a>
                                        </td>
                                        <td>
                                            {{ $shipment->recipient_name ?: 'N/A' }}
                                            @if ($shipment->recipient_phone)
                                                <br><small class="text-muted">{{ $shipment->recipient_phone }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $shipment->provider_name }}</td>
                                        <td>{{ $shipment->employee_name }}</td>
                                        <td><span class="badge badge-warning">{{ ucwords(str_replace('_', ' ', $shipment->current_status ?: 'N/A')) }}</span></td>
                                        <td>{{ number_format($shipment->pending_days) }}</td>
                                        <td>{{ number_format((float) $shipment->cod_amount, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center text-muted">No delayed shipments found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="mb-3">Status-wise Shipments</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead><tr><th>Status</th><th>Total</th><th>COD</th><th>Customer Charge</th><th>Provider Cost</th></tr></thead>
                            <tbody>
                                @forelse ($statusSummary as $row)
                                    <tr>
                                        <td>{{ ucwords(str_replace('_', ' ', $row->current_status ?: 'N/A')) }}</td>
                                        <td>{{ number_format($row->total_shipments) }}</td>
                                        <td>{{ number_format((float) $row->cod_amount, 2) }}</td>
                                        <td>{{ number_format((float) $row->customer_delivery_charge, 2) }}</td>
                                        <td>{{ number_format((float) $row->provider_cost, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted">No status report data.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="mb-3">Provider-wise Performance</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead><tr><th>Provider</th><th>Total</th><th>Delivered</th><th>Returned</th><th>Failed</th><th>Margin</th></tr></thead>
                            <tbody>
                                @forelse ($providerSummary as $row)
                                    <tr>
                                        <td>{{ $row->provider_name }}</td>
                                        <td>{{ number_format($row->total_shipments) }}</td>
                                        <td>{{ number_format($row->delivered_shipments) }}</td>
                                        <td>{{ number_format($row->returned_shipments) }}</td>
                                        <td>{{ number_format($row->failed_shipments) }}</td>
                                        <td>{{ number_format((float) $row->customer_delivery_charge - (float) $row->provider_cost, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted">No provider report data.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-12 mb-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-3">Employee Productivity & COD</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead><tr><th>Employee</th><th>Assigned</th><th>Delivered</th><th>Collected</th><th>Submitted</th><th>Pending</th></tr></thead>
                            <tbody>
                                @forelse ($employeeSummary as $row)
                                    <tr>
                                        <td>{{ $row->employee_name }}</td>
                                        <td>{{ number_format($row->assigned_shipments) }}</td>
                                        <td>{{ number_format($row->delivered_shipments) }}</td>
                                        <td>{{ number_format((float) $row->collected_amount, 2) }}</td>
                                        <td>{{ number_format((float) $row->submitted_amount, 2) }}</td>
                                        <td class="text-danger">{{ number_format((float) $row->pending_amount, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted">No employee report data.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

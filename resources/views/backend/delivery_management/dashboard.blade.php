@extends('backend.master')
@section('page_title', 'Delivery Management')
@section('page_heading', 'Delivery Management')
@section('content')
    @include('backend.delivery_management.partials.nav')

    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div class="mb-2">
                    <h4 class="mb-1">Delivery & Logistics Overview</h4>
                    <p class="mb-0 text-muted">Manage providers, company delivery employees, shipments, COD collection, and settlements.</p>
                </div>
                <div class="mb-2">
                    <a href="{{ route('delivery-management.providers.create') }}" class="btn btn-primary">
                        <i class="feather-plus-circle"></i> Create Provider
                    </a>
                    <a href="{{ route('delivery-management.providers.index') }}" class="btn btn-outline-primary">
                        <i class="feather-list"></i> View Providers
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-3 mb-2">
            <div class="card h-100"><div class="card-body">
                <div class="text-muted">Delivery Providers</div>
                <h3 class="mb-0">{{ number_format($summary['providers']) }}</h3>
                <small class="text-muted">API, local, internal, pickup</small>
            </div></div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="card h-100"><div class="card-body">
                <div class="text-muted">Active Providers</div>
                <h3 class="mb-0 text-success">{{ number_format($summary['active_providers']) }}</h3>
                <small class="text-muted">Ready for assignment</small>
            </div></div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="card h-100"><div class="card-body">
                <div class="text-muted">Delivery Employees</div>
                <h3 class="mb-0">{{ number_format($summary['employees']) }}</h3>
                <small class="text-muted">Company owned riders</small>
            </div></div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="card h-100"><div class="card-body">
                <div class="text-muted">Pending Shipments</div>
                <h3 class="mb-0 text-warning">{{ number_format($summary['pending_shipments']) }}</h3>
                <small class="text-muted">Not terminal yet</small>
            </div></div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="card h-100"><div class="card-body">
                <div class="text-muted">Delivered Today</div>
                <h3 class="mb-0 text-success">{{ number_format($summary['delivered_today']) }}</h3>
                <small class="text-muted">Shipment delivered date</small>
            </div></div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="card h-100"><div class="card-body">
                <div class="text-muted">COD Pending</div>
                <h3 class="mb-0 text-danger">{{ number_format((float) $summary['cod_pending'], 2) }}</h3>
                <small class="text-muted">Expected minus submitted</small>
            </div></div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="card h-100"><div class="card-body">
                <div class="text-muted">Settlement Pending</div>
                <h3 class="mb-0 text-info">{{ number_format((float) $summary['settlement_pending'], 2) }}</h3>
                <small class="text-muted">Draft and approved net</small>
            </div></div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="mb-3">Status Summary</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead><tr><th>Status</th><th>Total</th></tr></thead>
                            <tbody>
                                @forelse ($statusSummary as $row)
                                    <tr>
                                        <td>{{ ucwords(str_replace('_', ' ', $row->current_status ?: 'N/A')) }}</td>
                                        <td>{{ number_format($row->total) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="2" class="text-center text-muted">No shipment status data.</td></tr>
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
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Provider Performance</h5>
                        <a href="{{ route('delivery-management.reports.index') }}" class="btn btn-sm btn-outline-primary">Reports</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead><tr><th>Provider</th><th>Total</th><th>Delivered</th><th>Returned</th></tr></thead>
                            <tbody>
                                @forelse ($providerSummary as $row)
                                    <tr>
                                        <td>{{ $row->provider_name }}</td>
                                        <td>{{ number_format($row->total_shipments) }}</td>
                                        <td>{{ number_format($row->delivered_shipments) }}</td>
                                        <td>{{ number_format($row->returned_shipments) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted">No provider delivery data.</td></tr>
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
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Recent Providers</h5>
                        <a href="{{ route('delivery-management.providers.index') }}" class="btn btn-sm btn-outline-primary">Manage</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead><tr><th>Name</th><th>Type</th><th>Driver</th><th>Status</th></tr></thead>
                            <tbody>
                                @forelse ($recentProviders as $provider)
                                    <tr>
                                        <td><a href="{{ route('delivery-management.providers.edit', $provider) }}">{{ $provider->name }}</a></td>
                                        <td>{{ ucwords(str_replace('_', ' ', $provider->provider_type)) }}</td>
                                        <td>{{ $provider->integration_driver ?: 'N/A' }}</td>
                                        <td><span class="badge badge-{{ $provider->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($provider->status) }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted">No delivery provider found.</td></tr>
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
                    <h5 class="mb-3">Recent Shipments</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead><tr><th>Shipment</th><th>Provider</th><th>Employee</th><th>Status</th></tr></thead>
                            <tbody>
                                @forelse ($recentShipments as $shipment)
                                    <tr>
                                        <td>{{ $shipment->shipment_code }}</td>
                                        <td>{{ $shipment->provider->name ?? 'N/A' }}</td>
                                        <td>{{ $shipment->employee->name ?? 'N/A' }}</td>
                                        <td><span class="badge badge-info">{{ str_replace('_', ' ', $shipment->current_status) }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted">No shipment data yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <p class="text-muted mb-0">Shipment records will be added through a non-breaking dual-write layer from existing order flows.</p>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="mb-3">Employee COD Outstanding</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead><tr><th>Employee</th><th>Expected</th><th>Submitted</th><th>Pending</th></tr></thead>
                            <tbody>
                                @forelse ($employeeCodSummary as $row)
                                    <tr>
                                        <td>{{ $row->employee_name }}</td>
                                        <td>{{ number_format((float) $row->expected_amount, 2) }}</td>
                                        <td>{{ number_format((float) $row->submitted_amount, 2) }}</td>
                                        <td class="text-danger">{{ number_format((float) $row->pending_amount, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted">No pending COD.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

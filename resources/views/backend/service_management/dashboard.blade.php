@extends('backend.master')
@section('page_title', 'Service Management Dashboard')
@section('page_heading', 'Service Management Dashboard')
@section('content')
    @php
        $statusCards = [
            ['label' => 'Draft', 'value' => $summary['draft'], 'class' => 'secondary'],
            ['label' => 'Confirmed', 'value' => $summary['confirmed'], 'class' => 'primary'],
            ['label' => 'Billed', 'value' => $summary['billed'], 'class' => 'warning'],
            ['label' => 'Paid', 'value' => $summary['paid'], 'class' => 'success'],
            ['label' => 'Cancelled', 'value' => $summary['cancelled'], 'class' => 'danger'],
        ];
    @endphp

    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div class="mb-2">
                    <h4 class="mb-1">Service & Rental Overview</h4>
                    <p class="mb-0 text-muted">Track service lifecycle, billing, collections, and rental returns from one place.</p>
                </div>
                <div class="mb-2">
                    <a href="{{ route('service-management.instances.create') }}" class="btn btn-primary">Create Instance</a>
                    <a href="{{ route('service-management.services.create') }}" class="btn btn-outline-primary">New Service</a>
                    <a href="{{ route('service-management.payments.index') }}" class="btn btn-success">Receive Payment</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-3 mb-2">
            <div class="card h-100"><div class="card-body">
                <div class="text-muted">Active Services</div>
                <h3 class="mb-0">{{ number_format($summary['active_services']) }}</h3>
                <small class="text-muted">Catalog items ready for use</small>
            </div></div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="card h-100"><div class="card-body">
                <div class="text-muted">Total Instances</div>
                <h3 class="mb-0">{{ number_format($summary['total_instances']) }}</h3>
                <small class="text-muted">All service/rental records</small>
            </div></div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="card h-100"><div class="card-body">
                <div class="text-muted">Open Rentals</div>
                <h3 class="mb-0 text-primary">{{ number_format($summary['open_rentals']) }}</h3>
                <small class="text-muted">Need return/close tracking</small>
            </div></div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="card h-100"><div class="card-body">
                <div class="text-muted">Due Invoices</div>
                <h3 class="mb-0 text-danger">{{ number_format($summary['due_invoices']) }}</h3>
                <small class="text-muted">Billed instances with due</small>
            </div></div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-3 mb-2">
            <div class="card h-100"><div class="card-body">
                <div class="text-muted">Total Bill</div>
                <h4 class="mb-0">{{ number_format((float) $summary['total_bill'], 2) }}</h4>
            </div></div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="card h-100"><div class="card-body">
                <div class="text-muted">Total Paid</div>
                <h4 class="mb-0 text-success">{{ number_format((float) $summary['total_paid'], 2) }}</h4>
            </div></div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="card h-100"><div class="card-body">
                <div class="text-muted">Total Due</div>
                <h4 class="mb-0 text-danger">{{ number_format((float) $summary['total_due'], 2) }}</h4>
            </div></div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="card h-100"><div class="card-body">
                <div class="text-muted">Collection</div>
                <h4 class="mb-0">{{ number_format((float) $summary['month_collection'], 2) }}</h4>
                <small class="text-muted">Today: {{ number_format((float) $summary['today_collection'], 2) }}</small>
            </div></div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-8 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Lifecycle Pipeline</h5>
                        <a href="{{ route('service-management.instances.index') }}" class="btn btn-sm btn-outline-primary">View Instances</a>
                    </div>
                    <div class="row">
                        @foreach ($statusCards as $card)
                            <div class="col-md col-6 mb-2">
                                <div class="border rounded p-3 text-center">
                                    <span class="badge badge-{{ $card['class'] }}">{{ $card['label'] }}</span>
                                    <h4 class="mb-0 mt-2">{{ number_format($card['value']) }}</h4>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <p class="text-muted mb-0">Normal flow: draft -> confirmed -> billed -> paid. Cancel is allowed before billing/payment.</p>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="mb-3">Quick Links</h5>
                    <a href="{{ route('service-management.services.index') }}" class="btn btn-outline-primary btn-block text-left">Service Catalog</a>
                    <a href="{{ route('service-management.instances.index') }}" class="btn btn-outline-primary btn-block text-left">Service Instances</a>
                    <a href="{{ route('service-management.billing.index') }}" class="btn btn-outline-primary btn-block text-left">Billing & Invoice</a>
                    <a href="{{ route('service-management.payments.index') }}" class="btn btn-outline-success btn-block text-left">Payments</a>
                    <a href="{{ route('service-management.reports.index') }}" class="btn btn-outline-secondary btn-block text-left">Reports</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Due Billing</h5>
                        <a href="{{ route('service-management.payments.index') }}" class="btn btn-sm btn-success">Receive</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead><tr><th>Instance</th><th>Customer</th><th>Due</th></tr></thead>
                            <tbody>
                                @forelse ($dueInstances as $instance)
                                    <tr>
                                        <td>
                                            {{ $instance->instance_no }}
                                            <div class="small text-muted">{{ $instance->service->name ?? 'N/A' }}</div>
                                        </td>
                                        <td>{{ $instance->customer->name ?? $instance->customer->full_name ?? 'N/A' }}</td>
                                        <td class="text-danger">{{ number_format((float) $instance->due_amount, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted">No due billed instances.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="mb-3">Open Rentals</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead><tr><th>Instance</th><th>Customer</th><th>Status</th></tr></thead>
                            <tbody>
                                @forelse ($openRentals as $instance)
                                    <tr>
                                        <td>
                                            {{ $instance->instance_no }}
                                            <div class="small text-muted">{{ optional($instance->start_date)->format('Y-m-d') ?: 'N/A' }} - {{ optional($instance->end_date)->format('Y-m-d') ?: 'Open' }}</div>
                                        </td>
                                        <td>{{ $instance->customer->name ?? $instance->customer->full_name ?? 'N/A' }}</td>
                                        <td><span class="badge badge-info">{{ ucfirst($instance->status) }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted">No open rentals.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-7 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="mb-3">Recent Instances</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead><tr><th>Instance</th><th>Service</th><th>Customer</th><th>Total</th><th>Status</th></tr></thead>
                            <tbody>
                                @forelse ($recentInstances as $instance)
                                    <tr>
                                        <td>{{ $instance->instance_no }}</td>
                                        <td>{{ $instance->service->name ?? 'N/A' }}</td>
                                        <td>{{ $instance->customer->name ?? $instance->customer->full_name ?? 'N/A' }}</td>
                                        <td>{{ number_format((float) $instance->total_amount, 2) }}</td>
                                        <td><span class="badge badge-info">{{ ucfirst($instance->status) }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted">No service instances found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-5 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="mb-3">Recent Payments</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead><tr><th>Payment</th><th>Method</th><th>Amount</th></tr></thead>
                            <tbody>
                                @forelse ($recentPayments as $payment)
                                    <tr>
                                        <td>
                                            {{ $payment->payment_no }}
                                            <div class="small text-muted">{{ $payment->serviceInstance->instance_no ?? 'N/A' }}</div>
                                        </td>
                                        <td>{{ $payment->paymentType->payment_type ?? 'N/A' }}</td>
                                        <td class="text-success">{{ number_format((float) $payment->amount, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted">No payments found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

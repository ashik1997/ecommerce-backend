@extends('backend.master')
@section('header_css')
    <link href="{{ url('assets') }}/plugins/select2/select2.min.css" rel="stylesheet" type="text/css" />
@endsection
@section('page_title', 'Service Reports')
@section('page_heading', 'Service Reports')
@section('content')
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('service-management.reports.index') }}">
                <div class="row">
                    <div class="col-md-2 mb-2">
                        <label class="form-label">From</label>
                        <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] }}">
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="form-label">To</label>
                        <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] }}">
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label">Service</label>
                        <select name="service_id" class="form-control select2">
                            <option value="">All Services</option>
                            @foreach ($services as $service)
                                <option value="{{ $service->id }}" {{ (string) $filters['service_id'] === (string) $service->id ? 'selected' : '' }}>{{ $service->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label">Customer</label>
                        <select name="customer_id" class="form-control select2">
                            <option value="">All Customers</option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}" {{ (string) $filters['customer_id'] === (string) $customer->id ? 'selected' : '' }}>
                                    {{ $customer->name ?? $customer->full_name }}{{ $customer->phone ? ' - ' . $customer->phone : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-2 d-flex align-items-end">
                        <button class="btn btn-primary btn-block">Filter Report</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-2 mb-2"><div class="card"><div class="card-body"><div class="text-muted">Instances</div><h4 class="mb-0">{{ number_format($summary['instances']) }}</h4></div></div></div>
        <div class="col-md-2 mb-2"><div class="card"><div class="card-body"><div class="text-muted">Total Bill</div><h4 class="mb-0">{{ number_format((float) $summary['total_bill'], 2) }}</h4></div></div></div>
        <div class="col-md-2 mb-2"><div class="card"><div class="card-body"><div class="text-muted">Service Amount</div><h4 class="mb-0">{{ number_format((float) $summary['service_amount'], 2) }}</h4></div></div></div>
        <div class="col-md-2 mb-2"><div class="card"><div class="card-body"><div class="text-muted">Product Amount</div><h4 class="mb-0">{{ number_format((float) $summary['product_amount'], 2) }}</h4></div></div></div>
        <div class="col-md-2 mb-2"><div class="card"><div class="card-body"><div class="text-muted">Paid</div><h4 class="mb-0">{{ number_format((float) $summary['paid'], 2) }}</h4></div></div></div>
        <div class="col-md-2 mb-2"><div class="card"><div class="card-body"><div class="text-muted">Due</div><h4 class="mb-0 text-danger">{{ number_format((float) $summary['due'], 2) }}</h4></div></div></div>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="mb-3">Service-wise Billing</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th>Service</th>
                            <th>Type</th>
                            <th>Instances</th>
                            <th>Service Amount</th>
                            <th>Product Amount</th>
                            <th>Total</th>
                            <th>Paid</th>
                            <th>Due</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($serviceRows as $row)
                            <tr>
                                <td>{{ $row->name }}</td>
                                <td>{{ ucfirst($row->type) }}</td>
                                <td>{{ number_format($row->instance_count) }}</td>
                                <td>{{ number_format((float) $row->service_amount, 2) }}</td>
                                <td>{{ number_format((float) $row->product_amount, 2) }}</td>
                                <td>{{ number_format((float) $row->total_amount, 2) }}</td>
                                <td>{{ number_format((float) $row->paid_amount, 2) }}</td>
                                <td>{{ number_format((float) $row->due_amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted">No service data found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="mb-3">Top Customer Billing</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead><tr><th>Customer</th><th>Instances</th><th>Total</th><th>Paid</th><th>Due</th></tr></thead>
                            <tbody>
                                @forelse ($customerRows as $row)
                                    <tr>
                                        <td>{{ $row->name ?? $row->full_name }}<div class="small text-muted">{{ $row->phone }}</div></td>
                                        <td>{{ number_format($row->instance_count) }}</td>
                                        <td>{{ number_format((float) $row->total_amount, 2) }}</td>
                                        <td>{{ number_format((float) $row->paid_amount, 2) }}</td>
                                        <td>{{ number_format((float) $row->due_amount, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted">No customer data found.</td></tr>
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
                    <h5 class="mb-3">Product Usage</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead><tr><th>Product</th><th>Instances</th><th>Qty Used</th><th>Total</th></tr></thead>
                            <tbody>
                                @forelse ($productRows as $row)
                                    <tr>
                                        <td>{{ $row->name }}<div class="small text-muted">{{ $row->sku }}</div></td>
                                        <td>{{ number_format($row->instance_count) }}</td>
                                        <td>{{ number_format((float) $row->quantity_used) }}</td>
                                        <td>{{ number_format((float) $row->total_amount, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted">No product usage found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="mb-3">Payment Collection By Method</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead><tr><th>Payment Method</th><th>Payments</th><th>Amount</th></tr></thead>
                    <tbody>
                        @forelse ($paymentRows as $row)
                            <tr>
                                <td>{{ $row->payment_type }}</td>
                                <td>{{ number_format($row->payment_count) }}</td>
                                <td>{{ number_format((float) $row->amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted">No payment data found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <p class="text-muted mb-0">Note: true net profit needs cost allocation per service product. Current report shows invoice-ready billing, product usage, paid, and due totals.</p>
        </div>
    </div>
@endsection

@section('footer_js')
    <script src="{{ url('assets') }}/plugins/select2/select2.min.js"></script>
    <script>
        $('.select2').select2({ width: '100%' });
    </script>
@endsection

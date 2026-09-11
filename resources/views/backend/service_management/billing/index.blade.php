@extends('backend.master')
@section('page_title', 'Service Billing')
@section('page_heading', 'Service Billing')
@section('content')
    <div class="row mb-3">
        <div class="col-md-3 mb-2">
            <div class="card"><div class="card-body">
                <div class="text-muted">Billable Instances</div>
                <h4 class="mb-0">{{ number_format($summary['billable_count']) }}</h4>
            </div></div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="card"><div class="card-body">
                <div class="text-muted">Total Bill</div>
                <h4 class="mb-0">{{ number_format((float) $summary['total_billable'], 2) }}</h4>
            </div></div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="card"><div class="card-body">
                <div class="text-muted">Paid</div>
                <h4 class="mb-0">{{ number_format((float) $summary['total_paid'], 2) }}</h4>
            </div></div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="card"><div class="card-body">
                <div class="text-muted">Due</div>
                <h4 class="mb-0 text-danger">{{ number_format((float) $summary['total_due'], 2) }}</h4>
            </div></div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="mb-1">Service Billing</h5>
                    <p class="mb-0 text-muted">Invoice-ready service instances. Invoice generation will be added in the next billing phase.</p>
                </div>
                <div>
                    <a href="{{ route('service-management.payments.index') }}" class="btn btn-success">Receive Payment</a>
                    <a href="{{ route('service-management.instances.create') }}" class="btn btn-primary">Create Instance</a>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th>Instance No</th>
                            <th>Customer</th>
                            <th>Service</th>
                            <th>Billing</th>
                            <th>Service Amount</th>
                            <th>Products</th>
                            <th>Total</th>
                            <th>Due</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($instances as $instance)
                            @php
                                $badgeClass = [
                                    'confirmed' => 'badge-primary',
                                    'billed' => 'badge-warning',
                                    'paid' => 'badge-success',
                                ][$instance->status] ?? 'badge-info';
                            @endphp
                            <tr>
                                <td>{{ $instance->instance_no }}</td>
                                <td>
                                    <strong>{{ $instance->customer->name ?? $instance->customer->full_name ?? 'N/A' }}</strong>
                                    <div class="small text-muted">{{ $instance->customer->phone ?? '' }}</div>
                                </td>
                                <td>{{ $instance->service->name ?? 'N/A' }}</td>
                                <td>
                                    {{ $instance->billing_unit_qty }} x {{ number_format((float) $instance->service_unit_price, 2) }}
                                    <div class="small text-muted">{{ ucfirst($instance->service->billing_unit ?? 'unit') }}</div>
                                </td>
                                <td>{{ number_format((float) $instance->service_subtotal, 2) }}</td>
                                <td>
                                    {{ number_format((float) $instance->products_subtotal, 2) }}
                                    <div class="small text-muted">{{ $instance->products->count() }} item(s)</div>
                                </td>
                                <td>{{ number_format((float) $instance->total_amount, 2) }}</td>
                                <td>{{ number_format((float) $instance->due_amount, 2) }}</td>
                                <td>
                                    <span class="badge {{ $badgeClass }}">{{ ucfirst($instance->status) }}</span>
                                    @if ($instance->closed_at)
                                        <div class="small text-muted">Closed: {{ $instance->closed_at->format('Y-m-d') }}</div>
                                    @endif
                                    @if ($instance->rental_returned_at)
                                        <div class="small text-success">Rental returned</div>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        @if ($instance->canBill())
                                            <form method="POST" action="{{ route('service-management.instances.bill', $instance) }}" class="d-inline">
                                                @csrf
                                                <button class="btn btn-warning">Bill</button>
                                            </form>
                                        @endif

                                        <a href="{{ route('service-management.instances.invoice', $instance) }}" target="_blank" class="btn btn-info">Invoice</a>

                                        @if ($instance->status === 'billed' && (float) $instance->due_amount > 0)
                                            <a href="{{ route('service-management.payments.index') }}" class="btn btn-success">Payment</a>
                                        @endif

                                        @if ($instance->canClose())
                                            <form method="POST" action="{{ route('service-management.instances.close', $instance) }}" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="end_date" value="{{ now()->toDateString() }}">
                                                <button class="btn btn-secondary">{{ $instance->isRental() && !$instance->rental_returned_at ? 'Return & Close' : 'Close' }}</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted">No invoice-ready service instances found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $instances->links() }}
        </div>
    </div>
@endsection

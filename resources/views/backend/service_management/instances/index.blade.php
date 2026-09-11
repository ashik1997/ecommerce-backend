@extends('backend.master')
@section('page_title', 'Service Instances')
@section('page_heading', 'Service Instances')
@section('content')
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="mb-1">Service Instances</h5>
                    <p class="mb-0 text-muted">Customer service/rental entries with service and product charges.</p>
                </div>
                <a href="{{ route('service-management.instances.create') }}" class="btn btn-primary">Create Instance</a>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th>Instance No</th>
                            <th>Customer</th>
                            <th>Service</th>
                            <th>Period</th>
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
                                    'draft' => 'badge-secondary',
                                    'confirmed' => 'badge-primary',
                                    'billed' => 'badge-warning',
                                    'paid' => 'badge-success',
                                    'cancelled' => 'badge-danger',
                                ][$instance->status] ?? 'badge-info';
                            @endphp
                            <tr>
                                <td>{{ $instance->instance_no }}</td>
                                <td>
                                    <strong>{{ $instance->customer->name ?? $instance->customer->full_name ?? 'N/A' }}</strong>
                                    <div class="small text-muted">{{ $instance->customer->phone ?? '' }}</div>
                                </td>
                                <td>
                                    {{ $instance->service->name ?? 'N/A' }}
                                    <div class="small text-muted">{{ ucfirst($instance->service->type ?? '') }} / {{ ucfirst($instance->service->billing_unit ?? '') }}</div>
                                </td>
                                <td>
                                    {{ optional($instance->start_date)->format('Y-m-d') ?: 'N/A' }}
                                    -
                                    {{ optional($instance->end_date)->format('Y-m-d') ?: 'Open' }}
                                    <div class="small text-muted">Qty: {{ $instance->billing_unit_qty }}</div>
                                </td>
                                <td>{{ $instance->products->count() }}</td>
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
                                        <a href="{{ route('service-management.instances.invoice', $instance) }}" target="_blank" class="btn btn-info">Invoice</a>

                                        @if ($instance->canConfirm())
                                            <form method="POST" action="{{ route('service-management.instances.confirm', $instance) }}" class="d-inline">
                                                @csrf
                                                <button class="btn btn-primary">Confirm</button>
                                            </form>
                                        @endif

                                        @if ($instance->canBill())
                                            <form method="POST" action="{{ route('service-management.instances.bill', $instance) }}" class="d-inline">
                                                @csrf
                                                <button class="btn btn-warning">Bill</button>
                                            </form>
                                        @endif

                                        @if ($instance->canClose())
                                            <form method="POST" action="{{ route('service-management.instances.close', $instance) }}" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="end_date" value="{{ now()->toDateString() }}">
                                                <button class="btn btn-success">{{ $instance->isRental() && !$instance->rental_returned_at ? 'Return & Close' : 'Close' }}</button>
                                            </form>
                                        @endif

                                        @if ($instance->canCancel())
                                            <form method="POST" action="{{ route('service-management.instances.cancel', $instance) }}" class="d-inline" onsubmit="return confirm('Cancel this service instance?');">
                                                @csrf
                                                <button class="btn btn-danger">Cancel</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted">No service instances found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $instances->links() }}
        </div>
    </div>
@endsection

@extends('backend.master')
@section('page_title', 'Delivery Settlements')
@section('page_heading', 'Delivery Settlements')
@section('content')
    @include('backend.delivery_management.partials.nav')

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                <h4 class="mb-2">Delivery Settlements</h4>
                <a href="{{ route('delivery-management.settlements.create') }}" class="btn btn-primary mb-2">
                    <i class="feather-plus-circle"></i> Create Settlement
                </a>
            </div>

            <form method="get" class="row mb-3">
                <div class="col-md-4 mb-2">
                    <select name="settlement_type" class="form-control">
                        <option value="">All Types</option>
                        @foreach ($types as $value => $label)
                            <option value="{{ $value }}" {{ request('settlement_type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <select name="status" class="form-control">
                        <option value="">All Status</option>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <button class="btn btn-dark btn-block"><i class="feather-filter"></i> Filter</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead><tr><th>Code</th><th>Type</th><th>Provider/Employee</th><th>Items</th><th>COD</th><th>Cost</th><th>Net</th><th>Status</th><th>Action</th></tr></thead>
                    <tbody>
                        @forelse ($settlements as $settlement)
                            <tr>
                                <td><strong>{{ $settlement->settlement_code }}</strong><div class="small text-muted">{{ optional($settlement->settlement_date)->format('Y-m-d') }}</div></td>
                                <td>{{ $types[$settlement->settlement_type] ?? $settlement->settlement_type }}</td>
                                <td>{{ $settlement->provider->name ?? $settlement->employee->name ?? 'N/A' }}</td>
                                <td>{{ number_format($settlement->items_count) }}</td>
                                <td>{{ number_format((float) $settlement->total_cod_amount, 2) }}</td>
                                <td>{{ number_format((float) $settlement->total_delivery_cost, 2) }}</td>
                                <td>{{ number_format((float) $settlement->net_amount, 2) }}</td>
                                <td><span class="badge badge-info">{{ $statuses[$settlement->status] ?? ucfirst($settlement->status) }}</span></td>
                                <td><a href="{{ route('delivery-management.settlements.show', $settlement) }}" class="btn btn-sm btn-info">View</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-muted">No settlement found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $settlements->links() }}
        </div>
    </div>
@endsection

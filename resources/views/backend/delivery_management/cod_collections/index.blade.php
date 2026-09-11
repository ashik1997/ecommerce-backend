@extends('backend.master')
@section('page_title', 'COD Collections')
@section('page_heading', 'COD Collections')
@section('content')
    @include('backend.delivery_management.partials.nav')

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-body">
            <h4 class="mb-3">COD Collections</h4>

            <form method="get" class="row mb-3">
                <div class="col-md-4 mb-2">
                    <select name="delivery_employee_id" class="form-control">
                        <option value="">All Employees</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}" {{ (string) request('delivery_employee_id') === (string) $employee->id ? 'selected' : '' }}>{{ $employee->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-2">
                    <select name="collection_status" class="form-control">
                        <option value="">All Status</option>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" {{ request('collection_status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <button class="btn btn-dark btn-block"><i class="feather-filter"></i> Filter</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Shipment</th>
                            <th>Employee</th>
                            <th>Expected</th>
                            <th>Collected</th>
                            <th>Submitted</th>
                            <th>Status</th>
                            <th style="width: 150px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($collections as $collection)
                            <tr>
                                <td>
                                    <a href="{{ route('delivery-management.shipments.show', $collection->shipment) }}">{{ $collection->shipment->shipment_code ?? 'N/A' }}</a>
                                    <div class="small text-muted">{{ $collection->shipment->provider->name ?? 'N/A' }}</div>
                                </td>
                                <td>{{ $collection->employee->name ?? 'N/A' }}</td>
                                <td>{{ number_format((float) $collection->expected_amount, 2) }}</td>
                                <td>{{ number_format((float) $collection->collected_amount, 2) }}</td>
                                <td>{{ number_format((float) $collection->submitted_amount, 2) }}</td>
                                <td><span class="badge badge-info">{{ $statuses[$collection->collection_status] ?? ucfirst($collection->collection_status) }}</span></td>
                                <td>
                                    @if ($collection->collection_status !== 'verified')
                                        <form method="post" action="{{ route('delivery-management.cod-collections.verify', $collection) }}">
                                            @csrf
                                            <button class="btn btn-sm btn-success">Verify</button>
                                        </form>
                                    @else
                                        <span class="text-muted">Verified</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted">No COD collection found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $collections->links() }}
        </div>
    </div>
@endsection

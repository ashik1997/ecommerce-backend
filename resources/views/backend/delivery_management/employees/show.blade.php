@extends('backend.master')
@section('page_title', 'Delivery Employee')
@section('page_heading', 'Delivery Employee')
@section('content')
    @include('backend.delivery_management.partials.nav')

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">{{ $employee->name }}</h4>
                <a href="{{ route('delivery-management.employees.edit', $employee) }}" class="btn btn-primary">Edit</a>
            </div>
            <table class="table table-bordered">
                <tr><th style="width: 220px;">Linked User</th><td>{{ $employee->user->name ?? 'N/A' }}</td></tr>
                <tr><th>Phone</th><td>{{ $employee->phone ?: 'N/A' }}</td></tr>
                <tr><th>Email</th><td>{{ $employee->email ?: 'N/A' }}</td></tr>
                <tr><th>Vehicle</th><td>{{ trim(($employee->vehicle_type ?: '') . ' ' . ($employee->vehicle_number ?: '')) ?: 'N/A' }}</td></tr>
                <tr><th>Current Status</th><td>{{ ucwords(str_replace('_', ' ', $employee->current_status)) }}</td></tr>
                <tr><th>Cash Collection Limit</th><td>{{ number_format((float) $employee->cash_collection_limit, 2) }}</td></tr>
                <tr><th>Status</th><td>{{ ucfirst($employee->status) }}</td></tr>
            </table>
        </div>
    </div>
@endsection

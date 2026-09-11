@extends('backend.master')
@section('page_title', 'Delivery Employees')
@section('page_heading', 'Delivery Employees')
@section('content')
    @include('backend.delivery_management.partials.nav')

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                <h4 class="mb-2">Company Delivery Employees</h4>
                <a href="{{ route('delivery-management.employees.create') }}" class="btn btn-primary mb-2">
                    <i class="feather-user-plus"></i> Create Employee
                </a>
            </div>

            <form method="get" class="row mb-3">
                <div class="col-md-4 mb-2">
                    <input type="text" name="q" class="form-control" value="{{ request('q') }}" placeholder="Search name, phone, vehicle">
                </div>
                <div class="col-md-3 mb-2">
                    <select name="current_status" class="form-control">
                        <option value="">All Current Status</option>
                        @foreach ($currentStatuses as $value => $label)
                            <option value="{{ $value }}" {{ request('current_status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <select name="status" class="form-control">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
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
                            <th>Name</th>
                            <th>Contact</th>
                            <th>Vehicle</th>
                            <th>Current Status</th>
                            <th>Salary</th>
                            <th>Status</th>
                            <th style="width: 160px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($employees as $employee)
                            <tr>
                                <td>
                                    <strong>{{ $employee->name }}</strong>
                                    <div class="small text-muted">{{ $employee->user->name ?? 'No linked user' }}</div>
                                </td>
                                <td>
                                    {{ $employee->phone ?: 'N/A' }}
                                    <div class="small text-muted">{{ $employee->email }}</div>
                                </td>
                                <td>
                                    {{ $employee->vehicle_type ?: 'N/A' }}
                                    <div class="small text-muted">{{ $employee->vehicle_number }}</div>
                                </td>
                                <td><span class="badge badge-info">{{ $currentStatuses[$employee->current_status] ?? ucfirst($employee->current_status) }}</span></td>
                                <td>
                                    {{ $salaryTypes[$employee->salary_type] ?? ($employee->salary_type ?: 'N/A') }}
                                    <div class="small text-muted">{{ number_format((float) $employee->salary_amount, 2) }}</div>
                                </td>
                                <td><span class="badge badge-{{ $employee->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($employee->status) }}</span></td>
                                <td>
                                    <a href="{{ route('delivery-management.employees.edit', $employee) }}" class="btn btn-sm btn-info">Edit</a>
                                    <form action="{{ route('delivery-management.employees.destroy', $employee) }}" method="post" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-warning" onclick="return confirm('Deactivate this employee?')">Deactivate</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted">No delivery employee found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $employees->links() }}
        </div>
    </div>
@endsection

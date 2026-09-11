@extends('backend.master')

@section('page_title', 'Employee Master Report')
@section('page_heading', 'Employee Master Report')

@section('content')
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row">
                <div class="col-md-3 mb-2">
                    <label>Department</label>
                    <select name="department_id" class="form-control">
                        <option value="">All</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}" {{ (string) request('department_id') === (string) $department->id ? 'selected' : '' }}>{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <label>Branch</label>
                    <select name="branch_id" class="form-control">
                        <option value="">All</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" {{ (string) request('branch_id') === (string) $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="">All</option>
                        @foreach (['active', 'probation', 'resigned', 'terminated', 'suspended'] as $status)
                            <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-2 d-flex align-items-end"><button class="btn btn-secondary">Filter</button></div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-sm">
                <tr>
                    <th>Code</th><th>Name</th><th>Email</th><th>Department</th><th>Designation</th><th>Branch</th><th>Manager</th><th>Joining</th><th>Status</th><th>Contact</th>
                </tr>
                @foreach ($profiles as $profile)
                    <tr>
                        <td>{{ $profile->employee_code }}</td>
                        <td>{{ $profile->user->name ?? 'N/A' }}</td>
                        <td>{{ $profile->user->email ?? '-' }}</td>
                        <td>{{ $profile->departmentInfo->name ?? $profile->department ?? '-' }}</td>
                        <td>{{ $profile->designationInfo->name ?? $profile->designation ?? '-' }}</td>
                        <td>{{ $profile->branchInfo->name ?? $profile->branch ?? '-' }}</td>
                        <td>{{ $profile->reportingManager->name ?? '-' }}</td>
                        <td>{{ optional($profile->joining_date)->format('Y-m-d') }}</td>
                        <td>{{ ucfirst($profile->status) }}</td>
                        <td>{{ $profile->emergency_contact_phone }}</td>
                    </tr>
                @endforeach
            </table>
            {{ $profiles->links() }}
        </div>
    </div>
@endsection

@extends('backend.master')

@section('header_css')
    <link href="{{ url('assets') }}/plugins/select2/select2.min.css" rel="stylesheet" type="text/css" />
@endsection
@section('page_title', 'Leave Report')
@section('page_heading', 'Leave Report')

@section('content')
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET">
                <div class="row">
                    <div class="col-md-2 mb-2"><label>From</label><input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control"></div>
                    <div class="col-md-2 mb-2"><label>To</label><input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control"></div>
                    <div class="col-md-3 mb-2"><label>Employee</label>@include('backend.hrat.partials.employee-select', ['employees' => $employees, 'selected' => request('employee_id')])</div>
                    <div class="col-md-2 mb-2">
                        <label>Leave Type</label>
                        <select name="leave_type_id" class="form-control">
                            <option value="">All</option>
                            @foreach ($leaveTypes as $type)
                                <option value="{{ $type->id }}" {{ (string) request('leave_type_id') === (string) $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="">All</option>
                            @foreach (['pending', 'approved', 'rejected', 'cancelled'] as $status)
                                <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-1 mb-2 d-flex align-items-end"><button class="btn btn-secondary">Filter</button></div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-sm">
                <tr><th>Employee</th><th>Leave Type</th><th>Period</th><th>Days</th><th>Status</th><th>Reason</th><th>Reviewed By</th><th>Review Note</th></tr>
                @foreach ($applications as $application)
                    <tr>
                        <td>{{ $application->employee->name ?? 'N/A' }}</td>
                        <td>{{ $application->leaveType->name ?? 'N/A' }}</td>
                        <td>{{ optional($application->from_date)->format('Y-m-d') }} to {{ optional($application->to_date)->format('Y-m-d') }}</td>
                        <td>{{ number_format($application->total_days, 2) }}</td>
                        <td><span class="badge badge-info">{{ ucfirst($application->status) }}</span></td>
                        <td>{{ $application->reason }}</td>
                        <td>{{ $application->approver->name ?? $application->rejecter->name ?? '-' }}</td>
                        <td>{{ $application->review_note ?? '-' }}</td>
                    </tr>
                @endforeach
            </table>
            {{ $applications->links() }}
        </div>
    </div>
@endsection

@section('footer_js')
    <script src="{{ url('assets') }}/plugins/select2/select2.min.js"></script>
    <script>$('.select2').select2({ width: '100%' });</script>
@endsection

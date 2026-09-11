@extends('backend.master')

@section('page_title', 'Department/Branch Report')
@section('page_heading', 'Department/Branch Report')

@section('content')
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row">
                <div class="col-md-2 mb-2"><label>From</label><input type="date" name="date_from" value="{{ $from }}" class="form-control"></div>
                <div class="col-md-2 mb-2"><label>To</label><input type="date" name="date_to" value="{{ $to }}" class="form-control"></div>
                <div class="col-md-2 mb-2">
                    <label>Group By</label>
                    <select name="group_by" class="form-control">
                        <option value="department" {{ $groupBy === 'department' ? 'selected' : '' }}>Department</option>
                        <option value="branch" {{ $groupBy === 'branch' ? 'selected' : '' }}>Branch</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2 d-flex align-items-end"><button class="btn btn-secondary">Load</button></div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-sm">
                <tr>
                    <th>{{ ucfirst($groupBy) }}</th><th>Employees</th><th>Present</th><th>Absent</th><th>Late</th><th>Leave</th><th>Overtime</th><th>Payroll Net</th>
                </tr>
                @foreach ($rows as $row)
                    <tr>
                        <td>{{ $row['group'] }}</td>
                        <td>{{ $row['employees'] }}</td>
                        <td>{{ $row['present_days'] }}</td>
                        <td>{{ $row['absent_days'] }}</td>
                        <td>{{ $row['late_days'] }}</td>
                        <td>{{ $row['leave_days'] }}</td>
                        <td>{{ intdiv($row['overtime_minutes'], 60) }}h {{ $row['overtime_minutes'] % 60 }}m</td>
                        <td>{{ number_format($row['net_payable'], 2) }}</td>
                    </tr>
                @endforeach
            </table>
        </div>
    </div>
@endsection

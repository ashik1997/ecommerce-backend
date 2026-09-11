@extends('backend.master')

@section('page_title', 'Monthly Attendance Report')
@section('page_heading', 'Monthly Attendance Report')

@section('content')
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="form-inline">
                <label class="mr-2">Year</label>
                <input type="number" name="year" value="{{ $year }}" class="form-control mr-2" style="width:120px;">
                <label class="mr-2">Month</label>
                <input type="number" name="month" value="{{ $month }}" class="form-control mr-2" min="1" max="12" style="width:100px;">
                <button class="btn btn-secondary">Load</button>
            </form>
            <div class="mt-2 text-muted">Attendance cycle: {{ $from }} to {{ $to }}</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-sm">
                <tr><th>Employee</th><th>Working Days</th><th>Present</th><th>Absent</th><th>Late</th><th>Early Exit</th><th>Incomplete</th><th>Working Hours</th><th>Overtime</th><th>Salary Grade</th><th>Basic Salary</th></tr>
                @foreach ($rows as $row)
                    <tr>
                        <td>{{ $row['employee']->name }}</td>
                        <td>{{ $row['working_days'] }}</td>
                        <td>{{ $row['present_days'] }}</td>
                        <td>{{ $row['absent_days'] }}</td>
                        <td>{{ $row['late_count'] }}</td>
                        <td>{{ $row['early_exit_count'] }}</td>
                        <td>{{ $row['incomplete_days'] }}</td>
                        <td>{{ intdiv($row['working_minutes'], 60) }}h {{ $row['working_minutes'] % 60 }}m</td>
                        <td>{{ intdiv($row['overtime_minutes'], 60) }}h {{ $row['overtime_minutes'] % 60 }}m</td>
                        <td>{{ $row['salary_grade'] ?? 'N/A' }}</td>
                        <td>৳{{ number_format($row['basic_salary'], 2) }}</td>
                    </tr>
                @endforeach
            </table>
        </div>
    </div>
@endsection

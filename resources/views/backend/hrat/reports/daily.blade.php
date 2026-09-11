@extends('backend.master')

@section('header_css')
    <link href="{{ url('assets') }}/plugins/select2/select2.min.css" rel="stylesheet" type="text/css" />
@endsection
@section('page_title', 'Daily Attendance Report')
@section('page_heading', 'Daily Attendance Report')

@section('content')
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET">
                <div class="row">
                    <div class="col-md-2 mb-2"><label>From</label><input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control"></div>
                    <div class="col-md-2 mb-2"><label>To</label><input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control"></div>
                    <div class="col-md-3 mb-2"><label>Employee</label>@include('backend.hrat.partials.employee-select', ['employees' => $employees, 'selected' => request('employee_id')])</div>
                    <div class="col-md-2 mb-2">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="">All</option>
                            @foreach (['present','absent','late','early_exit','late_and_early_exit','incomplete','holiday','holiday_present','leave'] as $status)
                                <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-2 d-flex align-items-end">
                        <button class="btn btn-secondary mr-2">Filter</button>
                        <a href="{{ route('hrat.reports.export.csv', request()->query()) }}" class="btn btn-success">CSV Export</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-sm">
                <tr>
                    <th>SL</th><th>Employee Code</th><th>Employee</th><th>Date</th><th>First Entry</th><th>Last Exit</th>
                    <th>Entry</th><th>Exit</th><th>Working</th><th>Late</th><th>Early Exit</th><th>Overtime</th><th>Status</th><th>Source</th>
                </tr>
                @foreach ($rows as $row)
                    <tr>
                        <td>{{ $rows->firstItem() + $loop->index }}</td>
                        <td>{{ $row->employee_id }}</td>
                        <td>{{ $row->employee->name ?? 'N/A' }}</td>
                        <td>{{ optional($row->attendance_date)->format('Y-m-d') }}</td>
                        <td>{{ $row->first_entry_time }}</td>
                        <td>{{ $row->last_exit_time }}</td>
                        <td>{{ $row->total_entry_count }}</td>
                        <td>{{ $row->total_exit_count }}</td>
                        <td>{{ intdiv($row->net_working_minutes, 60) }}h {{ $row->net_working_minutes % 60 }}m</td>
                        <td>{{ $row->late_minutes }}</td>
                        <td>{{ $row->early_exit_minutes }}</td>
                        <td>{{ intdiv($row->overtime_minutes, 60) }}h {{ $row->overtime_minutes % 60 }}m</td>
                        <td><span class="badge badge-info">{{ ucfirst(str_replace('_', ' ', $row->status)) }}</span></td>
                        <td>{{ $row->source_summary }}</td>
                    </tr>
                @endforeach
            </table>
            {{ $rows->links() }}
        </div>
    </div>
@endsection

@section('footer_js')
    <script src="{{ url('assets') }}/plugins/select2/select2.min.js"></script>
    <script>$('.select2').select2({ width: '100%' });</script>
@endsection

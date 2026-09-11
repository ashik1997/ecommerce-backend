@extends('backend.master')

@section('header_css')
    <link href="{{ url('assets') }}/plugins/select2/select2.min.css" rel="stylesheet" type="text/css" />
@endsection
@section('page_title', 'Manual Attendance Entry')
@section('page_heading', 'Manual Attendance Entry')

@section('content')
    <div class="card mb-3">
        <div class="card-body">
            <form method="POST" action="{{ route('hrat.manual-entry.store') }}">
                @csrf
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label>Employee</label>
                        @include('backend.hrat.partials.employee-select', ['employees' => $employees, 'required' => true])
                    </div>
                    <div class="col-md-2 mb-3">
                        <label>Date</label>
                        <input type="date" name="attendance_date" value="{{ old('attendance_date', now()->toDateString()) }}" class="form-control" required>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label>Time</label>
                        <input type="time" name="attendance_time" value="{{ old('attendance_time', now()->format('H:i')) }}" class="form-control" required>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label>Type</label>
                        <select name="punch_type" class="form-control" required>
                            <option value="entry">Entry</option>
                            <option value="exit">Exit</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label>Reason / Note</label>
                        <input type="text" name="note" class="form-control" placeholder="Correction reason">
                    </div>
                </div>
                <button class="btn btn-success">Save Manual Attendance</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h4>Recent Logs</h4>
            <table class="table table-bordered table-sm">
                <tr><th>Employee</th><th>Date</th><th>Time</th><th>Type</th><th>Source</th><th>Note</th></tr>
                @foreach ($logs as $log)
                    <tr>
                        <td>{{ $log->employee->name ?? 'N/A' }}</td>
                        <td>{{ optional($log->attendance_date)->format('Y-m-d') }}</td>
                        <td>{{ $log->attendance_time }}</td>
                        <td>{{ ucfirst($log->punch_type) }}</td>
                        <td>{{ ucfirst($log->source) }}</td>
                        <td>{{ $log->note }}</td>
                    </tr>
                @endforeach
            </table>
            {{ $logs->links() }}
        </div>
    </div>
@endsection

@section('footer_js')
    <script src="{{ url('assets') }}/plugins/select2/select2.min.js"></script>
    <script>$('.select2').select2({ width: '100%' });</script>
@endsection

@extends('backend.master')

@section('page_title', 'HR Attendance Dashboard')
@section('page_heading', 'HR Attendance Dashboard')

@section('content')
    <div class="row">
        @foreach ([
            'present' => 'Today Present',
            'absent' => 'Today Absent',
            'late' => 'Today Late',
            'early_exit' => 'Early Exit',
            'overtime' => 'Overtime',
            'incomplete' => 'Incomplete',
        ] as $key => $label)
            <div class="col-md-2">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="text-muted">{{ $label }}</div>
                        <h3>{{ $stats[$key] ?? 0 }}</h3>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h4>Recent Manual Entries</h4>
                    <table class="table table-sm table-bordered">
                        <tr><th>Employee</th><th>Date</th><th>Time</th><th>Type</th></tr>
                        @forelse ($recentManualEntries as $log)
                            <tr>
                                <td>{{ $log->employee->name ?? 'N/A' }}</td>
                                <td>{{ optional($log->attendance_date)->format('Y-m-d') }}</td>
                                <td>{{ $log->attendance_time }}</td>
                                <td>{{ ucfirst($log->punch_type) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center">No entries found.</td></tr>
                        @endforelse
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h4>Recent CSV Imports</h4>
                    <table class="table table-sm table-bordered">
                        <tr><th>File</th><th>Success</th><th>Failed</th><th>Duplicate</th></tr>
                        @forelse ($recentImports as $batch)
                            <tr>
                                <td>{{ $batch->file_name }}</td>
                                <td>{{ $batch->success_rows }}</td>
                                <td>{{ $batch->failed_rows }}</td>
                                <td>{{ $batch->duplicate_rows }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center">No imports found.</td></tr>
                        @endforelse
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@extends('backend.master')

@section('page_title', 'CSV Preview')
@section('page_heading', 'CSV Preview')

@section('content')
    <div class="card">
        <div class="card-body">
            <a href="{{ route('hrat.csv-import.index') }}" class="btn btn-secondary mb-3">Back</a>
            <table class="table table-bordered table-sm">
                <tr><th>Row</th><th>Employee Code</th><th>Date</th><th>Time</th><th>Type</th><th>Status</th><th>Error</th></tr>
                @foreach ($previewRows as $row)
                    <tr>
                        <td>{{ $row['row_number'] }}</td>
                        <td>{{ $row['employee_code'] }}</td>
                        <td>{{ $row['attendance_date'] }}</td>
                        <td>{{ $row['attendance_time'] }}</td>
                        <td>{{ $row['punch_type'] }}</td>
                        <td><span class="badge {{ $row['valid'] ? 'badge-success' : 'badge-danger' }}">{{ $row['valid'] ? 'Valid' : 'Invalid' }}</span></td>
                        <td>{{ $row['error'] }}</td>
                    </tr>
                @endforeach
            </table>
        </div>
    </div>
@endsection

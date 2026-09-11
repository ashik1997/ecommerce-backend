@extends('backend.master')

@section('page_title', 'Attendance CSV Import')
@section('page_heading', 'Attendance CSV Import')

@section('content')
    <div class="card mb-3">
        <div class="card-body">
            <h4>Upload Attendance CSV</h4>
            <p class="text-muted">Columns: employee_code, attendance_date, attendance_time, punch_type, device_id</p>
            <form method="POST" action="{{ route('hrat.csv-import.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <input type="file" name="csv_file" class="form-control" accept=".csv,.txt" required>
                    </div>
                    <div class="col-md-6">
                        <button class="btn btn-success">Import CSV</button>
                        <button formaction="{{ route('hrat.csv-import.preview') }}" class="btn btn-info">Preview First 50 Rows</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @include('backend.hrat.csv._batch_table', ['batches' => $batches])
@endsection

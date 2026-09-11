@extends('backend.master')

@section('title')
    FB Marketing Reports
@endsection

@section('content')
    @php
        $label = fn($value) => ucwords(str_replace('_', ' ', (string) $value));
    @endphp
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="page-title mb-1">Reports</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('fbMarketing.dashboard') }}">FB Marketing</a></li>
                            <li class="breadcrumb-item active">Reports</li>
                        </ul>
                    </div>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            @foreach ($report['warnings'] as $warning)
                <div class="alert alert-{{ $warning['type'] }}">{{ $warning['message'] }}</div>
            @endforeach

            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('fbMarketing.reports.index') }}">
                        <div class="row align-items-end">
                            <div class="col-md-3 mb-3">
                                <label for="report_type">Report</label>
                                <select class="form-control" id="report_type" name="report_type">
                                    @foreach ($report['report_options'] as $value => $text)
                                        <option value="{{ $value }}" {{ $report['filters']['report_type'] === $value ? 'selected' : '' }}>{{ $text }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label for="from_date">Date from</label>
                                <input class="form-control" id="from_date" type="date" name="from_date" value="{{ $report['filters']['from_date'] }}" required>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label for="to_date">Date to</label>
                                <input class="form-control" id="to_date" type="date" name="to_date" value="{{ $report['filters']['to_date'] }}" required>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label for="campaign_id">Campaign ID</label>
                                <input class="form-control" id="campaign_id" type="number" name="campaign_id" min="1" value="{{ $report['filters']['campaign_id'] ?? '' }}">
                            </div>
                            <div class="col-md-2 mb-3">
                                <label for="product_id">Product ID</label>
                                <input class="form-control" id="product_id" type="number" name="product_id" min="1" value="{{ $report['filters']['product_id'] ?? '' }}">
                            </div>
                            <div class="col-md-1 mb-3">
                                <button class="btn btn-primary btn-block" type="submit">View</button>
                            </div>
                        </div>
                    </form>
                    <form method="POST" action="{{ route('fbMarketing.reports.exports.store', request()->query()) }}">
                        @csrf
                        @foreach ($report['filters'] as $key => $value)
                            @if ($value !== null && $value !== '')
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endif
                        @endforeach
                        <input type="hidden" name="format" value="csv">
                        <button class="btn btn-outline-primary" type="submit">Export CSV</button>
                    </form>
                </div>
            </div>

            <div class="row">
                @foreach ([
                    ['Report', $report['report_options'][$report['report_type']] ?? $label($report['report_type'])],
                    ['Rows', number_format($report['row_count'])],
                    ['Preview rows', number_format(count($report['rows']))],
                    ['Format', 'CSV'],
                ] as $card)
                    <div class="col-xl-3 col-md-4 col-sm-6 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <p class="text-muted mb-1">{{ $card[0] }}</p>
                                <h4 class="mb-0">{{ $card[1] }}</h4>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Preview</h5>
                    <p class="text-muted">Preview and CSV export use browser-safe local report projections only.</p>
                    @if (empty($report['rows']))
                        <p class="text-muted mb-0">No rows matched this report and filter set.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead>
                                    <tr>
                                        @foreach ($report['columns'] as $column)
                                            <th>{{ $label($column) }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($report['rows'] as $row)
                                        <tr>
                                            @foreach ($row as $value)
                                                <td>{{ is_scalar($value) || $value === null ? $value : json_encode($value) }}</td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Recent exports</h5>
                    @if (empty($report['recent_exports']))
                        <p class="text-muted mb-0">No export has been generated yet.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead><tr><th>Report</th><th>Format</th><th>Status</th><th>Rows</th><th>Generated</th><th>Downloaded</th><th>Note</th></tr></thead>
                                <tbody>
                                    @foreach ($report['recent_exports'] as $export)
                                        <tr>
                                            <td>{{ $export['report_label'] }}</td>
                                            <td>{{ strtoupper($export['format']) }}</td>
                                            <td>{{ $label($export['status']) }}</td>
                                            <td>{{ number_format($export['row_count']) }}</td>
                                            <td>{{ $export['generated_at'] ?: 'Unavailable' }}</td>
                                            <td>{{ $export['downloaded_at'] ?: 'Not downloaded' }}</td>
                                            <td>{{ $export['safe_error'] ?: 'Ready' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

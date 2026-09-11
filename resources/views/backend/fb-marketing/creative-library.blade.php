@extends('backend.master')

@section('title')
    FB Marketing Creative Library
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
                        <h3 class="page-title mb-1">Creative Library</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('fbMarketing.dashboard') }}">FB Marketing</a></li>
                            <li class="breadcrumb-item active">Creative Library</li>
                        </ul>
                    </div>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @foreach ($report['warnings'] as $warning)
                <div class="alert alert-{{ $warning['type'] }}">{{ $warning['message'] }}</div>
            @endforeach

            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('fbMarketing.creative-library.index') }}">
                        <div class="row align-items-end">
                            <div class="col-md-3 mb-3">
                                <label for="asset_type">Asset type</label>
                                <select class="form-control" id="asset_type" name="asset_type">
                                    <option value="">All types</option>
                                    @foreach ($report['asset_type_options'] as $value => $text)
                                        <option value="{{ $value }}" {{ $report['filters']['asset_type'] === $value ? 'selected' : '' }}>{{ $text }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="status">Status</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="">All statuses</option>
                                    @foreach ($report['status_options'] as $value => $text)
                                        <option value="{{ $value }}" {{ $report['filters']['status'] === $value ? 'selected' : '' }}>{{ $text }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
                                <button class="btn btn-primary btn-block" type="submit">Apply</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row">
                @foreach ([
                    ['Assets', number_format($report['summary']['asset_count'])],
                    ['Ready', number_format($report['summary']['ready_count'])],
                    ['Draft', number_format($report['summary']['draft_count'])],
                    ['Failed preflight', number_format($report['summary']['failed_preflight_count'])],
                ] as $card)
                    <div class="col-xl-3 col-md-4 col-sm-6 mb-3">
                        <div class="card h-100"><div class="card-body"><p class="text-muted mb-1">{{ $card[0] }}</p><h4 class="mb-0">{{ $card[1] }}</h4></div></div>
                    </div>
                @endforeach
            </div>

            @if ($canManageAssets && $report['schema_ready'])
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Add local creative asset</h5>
                        <form method="POST" action="{{ route('fbMarketing.creative-library.assets.store', request()->query()) }}">
                            @csrf
                            <div class="row align-items-end">
                                <div class="col-md-2 mb-3">
                                    <label>Type</label>
                                    <select class="form-control" name="asset_type">
                                        @foreach ($report['asset_type_options'] as $value => $text)
                                            <option value="{{ $value }}">{{ $text }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3"><label>Title</label><input class="form-control" type="text" name="title" maxlength="180" required></div>
                                <div class="col-md-3 mb-3"><label>Headline</label><input class="form-control" type="text" name="headline" maxlength="255"></div>
                                <div class="col-md-2 mb-3"><label>Media file ID</label><input class="form-control" type="number" name="media_file_id" min="1"></div>
                                <div class="col-md-2 mb-3">
                                    <label>Status</label>
                                    <select class="form-control" name="status">
                                        @foreach ($report['status_options'] as $value => $text)
                                            <option value="{{ $value }}">{{ $text }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3"><label>Primary text</label><input class="form-control" type="text" name="primary_text" maxlength="1000"></div>
                                <div class="col-md-3 mb-3"><label>External asset URL</label><input class="form-control" type="url" name="external_asset_url" maxlength="1000"></div>
                                <div class="col-md-3 mb-3"><label>Landing URL</label><input class="form-control" type="url" name="landing_url" maxlength="1000"></div>
                                <div class="col-md-2 mb-3"><label>CTA</label><input class="form-control" type="text" name="call_to_action" maxlength="80"></div>
                                <div class="col-md-2 mb-3"><label>UTM source</label><input class="form-control" type="text" name="utm_source" maxlength="80"></div>
                                <div class="col-md-2 mb-3"><label>UTM medium</label><input class="form-control" type="text" name="utm_medium" maxlength="80"></div>
                                <div class="col-md-3 mb-3"><label>UTM campaign</label><input class="form-control" type="text" name="utm_campaign" maxlength="160"></div>
                                <div class="col-md-4 mb-3"><label>Description</label><input class="form-control" type="text" name="description" maxlength="500"></div>
                                <div class="col-md-1 mb-3"><button class="btn btn-primary btn-block" type="submit">Save</button></div>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Local creative assets</h5>
                    <p class="text-muted">Assets and preflight checks are local only. No Meta upload or publish action is performed.</p>
                    @if (empty($report['rows']))
                        <p class="text-muted mb-0">No creative asset matched the current filters.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead><tr><th>Asset</th><th>Copy</th><th>Media</th><th>UTM</th><th>Preflight</th><th>Status</th><th>Action</th></tr></thead>
                                <tbody>
                                    @foreach ($report['rows'] as $row)
                                        <tr>
                                            <td>#{{ $row['id'] }} · {{ $row['title'] }}<br><small class="text-muted">{{ $label($row['asset_type']) }} · {{ $row['created_at'] }}</small></td>
                                            <td>{{ $row['headline'] ?: 'No headline' }}<br><small class="text-muted">{{ $row['primary_text_preview'] ?: 'No primary text' }}</small></td>
                                            <td>{{ $row['has_media'] ? 'Media file linked' : 'No media file' }}<br><small class="text-muted">{{ $row['has_external_asset_url'] ? 'External URL present' : 'No external URL' }}</small></td>
                                            <td>{{ $row['has_landing_url'] ? 'Landing URL present' : 'No landing URL' }}<br><small class="text-muted">{{ $row['utm_source'] }} / {{ $row['utm_medium'] }} / {{ $row['utm_campaign'] }}</small></td>
                                            <td>{{ $label($row['preflight_status']) }}<br><small class="text-muted">{{ $row['preflight_issues'] }} issue(s) · {{ $row['preflight_checked_at'] ?: 'Not checked' }}</small></td>
                                            <td><span class="badge badge-{{ $row['status'] === 'ready' ? 'success' : 'secondary' }}">{{ $label($row['status']) }}</span></td>
                                            <td>
                                                @if ($canRunPreflight)
                                                    <form method="POST" action="{{ route('fbMarketing.creative-library.assets.preflight', [$row['id']] + request()->query()) }}">
                                                        @csrf
                                                        <button class="btn btn-sm btn-outline-primary" type="submit">Preflight</button>
                                                    </form>
                                                @else
                                                    <span class="text-muted">No access</span>
                                                @endif
                                            </td>
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

@extends('backend.master')

@section('title')
    FB Marketing Audiences
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
                        <h3 class="page-title mb-1">Audiences</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('fbMarketing.dashboard') }}">FB Marketing</a></li>
                            <li class="breadcrumb-item active">Audiences</li>
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
                    <form method="GET" action="{{ route('fbMarketing.audiences.index') }}">
                        <div class="row align-items-end">
                            <div class="col-md-3 mb-3">
                                <label>Audience type</label>
                                <select class="form-control" name="audience_type">
                                    <option value="">All types</option>
                                    @foreach ($report['audience_type_options'] as $value => $text)
                                        <option value="{{ $value }}" {{ $report['filters']['audience_type'] === $value ? 'selected' : '' }}>{{ $text }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>Status</label>
                                <select class="form-control" name="status">
                                    <option value="">All statuses</option>
                                    @foreach ($report['status_options'] as $value => $text)
                                        <option value="{{ $value }}" {{ $report['filters']['status'] === $value ? 'selected' : '' }}>{{ $text }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>Selection</label>
                                <select class="form-control" name="selection">
                                    <option value="">All</option>
                                    <option value="selected" {{ $report['filters']['selection'] === 'selected' ? 'selected' : '' }}>Selected</option>
                                    <option value="unselected" {{ $report['filters']['selection'] === 'unselected' ? 'selected' : '' }}>Unselected</option>
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
                    ['Audiences', number_format($report['summary']['audience_count'])],
                    ['Selected audiences', number_format($report['summary']['selected_audience_count'])],
                    ['Custom audiences', number_format($report['summary']['custom_audience_count'])],
                    ['Product sets', number_format($report['summary']['product_set_count'])],
                    ['Selected product sets', number_format($report['summary']['selected_product_set_count'])],
                ] as $card)
                    <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                        <div class="card h-100"><div class="card-body"><p class="text-muted mb-1">{{ $card[0] }}</p><h4 class="mb-0">{{ $card[1] }}</h4></div></div>
                    </div>
                @endforeach
            </div>

            @if ($canRunSync && $report['schema_ready'] && !empty($report['connections']))
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Read-only audience sync</h5>
                        <div class="row">
                            @foreach ($report['connections'] as $connection)
                                <div class="col-md-4 mb-2">
                                    <form method="POST" action="{{ route('fbMarketing.audiences.sync', [$connection['id']]) }}">
                                        @csrf
                                        <button class="btn btn-outline-primary btn-block" type="submit">{{ $connection['connection_name'] }}</button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            @if ($canManageAudiences && $report['schema_ready'])
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Add local audience plan</h5>
                        <form method="POST" action="{{ route('fbMarketing.audiences.store', request()->query()) }}">
                            @csrf
                            <div class="row align-items-end">
                                <div class="col-md-2 mb-3">
                                    <label>Type</label>
                                    <select class="form-control" name="audience_type">
                                        @foreach ($report['audience_type_options'] as $value => $text)
                                            <option value="{{ $value }}">{{ $text }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3"><label>Name</label><input class="form-control" type="text" name="audience_name" maxlength="255" required></div>
                                <div class="col-md-2 mb-3"><label>Subtype</label><input class="form-control" type="text" name="subtype" maxlength="80"></div>
                                <div class="col-md-2 mb-3"><label>Approx. count</label><input class="form-control" type="number" name="approximate_count" min="0"></div>
                                <div class="col-md-2 mb-3">
                                    <label>Status</label>
                                    <select class="form-control" name="status">
                                        @foreach ($report['status_options'] as $value => $text)
                                            <option value="{{ $value }}">{{ $text }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3"><label>Planned use</label><input class="form-control" type="text" name="planned_use" maxlength="120"></div>
                                <div class="col-md-3 mb-3"><label>Consent basis</label><input class="form-control" type="text" name="consent_basis" maxlength="120"></div>
                                <div class="col-md-2 mb-3"><label>Retention days</label><input class="form-control" type="number" name="retention_days" min="1" max="3650"></div>
                                <div class="col-md-4 mb-3"><label>Consent note</label><input class="form-control" type="text" name="consent_note" maxlength="500"></div>
                                <div class="col-md-5 mb-3"><label>Description</label><input class="form-control" type="text" name="description" maxlength="500"></div>
                                <div class="col-md-1 mb-3"><button class="btn btn-primary btn-block" type="submit">Save</button></div>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Audience library</h5>
                    @if (empty($report['audiences']))
                        <p class="text-muted mb-0">No audience matched the current filters.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead><tr><th>Audience</th><th>Source</th><th>Consent</th><th>Status</th><th>Selection</th></tr></thead>
                                <tbody>
                                    @foreach ($report['audiences'] as $row)
                                        <tr>
                                            <td>{{ $row['audience_name'] }}<br><small class="text-muted">{{ $label($row['audience_type']) }} · {{ $row['subtype'] ?: 'No subtype' }}</small></td>
                                            <td>{{ $row['ad_account_name'] ?: 'Local plan' }}<br><small class="text-muted">{{ $row['approximate_count'] === null ? 'No size estimate' : number_format($row['approximate_count']) . ' people' }}</small></td>
                                            <td>{{ $row['consent_basis'] ?: 'Not recorded' }}<br><small class="text-muted">{{ $row['consent_note'] ? 'Consent note recorded' : 'No consent note' }}</small></td>
                                            <td><span class="badge badge-{{ $row['status'] === 'ready' ? 'success' : 'secondary' }}">{{ $label($row['status']) }}</span><br><small class="text-muted">{{ $row['last_synced_at'] ?: $row['created_at'] }}</small></td>
                                            <td>
                                                @if ($canManageSelection)
                                                    <form method="POST" action="{{ route('fbMarketing.audiences.selection.update', [$row['id']] + request()->query()) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="is_selected" value="{{ $row['is_selected'] ? 0 : 1 }}">
                                                        <input type="hidden" name="planned_use" value="{{ $row['planned_use'] }}">
                                                        <input type="hidden" name="consent_note" value="{{ $row['consent_note'] }}">
                                                        <button class="btn btn-sm btn-{{ $row['is_selected'] ? 'outline-secondary' : 'outline-primary' }}" type="submit">{{ $row['is_selected'] ? 'Deselect' : 'Select' }}</button>
                                                    </form>
                                                @else
                                                    <span class="badge badge-{{ $row['is_selected'] ? 'primary' : 'light' }}">{{ $row['is_selected'] ? 'Selected' : 'Available' }}</span>
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

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Product sets</h5>
                    @if (empty($report['product_sets']))
                        <p class="text-muted mb-0">No product set mirror is available yet.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead><tr><th>Product set</th><th>Catalog</th><th>Items</th><th>Status</th><th>Selection</th></tr></thead>
                                <tbody>
                                    @foreach ($report['product_sets'] as $row)
                                        <tr>
                                            <td>{{ $row['set_name'] ?: 'Unnamed product set' }}<br><small class="text-muted">{{ $row['planned_use'] ?: 'No planned use' }}</small></td>
                                            <td>{{ $row['catalog_name'] ?: 'Unknown catalog' }}</td>
                                            <td>{{ $row['item_count'] === null ? 'Unknown' : number_format($row['item_count']) }}</td>
                                            <td><span class="badge badge-{{ $row['is_available'] ? 'success' : 'secondary' }}">{{ $row['is_available'] ? 'Available' : 'Unavailable' }}</span><br><small class="text-muted">{{ $row['last_seen_at'] ?: 'Not seen yet' }}</small></td>
                                            <td>
                                                @if ($canManageSelection)
                                                    <form method="POST" action="{{ route('fbMarketing.audiences.product-sets.selection.update', [$row['id']] + request()->query()) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="is_selected" value="{{ $row['is_selected'] ? 0 : 1 }}">
                                                        <input type="hidden" name="planned_use" value="{{ $row['planned_use'] }}">
                                                        <input type="hidden" name="consent_note" value="{{ $row['consent_note'] }}">
                                                        <button class="btn btn-sm btn-{{ $row['is_selected'] ? 'outline-secondary' : 'outline-primary' }}" type="submit">{{ $row['is_selected'] ? 'Deselect' : 'Select' }}</button>
                                                    </form>
                                                @else
                                                    <span class="badge badge-{{ $row['is_selected'] ? 'primary' : 'light' }}">{{ $row['is_selected'] ? 'Selected' : 'Available' }}</span>
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

            <div class="row">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Recent syncs</h5>
                            @forelse ($report['sync_runs'] as $run)
                                <p class="mb-2">{{ $label($run['status']) }} · {{ $run['connection_name'] }}<br><small class="text-muted">{{ number_format($run['saved_audience_count']) }} saved · {{ number_format($run['custom_audience_count']) }} custom · {{ $run['completed_at'] }}</small></p>
                            @empty
                                <p class="text-muted mb-0">No audience sync run has been recorded.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Selection audit</h5>
                            @forelse ($report['audits'] as $audit)
                                <p class="mb-2">{{ $label($audit['object_type']) }} #{{ $audit['object_id'] }} {{ $label($audit['action']) }}<br><small class="text-muted">{{ $audit['created_at'] }}</small></p>
                            @empty
                                <p class="text-muted mb-0">No audience or product-set selection change has been recorded.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

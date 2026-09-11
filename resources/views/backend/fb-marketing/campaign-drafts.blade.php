@extends('backend.master')

@section('title')
    FB Marketing Campaign Drafts
@endsection

@section('content')
    @php
        $label = fn($value) => ucwords(str_replace('_', ' ', strtolower((string) $value)));
    @endphp
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="page-title mb-1">Campaign Drafts</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('fbMarketing.dashboard') }}">FB Marketing</a></li>
                            <li class="breadcrumb-item active">Campaign Drafts</li>
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
            @foreach ($publishEngine['warnings'] as $warning)
                <div class="alert alert-{{ $warning['type'] }}">{{ $warning['message'] }}</div>
            @endforeach
            @foreach ($operationalActions['warnings'] as $warning)
                <div class="alert alert-{{ $warning['type'] }}">{{ $warning['message'] }}</div>
            @endforeach
            @if ($publishEngine['schema_ready'] && !$publishEngine['provider_writes_enabled'])
                <div class="alert alert-info">Provider writes are disabled. Publish actions create idempotent local attempt and step ledgers only.</div>
            @endif
            @if ($operationalActions['schema_ready'] && !$operationalActions['provider_writes_enabled'])
                <div class="alert alert-info">Operational provider writes are disabled. Pause, resume, budget and schedule actions are logged locally only.</div>
            @endif

            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('fbMarketing.campaign-drafts.index') }}">
                        <div class="row align-items-end">
                            <div class="col-md-3 mb-3">
                                <label>Status</label>
                                <select class="form-control" name="status">
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
                    ['Drafts', number_format($report['summary']['draft_count'])],
                    ['Submitted', number_format($report['summary']['submitted_count'])],
                    ['Approved', number_format($report['summary']['approved_count'])],
                    ['Rejected', number_format($report['summary']['rejected_count'])],
                ] as $card)
                    <div class="col-xl-3 col-md-4 col-sm-6 mb-3">
                        <div class="card h-100"><div class="card-body"><p class="text-muted mb-1">{{ $card[0] }}</p><h4 class="mb-0">{{ $card[1] }}</h4></div></div>
                    </div>
                @endforeach
            </div>

            @if ($canManageDrafts && $report['schema_ready'])
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Create campaign draft</h5>
                        <form method="POST" action="{{ route('fbMarketing.campaign-drafts.store', request()->query()) }}">
                            @csrf
                            <div class="row align-items-end">
                                <div class="col-md-3 mb-3"><label>Name</label><input class="form-control" type="text" name="draft_name" maxlength="255" required></div>
                                <div class="col-md-2 mb-3">
                                    <label>Objective</label>
                                    <select class="form-control" name="objective">
                                        @foreach ($report['objective_options'] as $value => $text)
                                            <option value="{{ $value }}">{{ $text }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label>Budget type</label>
                                    <select class="form-control" name="budget_type"><option value="daily">Daily</option><option value="lifetime">Lifetime</option></select>
                                </div>
                                <div class="col-md-2 mb-3"><label>Budget</label><input class="form-control" type="number" step="0.01" name="budget_amount" min="1" required></div>
                                <div class="col-md-1 mb-3"><label>Currency</label><input class="form-control" type="text" name="currency" maxlength="10" value="BDT"></div>
                                <div class="col-md-2 mb-3">
                                    <label>Ad account</label>
                                    <select class="form-control" name="fbm_ad_account_id"><option value="">Select</option>@foreach ($report['ad_accounts'] as $item)<option value="{{ $item['id'] }}">{{ $item['label'] }}</option>@endforeach</select>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label>Connection</label>
                                    <select class="form-control" name="fbm_connection_id"><option value="">Select</option>@foreach ($report['connections'] as $item)<option value="{{ $item['id'] }}">{{ $item['label'] }}</option>@endforeach</select>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label>Page</label>
                                    <select class="form-control" name="fbm_page_id"><option value="">Select</option>@foreach ($report['pages'] as $item)<option value="{{ $item['id'] }}">{{ $item['label'] }}</option>@endforeach</select>
                                </div>
                                <div class="col-md-2 mb-3"><label>Start</label><input class="form-control" type="datetime-local" name="starts_at"></div>
                                <div class="col-md-2 mb-3"><label>End</label><input class="form-control" type="datetime-local" name="ends_at"></div>
                                <div class="col-md-2 mb-3"><label>Optimization</label><input class="form-control" type="text" name="optimization_goal" maxlength="120"></div>
                                <div class="col-md-2 mb-3"><label>Billing event</label><input class="form-control" type="text" name="billing_event" maxlength="120"></div>
                                <div class="col-md-3 mb-3">
                                    <label>Creative</label>
                                    <select class="form-control" name="creative_asset_id"><option value="">Select</option>@foreach ($report['creative_assets'] as $item)<option value="{{ $item['id'] }}">{{ $item['label'] }}</option>@endforeach</select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label>Audience</label>
                                    <select class="form-control" name="audience_id"><option value="">Select</option>@foreach ($report['audiences'] as $item)<option value="{{ $item['id'] }}">{{ $item['label'] }}</option>@endforeach</select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label>Product set</label>
                                    <select class="form-control" name="product_set_id"><option value="">Select</option>@foreach ($report['product_sets'] as $item)<option value="{{ $item['id'] }}">{{ $item['label'] }}</option>@endforeach</select>
                                </div>
                                <div class="col-md-3 mb-3"><label>Destination URL</label><input class="form-control" type="url" name="destination_url" maxlength="1000"></div>
                                <div class="col-md-2 mb-3"><label>UTM source</label><input class="form-control" type="text" name="utm_source" maxlength="80"></div>
                                <div class="col-md-2 mb-3"><label>UTM medium</label><input class="form-control" type="text" name="utm_medium" maxlength="80"></div>
                                <div class="col-md-3 mb-3"><label>UTM campaign</label><input class="form-control" type="text" name="utm_campaign" maxlength="160"></div>
                                <div class="col-md-3 mb-3">
                                    <label>Special category</label>
                                    <select class="form-control" name="special_ad_categories[]" multiple>
                                        @foreach ($report['special_category_options'] as $value => $text)
                                            <option value="{{ $value }}">{{ $text }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3"><label>Notes</label><input class="form-control" type="text" name="notes" maxlength="2000"></div>
                                <div class="col-md-1 mb-3"><button class="btn btn-primary btn-block" type="submit">Save</button></div>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Draft lifecycle</h5>
                    @if (empty($report['drafts']))
                        <p class="text-muted mb-0">No campaign draft matched the current filters.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead><tr><th>Draft</th><th>Budget</th><th>Assets</th><th>Schedule</th><th>Status</th><th>Actions</th></tr></thead>
                                <tbody>
                                    @foreach ($report['drafts'] as $row)
                                        <tr>
                                            <td>#{{ $row['id'] }} · {{ $row['draft_name'] }}<br><small class="text-muted">{{ $row['ad_account_name'] ?: 'No ad account' }} · {{ $row['page_name'] ?: 'No page' }}</small></td>
                                            <td>{{ $label($row['budget_type']) }} {{ number_format($row['budget_amount'], 2) }} {{ $row['currency'] }}<br><small class="text-muted">{{ $row['objective'] }}</small></td>
                                            <td>{{ $row['asset_counts']['creative_asset'] ?? 0 }} creative · {{ $row['asset_counts']['audience'] ?? 0 }} audience · {{ $row['asset_counts']['product_set'] ?? 0 }} set</td>
                                            <td>{{ $row['starts_at'] ?: 'No start' }}<br><small class="text-muted">{{ $row['ends_at'] ?: 'No end' }}</small></td>
                                            <td><span class="badge badge-{{ $row['status'] === 'approved' ? 'success' : ($row['status'] === 'submitted' ? 'warning' : 'secondary') }}">{{ $label($row['status']) }}</span><br><small class="text-muted">v{{ $row['approval_version'] }}</small></td>
                                            <td>
                                                @if ($canSubmitDrafts && $row['can_submit'])
                                                    <form class="d-inline" method="POST" action="{{ route('fbMarketing.campaign-drafts.submit', [$row['id']] + request()->query()) }}">@csrf<button class="btn btn-sm btn-outline-primary" type="submit">Submit</button></form>
                                                @endif
                                                @if ($canApproveDrafts && $row['can_approve'])
                                                    <form class="d-inline" method="POST" action="{{ route('fbMarketing.campaign-drafts.approve', [$row['id']] + request()->query()) }}">@csrf<button class="btn btn-sm btn-outline-success" type="submit">Approve</button></form>
                                                    <form class="d-inline" method="POST" action="{{ route('fbMarketing.campaign-drafts.reject', [$row['id']] + request()->query()) }}">@csrf<button class="btn btn-sm btn-outline-danger" type="submit">Reject</button></form>
                                                @endif
                                                @if ($canPublishDrafts && $row['status'] === 'approved')
                                                    <form class="d-inline" method="POST" action="{{ route('fbMarketing.campaign-drafts.publish', [$row['id']] + request()->query()) }}">@csrf<button class="btn btn-sm btn-outline-dark" type="submit">Publish attempt</button></form>
                                                @endif
                                                @if ($canManageOperationalActions && $row['status'] === 'approved')
                                                    <button class="btn btn-sm btn-outline-secondary" type="button" data-toggle="collapse" data-target="#ops-{{ $row['id'] }}">Action</button>
                                                @endif
                                            </td>
                                        </tr>
                                        @if ($canManageOperationalActions && $row['status'] === 'approved')
                                            <tr class="collapse" id="ops-{{ $row['id'] }}">
                                                <td colspan="6">
                                                    <form method="POST" action="{{ route('fbMarketing.campaign-drafts.operational-actions.store', [$row['id']] + request()->query()) }}">
                                                        @csrf
                                                        <div class="row align-items-end">
                                                            <div class="col-md-2 mb-2">
                                                                <label>Action</label>
                                                                <select class="form-control" name="action_type">
                                                                    @foreach ($operationalActions['action_type_options'] as $value => $text)
                                                                        <option value="{{ $value }}">{{ $text }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <div class="col-md-2 mb-2">
                                                                <label>Target</label>
                                                                <select class="form-control" name="target_type">
                                                                    @foreach ($operationalActions['target_type_options'] as $value => $text)
                                                                        <option value="{{ $value }}">{{ $text }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <div class="col-md-2 mb-2">
                                                                <label>Budget type</label>
                                                                <select class="form-control" name="budget_type"><option value="">No change</option><option value="daily">Daily</option><option value="lifetime">Lifetime</option></select>
                                                            </div>
                                                            <div class="col-md-2 mb-2"><label>Budget</label><input class="form-control" type="number" step="0.01" name="budget_amount" min="1"></div>
                                                            <div class="col-md-2 mb-2"><label>Start</label><input class="form-control" type="datetime-local" name="starts_at"></div>
                                                            <div class="col-md-2 mb-2"><label>End</label><input class="form-control" type="datetime-local" name="ends_at"></div>
                                                            <div class="col-md-10 mb-2"><label>Reason</label><input class="form-control" type="text" name="reason" maxlength="500"></div>
                                                            <div class="col-md-2 mb-2"><button class="btn btn-primary btn-block" type="submit">Log action</button></div>
                                                        </div>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Approval history</h5>
                    @forelse ($report['approvals'] as $approval)
                        <p class="mb-2">Draft #{{ $approval['fbm_campaign_draft_id'] }} {{ $label($approval['action']) }}: {{ $label($approval['from_status']) }} → {{ $label($approval['to_status']) }}<br><small class="text-muted">{{ $approval['created_at'] }}</small></p>
                    @empty
                        <p class="text-muted mb-0">No approval action has been recorded.</p>
                    @endforelse
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Publish attempts</h5>
                    @forelse ($publishEngine['attempts'] as $attempt)
                        <p class="mb-2">Draft #{{ $attempt['fbm_campaign_draft_id'] }} · {{ $attempt['draft_name'] ?: 'Campaign draft' }} · {{ $label($attempt['status']) }}<br><small class="text-muted">{{ $label($attempt['execution_mode']) }} · {{ $attempt['redacted_message'] ?: 'No message' }} · {{ $attempt['created_at'] }}</small></p>
                    @empty
                        <p class="text-muted mb-0">No publish attempt has been recorded.</p>
                    @endforelse
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Publish steps</h5>
                    @forelse ($publishEngine['steps'] as $step)
                        <p class="mb-2">{{ $label($step['step_key']) }} · {{ $label($step['status']) }}<br><small class="text-muted">{{ $step['graph_edge'] }} · {{ $step['redacted_message'] ?: 'No message' }}</small></p>
                    @empty
                        <p class="text-muted mb-0">No publish step has been recorded.</p>
                    @endforelse
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Operational actions</h5>
                    @forelse ($operationalActions['actions'] as $action)
                        <p class="mb-2">Draft #{{ $action['fbm_campaign_draft_id'] }} · {{ $label($action['action_type']) }} {{ $label($action['target_type']) }} · {{ $label($action['status']) }}<br><small class="text-muted">{{ $label($action['execution_mode']) }} · {{ $action['redacted_message'] ?: 'No message' }} · {{ $action['created_at'] }}</small></p>
                    @empty
                        <p class="text-muted mb-0">No operational action has been recorded.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection

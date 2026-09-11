@extends('backend.master')

@section('title')
    FB Marketing Rules & Recommendations
@endsection

@section('content')
    @php
        $label = fn($value) => ucwords(str_replace('_', ' ', strtolower((string) $value)));
        $badge = fn($severity) => ['critical' => 'danger', 'warning' => 'warning', 'info' => 'info'][$severity] ?? 'secondary';
    @endphp
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="page-title mb-1">Rules &amp; Recommendations</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('fbMarketing.dashboard') }}">FB Marketing</a></li>
                            <li class="breadcrumb-item active">Rules &amp; Recommendations</li>
                        </ul>
                    </div>
                    <div class="col-auto">
                        @if ($canRefresh && $report['schema_ready'])
                            <form method="POST" action="{{ route('fbMarketing.recommendations.refresh') }}">
                                @csrf
                                <button class="btn btn-primary btn-sm" type="submit"><i class="feather-refresh-cw mr-1"></i> Refresh</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            @foreach ($report['warnings'] as $warning)
                <div class="alert alert-{{ $warning['type'] }}">{{ $warning['message'] }}</div>
            @endforeach
            @if (session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
            @if (session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif

            <div class="alert alert-info">Recommendations are local review prompts only. Approval records an operator decision; it does not call Meta or bypass the controlled FBM-25 operational action flow.</div>

            <div class="row">
                @foreach ([
                    ['Rules', $report['summary']['rule_count']],
                    ['Suggested', $report['summary']['suggested_count']],
                    ['Approved', $report['summary']['approved_count']],
                    ['Dismissed', $report['summary']['dismissed_count']],
                ] as $card)
                    <div class="col-xl-3 col-md-4 col-sm-6 mb-3">
                        <div class="card h-100"><div class="card-body"><p class="text-muted mb-1">{{ $card[0] }}</p><h4 class="mb-0">{{ number_format($card[1]) }}</h4></div></div>
                    </div>
                @endforeach
            </div>

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Recommendation ledger</h5>
                    @if (empty($report['recommendations']))
                        <p class="text-muted mb-0">No recommendation has been generated. Refresh after Health &amp; Alerts has open alerts.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead><tr><th>Recommendation</th><th>Source</th><th>Suggested action</th><th>Status</th><th>Decision</th></tr></thead>
                                <tbody>
                                    @foreach ($report['recommendations'] as $row)
                                        <tr>
                                            <td>
                                                <span class="badge badge-{{ $badge($row['severity']) }}">{{ $label($row['severity']) }}</span>
                                                {{ $row['title'] }}
                                                <br><small class="text-muted">{{ $label($row['recommendation_type']) }} · {{ $row['last_seen_at'] }}</small>
                                            </td>
                                            <td>{{ $label($row['source_type']) }} #{{ $row['source_id'] ?: '-' }}</td>
                                            <td>{{ $row['recommended_action_type'] ? $label($row['recommended_action_type']) . ' ' . $label($row['recommended_target_type']) : 'Review only' }}</td>
                                            <td><span class="badge badge-{{ $row['status'] === 'suggested' ? 'warning' : ($row['status'] === 'approved' ? 'success' : 'secondary') }}">{{ $label($row['status']) }}</span></td>
                                            <td>
                                                @if ($canDecide && in_array($row['status'], ['suggested', 'approved'], true))
                                                    <form class="mb-1" method="POST" action="{{ route('fbMarketing.recommendations.approve', $row['recommendation_uuid']) }}">
                                                        @csrf
                                                        <input class="form-control form-control-sm mb-1" name="decision_note" maxlength="500" placeholder="Decision note">
                                                        <button class="btn btn-sm btn-outline-success" type="submit">Approve</button>
                                                    </form>
                                                    <form method="POST" action="{{ route('fbMarketing.recommendations.dismiss', $row['recommendation_uuid']) }}">
                                                        @csrf
                                                        <input type="hidden" name="decision_note" value="Dismissed from recommendation ledger">
                                                        <button class="btn btn-sm btn-outline-secondary" type="submit">Dismiss</button>
                                                    </form>
                                                @else
                                                    <small class="text-muted">{{ $row['decision_note'] ?: 'No decision note' }}</small>
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
                    <h5 class="card-title">Active rules</h5>
                    @forelse ($report['rules'] as $rule)
                        <p class="mb-2">
                            <span class="badge badge-{{ $rule['is_enabled'] ? 'success' : 'secondary' }}">{{ $rule['is_enabled'] ? 'Enabled' : 'Disabled' }}</span>
                            {{ $rule['title'] }}
                            <br><small class="text-muted">{{ $label($rule['rule_type']) }} · Approval required: {{ $rule['requires_approval'] ? 'yes' : 'no' }} · {{ $rule['redacted_message'] }}</small>
                        </p>
                    @empty
                        <p class="text-muted mb-0">No recommendation rule is available.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection

@extends('backend.master')

@section('title')
    FB Marketing Health & Alerts
@endsection

@section('content')
    @php
        $label = fn($value) => ucwords(str_replace('_', ' ', strtolower((string) $value)));
        $severityBadge = fn($severity) => ['critical' => 'danger', 'warning' => 'warning', 'info' => 'info'][$severity] ?? 'secondary';
    @endphp
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="page-title mb-1">Health & Alerts</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('fbMarketing.dashboard') }}">FB Marketing</a></li>
                            <li class="breadcrumb-item active">Health & Alerts</li>
                        </ul>
                    </div>
                    <div class="col-auto">
                        @if ($canReconcile && $report['schema_ready'])
                            <form method="POST" action="{{ route('fbMarketing.alerts.reconcile') }}">
                                @csrf
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="feather-refresh-cw mr-1"></i> Reconcile
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            @foreach ($report['warnings'] as $warning)
                <div class="alert alert-{{ $warning['type'] }}">{{ $warning['message'] }}</div>
            @endforeach

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Webhook endpoint</h5>
                    <p class="text-muted mb-0">{{ url($report['webhook_path']) }}</p>
                </div>
            </div>

            <div class="row">
                @foreach ([
                    ['Open alerts', number_format($report['summary']['open_alert_count'])],
                    ['Critical', number_format($report['summary']['critical_alert_count'])],
                    ['Webhook logs', number_format($report['summary']['webhook_log_count'])],
                    ['Reconciliations', number_format($report['summary']['reconciliation_run_count'])],
                ] as $card)
                    <div class="col-xl-3 col-md-4 col-sm-6 mb-3">
                        <div class="card h-100"><div class="card-body"><p class="text-muted mb-1">{{ $card[0] }}</p><h4 class="mb-0">{{ $card[1] }}</h4></div></div>
                    </div>
                @endforeach
            </div>

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Open alert ledger</h5>
                    @if (empty($report['alerts']))
                        <p class="text-muted mb-0">No local alert has been recorded.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead><tr><th>Alert</th><th>Source</th><th>Status</th><th>Context</th></tr></thead>
                                <tbody>
                                    @foreach ($report['alerts'] as $alert)
                                        <tr>
                                            <td>
                                                <span class="badge badge-{{ $severityBadge($alert['severity']) }}">{{ $label($alert['severity']) }}</span>
                                                {{ $alert['title'] }}
                                                <br><small class="text-muted">{{ $label($alert['alert_type']) }} · {{ $alert['last_seen_at'] }}</small>
                                            </td>
                                            <td>{{ $label($alert['source_type']) }} #{{ $alert['source_id'] ?: '-' }}</td>
                                            <td>{{ $label($alert['status']) }}</td>
                                            <td><small class="text-muted">{{ json_encode($alert['safe_context']) }}</small></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div class="row">
                <div class="col-lg-6 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">Recent ad-account webhooks</h5>
                            @forelse ($report['webhook_logs'] as $log)
                                <p class="mb-2">
                                    {{ $label($log['event_type']) }} · {{ $label($log['status']) }}
                                    <br><small class="text-muted">{{ $log['change_field'] ?: 'No field' }} · {{ $log['redacted_message'] }} · {{ $log['received_at'] }}</small>
                                </p>
                            @empty
                                <p class="text-muted mb-0">No ad-account webhook log has been recorded.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">Reconciliation runs</h5>
                            @forelse ($report['reconciliation_runs'] as $run)
                                <p class="mb-2">
                                    {{ $label($run['execution_mode']) }} · {{ $label($run['status']) }}
                                    <br><small class="text-muted">Alerts {{ $run['alert_count'] }} · Failed syncs {{ $run['failed_sync_count'] }} · {{ $run['completed_at'] ?: $run['started_at'] }}</small>
                                </p>
                            @empty
                                <p class="text-muted mb-0">No ad-account reconciliation has been run.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

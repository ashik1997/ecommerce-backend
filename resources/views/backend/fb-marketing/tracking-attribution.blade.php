@extends('backend.master')

@section('title')
    FB Marketing Tracking & Attribution
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="page-title mb-1">Tracking & Attribution</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('fbMarketing.dashboard') }}">FB Marketing</a></li>
                            <li class="breadcrumb-item active">Tracking & Attribution</li>
                        </ul>
                    </div>
                    <div class="col-auto">
                        <a class="btn btn-outline-primary" href="{{ route('fbMarketing.configuration.index') }}">Configuration</a>
                    </div>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <div class="row">
                <div class="col-lg-4 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">Landing attribution</h5>
                            <p class="text-muted">Safe readiness only. Browser identifiers, URLs and referrers remain hidden.</p>
                            <dl class="row mb-0">
                                <dt class="col-sm-6">Schema</dt><dd class="col-sm-6">{{ $landingAttributionSummary['schema_ready'] ? 'Ready' : 'FBM-12 migration required' }}</dd>
                                <dt class="col-sm-6">Capture</dt><dd class="col-sm-6">{{ $landingAttributionSummary['enabled'] ? 'Enabled' : 'Disabled' }}</dd>
                                <dt class="col-sm-6">Active sessions</dt><dd class="col-sm-6">{{ $landingAttributionSummary['session_count'] }}</dd>
                                <dt class="col-sm-6">Retention</dt><dd class="col-sm-6">{{ $landingAttributionSummary['retention_days'] }} day(s)</dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">Browser Pixel contract</h5>
                            <p class="text-muted">Secret-free consent-gated storefront contract.</p>
                            <dl class="row mb-0">
                                <dt class="col-sm-6">Schema</dt><dd class="col-sm-6">{{ $browserPixelSummary['schema_ready'] ? 'Ready' : 'FBM-13 migration required' }}</dd>
                                <dt class="col-sm-6">Mode</dt><dd class="col-sm-6">{{ ucwords(str_replace('_', ' ', $browserPixelSummary['mode'])) }}</dd>
                                <dt class="col-sm-6">Live ready</dt><dd class="col-sm-6">{{ $browserPixelSummary['live_delivery_ready'] ? 'Yes' : 'No' }}</dd>
                                <dt class="col-sm-6">Events</dt><dd class="col-sm-6">{{ implode(', ', $browserPixelSummary['supported_events']) }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">Server CAPI ledger</h5>
                            <p class="text-muted">Encrypted immutable snapshots and redacted attempts only.</p>
                            <dl class="row mb-0">
                                <dt class="col-sm-6">Schema</dt><dd class="col-sm-6">{{ $capiSummary['schema_ready'] ? 'Ready' : 'FBM-14 migration required' }}</dd>
                                <dt class="col-sm-6">Mode</dt><dd class="col-sm-6">{{ ucwords(str_replace('_', ' ', $capiSummary['mode'])) }}</dd>
                                <dt class="col-sm-6">Destination</dt><dd class="col-sm-6">{{ $capiSummary['destination_pixel_configured'] ? 'Configured' : 'Missing' }}</dd>
                                <dt class="col-sm-6">Events</dt><dd class="col-sm-6">{{ $capiSummary['event_count'] }}</dd>
                                <dt class="col-sm-6">Delivered</dt><dd class="col-sm-6">{{ $capiSummary['delivered_count'] }}</dd>
                                <dt class="col-sm-6">Retryable</dt><dd class="col-sm-6">{{ $capiSummary['retryable_count'] }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-start">
                        <div>
                            <h5 class="card-title mb-1">ERP order attribution bridge</h5>
                            <p class="text-muted mb-0">Immutable order snapshots and append-only lifecycle reconciliation. Raw identifiers, URLs, event IDs and customer values remain hidden.</p>
                        </div>
                        <span class="badge badge-{{ $orderAttributionSummary['schema_ready'] ? 'success' : 'danger' }} px-3 py-2">{{ $orderAttributionSummary['schema_ready'] ? 'FBM-15 schema ready' : 'FBM-15 migration required' }}</span>
                    </div>
                    <dl class="row mt-3 mb-3">
                        <dt class="col-sm-4">Bridged order snapshot(s)</dt><dd class="col-sm-8">{{ $orderAttributionSummary['total_orders'] }}</dd>
                        <dt class="col-sm-4">Attributed snapshot(s)</dt><dd class="col-sm-8">{{ $orderAttributionSummary['attributed_orders'] }}</dd>
                        <dt class="col-sm-4">Missing attribution session</dt><dd class="col-sm-8">{{ $orderAttributionSummary['missing_session_orders'] }}</dd>
                        <dt class="col-sm-4">Invalid or expired session</dt><dd class="col-sm-8">{{ $orderAttributionSummary['invalid_or_expired_session_orders'] }}</dd>
                        <dt class="col-sm-4">Pending / confirmed lifecycle</dt><dd class="col-sm-8">{{ $orderAttributionSummary['pending_orders'] }} / {{ $orderAttributionSummary['confirmed_orders'] }}</dd>
                        <dt class="col-sm-4">Cancelled / returned lifecycle</dt><dd class="col-sm-8">{{ $orderAttributionSummary['cancelled_orders'] }} / {{ $orderAttributionSummary['returned_orders'] }}</dd>
                        <dt class="col-sm-4">CAPI-linked snapshot(s)</dt><dd class="col-sm-8">{{ $orderAttributionSummary['capi_linked_orders'] }}</dd>
                        <dt class="col-sm-4">Pending manual delivery</dt><dd class="col-sm-8">{{ $orderAttributionSummary['pending_manual_delivery'] }}</dd>
                        <dt class="col-sm-4">Append-only reconciliation row(s)</dt><dd class="col-sm-8">{{ $orderAttributionSummary['reconciliation_count'] }}</dd>
                    </dl>
                    @if ($orderAttributionSummary['unknown_lifecycle_orders'] > 0)
                        <div class="alert alert-warning">{{ $orderAttributionSummary['unknown_lifecycle_orders'] }} order snapshot(s) have an unrecognized ERP lifecycle state. Verify the deployed order-status schema before relying on profitability reports.</div>
                    @endif
                    @if ($canReconcileOrderAttribution && $orderAttributionSummary['schema_ready'])
                        <form method="POST" action="{{ route('fbMarketing.tracking-attribution.orders.reconcile-recent') }}">
                            @csrf
                            <button class="btn btn-outline-primary" type="submit">Reconcile recent ecommerce orders</button>
                            <small class="form-text text-muted">Bounded application-local scan. Existing immutable snapshots are reused instead of duplicated.</small>
                        </form>
                    @elseif (!$canReconcileOrderAttribution)
                        <p class="text-muted mb-0">Your role does not have <code>fb_marketing_order_attribution_reconcile.update</code>.</p>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-start">
                        <div>
                            <h5 class="card-title mb-1">CAPI readiness and diagnostic</h5>
                            <p class="text-muted mb-0">Dry-run sends no Meta request. Test mode sends one synthetic Purchase tagged with the configured Meta Test Events code after explicit confirmation. Live synthetic diagnostics are intentionally unavailable.</p>
                        </div>
                        <span class="badge badge-{{ $capiSummary['schema_ready'] ? 'success' : 'danger' }} px-3 py-2">{{ $capiSummary['schema_ready'] ? 'FBM-14 schema ready' : 'FBM-14 migration required' }}</span>
                    </div>
                    <dl class="row mt-3 mb-3">
                        <dt class="col-sm-4">Active encrypted connection(s)</dt><dd class="col-sm-8">{{ $capiSummary['active_connection_count'] }}</dd>
                        <dt class="col-sm-4">CAPI token configured connection(s)</dt><dd class="col-sm-8">{{ $capiSummary['capi_token_connection_count'] }}</dd>
                        <dt class="col-sm-4">Test Events code configured connection(s)</dt><dd class="col-sm-8">{{ $capiSummary['test_code_connection_count'] }}</dd>
                        <dt class="col-sm-4">Queue readiness</dt><dd class="col-sm-8">{{ $queueReadiness['ready'] ? 'Ready' : 'Not ready; use manual no-queue attempt' }}</dd>
                    </dl>

                    @if ($canRunDiagnostic && $capiSummary['schema_ready'] && in_array($capiSummary['mode'], ['dry_run', 'test'], true))
                        <form method="POST" action="{{ route('fbMarketing.tracking-attribution.diagnostics.store') }}">
                            @csrf
                            <input type="hidden" name="diagnostic_mode" value="{{ $capiSummary['mode'] }}">
                            <div class="row align-items-end">
                                <div class="col-md-5 mb-3">
                                    <label>Encrypted connection</label>
                                    <select class="form-control" name="connection" required>
                                        <option value="">Select active connection</option>
                                        @foreach ($connections as $connection)
                                            <option value="{{ $connection['id'] }}">{{ $connection['connection_name'] }} · CAPI token {{ $connection['configured_secrets']['capi_access_token'] ? 'configured' : 'missing' }}{{ $capiSummary['mode'] === 'test' ? (' · test code ' . ($connection['configured_secrets']['capi_test_event_code'] ? 'configured' : 'missing')) : '' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @if ($capiSummary['mode'] === 'test')
                                    <div class="col-md-5 mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" id="confirm_test_delivery" type="checkbox" name="confirm_test_delivery" value="1" required>
                                            <label class="form-check-label" for="confirm_test_delivery">Send one synthetic Purchase tagged with the configured Meta Test Events code.</label>
                                        </div>
                                    </div>
                                @endif
                                <div class="col-md-2 mb-3">
                                    <button class="btn btn-primary btn-block" type="submit">{{ $capiSummary['mode'] === 'test' ? 'Run provider test' : 'Run dry-run' }}</button>
                                </div>
                            </div>
                        </form>
                    @elseif (!$canRunDiagnostic)
                        <p class="text-muted mb-0">Your role does not have <code>fb_marketing_capi_diagnostic_run.create</code>.</p>
                    @elseif ($capiSummary['mode'] === 'live')
                        <p class="text-muted mb-0">Live mode accepts production Purchase snapshots only. Synthetic live delivery is blocked intentionally.</p>
                    @else
                        <p class="text-muted mb-0">Select dry-run or test mode in Configuration before creating a diagnostic.</p>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Recent ERP order attribution snapshots</h5>
                    <p class="text-muted">Safe summaries only. Order references, landing evidence, browser identifiers, Purchase event IDs and customer values remain encrypted and hidden.</p>
                    @if ($orderAttributions->isEmpty())
                        <p class="text-muted mb-0">No ERP order attribution snapshot is available.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead><tr><th>Captured</th><th>Source</th><th>ERP order ID</th><th>Evidence</th><th>Lifecycle</th><th>Total snapshot</th><th>Quantity</th><th>CAPI linked</th></tr></thead>
                                <tbody>
                                    @foreach ($orderAttributions as $attribution)
                                        <tr>
                                            <td>{{ $attribution['snapshot_created_at'] ?: '—' }}</td>
                                            <td>{{ ucwords(str_replace('_', ' ', $attribution['source_order_type'])) }}</td>
                                            <td>#{{ $attribution['source_order_id'] }}</td>
                                            <td>{{ ucwords(str_replace('_', ' ', $attribution['evidence_state'])) }}</td>
                                            <td>{{ ucwords(str_replace('_', ' ', $attribution['lifecycle_state_current'])) }}</td>
                                            <td>{{ $attribution['currency'] }} {{ number_format($attribution['order_total_snapshot'], 2) }}</td>
                                            <td>{{ number_format($attribution['item_quantity_snapshot'], 3) }}</td>
                                            <td>{{ $attribution['conversion_event_count'] > 0 ? 'Yes' : 'No' }}</td>
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
                    <h5 class="card-title">Conversion event ledger</h5>
                    <p class="text-muted">Browser-safe rows exclude raw event IDs, Pixel IDs, URLs, identifiers, customer information and encrypted values.</p>
                    @if ($events->isEmpty())
                        <p class="text-muted mb-0">No CAPI event ledger row is available.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead><tr><th>Created</th><th>Event UUID</th><th>Connection</th><th>Event</th><th>Mode</th><th>Status</th><th>Attempts</th><th>Last attempt</th><th>Next retry</th><th>Diagnostic</th><th>Actions</th></tr></thead>
                                <tbody>
                                    @foreach ($events as $event)
                                        <tr>
                                            <td>{{ $event['created_at'] ?: '—' }}</td>
                                            <td><code>{{ $event['event_uuid'] }}</code></td>
                                            <td>{{ $event['connection_name'] ?: ('#' . $event['fbm_connection_id']) }}</td>
                                            <td>{{ $event['event_name'] }}</td>
                                            <td>{{ ucwords(str_replace('_', ' ', $event['delivery_mode'])) }}</td>
                                            <td>{{ ucwords(str_replace('_', ' ', $event['status'])) }}</td>
                                            <td>{{ $event['attempt_count'] }}</td>
                                            <td>{{ $event['last_attempt_at'] ?: '—' }}</td>
                                            <td>{{ $event['next_attempt_at'] ?: '—' }}</td>
                                            <td>{{ $event['latest_redacted_message'] ?: '—' }}</td>
                                            <td>
                                                @if ($event['status'] !== 'delivered' && $canRetryEvent)
                                                    <form class="d-inline" method="POST" action="{{ route('fbMarketing.tracking-attribution.events.retry-now', $event['event_uuid']) }}">
                                                        @csrf
                                                        <button class="btn btn-sm btn-outline-primary mb-1" type="submit">Retry now</button>
                                                    </form>
                                                @endif
                                                @if ($event['status'] !== 'delivered' && $queueReadiness['ready'] && $canDispatchEvent)
                                                    <form class="d-inline" method="POST" action="{{ route('fbMarketing.tracking-attribution.events.dispatch', $event['event_uuid']) }}">
                                                        @csrf
                                                        <button class="btn btn-sm btn-outline-secondary mb-1" type="submit">Queue</button>
                                                    </form>
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
                    <h5 class="card-title">Delivery attempt history</h5>
                    <p class="text-muted">Append-only redacted history. Request fingerprints stay hidden.</p>
                    @if ($attempts->isEmpty())
                        <p class="text-muted mb-0">No CAPI delivery attempt is available.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead><tr><th>Attempted</th><th>Event UUID</th><th>Attempt</th><th>Origin</th><th>Mode</th><th>Status</th><th>HTTP</th><th>Provider code</th><th>Duration</th><th>Diagnostic</th></tr></thead>
                                <tbody>
                                    @foreach ($attempts as $attempt)
                                        <tr>
                                            <td>{{ $attempt['attempted_at'] ?: '—' }}</td>
                                            <td><code>{{ $attempt['event_uuid'] ?: '—' }}</code></td>
                                            <td>{{ $attempt['attempt_number'] }}</td>
                                            <td>{{ ucwords(str_replace('_', ' ', $attempt['origin'])) }}</td>
                                            <td>{{ ucwords(str_replace('_', ' ', $attempt['delivery_mode'])) }}</td>
                                            <td>{{ ucwords(str_replace('_', ' ', $attempt['status'])) }}</td>
                                            <td>{{ $attempt['http_status'] ?? '—' }}</td>
                                            <td>{{ $attempt['provider_error_code'] ?: '—' }}{{ $attempt['provider_error_subcode'] ? (' / ' . $attempt['provider_error_subcode']) : '' }}</td>
                                            <td>{{ $attempt['duration_ms'] === null ? '—' : ($attempt['duration_ms'] . ' ms') }}</td>
                                            <td>{{ $attempt['redacted_message'] ?: '—' }}</td>
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

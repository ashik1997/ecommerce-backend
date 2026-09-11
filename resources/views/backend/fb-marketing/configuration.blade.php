@extends('backend.master')

@section('title')
    FB Marketing Configuration
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="page-title mb-1">FB Marketing Configuration</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('fbMarketing.dashboard') }}">FB Marketing</a></li>
                            <li class="breadcrumb-item active">Configuration</li>
                        </ul>
                    </div>
                    <div class="col-auto">
                        @if ($canViewPerformance)
                            <a class="btn btn-outline-info mr-2" href="{{ route('fbMarketing.performance.index') }}">Performance</a>
                        @endif
                        @if ($canViewCatalog)
                            <a class="btn btn-outline-success mr-2" href="{{ route('fbMarketing.products-catalog.index') }}">Products & Catalog</a>
                        @endif
                        @if ($canViewTracking)
                            <a class="btn btn-outline-dark mr-2" href="{{ route('fbMarketing.tracking-attribution.index') }}">Tracking & Attribution</a>
                        @endif
                        <a class="btn btn-outline-primary" href="{{ route('fbMarketing.configuration.setup-wizard') }}">Setup Wizard</a>
                    </div>
                </div>
            </div>

            @include('backend.fb-marketing._status-card')

            @unless ($insightsSummary['schema_ready'])
                <div class="alert alert-warning"><strong>FBM-08 migration required.</strong> Apply the Ads Insights snapshot migration before daily refresh or historical backfill can run.</div>
            @endunless

            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-start mb-3">
                        <div>
                            <h5 class="card-title mb-1">Ads Insights snapshot readiness</h5>
                            <p class="text-muted mb-0">Operational counts only. Provider identifiers, raw payloads and async report keys remain hidden.</p>
                        </div>
                        <span class="badge badge-{{ $insightsSummary['schema_ready'] ? 'success' : 'danger' }} px-3 py-2">{{ $insightsSummary['schema_ready'] ? 'FBM-08 schema ready' : 'Migration required' }}</span>
                    </div>
                    <div class="row">
                        <div class="col-lg-3 col-md-6 mb-2"><div class="border rounded p-3 h-100"><div class="text-muted small">Daily snapshots</div><div class="h4 mb-0">{{ number_format($insightsSummary['snapshot_count']) }}</div></div></div>
                        <div class="col-lg-3 col-md-6 mb-2"><div class="border rounded p-3 h-100"><div class="text-muted small">Report runs</div><div class="h4 mb-0">{{ number_format($insightsSummary['report_count']) }}</div></div></div>
                        <div class="col-lg-3 col-md-6 mb-2"><div class="border rounded p-3 h-100"><div class="text-muted small">Latest snapshot</div><strong>{{ $insightsSummary['latest_snapshot_date'] ?: '—' }}</strong></div></div>
                        <div class="col-lg-3 col-md-6 mb-2"><div class="border rounded p-3 h-100"><div class="text-muted small">Freshness watermark</div><strong>{{ $insightsSummary['freshness_watermark'] ?: '—' }}</strong></div></div>
                    </div>
                    @if ($insightsSummary['latest_report'])
                        <p class="text-muted mb-0 mt-2">Latest report: <strong>{{ ucwords(str_replace('_', ' ', $insightsSummary['latest_report']['status'])) }}</strong> · {{ ucwords($insightsSummary['latest_report']['execution_mode']) }} · {{ ucwords($insightsSummary['latest_report']['insight_level']) }} level · {{ $insightsSummary['latest_report']['row_count'] }} row(s)</p>
                    @endif
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    <strong>The FB MARKETING configuration could not be saved.</strong>
                    <ul class="mb-0 mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @unless ($vaultReady)
                <div class="alert alert-danger">
                    <strong>Application migration required.</strong> Run the application migrations before saving any Meta credential. The configuration page remains read-only until both vault tables exist.
                </div>
            @endunless

            @if ($vaultReady && !$healthReady)
                <div class="alert alert-warning">
                    <strong>FBM-03 migration required.</strong> Credentials remain available, but connection tests stay disabled until the health-ledger migration is applied.
                </div>
            @endif

            @if (!$assetDiscoveryReady)
                <div class="alert alert-warning">
                    <strong>FBM-04 migration required.</strong> Apply the asset-discovery migration before importing or selecting Meta assets.
                </div>
            @endif

            @if (!$catalogSummary['schema_ready'])
                <div class="alert alert-warning">
                    <strong>FBM-11 migration required.</strong> Apply the catalog mapping migration before mirroring catalog items, product sets or local ERP mapping overrides.
                </div>
            @elseif ($canViewCatalog)
                <div class="alert alert-info">
                    <strong>FBM-11 catalog mapping is ready.</strong> Selected catalogs: {{ $catalogSummary['selected_catalogs'] }} · mapped items: {{ $catalogSummary['automatic_mapping_count'] + $catalogSummary['manual_mapping_count'] }} · unmatched: {{ $catalogSummary['unmatched_count'] + $catalogSummary['ambiguous_count'] }}. <a href="{{ route('fbMarketing.products-catalog.index') }}">Open Products & Catalog</a>.
                </div>
            @endif

            @if (!$syncReady)
                <div class="alert alert-warning">
                    <strong>FBM-06 queue readiness required for background sync.</strong> {{ $syncReadiness['message'] }}
                </div>
                @if ($manualSyncReady)
                    <div class="alert alert-info">
                        <strong>Manual no-queue fallback is available.</strong> Use the connection-level <em>Run manual sync now</em> action for bounded application testing. Historical backfill remains deferred until the dedicated queue worker is available.
                    </div>
                @elseif ($manualSyncEnabled)
                    <div class="alert alert-warning">
                        <strong>Manual no-queue fallback needs prior migrations.</strong> Apply FBM-06 through FBM-08 tables before running the request-bound test action.
                    </div>
                @endif
            @endif

            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-start mb-3">
                        <div>
                            <h5 class="card-title mb-1">Isolated FB MARKETING module configuration</h5>
                            <p class="text-muted mb-0">This module keeps its own application settings and encrypted vault. Existing stable General Information Pixel settings are not imported, overwritten or deleted.</p>
                        </div>
                        <span class="badge badge-{{ $feedDiagnostics['settings_schema_ready'] ? 'success' : 'danger' }}">{{ $feedDiagnostics['settings_schema_ready'] ? 'FBM-05 schema ready' : 'FBM-05 migration required' }}</span>
                    </div>

                    <div class="row">
                        <div class="col-lg-6 mb-3">
                            <div class="border rounded p-3 h-100">
                                <h6>Product-feed settings</h6>
                                @if ($feedDiagnostics['settings_schema_ready'] && $canManageModuleSettings)
                                    <form method="POST" action="{{ route('fbMarketing.configuration.module-settings.update') }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="feed_enabled" value="0">
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" name="feed_enabled" id="fbm_feed_enabled" value="1" {{ $moduleSettings['feed_enabled'] ? 'checked' : '' }}>
                                            <label class="form-check-label" for="fbm_feed_enabled">Enable public catalog feed</label>
                                        </div>
                                        <div class="form-group">
                                            <label for="fbm_feed_cache_ttl">Cache TTL in minutes</label>
                                            <input class="form-control" type="number" min="5" max="1440" name="feed_cache_ttl_minutes" id="fbm_feed_cache_ttl" value="{{ $moduleSettings['feed_cache_ttl_minutes'] }}" required>
                                        </div>
                                        <hr>
                                        <h6>Scheduled read-only sync</h6>
                                        <input type="hidden" name="scheduled_sync_enabled" value="0">
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" name="scheduled_sync_enabled" id="fbm_scheduled_sync_enabled" value="1" {{ $moduleSettings['scheduled_sync_enabled'] ? 'checked' : '' }}>
                                            <label class="form-check-label" for="fbm_scheduled_sync_enabled">Enable scheduled sync dispatch</label>
                                        </div>
                                        <div class="form-group">
                                            <label for="fbm_scheduled_sync_interval">Dispatch interval in minutes</label>
                                            <input class="form-control" type="number" min="15" max="1440" name="scheduled_sync_interval_minutes" id="fbm_scheduled_sync_interval" value="{{ $moduleSettings['scheduled_sync_interval_minutes'] }}" required>
                                            <small class="text-muted">Provider calls still run only through the dedicated FB MARKETING worker.</small>
                                        </div>
                                        <hr>
                                        <h6>Landing attribution capture</h6>
                                        @if ($moduleSettings['attribution_settings_schema_ready'])
                                            <input type="hidden" name="landing_attribution_enabled" value="0">
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="checkbox" name="landing_attribution_enabled" id="fbm_landing_attribution_enabled" value="1" {{ $moduleSettings['landing_attribution_enabled'] ? 'checked' : '' }}>
                                                <label class="form-check-label" for="fbm_landing_attribution_enabled">Enable privacy-bounded landing attribution capture</label>
                                            </div>
                                            <div class="form-group">
                                                <label for="fbm_landing_attribution_retention_days">Retention window in days</label>
                                                <input class="form-control" type="number" min="1" max="365" name="landing_attribution_retention_days" id="fbm_landing_attribution_retention_days" value="{{ $moduleSettings['landing_attribution_retention_days'] }}" required>
                                                <small class="text-muted">Disabled by default. Raw browser identifiers remain encrypted at rest and never appear on this screen.</small>
                                            </div>
                                        @else
                                            <p class="text-muted">Apply the FBM-12 migration before enabling landing attribution capture.</p>
                                        @endif
                                        <hr>
                                        <h6>Browser Pixel event contract</h6>
                                        @if ($moduleSettings['browser_pixel_settings_schema_ready'])
                                            <div class="form-group">
                                                <label for="fbm_browser_pixel_mode">Delivery mode</label>
                                                <select class="form-control" name="browser_pixel_mode" id="fbm_browser_pixel_mode" required>
                                                    @foreach ($browserPixelModes as $mode)
                                                        <option value="{{ $mode }}" {{ $moduleSettings['browser_pixel_mode'] === $mode ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $mode)) }}</option>
                                                    @endforeach
                                                </select>
                                                <small class="text-muted">Disabled is fail-closed. Dry run validates storefront payloads without loading Meta. Live loads the browser Pixel script only after explicit consent.</small>
                                            </div>
                                        @else
                                            <p class="text-muted">Apply the FBM-13 migration before enabling the browser Pixel contract.</p>
                                        @endif
                                        <hr>
                                        <h6>Server Conversions API event ledger</h6>
                                        @if ($moduleSettings['server_capi_settings_schema_ready'])
                                            <div class="form-group">
                                                <label for="fbm_server_capi_mode">Server delivery mode</label>
                                                <select class="form-control" name="server_capi_mode" id="fbm_server_capi_mode" required>
                                                    @foreach ($serverCapiModes as $mode)
                                                        <option value="{{ $mode }}" {{ $moduleSettings['server_capi_mode'] === $mode ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $mode)) }}</option>
                                                    @endforeach
                                                </select>
                                                <small class="text-muted">Disabled is fail-closed. Dry run writes and validates the encrypted ledger without a Meta request. Test mode requires an encrypted Test Events code. Live mode is reserved for FBM-15 order hooks.</small>
                                            </div>
                                        @else
                                            <p class="text-muted">Apply the FBM-14 migration before enabling server-side CAPI delivery.</p>
                                        @endif
                                        <button class="btn btn-primary" type="submit">Save module settings</button>
                                    </form>
                                @elseif (!$feedDiagnostics['settings_schema_ready'])
                                    <p class="text-muted mb-0">Apply the FBM-05 migration before editing module settings.</p>
                                @else
                                    <p class="text-muted mb-0">Read-only view. Grant <code>fb_marketing_configuration_manage.update</code> to trusted operators to change these settings.</p>
                                @endif
                            </div>
                        </div>
                        <div class="col-lg-6 mb-3">
                            <div class="border rounded p-3 h-100">
                                <h6>Safe feed diagnostics</h6>
                                <dl class="row mb-0">
                                    <dt class="col-sm-5">Feed URL</dt><dd class="col-sm-7"><a href="{{ $feedDiagnostics['feed_url'] }}" target="_blank" rel="noopener">Open XML feed</a></dd>
                                    <dt class="col-sm-5">Selection mode</dt><dd class="col-sm-7">{{ str_replace('_', ' ', $feedDiagnostics['selection_mode']) }}</dd>
                                    <dt class="col-sm-5">Feed status</dt><dd class="col-sm-7"><span class="badge badge-{{ $feedDiagnostics['feed_enabled'] ? 'success' : 'secondary' }}">{{ $feedDiagnostics['feed_enabled'] ? 'Enabled' : 'Disabled' }}</span></dd>
                                    <dt class="col-sm-5">Cache TTL</dt><dd class="col-sm-7">{{ $feedDiagnostics['feed_cache_ttl_minutes'] }} minute(s)</dd>
                                    <dt class="col-sm-5">Opted-in products</dt><dd class="col-sm-7">{{ $feedDiagnostics['opted_in_products'] }}</dd>
                                    <dt class="col-sm-5">Feed-ready products</dt><dd class="col-sm-7">{{ $feedDiagnostics['feed_ready_products'] }}</dd>
                                    <dt class="col-sm-5">Selected Pixel(s)</dt><dd class="col-sm-7">{{ $feedDiagnostics['selected_pixels'] }}</dd>
                                    <dt class="col-sm-5">Selected catalog(s)</dt><dd class="col-sm-7">{{ $feedDiagnostics['selected_catalogs'] }}</dd>
                                    <dt class="col-sm-5">Last cache invalidation</dt><dd class="col-sm-7">{{ $feedDiagnostics['last_feed_cache_invalidated_at'] ?: '—' }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>

                    <div class="border rounded p-3 mb-3">
                        <div class="d-flex flex-wrap justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Landing attribution capture readiness</h6>
                                <p class="text-muted mb-0">Privacy-safe summary only. Browser identifiers, landing URLs and referrers are not displayed.</p>
                            </div>
                            <span class="badge badge-{{ !$landingAttributionSummary['schema_ready'] ? 'danger' : ($landingAttributionSummary['enabled'] ? 'success' : 'secondary') }}">
                                {{ !$landingAttributionSummary['schema_ready'] ? 'FBM-12 migration required' : ($landingAttributionSummary['enabled'] ? 'Enabled' : 'Disabled') }}
                            </span>
                        </div>
                        <dl class="row mb-0 mt-3">
                            <dt class="col-sm-4">Capture endpoint</dt><dd class="col-sm-8"><code>{{ $landingAttributionSummary['capture_endpoint'] }}</code></dd>
                            <dt class="col-sm-4">Retention</dt><dd class="col-sm-8">{{ $landingAttributionSummary['retention_days'] }} day(s)</dd>
                            <dt class="col-sm-4">Active captured sessions</dt><dd class="col-sm-8">{{ $landingAttributionSummary['session_count'] }}</dd>
                            <dt class="col-sm-4">Latest capture</dt><dd class="col-sm-8">{{ $landingAttributionSummary['latest_capture_at'] ?: '—' }}</dd>
                            <dt class="col-sm-4">Storefront helper</dt><dd class="col-sm-8"><code>/assets/js/fb-marketing/fbm-landing-attribution.js</code></dd>
                        </dl>
                    </div>

                    <div class="border rounded p-3 mb-3">
                        <div class="d-flex flex-wrap justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Browser Pixel event contract readiness</h6>
                                <p class="text-muted mb-0">Secret-free storefront contract only. API keys, CAPI tokens, test-event codes and provider asset identifiers are not exposed.</p>
                            </div>
                            <span class="badge badge-{{ !$browserPixelSummary['schema_ready'] ? 'danger' : ($browserPixelSummary['live_delivery_ready'] ? 'success' : ($browserPixelSummary['mode'] === 'dry_run' ? 'info' : 'secondary')) }}">
                                {{ !$browserPixelSummary['schema_ready'] ? 'FBM-13 migration required' : ($browserPixelSummary['live_delivery_ready'] ? 'Live ready' : ucwords(str_replace('_', ' ', $browserPixelSummary['mode']))) }}
                            </span>
                        </div>
                        <dl class="row mb-0 mt-3">
                            <dt class="col-sm-4">Delivery mode</dt><dd class="col-sm-8">{{ ucwords(str_replace('_', ' ', $browserPixelSummary['mode'])) }}</dd>
                            <dt class="col-sm-4">Legacy stable Pixel enabled</dt><dd class="col-sm-8">{{ $browserPixelSummary['legacy_pixel_status_enabled'] ? 'Yes' : 'No' }}</dd>
                            <dt class="col-sm-4">Safe Pixel ID configured</dt><dd class="col-sm-8">{{ $browserPixelSummary['pixel_id_configured'] ? 'Yes' : 'No' }}</dd>
                            <dt class="col-sm-4">Live delivery readiness</dt><dd class="col-sm-8">{{ $browserPixelSummary['live_delivery_ready'] ? 'Ready' : 'Not ready' }}</dd>
                            <dt class="col-sm-4">Config endpoint</dt><dd class="col-sm-8"><code>{{ $browserPixelSummary['configuration_endpoint'] }}</code></dd>
                            <dt class="col-sm-4">Storefront helper</dt><dd class="col-sm-8"><code>{{ $browserPixelSummary['storefront_helper'] }}</code></dd>
                            <dt class="col-sm-4">Supported events</dt><dd class="col-sm-8">{{ implode(', ', $browserPixelSummary['supported_events']) }}</dd>
                        </dl>
                    </div>

                    <div class="border rounded p-3 mb-3">
                        <div class="d-flex flex-wrap justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Server Conversions API ledger readiness</h6>
                                <p class="text-muted mb-0">Privacy-safe summary only. Tokens, Test Events codes, raw provider payloads, destination IDs and browser identifiers stay hidden.</p>
                            </div>
                            <span class="badge badge-{{ !$capiSummary['schema_ready'] ? 'danger' : ($capiSummary['live_ready'] ? 'success' : ($capiSummary['mode'] === 'dry_run' ? 'info' : ($capiSummary['mode'] === 'test' ? 'warning' : 'secondary'))) }}">
                                {{ !$capiSummary['schema_ready'] ? 'FBM-14 migration required' : ($capiSummary['live_ready'] ? 'Live ready' : ucwords(str_replace('_', ' ', $capiSummary['mode']))) }}
                            </span>
                        </div>
                        <dl class="row mb-0 mt-3">
                            <dt class="col-sm-4">Server delivery mode</dt><dd class="col-sm-8">{{ ucwords(str_replace('_', ' ', $capiSummary['mode'])) }}</dd>
                            <dt class="col-sm-4">Stable Pixel destination configured</dt><dd class="col-sm-8">{{ $capiSummary['destination_pixel_configured'] ? 'Yes' : 'No' }}</dd>
                            <dt class="col-sm-4">Active encrypted connection(s)</dt><dd class="col-sm-8">{{ $capiSummary['active_connection_count'] }}</dd>
                            <dt class="col-sm-4">Connection(s) with hidden CAPI token</dt><dd class="col-sm-8">{{ $capiSummary['capi_token_connection_count'] }}</dd>
                            <dt class="col-sm-4">Connection(s) with hidden Test Events code</dt><dd class="col-sm-8">{{ $capiSummary['test_code_connection_count'] }}</dd>
                            <dt class="col-sm-4">Conversion events / delivered / retryable</dt><dd class="col-sm-8">{{ $capiSummary['event_count'] }} / {{ $capiSummary['delivered_count'] }} / {{ $capiSummary['retryable_count'] }}</dd>
                            <dt class="col-sm-4">Latest event snapshot</dt><dd class="col-sm-8">{{ $capiSummary['latest_event_at'] ?: '—' }}</dd>
                        </dl>
                        @if ($canViewTracking)
                            <a class="btn btn-sm btn-outline-dark mt-3" href="{{ route('fbMarketing.tracking-attribution.index') }}">Open Tracking & Attribution</a>
                        @endif
                    </div>

                    <div class="border rounded p-3 mb-3">
                        @php
                            $writerBadge = [
                                'migration_required' => 'danger',
                                'not_ready' => 'warning',
                                'ready_disabled' => 'info',
                                'ready_enabled' => 'success',
                            ][$providerWriterSummary['status'] ?? 'not_ready'] ?? 'secondary';
                        @endphp
                        <div class="d-flex flex-wrap justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Provider writer foundation readiness</h6>
                                <p class="text-muted mb-0">Local production gate for live Meta campaign writes. This screen never sends a campaign or budget mutation.</p>
                            </div>
                            <span class="badge badge-{{ $writerBadge }}">
                                {{ ucwords(str_replace('_', ' ', $providerWriterSummary['status'] ?? 'not_ready')) }}
                            </span>
                        </div>
                        <dl class="row mb-0 mt-3">
                            <dt class="col-sm-4">Campaign publish writes</dt><dd class="col-sm-8">{{ $providerWriterSummary['campaign_publish_enabled'] ? 'Enabled' : 'Disabled' }}</dd>
                            <dt class="col-sm-4">Operational action writes</dt><dd class="col-sm-8">{{ $providerWriterSummary['operational_actions_enabled'] ? 'Enabled' : 'Disabled' }}</dd>
                            <dt class="col-sm-4">Ready connection(s)</dt><dd class="col-sm-8">{{ $providerWriterSummary['ready_connection_count'] }} / {{ $providerWriterSummary['active_connection_count'] }} active</dd>
                            <dt class="col-sm-4">Selected Ad Account(s)</dt><dd class="col-sm-8">{{ $providerWriterSummary['selected_ad_account_count'] }}</dd>
                            <dt class="col-sm-4">Required writer scopes</dt><dd class="col-sm-8">{{ implode(', ', $providerWriterSummary['required_scopes']) ?: '—' }}</dd>
                            <dt class="col-sm-4">Safety budget caps</dt><dd class="col-sm-8">Daily {{ number_format($providerWriterSummary['safety_policy']['max_single_daily_budget_amount'], 2) }} · Lifetime {{ number_format($providerWriterSummary['safety_policy']['max_single_lifetime_budget_amount'], 2) }}</dd>
                            <dt class="col-sm-4">Retry/idempotency policy</dt><dd class="col-sm-8">{{ $providerWriterSummary['safety_policy']['max_retry_attempts'] }} retry attempt(s) · {{ $providerWriterSummary['safety_policy']['idempotency_ttl_hours'] }} hour idempotency boundary</dd>
                        </dl>
                        <p class="text-muted mb-2">{{ $providerWriterSummary['message'] }}</p>
                        @if (!empty($providerWriterSummary['connections']))
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-0">
                                    <thead><tr><th>Connection</th><th>Health</th><th>Selected Ad Accounts</th><th>Writer scopes</th><th>Status</th></tr></thead>
                                    <tbody>
                                        @foreach ($providerWriterSummary['connections'] as $writerConnection)
                                            <tr>
                                                <td>{{ $writerConnection['connection_name'] }}</td>
                                                <td>{{ ucwords(str_replace('_', ' ', $writerConnection['latest_health_status'])) }}</td>
                                                <td>{{ $writerConnection['selected_ad_account_count'] }}</td>
                                                <td>{{ implode(', ', $writerConnection['missing_writer_scopes']) ?: 'Ready' }}</td>
                                                <td>
                                                    <span class="badge badge-{{ $writerConnection['ready_for_provider_writer'] ? 'success' : 'warning' }}">
                                                        {{ $writerConnection['ready_for_provider_writer'] ? 'Ready' : 'Needs attention' }}
                                                    </span>
                                                    <small class="text-muted d-block">{{ $writerConnection['readiness_message'] }}</small>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    <div class="border rounded p-3 mb-3">
                        @php
                            $launchReady = (bool) ($providerWriterLaunchChecklist['ready_for_signed_enablement'] ?? false);
                        @endphp
                        <div class="d-flex flex-wrap justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Final provider-writer launch gate</h6>
                                <p class="text-muted mb-0">Read-only production checklist for signed live-write enablement.</p>
                            </div>
                            <span class="badge badge-{{ $launchReady ? 'success' : 'warning' }}">
                                {{ $launchReady ? 'Ready for signed enablement' : 'Blocked' }}
                            </span>
                        </div>
                        <p class="text-muted mt-3 mb-2">{{ $providerWriterLaunchChecklist['message'] }}</p>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead><tr><th>Gate</th><th>Status</th><th>Detail</th></tr></thead>
                                <tbody>
                                    @foreach ($providerWriterLaunchChecklist['items'] as $launchItem)
                                        <tr>
                                            <td>{{ $launchItem['label'] }}</td>
                                            <td><span class="badge badge-{{ $launchItem['ready'] ? 'success' : 'warning' }}">{{ $launchItem['ready'] ? 'Pass' : 'Blocked' }}</span></td>
                                            <td><small class="text-muted">{{ $launchItem['message'] }}</small></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if (!empty($feedDiagnostics['warnings']))
                        <div class="alert alert-warning mb-0">
                            <strong>Diagnostics warnings</strong>
                            <ul class="mb-0 mt-2">
                                @foreach ($feedDiagnostics['warnings'] as $warning)
                                    <li>{{ $warning }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-start">
                        <div>
                            <h5 class="card-title mb-1">Encrypted connection vault</h5>
                            <p class="text-muted mb-0">Secret fields are replacement-only. After save, only configured/not-configured state is shown.</p>
                        </div>
                        <div class="text-right">
                            <span class="badge badge-{{ $vaultReady ? 'success' : 'danger' }} px-3 py-2">{{ $vaultReady ? 'Vault schema ready' : 'Vault schema missing' }}</span>
                            <span class="badge badge-{{ $healthReady ? 'success' : 'warning' }} px-3 py-2">{{ $healthReady ? 'Health ledger ready' : 'Health ledger missing' }}</span>
                            <span class="badge badge-{{ $assetDiscoveryReady ? 'success' : 'warning' }} px-3 py-2">{{ $assetDiscoveryReady ? 'Asset schema ready' : 'Asset schema missing' }}</span>
                            <span class="badge badge-{{ $syncReady ? 'success' : 'warning' }} px-3 py-2">{{ $syncReady ? 'Queue ready' : 'Queue readiness missing' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            @if ($vaultReady && $canCreateCredentials)
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Add encrypted Meta connection</h5>
                        <p class="text-muted">Save rotated values for server-side use. Health checks are explicit, permission-controlled and read-only.</p>

                        <form method="POST" action="{{ route('fbMarketing.configuration.connections.store') }}">
                            @csrf
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label>Connection name <span class="text-danger">*</span></label>
                                    <input class="form-control" type="text" name="connection_name" value="{{ old('connection_name') }}" maxlength="120" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label>Meta App ID</label>
                                    <input class="form-control" type="text" name="app_id" value="{{ old('app_id') }}" maxlength="120">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label>Credential mode <span class="text-danger">*</span></label>
                                    <select class="form-control" name="credential_mode" required>
                                        @foreach ($credentialModes as $mode)
                                            <option value="{{ $mode }}" {{ old('credential_mode', 'system_user') === $mode ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $mode)) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label>Graph API version</label>
                                    <select class="form-control" name="graph_api_version">
                                        @foreach ($graphApiVersions as $version)
                                            <option value="{{ $version }}" {{ old('graph_api_version', $defaultGraphApiVersion) === $version ? 'selected' : '' }}>{{ $version }}</option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Centralized application policy controls permitted versions.</small>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label>Meta App Secret</label>
                                    <input class="form-control" type="password" name="app_secret" autocomplete="new-password" maxlength="10000">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label>Meta Access Token</label>
                                    <input class="form-control" type="password" name="access_token" autocomplete="new-password" maxlength="20000">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label>Conversions API Token</label>
                                    <input class="form-control" type="password" name="capi_access_token" autocomplete="new-password" maxlength="20000">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label>Conversions API Test Events Code</label>
                                    <input class="form-control" type="password" name="capi_test_event_code" autocomplete="new-password" maxlength="10000">
                                    <small class="text-muted">Optional. Used only during an explicit server CAPI test-mode diagnostic.</small>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label>Webhook Verify Token</label>
                                    <input class="form-control" type="password" name="webhook_verify_token" autocomplete="new-password" maxlength="10000">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label>Change reason</label>
                                    <input class="form-control" type="text" name="change_reason" value="{{ old('change_reason') }}" maxlength="500" placeholder="Optional audit note">
                                </div>
                                <div class="col-12 mb-3">
                                    <label>Notes</label>
                                    <textarea class="form-control" name="notes" rows="2" maxlength="2000" placeholder="Do not paste credentials into notes.">{{ old('notes') }}</textarea>
                                </div>
                            </div>
                            <button class="btn btn-primary" type="submit">Save encrypted connection</button>
                        </form>
                    </div>
                </div>
            @elseif ($vaultReady)
                <div class="alert alert-info">You can inspect configured state, but your role does not have credential-create permission.</div>
            @endif

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Configured connections</h5>
                    <p class="text-muted">No stored secret, ciphertext, suffix fragment or reusable token is displayed.</p>

                    @if (!$vaultReady)
                        <p class="text-muted mb-0">Connection list is unavailable until the application migration is complete.</p>
                    @elseif ($connections->isEmpty())
                        <p class="text-muted mb-0">No FB MARKETING connection has been configured yet.</p>
                    @else
                        @foreach ($connections as $connection)
                            @php
                                $health = $connection['health'];
                                $healthStatus = $health['status'] ?? 'never_tested';
                                $healthBadge = ['healthy' => 'success', 'warning' => 'warning', 'failed' => 'danger', 'never_tested' => 'secondary'][$healthStatus] ?? 'secondary';
                                $discovery = $connection['discovery'] ?? null;
                                $discoveryStatus = $discovery['status'] ?? 'never_run';
                                $discoveryBadge = ['success' => 'success', 'partial_success' => 'warning', 'failed' => 'danger', 'never_run' => 'secondary'][$discoveryStatus] ?? 'secondary';
                                $sync = $connection['sync'] ?? null;
                                $syncStatus = $sync['status'] ?? 'never_queued';
                                $syncBadge = ['queued' => 'info', 'running' => 'primary', 'success' => 'success', 'partial_success' => 'warning', 'failed' => 'danger', 'skipped' => 'secondary', 'never_queued' => 'secondary'][$syncStatus] ?? 'secondary';
                                $healthAllowsDiscovery = in_array($healthStatus, ['healthy', 'warning'], true);
                            @endphp
                            <div class="border rounded p-3 mb-3">
                                <div class="d-flex flex-wrap justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1">{{ $connection['connection_name'] }}</h6>
                                        <p class="text-muted mb-1">App ID: {{ $connection['app_id'] ?: 'Not set' }} · Mode: {{ ucwords(str_replace('_', ' ', $connection['credential_mode'])) }}</p>
                                        <p class="text-muted mb-0">Graph version: {{ $connection['graph_api_version'] ?: $defaultGraphApiVersion . ' (default)' }} · Secret version: {{ $connection['secret_version'] }}</p>
                                    </div>
                                    <div class="text-right">
                                        <span class="badge badge-{{ $connection['is_active'] ? 'success' : 'secondary' }} px-3 py-2">{{ $connection['is_active'] ? 'Active' : 'Disabled' }}</span>
                                        <span class="badge badge-{{ $healthBadge }} px-3 py-2">Health: {{ ucwords(str_replace('_', ' ', $healthStatus)) }}</span>
                                        <span class="badge badge-{{ $discoveryBadge }} px-3 py-2">Discovery: {{ ucwords(str_replace('_', ' ', $discoveryStatus)) }}</span>
                                        <span class="badge badge-{{ $syncBadge }} px-3 py-2">Sync: {{ ucwords(str_replace('_', ' ', $syncStatus)) }}</span>
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    @foreach ($connection['configured_secrets'] as $secretName => $configured)
                                        <div class="col-lg-3 col-md-6 mb-2">
                                            <small class="text-muted d-block">{{ ucwords(str_replace('_', ' ', $secretName)) }}</small>
                                            <strong>{{ $configured ? 'Configured — hidden' : 'Not configured' }}</strong>
                                        </div>
                                    @endforeach
                                </div>

                                @if ($health)
                                    <div class="alert alert-light border mt-2 mb-2">
                                        <div class="row">
                                            <div class="col-md-4 mb-2"><small class="text-muted d-block">Last checked</small><strong>{{ $health['checked_at'] ?: '—' }}</strong></div>
                                            <div class="col-md-4 mb-2"><small class="text-muted d-block">Token validity</small><strong>{{ $health['token_is_valid'] === null ? 'Not confirmed' : ($health['token_is_valid'] ? 'Valid' : 'Invalid') }}</strong></div>
                                            <div class="col-md-4 mb-2"><small class="text-muted d-block">HTTP / duration</small><strong>{{ $health['http_status'] ?: '—' }} / {{ $health['duration_ms'] === null ? '—' : ($health['duration_ms'] . ' ms') }}</strong></div>
                                            <div class="col-md-4 mb-2"><small class="text-muted d-block">Expires at</small><strong>{{ $health['expires_at'] ?: 'Not reported' }}</strong></div>
                                            <div class="col-md-4 mb-2"><small class="text-muted d-block">Data access expires</small><strong>{{ $health['data_access_expires_at'] ?: 'Not reported' }}</strong></div>
                                            <div class="col-md-4 mb-2"><small class="text-muted d-block">Missing required scopes</small><strong>{{ implode(', ', $health['missing_required_scopes']) ?: 'None' }}</strong></div>
                                        </div>
                                        <p class="mb-0"><small class="text-muted">Diagnostic:</small> {{ $health['redacted_message'] ?: 'No safe diagnostic message.' }}</p>
                                    </div>
                                @else
                                    <div class="alert alert-light border mt-2 mb-2">No read-only connection health test has been recorded yet.</div>
                                @endif

                                @if ($healthReady && $canTestConnections)
                                    <form class="d-inline-block mt-2" method="POST" action="{{ route('fbMarketing.configuration.connections.test', $connection['id']) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-info" type="submit">Run read-only connection test</button>
                                    </form>
                                @elseif (!$canTestConnections)
                                    <p class="text-muted mt-2 mb-0"><small>Your role does not have the contextual connection-health-test permission.</small></p>
                                @endif


                                @if ($discovery)
                                    <div class="alert alert-light border mt-2 mb-2">
                                        <div class="row">
                                            <div class="col-md-4 mb-2"><small class="text-muted d-block">Last discovery</small><strong>{{ $discovery['completed_at'] ?: '—' }}</strong></div>
                                            <div class="col-md-4 mb-2"><small class="text-muted d-block">Successful families</small><strong>{{ implode(', ', $discovery['successful_families']) ?: 'None' }}</strong></div>
                                            <div class="col-md-4 mb-2"><small class="text-muted d-block">Warning families</small><strong>{{ implode(', ', $discovery['failed_families']) ?: 'None' }}</strong></div>
                                        </div>
                                        <p class="mb-0"><small class="text-muted">Diagnostic:</small> {{ $discovery['redacted_message'] ?: 'No safe diagnostic message.' }}</p>
                                    </div>
                                @endif

                                @if ($sync)
                                    <div class="alert alert-light border mt-2 mb-2">
                                        <div class="row">
                                            <div class="col-md-3 mb-2"><small class="text-muted d-block">Latest sync trigger</small><strong>{{ ucwords(str_replace('_', ' ', $sync['trigger_type'])) }}</strong></div>
                                            <div class="col-md-3 mb-2"><small class="text-muted d-block">Requested</small><strong>{{ $sync['requested_at'] ?: '—' }}</strong></div>
                                            <div class="col-md-3 mb-2"><small class="text-muted d-block">Completed</small><strong>{{ $sync['completed_at'] ?: '—' }}</strong></div>
                                            <div class="col-md-3 mb-2"><small class="text-muted d-block">Attempts</small><strong>{{ $sync['attempt_count'] }}</strong></div>
                                        </div>
                                        <p class="mb-0"><small class="text-muted">Sync diagnostic:</small> {{ $sync['redacted_message'] ?: 'No safe diagnostic message.' }}</p>
                                    </div>
                                @endif

                                @if ($connection['is_active'] && $healthAllowsDiscovery && $canRunCatalogSync)
                                    @if ($manualCatalogSyncReady)
                                        <form class="d-inline-block mt-2 mr-2" method="POST" action="{{ route('fbMarketing.configuration.connections.refresh-catalog-now', $connection['id']) }}">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-success" type="submit">Refresh catalog mappings now (no queue)</button>
                                        </form>
                                    @elseif ($manualCatalogSyncEnabled)
                                        <p class="text-muted mt-2 mb-0"><small>Apply the FBM-11 migration before using catalog-only refresh.</small></p>
                                    @endif
                                @endif

                                @if ($canRunSync && $connection['is_active'] && $healthAllowsDiscovery)
                                    @if ($manualSyncReady)
                                        <form class="d-inline-block mt-2 mr-2" method="POST" action="{{ route('fbMarketing.configuration.connections.sync-now', $connection['id']) }}">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-success" type="submit">Run manual sync now (no queue)</button>
                                        </form>
                                    @elseif ($manualSyncEnabled)
                                        <p class="text-muted mt-2 mb-0"><small>Apply prior FB MARKETING migrations before using the manual no-queue action.</small></p>
                                    @endif
                                    @if ($manualDrilldownSyncReady)
                                        <form class="d-inline-block mt-2 mr-2" method="POST" action="{{ route('fbMarketing.configuration.connections.refresh-drilldowns-now', $connection['id']) }}">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-info" type="submit">Refresh drilldown snapshots now (no queue)</button>
                                        </form>
                                    @elseif ($manualDrilldownSyncEnabled)
                                        <p class="text-muted mt-2 mb-0"><small>Apply FBM-07 and FBM-08 migrations before using the bounded drilldown refresh.</small></p>
                                    @endif
                                    @if ($syncReady)
                                        <form class="d-inline-block mt-2" method="POST" action="{{ route('fbMarketing.configuration.connections.sync', $connection['id']) }}">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-primary" type="submit">Queue full read-only Meta sync</button>
                                        </form>
                                    @else
                                        @if ($manualSyncReady)
                                            <p class="text-muted mt-2 mb-0"><small>Background sync is unavailable until FBM-06 queue readiness passes. The manual action above does not require queue storage or a worker.</small></p>
                                        @else
                                            <p class="text-muted mt-2 mb-0"><small>Background sync is unavailable until FBM-06 queue readiness passes.</small></p>
                                        @endif
                                    @endif
                                    @if ($manualSyncReady)
                                        <p class="text-muted mt-2 mb-0"><small>Manual mode runs in this application request: asset discovery, bounded selected-catalog mapping, selected-account hierarchy refresh and a bounded recent account-level Insights refresh. It intentionally does not queue historical backfill.</small></p>
                                    @endif
                                    @if ($manualDrilldownSyncReady)
                                        <p class="text-muted mt-2 mb-0"><small>Drilldown refresh skips asset discovery, refreshes at most one selected Ad Account, stores a recent three-day campaign/ad-set/ad window, and dispatches no worker or historical async report.</small></p>
                                    @endif
                                @elseif (!$canRunSync)
                                    <p class="text-muted mt-2 mb-0"><small>Your role does not have the contextual read-only sync permission.</small></p>
                                @else
                                    <p class="text-muted mt-2 mb-0"><small>Sync requires an active connection with a latest health status of Healthy or Warning.</small></p>
                                @endif

                                @if ($connection['notes'])
                                    <p class="text-muted mt-2 mb-2">Notes: {{ $connection['notes'] }}</p>
                                @endif

                                @if ($canUpdateCredentials)
                                    <details class="mt-3">
                                        <summary class="btn btn-sm btn-outline-primary">Edit safe fields or replace secrets</summary>
                                        <div class="border rounded p-3 mt-3">
                                            <form method="POST" action="{{ route('fbMarketing.configuration.connections.update', $connection['id']) }}">
                                                @csrf
                                                @method('PUT')
                                                <div class="row">
                                                    <div class="col-md-4 mb-3">
                                                        <label>Connection name <span class="text-danger">*</span></label>
                                                        <input class="form-control" type="text" name="connection_name" value="{{ $connection['connection_name'] }}" maxlength="120" required>
                                                    </div>
                                                    <div class="col-md-4 mb-3">
                                                        <label>Meta App ID</label>
                                                        <input class="form-control" type="text" name="app_id" value="{{ $connection['app_id'] }}" maxlength="120">
                                                    </div>
                                                    <div class="col-md-4 mb-3">
                                                        <label>Credential mode <span class="text-danger">*</span></label>
                                                        <select class="form-control" name="credential_mode" required>
                                                            @foreach ($credentialModes as $mode)
                                                                <option value="{{ $mode }}" {{ $connection['credential_mode'] === $mode ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $mode)) }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-4 mb-3">
                                                        <label>Graph API version</label>
                                                        <select class="form-control" name="graph_api_version">
                                                            @foreach ($graphApiVersions as $version)
                                                                <option value="{{ $version }}" {{ ($connection['graph_api_version'] ?: $defaultGraphApiVersion) === $version ? 'selected' : '' }}>{{ $version }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-4 mb-3">
                                                        <label>Replace Meta App Secret</label>
                                                        <input class="form-control" type="password" name="app_secret" autocomplete="new-password" maxlength="10000" placeholder="Leave blank to preserve">
                                                    </div>
                                                    <div class="col-md-4 mb-3">
                                                        <label>Replace Meta Access Token</label>
                                                        <input class="form-control" type="password" name="access_token" autocomplete="new-password" maxlength="20000" placeholder="Leave blank to preserve">
                                                    </div>
                                                    <div class="col-md-4 mb-3">
                                                        <label>Replace Conversions API Token</label>
                                                        <input class="form-control" type="password" name="capi_access_token" autocomplete="new-password" maxlength="20000" placeholder="Leave blank to preserve">
                                                    </div>
                                                    <div class="col-md-4 mb-3">
                                                        <label>Replace Conversions API Test Events Code</label>
                                                        <input class="form-control" type="password" name="capi_test_event_code" autocomplete="new-password" maxlength="10000" placeholder="Leave blank to preserve">
                                                        <small class="text-muted">Optional. Test-mode only; stored encrypted and never displayed.</small>
                                                    </div>
                                                    <div class="col-md-4 mb-3">
                                                        <label>Replace Webhook Verify Token</label>
                                                        <input class="form-control" type="password" name="webhook_verify_token" autocomplete="new-password" maxlength="10000" placeholder="Leave blank to preserve">
                                                    </div>
                                                    <div class="col-md-4 mb-3">
                                                        <label>Change reason</label>
                                                        <input class="form-control" type="text" name="change_reason" maxlength="500" placeholder="Optional audit note">
                                                    </div>
                                                    <div class="col-12 mb-3">
                                                        <label>Notes</label>
                                                        <textarea class="form-control" name="notes" rows="2" maxlength="2000" placeholder="Do not paste credentials into notes.">{{ $connection['notes'] }}</textarea>
                                                    </div>
                                                </div>
                                                <button class="btn btn-primary" type="submit">Save replacement</button>
                                            </form>
                                        </div>
                                    </details>

                                    <form class="mt-3" method="POST" action="{{ route('fbMarketing.configuration.connections.status', $connection['id']) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="is_active" value="{{ $connection['is_active'] ? 0 : 1 }}">
                                        <div class="input-group">
                                            <input class="form-control" type="text" name="change_reason" maxlength="500" placeholder="Optional reason for {{ $connection['is_active'] ? 'disabling' : 'enabling' }}">
                                            <div class="input-group-append">
                                                <button class="btn btn-outline-{{ $connection['is_active'] ? 'danger' : 'success' }}" type="submit">{{ $connection['is_active'] ? 'Disable connection' : 'Enable connection' }}</button>
                                            </div>
                                        </div>
                                    </form>
                                @endif
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Queue and sync run ledger</h5>
                    <p class="text-muted">Privacy-safe lifecycle summaries. Job payload secrets, database passwords, provider payloads and hidden fingerprints are excluded.</p>
                    @if ($syncRuns->isEmpty())
                        <p class="text-muted mb-0">No queued sync run is available.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead><tr><th>Requested</th><th>Connection</th><th>Trigger</th><th>Status</th><th>Attempts</th><th>Duration</th><th>Warnings</th><th>Diagnostic</th></tr></thead>
                                <tbody>
                                    @foreach ($syncRuns as $run)
                                        <tr>
                                            <td>{{ $run['requested_at'] ?: '—' }}</td>
                                            <td>{{ $run['connection_name'] ?: ('#' . $run['fbm_connection_id']) }}</td>
                                            <td>{{ ucwords(str_replace('_', ' ', $run['trigger_type'])) }}</td>
                                            <td>{{ ucwords(str_replace('_', ' ', $run['status'])) }}</td>
                                            <td>{{ $run['attempt_count'] }}</td>
                                            <td>{{ $run['duration_ms'] === null ? '—' : ($run['duration_ms'] . ' ms') }}</td>
                                            <td>{{ $run['warning_count'] }}</td>
                                            <td>{{ $run['redacted_message'] ?: '—' }}</td>
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
                    <h5 class="card-title">Meta asset discovery ledger</h5>
                    <p class="text-muted">Append-only safe summaries. Raw Graph responses, reusable tokens, internal provider IDs, request fingerprints and IP hashes are excluded.</p>

                    @if (!$assetDiscoveryReady || $discoveryRuns->isEmpty())
                        <p class="text-muted mb-0">No read-only asset discovery run is available.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>Connection</th>
                                        <th>Status</th>
                                        <th>Available assets</th>
                                        <th>Warnings</th>
                                        <th>Diagnostic</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($discoveryRuns as $run)
                                        <tr>
                                            <td>{{ $run['completed_at'] ?: '—' }}</td>
                                            <td>{{ $run['connection_name'] ?: ('#' . $run['fbm_connection_id']) }}</td>
                                            <td>{{ ucwords(str_replace('_', ' ', $run['status'])) }}</td>
                                            <td>
                                                @foreach ($run['asset_counts'] as $family => $count)
                                                    <span class="badge badge-light border">{{ str_replace('_', ' ', $family) }}: {{ $count }}</span>
                                                @endforeach
                                            </td>
                                            <td>
                                                @forelse ($run['warning_details'] as $warning)
                                                    <div><small><strong>{{ $warning['family'] ?? 'asset' }}:</strong> {{ $warning['message'] ?? 'Safe warning recorded.' }}</small></div>
                                                @empty
                                                    —
                                                @endforelse
                                            </td>
                                            <td>{{ $run['redacted_message'] ?: '—' }}</td>
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
                    <h5 class="card-title">Safe Graph request diagnostics</h5>
                    <p class="text-muted">Allow-listed operation summaries only. Raw URLs, query strings, headers, provider payloads, reusable tokens and provider IDs are excluded.</p>
                    @if ($apiRequestLogs->isEmpty())
                        <p class="text-muted mb-0">No safe Graph request diagnostic is available.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead><tr><th>Time</th><th>Operation</th><th>Graph version</th><th>Pages</th><th>HTTP</th><th>Duration</th><th>Safe diagnostic</th></tr></thead>
                                <tbody>
                                    @foreach ($apiRequestLogs as $log)
                                        <tr>
                                            <td>{{ $log['created_at'] ?: '—' }}</td>
                                            <td>{{ ucwords(str_replace('_', ' ', $log['operation_key'])) }}</td>
                                            <td>{{ $log['graph_api_version'] ?: '—' }}</td>
                                            <td>{{ $log['page_number'] ?: '—' }}</td>
                                            <td>{{ $log['http_status'] ?: '—' }}</td>
                                            <td>{{ $log['duration_ms'] === null ? '—' : ($log['duration_ms'] . ' ms') }}</td>
                                            <td>{{ $log['redacted_message'] ?: '—' }}</td>
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
                    <h5 class="card-title">Connection health ledger</h5>
                    <p class="text-muted">Append-only browser-safe rows. Raw tokens, app secrets, query strings, provider user IDs, granular target IDs, fingerprints and IP hashes are excluded.</p>

                    @if (!$healthReady || $healthChecks->isEmpty())
                        <p class="text-muted mb-0">No connection health audit is available.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>Connection</th>
                                        <th>Status</th>
                                        <th>Graph version</th>
                                        <th>Validity</th>
                                        <th>Scopes missing</th>
                                        <th>HTTP</th>
                                        <th>Actor</th>
                                        <th>Safe diagnostic</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($healthChecks as $check)
                                        <tr>
                                            <td>{{ $check['checked_at'] ?: '—' }}</td>
                                            <td>{{ $check['connection_name'] ?: ('#' . $check['fbm_connection_id']) }}</td>
                                            <td>{{ ucfirst($check['status']) }}</td>
                                            <td>{{ $check['graph_api_version'] ?: '—' }}</td>
                                            <td>{{ $check['token_is_valid'] === null ? '—' : ($check['token_is_valid'] ? 'Valid' : 'Invalid') }}</td>
                                            <td>{{ implode(', ', $check['missing_required_scopes']) ?: 'None' }}</td>
                                            <td>{{ $check['http_status'] ?: '—' }}</td>
                                            <td>{{ $check['actor_user_id'] ? ('User #' . $check['actor_user_id']) : 'System' }}</td>
                                            <td>{{ $check['redacted_message'] ?: '—' }}</td>
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
                    <h5 class="card-title">Credential change audit</h5>
                    <p class="text-muted">Append-only audit rows contain safe state, configured-field names and non-reversible fingerprints only. Secret values are excluded.</p>

                    @if (!$vaultReady || $audits->isEmpty())
                        <p class="text-muted mb-0">No credential change audit is available.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>Connection</th>
                                        <th>Action</th>
                                        <th>Changed fields</th>
                                        <th>Actor</th>
                                        <th>Reason</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($audits as $audit)
                                        <tr>
                                            <td>{{ $audit['created_at'] ?: '—' }}</td>
                                            <td>{{ $audit['connection_name'] ?: ('#' . $audit['fbm_connection_id']) }}</td>
                                            <td>{{ ucfirst($audit['action']) }}</td>
                                            <td>{{ implode(', ', $audit['changed_fields']) ?: '—' }}</td>
                                            <td>{{ $audit['actor_user_id'] ? ('User #' . $audit['actor_user_id']) : 'System' }}</td>
                                            <td>{{ $audit['change_reason'] ?: '—' }}</td>
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

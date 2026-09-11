@extends('backend.master')

@section('title')
    FB Marketing Setup Wizard
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="page-title mb-1">FB Marketing Setup Wizard</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('fbMarketing.dashboard') }}">FB Marketing</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('fbMarketing.configuration.index') }}">Configuration</a></li>
                            <li class="breadcrumb-item active">Setup Wizard</li>
                        </ul>
                    </div>
                </div>
            </div>

            @include('backend.fb-marketing._status-card')

            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="card-title mb-1">Setup progress</h5>
                            <p class="text-muted mb-0">The wizard displays a privacy-safe readiness state. Provider calls run only after an authorized health test or through the dedicated FB MARKETING queue worker.</p>
                        </div>
                        <div>
                            @if ($canViewPerformance)
                                <a class="btn btn-outline-info mt-2 mt-md-0 mr-2" href="{{ route('fbMarketing.performance.index') }}">Performance</a>
                            @endif
                            @if ($canViewCatalog)
                                <a class="btn btn-outline-success mt-2 mt-md-0 mr-2" href="{{ route('fbMarketing.products-catalog.index') }}">Products &amp; Catalog</a>
                            @endif
                            @if ($canViewTracking)
                                <a class="btn btn-outline-dark mt-2 mt-md-0 mr-2" href="{{ route('fbMarketing.tracking-attribution.index') }}">Tracking &amp; Attribution</a>
                            @endif
                            <a class="btn btn-primary mt-2 mt-md-0" href="{{ route('fbMarketing.configuration.index') }}">Open configuration</a>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="border rounded p-3 h-100">
                                <small class="text-muted d-block">Step 1</small>
                                <strong>Vault schema</strong>
                                <div class="mt-2"><span class="badge badge-{{ $vaultReady ? 'success' : 'danger' }}">{{ $vaultReady ? 'Ready' : 'Migration required' }}</span></div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="border rounded p-3 h-100">
                                <small class="text-muted d-block">Step 2</small>
                                <strong>Encrypted connection</strong>
                                <div class="mt-2"><span class="badge badge-{{ $connectionCount > 0 ? 'success' : 'warning' }}">{{ $connectionCount }} configured</span></div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="border rounded p-3 h-100">
                                <small class="text-muted d-block">Step 3</small>
                                <strong>Connection health</strong>
                                <div class="mt-2">
                                    <span class="badge badge-{{ !$healthReady ? 'danger' : ($healthyConnectionCount > 0 ? 'success' : 'warning') }}">
                                        {{ !$healthReady ? 'Migration required' : ($healthyConnectionCount . ' healthy / ' . $testedConnectionCount . ' tested') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="border rounded p-3 h-100">
                                <small class="text-muted d-block">Step 4</small>
                                <strong>Meta asset discovery</strong>
                                <div class="mt-2">
                                    <span class="badge badge-{{ !$assetDiscoveryReady ? 'danger' : ($discoveryRunCount > 0 ? 'success' : 'warning') }}">
                                        {{ !$assetDiscoveryReady ? 'Migration required' : ($discoveryRunCount . ' run(s)' . ($latestDiscoveryStatus ? ' · ' . str_replace('_', ' ', $latestDiscoveryStatus) : '')) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="border rounded p-3 h-100">
                                <small class="text-muted d-block">Step 5</small>
                                <strong>Pixel and feed consolidation</strong>
                                <div class="mt-2">
                                    <span class="badge badge-{{ $feedDiagnostics['settings_schema_ready'] && $feedDiagnostics['canonical_flag_ready'] ? 'success' : 'danger' }}">
                                        {{ $feedDiagnostics['settings_schema_ready'] && $feedDiagnostics['canonical_flag_ready'] ? 'Ready' : 'FBM-05 migration required' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="border rounded p-3 h-100">
                                <small class="text-muted d-block">Step 6</small>
                                <strong>Queue, scheduler and sync infrastructure</strong>
                                <div class="mt-2"><span class="badge badge-{{ $syncReady ? 'success' : 'danger' }}">{{ $syncReady ? ('Background ready · ' . $syncRunCount . ' run(s)') : 'Background queue readiness required' }}</span></div>
                                @if ($manualSyncReady)
                                    <div class="mt-2"><span class="badge badge-info">Manual no-queue fallback ready</span></div>
                                @elseif ($manualSyncEnabled)
                                    <div class="mt-2"><span class="badge badge-warning">Manual fallback needs prior migrations</span></div>
                                @endif
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="border rounded p-3 h-100">
                                <small class="text-muted d-block">Step 7</small>
                                <strong>Campaign hierarchy read-only sync</strong>
                                <div class="mt-2"><span class="badge badge-{{ $hierarchyReady ? 'success' : 'danger' }}">{{ $hierarchyReady ? 'Mirror schema ready' : 'FBM-07 migration required' }}</span></div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="border rounded p-3 h-100">
                                <small class="text-muted d-block">Step 8</small>
                                <strong>Ads Insights snapshots</strong>
                                <div class="mt-2"><span class="badge badge-{{ $insightsSummary['schema_ready'] ? 'success' : 'danger' }}">{{ $insightsSummary['schema_ready'] ? ($insightsSummary['snapshot_count'] . ' daily row(s)') : 'FBM-08 migration required' }}</span></div>
                                <small class="text-muted d-block mt-2">Freshness: {{ $insightsSummary['freshness_watermark'] ?: '—' }}</small>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="border rounded p-3 h-100">
                                <small class="text-muted d-block">Step 9</small>
                                <strong>Executive stored-snapshot dashboard</strong>
                                <div class="mt-2"><span class="badge badge-{{ $insightsSummary['schema_ready'] && $insightsSummary['snapshot_count'] > 0 ? 'success' : 'warning' }}">{{ !$insightsSummary['schema_ready'] ? 'FBM-08 migration required' : ($insightsSummary['snapshot_count'] > 0 ? 'Reporting rows available' : 'Run first Insights sync') }}</span></div>
                                <small class="text-muted d-block mt-2">Open Dashboard for currency-safe KPI totals and freshness coverage.</small>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="border rounded p-3 h-100">
                                <small class="text-muted d-block">Step 10</small>
                                <strong>Performance drilldowns and worklists</strong>
                                <div class="mt-2"><span class="badge badge-{{ $insightsSummary['schema_ready'] && $hierarchyReady ? 'success' : 'warning' }}">{{ !$hierarchyReady ? 'FBM-07 migration required' : (!$insightsSummary['schema_ready'] ? 'FBM-08 migration required' : 'Stored-snapshot drilldowns ready') }}</span></div>
                                @if ($manualDrilldownSyncReady)
                                    <small class="text-muted d-block mt-2">Bounded manual campaign/ad-set/ad snapshot refresh is available without a queue worker.</small>
                                @elseif ($manualDrilldownSyncEnabled)
                                    <small class="text-muted d-block mt-2">Apply prior migrations before using manual drilldown refresh.</small>
                                @else
                                    <small class="text-muted d-block mt-2">Use the dedicated queue worker to populate drilldown snapshots.</small>
                                @endif
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="border rounded p-3 h-100">
                                <small class="text-muted d-block">Step 11</small>
                                <strong>Catalog, product mapping and feed diagnostics</strong>
                                <div class="mt-2"><span class="badge badge-{{ $catalogSummary['schema_ready'] ? 'success' : 'danger' }}">{{ $catalogSummary['schema_ready'] ? ($catalogSummary['mapping_count'] . ' mirrored item(s)') : 'FBM-11 migration required' }}</span></div>
                                <small class="text-muted d-block mt-2">Selected catalogs: {{ $catalogSummary['selected_catalogs'] }} · unmatched: {{ $catalogSummary['unmatched_count'] + $catalogSummary['ambiguous_count'] }} · feed-valid: {{ $feedDiagnostics['feed_ready_products'] }}.</small>
                                @if ($manualCatalogSyncReady)
                                    <small class="text-muted d-block mt-2">Bounded catalog-only refresh is available without a queue worker.</small>
                                @elseif ($manualCatalogSyncEnabled)
                                    <small class="text-muted d-block mt-2">Apply FBM-11 migration before using catalog-only refresh.</small>
                                @endif
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="border rounded p-3 h-100">
                                <small class="text-muted d-block">Step 12</small>
                                <strong>Landing attribution capture</strong>
                                <div class="mt-2"><span class="badge badge-{{ !$landingAttributionSummary['schema_ready'] ? 'danger' : ($landingAttributionSummary['enabled'] ? 'success' : 'secondary') }}">{{ !$landingAttributionSummary['schema_ready'] ? 'FBM-12 migration required' : ($landingAttributionSummary['enabled'] ? 'Capture enabled' : 'Ready but disabled') }}</span></div>
                                <small class="text-muted d-block mt-2">Retention: {{ $landingAttributionSummary['retention_days'] }} day(s) · active captured sessions: {{ $landingAttributionSummary['session_count'] }}.</small>
                                <small class="text-muted d-block mt-2">Storefront helper integration and explicit consent remain required before browser capture.</small>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="border rounded p-3 h-100">
                                <small class="text-muted d-block">Step 13</small>
                                <strong>Browser Pixel event contract</strong>
                                <div class="mt-2"><span class="badge badge-{{ !$browserPixelSummary['schema_ready'] ? 'danger' : ($browserPixelSummary['live_delivery_ready'] ? 'success' : ($browserPixelSummary['mode'] === 'dry_run' ? 'info' : 'secondary')) }}">{{ !$browserPixelSummary['schema_ready'] ? 'FBM-13 migration required' : ($browserPixelSummary['live_delivery_ready'] ? 'Live ready' : ucwords(str_replace('_', ' ', $browserPixelSummary['mode']))) }}</span></div>
                                <small class="text-muted d-block mt-2">Stable Pixel enabled: {{ $browserPixelSummary['legacy_pixel_status_enabled'] ? 'yes' : 'no' }} · Pixel ID configured: {{ $browserPixelSummary['pixel_id_configured'] ? 'yes' : 'no' }}.</small>
                                <small class="text-muted d-block mt-2">Dry run loads no Meta script. Live browser delivery still requires explicit consent and storefront method calls.</small>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="border rounded p-3 h-100">
                                <small class="text-muted d-block">Step 14</small>
                                <strong>Conversions API event ledger</strong>
                                <div class="mt-2"><span class="badge badge-{{ !$capiSummary['schema_ready'] ? 'danger' : ($capiSummary['live_ready'] ? 'success' : ($capiSummary['mode'] === 'dry_run' ? 'info' : ($capiSummary['mode'] === 'test' ? 'warning' : 'secondary'))) }}">{{ !$capiSummary['schema_ready'] ? 'FBM-14 migration required' : ($capiSummary['live_ready'] ? 'Live ready' : ucwords(str_replace('_', ' ', $capiSummary['mode']))) }}</span></div>
                                <small class="text-muted d-block mt-2">Pixel destination: {{ $capiSummary['destination_pixel_configured'] ? 'configured' : 'missing' }} · hidden CAPI token connection(s): {{ $capiSummary['capi_token_connection_count'] }}.</small>
                                <small class="text-muted d-block mt-2">Use Tracking &amp; Attribution for dry-run diagnostics and bounded manual no-queue retry.</small>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <h5 class="card-title">Advanced readiness checklist</h5>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead><tr><th>Stage</th><th>Area</th><th>Status</th><th>Next action</th></tr></thead>
                                <tbody>
                                    @foreach ($advancedSteps as $step)
                                        <tr>
                                            <td>FBM-{{ str_pad($step['number'], 2, '0', STR_PAD_LEFT) }}</td>
                                            <td>{{ $step['title'] }}</td>
                                            <td><span class="badge badge-{{ $step['ready'] ? 'success' : 'warning' }}">{{ $step['ready'] ? 'Ready' : ($step['number'] >= 31 ? 'Blocked' : 'Migration pending') }}</span></td>
                                            <td>
                                                @if ($step['ready'])
                                                    <small class="text-muted">{{ $step['ready_message'] }}</small>
                                                @else
                                                    <small class="text-muted">Missing: {{ implode(', ', $step['missing']) }}</small>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <p class="text-muted">Active vault connections: <strong>{{ $activeConnectionCount }}</strong>. Feed-ready selected products: <strong>{{ $feedDiagnostics['feed_ready_products'] }}</strong>. Locally selected Pixel(s): <strong>{{ $feedDiagnostics['selected_pixels'] }}</strong>. Existing stable General Information Pixel settings remain separate.</p>

                    @php
                        $writerBadge = [
                            'migration_required' => 'danger',
                            'not_ready' => 'warning',
                            'ready_disabled' => 'info',
                            'ready_enabled' => 'success',
                        ][$providerWriterSummary['status'] ?? 'not_ready'] ?? 'secondary';
                    @endphp
                    <div class="alert alert-{{ $writerBadge === 'success' ? 'success' : ($writerBadge === 'danger' ? 'danger' : 'info') }}">
                        <strong>FBM-31 Provider writer foundation:</strong>
                        {{ $providerWriterSummary['message'] }}
                        Required writer scopes: {{ implode(', ', $providerWriterSummary['required_scopes']) ?: '—' }}.
                        Ready connections: {{ $providerWriterSummary['ready_connection_count'] }} / {{ $providerWriterSummary['active_connection_count'] }}.
                        Selected Ad Accounts: {{ $providerWriterSummary['selected_ad_account_count'] }}.
                    </div>

                    <div class="alert alert-{{ ($providerWriterLaunchChecklist['ready_for_signed_enablement'] ?? false) ? 'success' : 'warning' }}">
                        <strong>FBM-35 Final provider-writer launch gate:</strong>
                        {{ $providerWriterLaunchChecklist['message'] }}
                        Blocking item(s): {{ $providerWriterLaunchChecklist['blocking_count'] }}.
                    </div>

                    @if ($assetDiscoveryReady)
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead><tr><th>Asset family</th><th>Available</th><th>Selected and available</th><th>Readiness</th></tr></thead>
                                <tbody>
                                    @foreach (['business_accounts' => 'Business accounts', 'ad_accounts' => 'Ad accounts', 'pages' => 'Pages', 'pixels' => 'Pixels', 'datasets' => 'Datasets', 'catalogs' => 'Catalogs', 'instagram_accounts' => 'Instagram accounts'] as $family => $label)
                                        @php
                                            $available = $availableAssetCounts[$family] ?? 0;
                                            $selected = $selectedAvailableAssetCounts[$family] ?? 0;
                                            $required = in_array($family, ['business_accounts', 'ad_accounts', 'pages'], true);
                                        @endphp
                                        <tr>
                                            <td>{{ $label }} {{ $required ? '(required)' : '(optional)' }}</td>
                                            <td>{{ $available }}</td>
                                            <td>{{ $selected }}</td>
                                            <td><span class="badge badge-{{ $selected > 0 ? 'success' : ($required ? 'warning' : 'light border') }}">{{ $selected > 0 ? 'Selected' : ($required ? 'Selection required' : 'Optional') }}</span></td>
                                        </tr>
                                    @endforeach
                                    <tr>
                                        <td>Pixel or Dataset readiness</td>
                                        <td>{{ ($availableAssetCounts['pixels'] ?? 0) + ($availableAssetCounts['datasets'] ?? 0) }}</td>
                                        <td>{{ ($selectedAvailableAssetCounts['pixels'] ?? 0) + ($selectedAvailableAssetCounts['datasets'] ?? 0) }}</td>
                                        <td><span class="badge badge-{{ (($selectedAvailableAssetCounts['pixels'] ?? 0) + ($selectedAvailableAssetCounts['datasets'] ?? 0)) > 0 ? 'success' : 'warning' }}">{{ (($selectedAvailableAssetCounts['pixels'] ?? 0) + ($selectedAvailableAssetCounts['datasets'] ?? 0)) > 0 ? 'Ready' : 'Select when available' }}</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

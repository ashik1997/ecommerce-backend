@extends('backend.master')

@section('title')
    FB Marketing Dashboard
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="page-title mb-1">FB Marketing Dashboard</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">FB Marketing</li>
                        </ul>
                    </div>
                    <div class="col-auto">
                        @if ($canViewPerformance)
                            <a class="btn btn-outline-info mr-2" href="{{ route('fbMarketing.performance.index') }}">Open performance</a>
                        @endif
                        <a class="btn btn-outline-primary" href="{{ route('fbMarketing.configuration.index') }}">Open configuration</a>
                    </div>
                </div>
            </div>

            @include('backend.fb-marketing._status-card')

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    <strong>The FB MARKETING dashboard request could not be completed.</strong>
                    <ul class="mb-0 mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif


            @include('backend.fb-marketing._executive-dashboard')

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

            @unless ($assetDiscoveryReady)
                <div class="alert alert-warning"><strong>FBM-04 migration required.</strong> Apply the asset-discovery migration before importing or selecting Meta assets.</div>
            @endunless

            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-start">
                        <div>
                            <h5 class="card-title mb-1">Active Meta asset readiness</h5>
                            <p class="text-muted mb-0">Selections are local application settings only. This dashboard sends no Meta write request.</p>
                        </div>
                        @if ($latestDiscoveryRun)
                            <span class="badge badge-{{ $latestDiscoveryRun['status'] === 'success' ? 'success' : ($latestDiscoveryRun['status'] === 'partial_success' ? 'warning' : 'danger') }} px-3 py-2">
                                Latest discovery: {{ ucwords(str_replace('_', ' ', $latestDiscoveryRun['status'])) }}
                            </span>
                        @endif
                    </div>
                    @if ($latestDiscoveryRun)
                        <hr>
                        <p class="text-muted mb-0">Last discovery: <strong>{{ $latestDiscoveryRun['completed_at'] ?: '—' }}</strong> · Connection: <strong>{{ $latestDiscoveryRun['connection_name'] ?: ('#' . $latestDiscoveryRun['fbm_connection_id']) }}</strong></p>
                    @endif
                </div>
            </div>



            @if ($hierarchyReady)
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex flex-wrap justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="card-title mb-1">Campaign hierarchy mirror</h5>
                                <p class="text-muted mb-0">Local read-only mirror from selected Ad Accounts. Provider IDs and raw Graph payloads are hidden.</p>
                            </div>
                            <span class="badge badge-info px-3 py-2">FBM-07 ready</span>
                        </div>
                        <div class="row">
                            @foreach (['campaigns' => 'Campaigns', 'ad_sets' => 'Ad sets', 'ads' => 'Ads', 'creatives' => 'Creatives'] as $key => $label)
                                <div class="col-md-3 mb-2">
                                    <div class="border rounded p-3 h-100">
                                        <div class="text-muted small">{{ $label }}</div>
                                        <div class="h4 mb-0">{{ number_format($hierarchyCounts[$key] ?? 0) }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @if ($latestHierarchyItems->isNotEmpty())
                            <div class="table-responsive mt-3">
                                <table class="table table-sm table-bordered mb-0">
                                    <thead><tr><th>Family</th><th>Status</th><th>Ad account</th><th>Seen</th><th>Warnings</th><th>Message</th></tr></thead>
                                    <tbody>
                                        @foreach ($latestHierarchyItems as $item)
                                            <tr>
                                                <td>{{ ucwords(str_replace('_', ' ', $item['family'])) }}</td>
                                                <td><span class="badge badge-{{ $item['status'] === 'success' ? 'success' : ($item['status'] === 'partial_success' ? 'warning' : 'danger') }}">{{ ucwords(str_replace('_', ' ', $item['status'])) }}</span></td>
                                                <td>{{ $item['ad_account_name'] ?: '—' }}</td>
                                                <td>{{ number_format($item['seen_count']) }}</td>
                                                <td>{{ number_format($item['warning_count']) }}</td>
                                                <td>{{ $item['redacted_message'] ?: '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            @else
                <div class="alert alert-warning"><strong>FBM-07 migration required.</strong> Apply the campaign hierarchy mirror migration before running full read-only sync.</div>
            @endif

            @if ($assetDiscoveryReady)
                @foreach ($assetLabels as $family => $label)
                    @php $familyAssets = $assets->get($family, collect()); @endphp
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
                                <h5 class="card-title mb-0">{{ $label }}</h5>
                                <span class="badge badge-light border">{{ $familyAssets->where('is_available', true)->count() }} available · {{ $familyAssets->where('is_selected', true)->count() }} selected</span>
                            </div>

                            @if ($familyAssets->isEmpty())
                                <p class="text-muted mb-0">No {{ strtolower($label) }} have been discovered yet.</p>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-0">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Connection</th>
                                                <th>Business</th>
                                                <th>Relationship</th>
                                                <th>Availability</th>
                                                <th>Safe metadata</th>
                                                <th>Local selection</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($familyAssets as $asset)
                                                <tr>
                                                    <td>{{ $asset['asset_name'] ?: 'Unnamed Meta asset' }}</td>
                                                    <td>{{ $asset['connection_name'] ?: ('#' . $asset['fbm_connection_id']) }}</td>
                                                    <td>{{ $asset['business_account_name'] ?: '—' }}</td>
                                                    <td>{{ ucfirst($asset['asset_relationship']) }}</td>
                                                    <td>
                                                        <span class="badge badge-{{ $asset['is_available'] ? 'success' : 'warning' }}">{{ $asset['is_available'] ? 'Available' : 'Unavailable' }}</span>
                                                        @if (!$asset['is_available'] && $asset['is_selected'])
                                                            <span class="badge badge-danger">Selected stale asset</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @forelse ($asset['metadata'] as $key => $value)
                                                            @if ($value !== null && $value !== '')
                                                                <div><small><strong>{{ str_replace('_', ' ', ucfirst($key)) }}:</strong> {{ $value }}</small></div>
                                                            @endif
                                                        @empty
                                                            —
                                                        @endforelse
                                                    </td>
                                                    <td>
                                                        @if ($canManageAssetSelection)
                                                            <form method="POST" action="{{ route('fbMarketing.assets.selection', [$family, $asset['id']]) }}">
                                                                @csrf
                                                                @method('PATCH')
                                                                <input type="hidden" name="is_selected" value="{{ $asset['is_selected'] ? 0 : 1 }}">
                                                                <button class="btn btn-sm btn-outline-{{ $asset['is_selected'] ? 'danger' : 'success' }}" type="submit" {{ !$asset['is_available'] && !$asset['is_selected'] ? 'disabled' : '' }}>
                                                                    {{ $asset['is_selected'] ? 'Deselect' : 'Select' }}
                                                                </button>
                                                            </form>
                                                        @else
                                                            <span class="badge badge-{{ $asset['is_selected'] ? 'primary' : 'secondary' }}">{{ $asset['is_selected'] ? 'Selected' : 'Not selected' }}</span>
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
                @endforeach
            @endif
        </div>
    </div>
@endsection

@extends('backend.master')

@section('title')
    FB Marketing Attribution Reports
@endsection

@section('content')
    @php
        $money = function ($value, $currency = null) {
            return ($currency ? $currency . ' ' : '') . number_format((float) $value, 2);
        };
        $label = fn($value) => ucwords(str_replace('_', ' ', (string) $value));
    @endphp
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="page-title mb-1">Attribution Reports</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('fbMarketing.dashboard') }}">FB Marketing</a></li>
                            <li class="breadcrumb-item active">Attribution Reports</li>
                        </ul>
                    </div>
                    <div class="col-auto">
                        <a class="btn btn-outline-primary" href="{{ route('fbMarketing.tracking-attribution.index') }}">Tracking &amp; Attribution</a>
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
                    <form method="GET" action="{{ route('fbMarketing.attribution-reports.index') }}">
                        <div class="row align-items-end">
                            <div class="col-md-2 mb-3">
                                <label for="from_date">Date from</label>
                                <input class="form-control" id="from_date" type="date" name="from_date" value="{{ $report['filters']['from_date'] }}" required>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label for="to_date">Date to</label>
                                <input class="form-control" id="to_date" type="date" name="to_date" value="{{ $report['filters']['to_date'] }}" required>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label for="campaign_id">Campaign</label>
                                <select class="form-control" id="campaign_id" name="campaign_id">
                                    <option value="">All campaigns</option>
                                    @foreach ($report['campaign_options'] as $option)
                                        <option value="{{ $option['id'] }}" {{ (int) $report['filters']['campaign_id'] === (int) $option['id'] ? 'selected' : '' }}>{{ $option['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label for="ad_set_id">Ad set</label>
                                <select class="form-control" id="ad_set_id" name="ad_set_id">
                                    <option value="">All ad sets</option>
                                    @foreach ($report['ad_set_options'] as $option)
                                        <option value="{{ $option['id'] }}" {{ (int) $report['filters']['ad_set_id'] === (int) $option['id'] ? 'selected' : '' }}>{{ $option['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label for="ad_id">Ad</label>
                                <select class="form-control" id="ad_id" name="ad_id">
                                    <option value="">All ads</option>
                                    @foreach ($report['ad_options'] as $option)
                                        <option value="{{ $option['id'] }}" {{ (int) $report['filters']['ad_id'] === (int) $option['id'] ? 'selected' : '' }}>{{ $option['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
                                <button class="btn btn-primary btn-block" type="submit">Apply filters</button>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="evidence_state">Attribution status</label>
                                <select class="form-control" id="evidence_state" name="evidence_state">
                                    <option value="">All statuses</option>
                                    @foreach ($report['evidence_options'] as $value => $text)
                                        <option value="{{ $value }}" {{ $report['filters']['evidence_state'] === $value ? 'selected' : '' }}>{{ $text }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="attribution_method">Attribution method</label>
                                <select class="form-control" id="attribution_method" name="attribution_method">
                                    <option value="">All methods</option>
                                    @foreach ($report['method_options'] as $value => $text)
                                        <option value="{{ $value }}" {{ $report['filters']['attribution_method'] === $value ? 'selected' : '' }}>{{ $text }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a class="btn btn-outline-secondary" href="{{ route('fbMarketing.attribution-reports.index') }}">Reset</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row">
                @foreach ([
                    ['Total spend', $money($report['summary']['spend'], $report['summary']['currency'])],
                    ['Attributed orders', number_format($report['summary']['attributed_orders'])],
                    ['Attributed revenue', $money($report['summary']['attributed_revenue'], $report['summary']['currency'])],
                    ['Average order value', $money($report['summary']['average_order_value'], $report['summary']['currency'])],
                    ['Cost per attributed order', $money($report['summary']['cost_per_attributed_order'], $report['summary']['currency'])],
                    ['ROAS', number_format($report['summary']['roas'], 4)],
                    ['Revenue minus spend', $money($report['summary']['revenue_minus_spend'], $report['summary']['currency'])],
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

            <div class="row">
                <div class="col-lg-6 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex flex-wrap justify-content-between align-items-start">
                                <div>
                                    <h5 class="card-title mb-1">Reconciliation summary</h5>
                                    <p class="text-muted mb-0">Application-local ERP attribution snapshots only.</p>
                                </div>
                                @if ($canReconcile && $report['schema_ready'])
                                    <form method="POST" action="{{ route('fbMarketing.attribution-reports.reconcile', request()->query()) }}">
                                        @csrf
                                        <button class="btn btn-outline-primary" type="submit">Reconcile recent</button>
                                    </form>
                                @endif
                            </div>
                            <dl class="row mt-3 mb-0">
                                <dt class="col-sm-6">Total considered ERP orders</dt><dd class="col-sm-6">{{ $report['reconciliation']['total_considered_orders'] }}</dd>
                                <dt class="col-sm-6">Attributed orders</dt><dd class="col-sm-6">{{ $report['reconciliation']['attributed_orders'] }}</dd>
                                <dt class="col-sm-6">Unattributed orders</dt><dd class="col-sm-6">{{ $report['reconciliation']['unattributed_orders'] }}</dd>
                                <dt class="col-sm-6">Direct or organic</dt><dd class="col-sm-6">{{ $report['reconciliation']['direct_or_organic_orders'] }}</dd>
                                <dt class="col-sm-6">Pending or unresolved</dt><dd class="col-sm-6">{{ $report['reconciliation']['pending_or_unresolved_orders'] }}</dd>
                                <dt class="col-sm-6">Last reconciliation</dt><dd class="col-sm-6">{{ $report['reconciliation']['last_reconciliation_at'] ?: 'Unavailable' }}</dd>
                            </dl>
                            @if (!$canReconcile)
                                <p class="text-muted mt-3 mb-0">Your role does not have <code>fb_marketing_attribution_reports_reconcile.update</code>.</p>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title mb-1">Data freshness</h5>
                            <p class="text-muted">Report render uses local database rows only.</p>
                            <dl class="row mb-0">
                                <dt class="col-sm-6">Latest snapshot date</dt><dd class="col-sm-6">{{ $report['freshness']['latest_snapshot_date'] ?: 'Unavailable' }}</dd>
                                <dt class="col-sm-6">Latest fetched at</dt><dd class="col-sm-6">{{ $report['freshness']['latest_fetched_at'] ?: 'Unavailable' }}</dd>
                                <dt class="col-sm-6">Freshness watermark</dt><dd class="col-sm-6">{{ $report['freshness']['freshness_watermark'] ?: 'Unavailable' }}</dd>
                                <dt class="col-sm-6">Latest ERP snapshot</dt><dd class="col-sm-6">{{ $report['freshness']['latest_order_snapshot_at'] ?: 'Unavailable' }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-6 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">Attribution status breakdown</h5>
                            @include('backend.fb-marketing._attribution-breakdown-table', ['rows' => $report['status_breakdown'], 'money' => $money])
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">Attribution method breakdown</h5>
                            @include('backend.fb-marketing._attribution-breakdown-table', ['rows' => $report['method_breakdown'], 'money' => $money])
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Campaign, ad set and ad performance</h5>
                    <p class="text-muted">Spend is from stored ad-level Insights snapshots. ERP revenue is from immutable attribution snapshots and append-only amount adjustments.</p>
                    @if (empty($report['performance_rows']))
                        <p class="text-muted mb-0">No performance row matched the current filters.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th>Campaign</th>
                                        <th>Ad set</th>
                                        <th>Ad</th>
                                        <th>Spend</th>
                                        <th>Attributed orders</th>
                                        <th>Attributed revenue</th>
                                        <th>CPA</th>
                                        <th>ROAS</th>
                                        <th>Revenue minus spend</th>
                                        <th>Snapshot</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($report['performance_rows'] as $row)
                                        <tr>
                                            <td>{{ $row['campaign_name'] }}</td>
                                            <td>{{ $row['ad_set_name'] }}</td>
                                            <td>{{ $row['ad_name'] }}</td>
                                            <td>{{ $money($row['spend'], $row['currency']) }}</td>
                                            <td>{{ $row['attributed_orders'] }}</td>
                                            <td>{{ $money($row['attributed_revenue'], $row['currency']) }}</td>
                                            <td>{{ $money($row['cost_per_attributed_order'], $row['currency']) }}</td>
                                            <td>{{ number_format($row['roas'], 4) }}</td>
                                            <td>{{ $money($row['revenue_minus_spend'], $row['currency']) }}</td>
                                            <td>{{ $row['latest_snapshot_date'] ?: 'ERP only' }}</td>
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

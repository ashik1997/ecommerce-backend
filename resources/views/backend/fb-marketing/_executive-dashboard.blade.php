@php
    $comparisonPresentation = function (array $comparison): array {
        switch ($comparison['state'] ?? 'unavailable') {
            case 'up':
                return ['class' => 'success', 'text' => '↑ ' . number_format((float) $comparison['percent'], 1) . '% vs previous period'];
            case 'down':
                return ['class' => 'danger', 'text' => '↓ ' . number_format((float) $comparison['percent'], 1) . '% vs previous period'];
            case 'new_activity':
                return ['class' => 'info', 'text' => 'New activity vs previous period'];
            case 'no_change':
                return ['class' => 'secondary', 'text' => 'No change vs previous period'];
            default:
                return ['class' => 'light border', 'text' => 'Comparison unavailable'];
        }
    };
    $formatMoney = fn($value): string => number_format((float) $value, 2);
    $formatCount = fn($value): string => number_format((float) $value, ((float) $value == (int) $value) ? 0 : 2);
@endphp

<div class="card">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-start mb-3">
            <div>
                <h5 class="card-title mb-1">Executive stored-snapshot dashboard</h5>
                <p class="text-muted mb-0">Application-local read-only reporting from stored account-level Insights snapshots. This page sends no Meta API request.</p>
            </div>
            <span class="badge badge-{{ $executiveDashboard['schema_ready'] ? 'success' : 'danger' }} px-3 py-2">
                {{ $executiveDashboard['schema_ready'] ? 'FBM-09 reporting ready' : 'FBM-08 migration required' }}
            </span>
        </div>

        <form class="border rounded p-3 mb-3" method="GET" action="{{ route('fbMarketing.dashboard') }}">
            <div class="row align-items-end">
                <div class="col-lg-3 col-md-6 mb-2">
                    <label class="mb-1" for="fbm-dashboard-from-date">From date</label>
                    <input class="form-control" id="fbm-dashboard-from-date" type="date" name="from_date" value="{{ $executiveDashboard['filters']['from_date'] }}" required>
                </div>
                <div class="col-lg-3 col-md-6 mb-2">
                    <label class="mb-1" for="fbm-dashboard-to-date">To date</label>
                    <input class="form-control" id="fbm-dashboard-to-date" type="date" name="to_date" value="{{ $executiveDashboard['filters']['to_date'] }}" required>
                </div>
                <div class="col-lg-3 col-md-6 mb-2">
                    <label class="mb-1" for="fbm-dashboard-ad-account">Ad account</label>
                    <select class="form-control" id="fbm-dashboard-ad-account" name="ad_account_id">
                        <option value="">All selected and available accounts</option>
                        @foreach ($executiveDashboard['account_options'] as $account)
                            <option value="{{ $account['id'] }}" {{ (int) $executiveDashboard['filters']['ad_account_id'] === (int) $account['id'] ? 'selected' : '' }}>
                                {{ $account['asset_name'] }} · {{ $account['currency'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-3 col-md-6 mb-2">
                    <div class="form-check mb-2">
                        <input type="hidden" name="compare_period" value="0">
                        <input class="form-check-input" id="fbm-dashboard-compare-period" type="checkbox" name="compare_period" value="1" {{ $executiveDashboard['filters']['compare_period'] ? 'checked' : '' }}>
                        <label class="form-check-label" for="fbm-dashboard-compare-period">Compare previous equal-length period</label>
                    </div>
                    <button class="btn btn-primary btn-sm" type="submit">Apply filters</button>
                    <a class="btn btn-outline-secondary btn-sm" href="{{ route('fbMarketing.dashboard') }}">Reset</a>
                </div>
            </div>
            <small class="text-muted d-block mt-2">
                Selected range: <strong>{{ $executiveDashboard['range']['from_date'] }}</strong> → <strong>{{ $executiveDashboard['range']['to_date'] }}</strong> · {{ $executiveDashboard['range']['days'] }} calendar day(s)
                @if ($executiveDashboard['comparison_range'])
                    · Comparison: <strong>{{ $executiveDashboard['comparison_range']['from_date'] }}</strong> → <strong>{{ $executiveDashboard['comparison_range']['to_date'] }}</strong>
                @endif
            </small>
        </form>

        @foreach ($executiveDashboard['warnings'] as $warning)
            <div class="alert alert-{{ $warning['type'] }} py-2 mb-2">{{ $warning['message'] }}</div>
        @endforeach

        @if ($executiveDashboard['has_rows'])
            <div class="row mt-3">
                @foreach ([
                    ['label' => 'Impressions', 'value' => number_format($executiveDashboard['delivery']['impressions']), 'comparison' => 'impressions'],
                    ['label' => 'Clicks', 'value' => number_format($executiveDashboard['delivery']['clicks']), 'comparison' => 'clicks'],
                    ['label' => 'CTR', 'value' => number_format($executiveDashboard['delivery']['ctr'], 2) . '%', 'comparison' => 'ctr'],
                    ['label' => 'Summed daily reach', 'value' => number_format($executiveDashboard['delivery']['summed_daily_reach']), 'comparison' => 'summed_daily_reach'],
                    ['label' => 'Meta-reported purchases', 'value' => $formatCount($executiveDashboard['delivery']['meta_purchase_count']), 'comparison' => 'meta_purchase_count'],
                    ['label' => 'Current active campaigns', 'value' => number_format($executiveDashboard['active_campaign_count']), 'comparison' => null],
                ] as $card)
                    <div class="col-xl-2 col-lg-4 col-md-6 mb-3">
                        <div class="border rounded p-3 h-100">
                            <small class="text-muted d-block">{{ $card['label'] }}</small>
                            <div class="h4 mb-1">{{ $card['value'] }}</div>
                            @if ($executiveDashboard['filters']['compare_period'] && $card['comparison'])
                                @php $presentation = $comparisonPresentation($executiveDashboard['delivery']['comparisons'][$card['comparison']]); @endphp
                                <small class="text-{{ $presentation['class'] }}">{{ $presentation['text'] }}</small>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="alert alert-light border py-2">
                <strong>Reach note:</strong> Summed daily reach adds daily snapshots and may include repeat users across multiple days. It is not an exact unique date-range reach value.
            </div>

            <div class="row">
                @foreach ($executiveDashboard['currency_groups'] as $group)
                    <div class="col-xl-6 mb-3">
                        <div class="border rounded p-3 h-100">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <strong>Financial totals · {{ $group['currency'] }}</strong>
                                <span class="badge badge-light border">Account-level snapshots only</span>
                            </div>
                            <div class="row">
                                @foreach ([
                                    ['label' => 'Meta ad spend', 'value' => $formatMoney($group['spend']), 'comparison' => 'spend'],
                                    ['label' => 'CPC', 'value' => $formatMoney($group['cpc']), 'comparison' => 'cpc'],
                                    ['label' => 'CPM', 'value' => $formatMoney($group['cpm']), 'comparison' => 'cpm'],
                                    ['label' => 'Meta-reported purchase value', 'value' => $formatMoney($group['meta_purchase_value']), 'comparison' => 'meta_purchase_value'],
                                ] as $financialCard)
                                    <div class="col-md-6 mb-2">
                                        <div class="bg-light rounded p-2 h-100">
                                            <small class="text-muted d-block">{{ $financialCard['label'] }}</small>
                                            <strong>{{ $group['currency'] }} {{ $financialCard['value'] }}</strong>
                                            @if ($executiveDashboard['filters']['compare_period'])
                                                @php $presentation = $comparisonPresentation($group['comparisons'][$financialCard['comparison']]); @endphp
                                                <small class="text-{{ $presentation['class'] }} d-block">{{ $presentation['text'] }}</small>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="alert alert-light border mb-3">
                No stored account-level Insights rows are available for the selected date range. Run an authorized queued read-only sync after selecting an Ad Account.
            </div>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-start mb-3">
            <div>
                <h5 class="card-title mb-1">Reporting freshness and coverage</h5>
                <p class="text-muted mb-0">Coverage checks use selected account-days, so one missing account snapshot cannot be hidden by another account's row.</p>
            </div>
            <span class="badge badge-{{ $executiveDashboard['coverage']['missing_account_days'] > 0 ? 'warning' : 'success' }} px-3 py-2">
                {{ number_format($executiveDashboard['coverage']['coverage_percent'], 1) }}% account-day coverage
            </span>
        </div>
        <div class="row">
            <div class="col-lg-3 col-md-6 mb-2"><div class="border rounded p-3 h-100"><small class="text-muted d-block">Latest snapshot date</small><strong>{{ $executiveDashboard['latest_snapshot_date'] ?: '—' }}</strong></div></div>
            <div class="col-lg-3 col-md-6 mb-2"><div class="border rounded p-3 h-100"><small class="text-muted d-block">Freshness watermark</small><strong>{{ $executiveDashboard['freshness_watermark'] ?: '—' }}</strong></div></div>
            <div class="col-lg-3 col-md-6 mb-2"><div class="border rounded p-3 h-100"><small class="text-muted d-block">Latest fetched time</small><strong>{{ $executiveDashboard['latest_fetched_at'] ?: '—' }}</strong></div></div>
            <div class="col-lg-3 col-md-6 mb-2"><div class="border rounded p-3 h-100"><small class="text-muted d-block">Selected account-day coverage</small><strong>{{ number_format($executiveDashboard['coverage']['covered_account_days']) }} / {{ number_format($executiveDashboard['coverage']['expected_account_days']) }}</strong></div></div>
        </div>

        @if ($executiveDashboard['coverage']['missing_dates'])
            <p class="text-muted mb-1 mt-2">
                Incomplete date(s): <strong>{{ implode(', ', $executiveDashboard['coverage']['missing_dates']) }}</strong>
                @if ($executiveDashboard['coverage']['hidden_missing_date_count'] > 0)
                    · and {{ number_format($executiveDashboard['coverage']['hidden_missing_date_count']) }} more
                @endif
            </p>
        @endif

        @if ($executiveDashboard['comparison_coverage'])
            <p class="text-muted mb-1 mt-2">
                Previous-period account-day coverage: <strong>{{ number_format($executiveDashboard['comparison_coverage']['covered_account_days']) }} / {{ number_format($executiveDashboard['comparison_coverage']['expected_account_days']) }}</strong>
                · {{ number_format($executiveDashboard['comparison_coverage']['coverage_percent'], 1) }}%
            </p>
        @endif

        @if ($executiveDashboard['latest_report'])
            <p class="text-muted mb-0 mt-2">
                Latest selected-account report: <strong>{{ ucwords(str_replace('_', ' ', $executiveDashboard['latest_report']['status'])) }}</strong>
                · {{ ucwords($executiveDashboard['latest_report']['execution_mode']) }}
                · {{ ucwords($executiveDashboard['latest_report']['insight_level']) }} level
                · {{ number_format($executiveDashboard['latest_report']['row_count']) }} row(s)
                @if ($executiveDashboard['latest_report']['redacted_message'])
                    · {{ $executiveDashboard['latest_report']['redacted_message'] }}
                @endif
            </p>
        @endif
    </div>
</div>

@if ($executiveDashboard['has_rows'])
    <div class="card">
        <div class="card-body">
            <h5 class="card-title mb-1">Daily stored-snapshot trend</h5>
            <p class="text-muted">Bars are relative within each currency group. Financial series are never merged across currencies.</p>

            @foreach ($executiveDashboard['trend_groups'] as $trendGroup)
                <div class="border rounded p-3 mb-3">
                    <strong class="d-block mb-2">Currency group · {{ $trendGroup['currency'] }}</strong>
                    @foreach ($trendGroup['rows'] as $row)
                        <div class="row align-items-center mb-2">
                            <div class="col-lg-2"><small><strong>{{ $row['date'] }}</strong></small></div>
                            <div class="col-lg-10">
                                <small class="d-block">Spend: {{ $row['currency'] }} {{ $formatMoney($row['spend']) }}</small>
                                <div class="progress mb-1" style="height: 6px;"><div class="progress-bar" role="progressbar" style="width: {{ $row['spend_bar_percent'] }}%" aria-valuenow="{{ $row['spend_bar_percent'] }}" aria-valuemin="0" aria-valuemax="100"></div></div>
                                <small class="d-block">Clicks: {{ number_format($row['clicks']) }}</small>
                                <div class="progress mb-1" style="height: 6px;"><div class="progress-bar bg-info" role="progressbar" style="width: {{ $row['clicks_bar_percent'] }}%" aria-valuenow="{{ $row['clicks_bar_percent'] }}" aria-valuemin="0" aria-valuemax="100"></div></div>
                                <small class="d-block">Purchases: {{ $formatCount($row['meta_purchase_count']) }}</small>
                                <div class="progress" style="height: 6px;"><div class="progress-bar bg-success" role="progressbar" style="width: {{ $row['purchases_bar_percent'] }}%" aria-valuenow="{{ $row['purchases_bar_percent'] }}" aria-valuemin="0" aria-valuemax="100"></div></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach

            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                    <thead>
                        <tr>
                            <th>Date</th><th>Currency</th><th>Spend</th><th>Impressions</th><th>Clicks</th><th>CTR</th><th>CPC</th><th>CPM</th><th>Purchases</th><th>Purchase value</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($executiveDashboard['daily_rows'] as $row)
                            <tr>
                                <td>{{ $row['date'] }}</td>
                                <td>{{ $row['currency'] }}</td>
                                <td>{{ $formatMoney($row['spend']) }}</td>
                                <td>{{ number_format($row['impressions']) }}</td>
                                <td>{{ number_format($row['clicks']) }}</td>
                                <td>{{ number_format($row['ctr'], 2) }}%</td>
                                <td>{{ $formatMoney($row['cpc']) }}</td>
                                <td>{{ $formatMoney($row['cpm']) }}</td>
                                <td>{{ $formatCount($row['meta_purchase_count']) }}</td>
                                <td>{{ $formatMoney($row['meta_purchase_value']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif

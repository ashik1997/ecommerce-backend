<div class="card">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-start mb-3">
            <div>
                <h5 class="card-title mb-1">Currency-safe stored-snapshot totals</h5>
                <p class="text-muted mb-0">Spend, CPC and CPM are derived independently inside each currency group. Reach is summed daily reach, not exact unique reach across the whole range.</p>
            </div>
            <span class="badge badge-{{ $performance['mixed_currency'] ? 'warning' : 'info' }} px-3 py-2">{{ $performance['mixed_currency'] ? 'Multiple currencies separated' : 'Currency-safe totals' }}</span>
        </div>

        @if (empty($performance['currency_groups']))
            <p class="text-muted mb-0">No matching stored snapshots are available for this range.</p>
        @else
            @foreach ($performance['currency_groups'] as $metrics)
                <div class="border rounded p-3 mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong>{{ $metrics['currency'] }}</strong>
                        <small class="text-muted">Latest snapshot: {{ $metrics['latest_snapshot_date'] ?: '—' }}</small>
                    </div>
                    <div class="row">
                        <div class="col-lg-2 col-md-4 mb-2"><small class="text-muted d-block">Spend</small><strong>{{ number_format($metrics['spend'], 2) }}</strong></div>
                        <div class="col-lg-2 col-md-4 mb-2"><small class="text-muted d-block">Impressions</small><strong>{{ number_format($metrics['impressions']) }}</strong></div>
                        <div class="col-lg-2 col-md-4 mb-2"><small class="text-muted d-block">Clicks</small><strong>{{ number_format($metrics['clicks']) }}</strong></div>
                        <div class="col-lg-2 col-md-4 mb-2"><small class="text-muted d-block">CTR</small><strong>{{ number_format($metrics['ctr'], 2) }}%</strong></div>
                        <div class="col-lg-2 col-md-4 mb-2"><small class="text-muted d-block">CPC</small><strong>{{ number_format($metrics['cpc'], 2) }}</strong></div>
                        <div class="col-lg-2 col-md-4 mb-2"><small class="text-muted d-block">CPM</small><strong>{{ number_format($metrics['cpm'], 2) }}</strong></div>
                        <div class="col-lg-2 col-md-4 mb-2"><small class="text-muted d-block">Summed daily reach</small><strong>{{ number_format($metrics['summed_daily_reach']) }}</strong></div>
                        <div class="col-lg-2 col-md-4 mb-2"><small class="text-muted d-block">Inline-link clicks</small><strong>{{ number_format($metrics['inline_link_clicks']) }}</strong></div>
                        <div class="col-lg-2 col-md-4 mb-2"><small class="text-muted d-block">Meta results</small><strong>{{ number_format($metrics['meta_result_count'], 2) }}</strong></div>
                        <div class="col-lg-2 col-md-4 mb-2"><small class="text-muted d-block">Meta result value</small><strong>{{ number_format($metrics['meta_result_value'], 2) }}</strong></div>
                        <div class="col-lg-2 col-md-4 mb-2"><small class="text-muted d-block">Meta purchases</small><strong>{{ number_format($metrics['meta_purchase_count'], 2) }}</strong></div>
                        <div class="col-lg-2 col-md-4 mb-2"><small class="text-muted d-block">Meta purchase value</small><strong>{{ number_format($metrics['meta_purchase_value'], 2) }}</strong></div>
                    </div>
                </div>
            @endforeach
        @endif

        <p class="text-muted mb-0">Coverage: <strong>{{ $performance['coverage']['coverage_percent'] }}%</strong> · {{ number_format($performance['coverage']['covered_entity_days']) }} / {{ number_format($performance['coverage']['expected_entity_days']) }} expected entity-day row(s) · Freshness watermark: <strong>{{ $performance['freshness_watermark'] ?: '—' }}</strong></p>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-start mb-3">
            <div>
                <h5 class="card-title mb-1">Needs-attention review worklist</h5>
                <p class="text-muted mb-0">Dynamic stored-snapshot indicators only. These are review prompts, not automatic optimization decisions or Meta write actions.</p>
            </div>
            <span class="badge badge-{{ $worklist['total_count'] > 0 ? 'warning' : 'success' }} px-3 py-2">{{ number_format($worklist['total_count']) }} indicator(s)</span>
        </div>
        <p class="text-muted small">CTR review threshold: {{ number_format($worklist['ctr_review_threshold_percent'], 2) }}% after {{ number_format($worklist['minimum_impressions_for_ctr_review']) }} impression(s). Stale threshold: {{ $worklist['stale_after_days'] }} day(s).</p>

        @if (empty($worklist['items']))
            <p class="text-muted mb-0">No review indicator was derived from the current rows.</p>
        @else
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                    <thead><tr><th>Severity</th><th>Entity</th><th>Ad account</th><th>Indicator</th><th>Message</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($worklist['items'] as $item)
                            <tr>
                                <td><span class="badge badge-{{ $item['severity'] }}">{{ ucfirst($item['severity']) }}</span></td>
                                <td>{{ $item['entity_name'] }}</td>
                                <td>{{ $item['ad_account_name'] }}</td>
                                <td>{{ ucwords(str_replace('_', ' ', $item['indicator'])) }}</td>
                                <td>{{ $item['message'] }}</td>
                                <td><a class="btn btn-sm btn-outline-primary" href="{{ route($item['drilldown_route'], array_merge([$item['drilldown_parameter'] => $item['entity_id']], request()->only(['from_date', 'to_date', 'status', 'search']))) }}">Review</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($worklist['hidden_count'] > 0)
                <p class="text-muted mt-2 mb-0">{{ number_format($worklist['hidden_count']) }} additional indicator(s) are hidden by the bounded UI limit.</p>
            @endif
        @endif
    </div>
</div>

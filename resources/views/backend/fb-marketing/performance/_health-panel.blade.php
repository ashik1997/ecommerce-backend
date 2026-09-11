<div class="card">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-start mb-3">
            <div>
                <h5 class="card-title mb-1">Safe operational health</h5>
                <p class="text-muted mb-0">Redacted diagnostics only. Tokens, provider identifiers, raw URLs, raw payloads and internal fingerprints are not displayed.</p>
            </div>
            <span class="badge badge-{{ $health['recent_api_error_count'] > 0 ? 'warning' : 'success' }} px-3 py-2">{{ number_format($health['recent_api_error_count']) }} recent API issue(s)</span>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <div class="border rounded p-3 h-100">
                    <small class="text-muted d-block">Latest sync</small>
                    @if ($health['latest_sync'])
                        <strong>{{ ucwords(str_replace('_', ' ', $health['latest_sync']['status'])) }}</strong>
                        <p class="text-muted mb-0">{{ $health['latest_sync']['redacted_message'] ?: 'No safe diagnostic message.' }}</p>
                    @else
                        <p class="text-muted mb-0">No sync ledger row is available.</p>
                    @endif
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="border rounded p-3 h-100">
                    <small class="text-muted d-block">Latest Insights report</small>
                    @if ($health['latest_report'])
                        <strong>{{ ucwords(str_replace('_', ' ', $health['latest_report']['status'])) }} · {{ ucwords($health['latest_report']['insight_level']) }} level</strong>
                        <p class="text-muted mb-0">{{ $health['latest_report']['redacted_message'] ?: 'No safe diagnostic message.' }}</p>
                    @else
                        <p class="text-muted mb-0">No Insights report ledger row is available.</p>
                    @endif
                </div>
            </div>
        </div>

        @if ($health['recent_api_errors']->isNotEmpty())
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                    <thead><tr><th>Time</th><th>Operation</th><th>HTTP</th><th>Provider code</th><th>Subcode</th><th>Duration</th><th>Redacted message</th></tr></thead>
                    <tbody>
                        @foreach ($health['recent_api_errors'] as $log)
                            <tr>
                                <td>{{ $log['created_at'] ?: '—' }}</td>
                                <td>{{ $log['operation_key'] }}</td>
                                <td>{{ $log['http_status'] === null ? '—' : $log['http_status'] }}</td>
                                <td>{{ $log['provider_error_code'] ?: '—' }}</td>
                                <td>{{ $log['provider_error_subcode'] ?: '—' }}</td>
                                <td>{{ $log['duration_ms'] === null ? '—' : number_format($log['duration_ms']) . ' ms' }}</td>
                                <td>{{ $log['redacted_message'] ?: 'Safe error metadata recorded.' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-start mb-3">
            <div>
                <h5 class="card-title mb-1">{{ $tableTitle }}</h5>
                <p class="text-muted mb-0">Each row uses only {{ $rowLevel }}-level stored snapshots. Provider identifiers and raw Graph payloads remain hidden.</p>
            </div>
            <span class="badge badge-light border px-3 py-2">{{ number_format(count($tableRows)) }} row(s)</span>
        </div>

        @if (empty($tableRows))
            <p class="text-muted mb-0">No matching locally mirrored rows are available.</p>
        @else
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Ad account</th>
                            <th>Status</th>
                            <th>Currency</th>
                            <th>Spend</th>
                            <th>Impressions</th>
                            <th>Clicks</th>
                            <th>CTR</th>
                            <th>Meta results</th>
                            <th>Coverage</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tableRows as $row)
                            <tr>
                                <td><strong>{{ $row['name'] }}</strong></td>
                                <td>{{ $row['ad_account_name'] }}</td>
                                <td>{{ $row['effective_status'] ?: ($row['configured_status'] ?: '—') }}</td>
                                <td>{{ $row['currency'] }}</td>
                                <td>{{ number_format($row['spend'], 2) }}</td>
                                <td>{{ number_format($row['impressions']) }}</td>
                                <td>{{ number_format($row['clicks']) }}</td>
                                <td>{{ number_format($row['ctr'], 2) }}%</td>
                                <td>{{ number_format($row['meta_result_count'], 2) }}</td>
                                <td>{{ $row['covered_days'] }} / {{ $performance['range']['days'] }} day(s)</td>
                                <td>
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route($row['drilldown_route'], array_merge([$row['drilldown_parameter'] => $row['id']], request()->only(['from_date', 'to_date', 'status', 'search']))) }}">Open</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

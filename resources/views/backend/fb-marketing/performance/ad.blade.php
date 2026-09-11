@extends('backend.master')

@section('title')
    Ad Performance
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="page-title mb-1">Ad: {{ $performance['entity']['name'] }}</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('fbMarketing.dashboard') }}">FB Marketing</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('fbMarketing.performance.index', request()->only(['from_date', 'to_date', 'ad_account_id', 'status', 'search'])) }}">Performance</a></li>
                            @if ($performance['entity']['fbm_campaign_id'])
                                <li class="breadcrumb-item"><a href="{{ route('fbMarketing.performance.campaigns.show', array_merge(['campaign' => $performance['entity']['fbm_campaign_id']], request()->only(['from_date', 'to_date', 'status', 'search']))) }}">Campaign</a></li>
                            @endif
                            @if ($performance['entity']['fbm_ad_set_id'])
                                <li class="breadcrumb-item"><a href="{{ route('fbMarketing.performance.ad-sets.show', array_merge(['adSet' => $performance['entity']['fbm_ad_set_id']], request()->only(['from_date', 'to_date', 'status', 'search']))) }}">Ad Set</a></li>
                            @endif
                            <li class="breadcrumb-item active">Ad</li>
                        </ul>
                    </div>
                </div>
            </div>

            @include('backend.fb-marketing._status-card')
            @include('backend.fb-marketing.performance._warnings')
            @include('backend.fb-marketing.performance._filters', ['filterAction' => route('fbMarketing.performance.ads.show', $performance['entity']['id']), 'showAccountFilter' => false])
            <div class="alert alert-info">Campaign: <strong>{{ $performance['entity']['campaign_name'] ?: '—' }}</strong> · Ad Set: <strong>{{ $performance['entity']['ad_set_name'] ?: '—' }}</strong> · Creative: <strong>{{ $performance['entity']['creative_name'] ?: '—' }}</strong> · Status: <strong>{{ $performance['entity']['effective_status'] ?: ($performance['entity']['configured_status'] ?: '—') }}</strong></div>
            @include('backend.fb-marketing.performance._metrics')

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Ad daily stored snapshots</h5>
                    @if (empty($performance['daily_rows']))
                        <p class="text-muted mb-0">No ad-level stored snapshot exists for this range.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead><tr><th>Date</th><th>Currency</th><th>Spend</th><th>Impressions</th><th>Clicks</th><th>CTR</th><th>CPC</th><th>Meta results</th><th>Meta purchases</th></tr></thead>
                                <tbody>
                                    @foreach ($performance['daily_rows'] as $row)
                                        <tr>
                                            <td>{{ $row['date'] }}</td>
                                            <td>{{ $row['currency'] }}</td>
                                            <td>{{ number_format($row['spend'], 2) }}</td>
                                            <td>{{ number_format($row['impressions']) }}</td>
                                            <td>{{ number_format($row['clicks']) }}</td>
                                            <td>{{ number_format($row['ctr'], 2) }}%</td>
                                            <td>{{ number_format($row['cpc'], 2) }}</td>
                                            <td>{{ number_format($row['meta_result_count'], 2) }}</td>
                                            <td>{{ number_format($row['meta_purchase_count'], 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            @include('backend.fb-marketing.performance._health-panel')
        </div>
    </div>
@endsection

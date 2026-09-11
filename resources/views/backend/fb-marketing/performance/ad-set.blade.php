@extends('backend.master')

@section('title')
    Ad Set Performance
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="page-title mb-1">Ad Set: {{ $performance['entity']['name'] }}</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('fbMarketing.dashboard') }}">FB Marketing</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('fbMarketing.performance.index', request()->only(['from_date', 'to_date', 'ad_account_id', 'status', 'search'])) }}">Performance</a></li>
                            @if ($performance['entity']['fbm_campaign_id'])
                                <li class="breadcrumb-item"><a href="{{ route('fbMarketing.performance.campaigns.show', array_merge(['campaign' => $performance['entity']['fbm_campaign_id']], request()->only(['from_date', 'to_date', 'status', 'search']))) }}">Campaign</a></li>
                            @endif
                            <li class="breadcrumb-item active">Ad Set</li>
                        </ul>
                    </div>
                </div>
            </div>

            @include('backend.fb-marketing._status-card')
            @include('backend.fb-marketing.performance._warnings')
            @include('backend.fb-marketing.performance._filters', ['filterAction' => route('fbMarketing.performance.ad-sets.show', $performance['entity']['id']), 'showAccountFilter' => false])
            <div class="alert alert-info">Campaign: <strong>{{ $performance['entity']['campaign_name'] ?: '—' }}</strong> · Optimization goal: <strong>{{ $performance['entity']['optimization_goal'] ?: '—' }}</strong> · Status: <strong>{{ $performance['entity']['effective_status'] ?: ($performance['entity']['configured_status'] ?: '—') }}</strong></div>
            @include('backend.fb-marketing.performance._metrics')
            @include('backend.fb-marketing.performance._entity-table', ['tableTitle' => 'Ad performance', 'tableRows' => $performance['child_rows'], 'rowLevel' => 'ad'])
            @include('backend.fb-marketing.performance._worklist')
            @include('backend.fb-marketing.performance._health-panel')
        </div>
    </div>
@endsection

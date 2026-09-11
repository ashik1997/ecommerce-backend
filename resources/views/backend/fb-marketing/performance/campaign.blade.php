@extends('backend.master')

@section('title')
    Campaign Performance
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="page-title mb-1">Campaign: {{ $performance['entity']['name'] }}</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('fbMarketing.dashboard') }}">FB Marketing</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('fbMarketing.performance.index', request()->only(['from_date', 'to_date', 'ad_account_id', 'status', 'search'])) }}">Performance</a></li>
                            <li class="breadcrumb-item active">Campaign</li>
                        </ul>
                    </div>
                </div>
            </div>

            @include('backend.fb-marketing._status-card')
            @include('backend.fb-marketing.performance._warnings')
            @include('backend.fb-marketing.performance._filters', ['filterAction' => route('fbMarketing.performance.campaigns.show', $performance['entity']['id']), 'showAccountFilter' => false])
            <div class="alert alert-info">Ad account: <strong>{{ $performance['entity']['ad_account_name'] }}</strong> · Objective: <strong>{{ $performance['entity']['objective'] ?: '—' }}</strong> · Status: <strong>{{ $performance['entity']['effective_status'] ?: ($performance['entity']['configured_status'] ?: '—') }}</strong></div>
            @include('backend.fb-marketing.performance._metrics')
            @include('backend.fb-marketing.performance._entity-table', ['tableTitle' => 'Ad Set performance', 'tableRows' => $performance['child_rows'], 'rowLevel' => 'ad-set'])
            @include('backend.fb-marketing.performance._worklist')
            @include('backend.fb-marketing.performance._health-panel')
        </div>
    </div>
@endsection

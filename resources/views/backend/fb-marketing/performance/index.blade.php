@extends('backend.master')

@section('title')
    FB Marketing Performance
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="page-title mb-1">FB Marketing Performance</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('fbMarketing.dashboard') }}">FB Marketing</a></li>
                            <li class="breadcrumb-item active">Performance</li>
                        </ul>
                    </div>
                    @if ($canViewConfiguration)
                        <div class="col-auto"><a class="btn btn-outline-primary" href="{{ route('fbMarketing.configuration.index') }}">Open configuration</a></div>
                    @endif
                </div>
            </div>

            @include('backend.fb-marketing._status-card')
            @include('backend.fb-marketing.performance._warnings')
            @include('backend.fb-marketing.performance._filters', ['filterAction' => route('fbMarketing.performance.index'), 'showAccountFilter' => true])
            @include('backend.fb-marketing.performance._metrics')
            @include('backend.fb-marketing.performance._entity-table', ['tableTitle' => 'Campaign performance drilldown', 'tableRows' => $performance['rows'], 'rowLevel' => 'campaign'])
            @include('backend.fb-marketing.performance._worklist')
            @include('backend.fb-marketing.performance._health-panel')
        </div>
    </div>
@endsection

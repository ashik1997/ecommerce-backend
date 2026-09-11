@extends('backend.master')

@section('page_title', 'Courier Settlement Report')
@section('page_heading', 'Courier Settlement Report')

@section('content')
    @php
        $isDeliveryContext = request()->routeIs('delivery-management.*');
        $printRoute = $isDeliveryContext
            ? route('delivery-management.courier-settlements.print', $settlement->id)
            : route('courier-settlements.print', $settlement->id);
    @endphp
    @if ($isDeliveryContext)
        @include('backend.delivery_management.partials.nav')
    @endif
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">{{ $settlement->settlement_code }}</h5>
                <small class="text-muted">{{ ucfirst($settlement->courier) }} | {{ optional($settlement->settlement_date)->format('Y-m-d') }}</small>
            </div>
            <a class="btn btn-sm btn-primary" target="_blank" href="{{ $printRoute }}">Print Report</a>
        </div>
        <div class="card-body">
            @include('backend.courier_settlement.partials.report_table', ['settlement' => $settlement])
        </div>
    </div>
@endsection

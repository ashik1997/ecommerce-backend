@extends('backend.master')
@section('page_title', 'Delivery Rate Card')
@section('page_heading', 'Delivery Rate Card')
@section('content')
    @include('backend.delivery_management.partials.nav')

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">{{ $rateCard->provider->name ?? 'Rate Card' }}</h4>
                <a href="{{ route('delivery-management.rate-cards.edit', $rateCard) }}" class="btn btn-primary">Edit</a>
            </div>
            <table class="table table-bordered">
                <tr><th style="width: 220px;">Provider</th><td>{{ $rateCard->provider->name ?? 'N/A' }}</td></tr>
                <tr><th>Zone</th><td>{{ $rateCard->zone->name ?? 'Any' }}</td></tr>
                <tr><th>Service Type</th><td>{{ $rateCard->serviceType->name ?? 'Any' }}</td></tr>
                <tr><th>Base Charge</th><td>{{ number_format((float) $rateCard->base_charge, 2) }}</td></tr>
                <tr><th>Additional Weight Charge</th><td>{{ number_format((float) $rateCard->additional_weight_charge, 2) }}</td></tr>
                <tr><th>Return Charge</th><td>{{ number_format((float) $rateCard->return_charge, 2) }}</td></tr>
                <tr><th>Status</th><td>{{ ucfirst($rateCard->status) }}</td></tr>
            </table>
        </div>
    </div>
@endsection

@extends('backend.master')
@section('page_title', 'Delivery Provider')
@section('page_heading', 'Delivery Provider')
@section('content')
    @include('backend.delivery_management.partials.nav')

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">{{ $provider->name }}</h4>
                <a href="{{ route('delivery-management.providers.edit', $provider) }}" class="btn btn-primary">Edit</a>
            </div>
            <table class="table table-bordered">
                <tr><th style="width: 220px;">Type</th><td>{{ ucwords(str_replace('_', ' ', $provider->provider_type)) }}</td></tr>
                <tr><th>Driver</th><td>{{ $provider->integration_driver ?: 'N/A' }}</td></tr>
                <tr><th>Contact</th><td>{{ $provider->contact_person ?: 'N/A' }} / {{ $provider->phone ?: 'N/A' }}</td></tr>
                <tr><th>Email</th><td>{{ $provider->email ?: 'N/A' }}</td></tr>
                <tr><th>Address</th><td>{{ $provider->address ?: 'N/A' }}</td></tr>
                <tr><th>Status</th><td>{{ ucfirst($provider->status) }}</td></tr>
            </table>
        </div>
    </div>
@endsection

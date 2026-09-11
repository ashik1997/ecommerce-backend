@extends('backend.master')
@section('page_title', 'Delivery Zone')
@section('page_heading', 'Delivery Zone')
@section('content')
    @include('backend.delivery_management.partials.nav')
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">{{ $zone->name }}</h4>
                <a href="{{ route('delivery-management.zones.edit', $zone) }}" class="btn btn-primary">Edit</a>
            </div>
            <table class="table table-bordered">
                <tr><th style="width: 220px;">Slug</th><td>{{ $zone->slug }}</td></tr>
                <tr><th>Website</th><td>{{ $zone->product_website_id ?: 'All' }}</td></tr>
                <tr><th>Status</th><td>{{ ucfirst($zone->status) }}</td></tr>
                <tr><th>Description</th><td>{{ $zone->description ?: 'N/A' }}</td></tr>
            </table>
        </div>
    </div>
@endsection

@extends('backend.master')
@section('page_title', 'Delivery Shipments')
@section('page_heading', 'Delivery Shipments')
@section('content')
    @include('backend.delivery_management.partials.nav')

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                <h4 class="mb-2">Delivery Shipments</h4>
                <span class="text-muted mb-2">Shipments are synced from order flows without changing POS behavior.</span>
            </div>

            <form method="get" class="row mb-3">
                <div class="col-md-4 mb-2">
                    <input type="text" name="q" class="form-control" value="{{ request('q') }}" placeholder="Search shipment, customer, tracking">
                </div>
                <div class="col-md-3 mb-2">
                    <select name="provider_id" class="form-control">
                        <option value="">All Providers</option>
                        @foreach ($providers as $provider)
                            <option value="{{ $provider->id }}" {{ (string) request('provider_id') === (string) $provider->id ? 'selected' : '' }}>{{ $provider->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <select name="current_status" class="form-control">
                        <option value="">All Status</option>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" {{ request('current_status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <button class="btn btn-dark btn-block"><i class="feather-filter"></i> Filter</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Shipment</th>
                            <th>Order</th>
                            <th>Recipient</th>
                            <th>Provider</th>
                            <th>Charge</th>
                            <th>Status</th>
                            <th style="width: 90px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($shipments as $shipment)
                            <tr>
                                <td>
                                    <strong>{{ $shipment->shipment_code }}</strong>
                                    <div class="small text-muted">{{ $shipment->tracking_number ?: 'No tracking' }}</div>
                                </td>
                                <td>
                                    {{ $shipment->order->order_code ?? $shipment->source_reference }}
                                    <div class="small text-muted">{{ $shipment->source_type }}</div>
                                </td>
                                <td>
                                    {{ $shipment->recipient_name ?: 'N/A' }}
                                    <div class="small text-muted">{{ $shipment->recipient_phone }}</div>
                                </td>
                                <td>{{ $shipment->provider->name ?? 'N/A' }}</td>
                                <td>{{ number_format((float) $shipment->customer_delivery_charge, 2) }}</td>
                                <td><span class="badge badge-info">{{ ucwords(str_replace('_', ' ', $shipment->current_status)) }}</span></td>
                                <td><a href="{{ route('delivery-management.shipments.show', $shipment) }}" class="btn btn-sm btn-info">View</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted">No shipment found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $shipments->links() }}
        </div>
    </div>
@endsection

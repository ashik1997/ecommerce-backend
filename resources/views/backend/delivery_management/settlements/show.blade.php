@extends('backend.master')
@section('page_title', 'Delivery Settlement')
@section('page_heading', 'Delivery Settlement')
@section('content')
    @include('backend.delivery_management.partials.nav')

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                <div>
                    <h4 class="mb-1">{{ $settlement->settlement_code }}</h4>
                    <div class="text-muted">{{ $types[$settlement->settlement_type] ?? $settlement->settlement_type }}</div>
                </div>
                @if ($settlement->status === 'draft')
                    <form method="post" action="{{ route('delivery-management.settlements.approve', $settlement) }}">
                        @csrf
                        <button class="btn btn-success" onclick="return confirm('Approve this settlement?')">Approve</button>
                    </form>
                @endif
            </div>
            <div class="row">
                <div class="col-md-3 mb-2"><div class="border p-3"><div class="text-muted">COD</div><h4>{{ number_format((float) $settlement->total_cod_amount, 2) }}</h4></div></div>
                <div class="col-md-3 mb-2"><div class="border p-3"><div class="text-muted">Delivery Cost</div><h4>{{ number_format((float) $settlement->total_delivery_cost, 2) }}</h4></div></div>
                <div class="col-md-3 mb-2"><div class="border p-3"><div class="text-muted">Adjustment</div><h4>{{ number_format((float) $settlement->total_adjustment, 2) }}</h4></div></div>
                <div class="col-md-3 mb-2"><div class="border p-3"><div class="text-muted">Net</div><h4>{{ number_format((float) $settlement->net_amount, 2) }}</h4></div></div>
            </div>
            <table class="table table-bordered mt-3">
                <tr><th style="width: 220px;">Provider</th><td>{{ $settlement->provider->name ?? 'N/A' }}</td></tr>
                <tr><th>Employee</th><td>{{ $settlement->employee->name ?? 'N/A' }}</td></tr>
                <tr><th>Status</th><td>{{ $statuses[$settlement->status] ?? ucfirst($settlement->status) }}</td></tr>
                <tr><th>Note</th><td>{{ $settlement->note ?: 'N/A' }}</td></tr>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="mb-3">Settlement Items</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead><tr><th>Shipment</th><th>Provider</th><th>COD</th><th>Cost</th><th>Adjustment</th><th>Net</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse ($settlement->items as $item)
                            <tr>
                                <td>
                                    @if ($item->shipment)
                                        <a href="{{ route('delivery-management.shipments.show', $item->shipment) }}">{{ $item->shipment->shipment_code }}</a>
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td>{{ $item->shipment->provider->name ?? 'N/A' }}</td>
                                <td>{{ number_format((float) $item->cod_amount, 2) }}</td>
                                <td>{{ number_format((float) $item->delivery_cost, 2) }}</td>
                                <td>{{ number_format((float) $item->adjustment_amount, 2) }}</td>
                                <td>{{ number_format((float) $item->net_amount, 2) }}</td>
                                <td><span class="badge badge-info">{{ ucfirst($item->status) }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted">No settlement item found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

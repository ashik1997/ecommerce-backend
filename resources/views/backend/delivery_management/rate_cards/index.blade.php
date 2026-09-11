@extends('backend.master')
@section('page_title', 'Delivery Rate Cards')
@section('page_heading', 'Delivery Rate Cards')
@section('content')
    @include('backend.delivery_management.partials.nav')

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                <h4 class="mb-2">Provider Rate Cards</h4>
                <a href="{{ route('delivery-management.rate-cards.create') }}" class="btn btn-primary mb-2">
                    <i class="feather-plus-circle"></i> Create Rate Card
                </a>
            </div>

            <form method="get" class="row mb-3">
                <div class="col-md-3 mb-2">
                    <select name="provider_id" class="form-control">
                        <option value="">All Providers</option>
                        @foreach ($providers as $provider)
                            <option value="{{ $provider->id }}" {{ (string) request('provider_id') === (string) $provider->id ? 'selected' : '' }}>{{ $provider->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <select name="zone_id" class="form-control">
                        <option value="">All Zones</option>
                        @foreach ($zones as $zone)
                            <option value="{{ $zone->id }}" {{ (string) request('zone_id') === (string) $zone->id ? 'selected' : '' }}>{{ $zone->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <select name="status" class="form-control">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <button class="btn btn-dark btn-block"><i class="feather-filter"></i> Filter</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead><tr><th>Provider</th><th>Zone</th><th>Weight</th><th>Base Charge</th><th>COD</th><th>Return</th><th>Status</th><th style="width: 160px;">Action</th></tr></thead>
                    <tbody>
                        @forelse ($rateCards as $rateCard)
                            <tr>
                                <td>{{ $rateCard->provider->name ?? 'N/A' }}</td>
                                <td>{{ $rateCard->zone->name ?? 'Any' }}</td>
                                <td>{{ number_format((float) $rateCard->minimum_weight, 2) }} - {{ $rateCard->maximum_weight !== null ? number_format((float) $rateCard->maximum_weight, 2) : 'Open' }}</td>
                                <td>{{ number_format((float) $rateCard->base_charge, 2) }}</td>
                                <td>{{ $codChargeTypes[$rateCard->cod_charge_type] ?? ($rateCard->cod_charge_type ?: 'N/A') }} / {{ number_format((float) $rateCard->cod_charge_value, 2) }}</td>
                                <td>{{ number_format((float) $rateCard->return_charge, 2) }}</td>
                                <td><span class="badge badge-{{ $rateCard->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($rateCard->status) }}</span></td>
                                <td>
                                    <a href="{{ route('delivery-management.rate-cards.edit', $rateCard) }}" class="btn btn-sm btn-info">Edit</a>
                                    <form action="{{ route('delivery-management.rate-cards.destroy', $rateCard) }}" method="post" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-warning" onclick="return confirm('Deactivate this rate card?')">Deactivate</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted">No rate card found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $rateCards->links() }}
        </div>
    </div>
@endsection

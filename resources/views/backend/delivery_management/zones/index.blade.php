@extends('backend.master')
@section('page_title', 'Delivery Zones')
@section('page_heading', 'Delivery Zones')
@section('content')
    @include('backend.delivery_management.partials.nav')

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                <h4 class="mb-2">Delivery Zones</h4>
                <a href="{{ route('delivery-management.zones.create') }}" class="btn btn-primary mb-2">
                    <i class="feather-plus-circle"></i> Create Zone
                </a>
            </div>

            <form method="get" class="row mb-3">
                <div class="col-md-5 mb-2">
                    <input type="text" name="q" class="form-control" value="{{ request('q') }}" placeholder="Search zone">
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
                    <thead><tr><th>Name</th><th>Areas</th><th>Website</th><th>Status</th><th style="width: 160px;">Action</th></tr></thead>
                    <tbody>
                        @forelse ($zones as $zone)
                            <tr>
                                <td><strong>{{ $zone->name }}</strong><div class="small text-muted">{{ $zone->slug }}</div></td>
                                <td>{{ number_format($zone->areas_count) }}</td>
                                <td>{{ $zone->product_website_id ?: 'All' }}</td>
                                <td><span class="badge badge-{{ $zone->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($zone->status) }}</span></td>
                                <td>
                                    <a href="{{ route('delivery-management.zones.edit', $zone) }}" class="btn btn-sm btn-info">Edit</a>
                                    <form action="{{ route('delivery-management.zones.destroy', $zone) }}" method="post" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-warning" onclick="return confirm('Deactivate this zone?')">Deactivate</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">No delivery zone found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $zones->links() }}
        </div>
    </div>
@endsection

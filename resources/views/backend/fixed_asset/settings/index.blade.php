@extends('backend.master')
@section('header_css')
@include('backend.fixed_asset._style')
@endsection

@section('content')
<div class="page-content">
    <div class="container-fluid">
        @include('backend.fixed_asset.partials.nav')

        <div class="row mb-3">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div>
                            <h4 class="mb-1">Fixed Asset Module Setup</h4>
                            <p class="text-muted mb-0">
                                This installer creates only missing Fixed Asset account heads. It will not duplicate accounts or overwrite balances.
                            </p>
                            <div class="mt-2">
                                @if(($installStatus['installed'] ?? false) === true)
                                    <span class="badge badge-success">Installed</span>
                                    <small class="text-muted ml-2">{{ $installStatus['existing'] ?? 0 }}/{{ $installStatus['total'] ?? 0 }} required heads found.</small>
                                @else
                                    <span class="badge badge-warning">Not Fully Installed</span>
                                    <small class="text-muted ml-2">Missing {{ $installStatus['missing_count'] ?? 0 }} of {{ $installStatus['total'] ?? 0 }} required heads.</small>
                                @endif
                            </div>
                        </div>

                        <form method="POST" action="{{ route('fixed-assets.settings.ensure-accounts') }}" onsubmit="return confirm('This will create only missing Fixed Asset account heads. Existing heads and balances will not be changed. Continue?')">
                            @csrf
                            <button type="submit" class="btn {{ ($installStatus['installed'] ?? false) ? 'btn-secondary' : 'btn-dark' }}" {{ ($installStatus['installed'] ?? false) ? 'disabled' : '' }}>
                                @if(($installStatus['installed'] ?? false) === true)
                                    Already Installed
                                @else
                                    Run Fixed Asset Installer
                                @endif
                            </button>
                        </form>
                    </div>

                    @if(!($installStatus['installed'] ?? false) && !empty($installStatus['missing']))
                        <div class="card-footer bg-light">
                            <strong>Missing heads:</strong>
                            <span class="text-muted">{{ implode(', ', $installStatus['missing']) }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h4>Add Category</h4>
                        <form method="POST" action="{{ route('fixed-assets.settings.categories.store') }}">
                            @csrf
                            <input name="name" class="form-control mb-2" placeholder="Category Name">
                            <input name="code" class="form-control mb-2" placeholder="Code e.g. IT">
                            <select name="tracking_mode" class="form-control mb-2">
                                <option value="individual">Individual</option>
                                <option value="pooled">Pooled</option>
                            </select>
                            <input name="default_useful_life_months" type="number" class="form-control mb-2" placeholder="Useful life months">
                            <label><input type="checkbox" name="is_depreciable" value="1" checked> Depreciable</label><br>
                            <button class="btn btn-fa-primary">Save Category</button>
                        </form>

                        <hr>
                        <h5>Categories</h5>
                        <ul class="mb-0">
                            @foreach($categories as $c)
                                <li>{{ $c->name }} ({{ $c->code }})</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h4>Add Warehouse Location</h4>
                        <form method="POST" action="{{ route('fixed-assets.settings.locations.store') }}">
                            @csrf
                            <select name="warehouse_id" class="form-control mb-2">
                                <option value="">Warehouse</option>
                                @foreach($warehouses as $w)
                                    <option value="{{ $w->id }}">{{ $w->title }}</option>
                                @endforeach
                            </select>
                            <input name="name" class="form-control mb-2" placeholder="Room / Zone / Rack">
                            <input name="code" class="form-control mb-2" placeholder="Code">
                            <select name="type" class="form-control mb-2">
                                <option value="room">Room</option>
                                <option value="zone">Zone</option>
                                <option value="rack">Rack</option>
                                <option value="desk">Desk</option>
                                <option value="building">Building</option>
                                <option value="floor">Floor</option>
                                <option value="other">Other</option>
                            </select>
                            <button class="btn btn-fa-primary">Save Location</button>
                        </form>

                        <hr>
                        <h5>Locations</h5>
                        <ul class="mb-0">
                            @forelse($locations as $location)
                                <li>{{ $location->name }} @if($location->code)({{ $location->code }})@endif</li>
                            @empty
                                <li class="text-muted">No location added yet.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

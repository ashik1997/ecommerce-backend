@extends('backend.master')
@section('page_title', 'Services')
@section('page_heading', 'Services')
@section('content')
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="mb-1">Service Catalog</h5>
                    <p class="mb-0 text-muted">Manage rental, sale, and mixed services before attaching products.</p>
                </div>
                <a href="{{ route('service-management.services.create') }}" class="btn btn-primary">Create Service</a>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Base Price</th>
                            <th>Billing Unit</th>
                            <th>Status</th>
                            <th style="width: 160px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($services as $service)
                            <tr>
                                <td>
                                    <strong>{{ $service->name }}</strong>
                                    @if ($service->description)
                                        <div class="small text-muted">{{ \Illuminate\Support\Str::limit($service->description, 80) }}</div>
                                    @endif
                                </td>
                                <td>{{ ucfirst($service->type) }}</td>
                                <td>{{ number_format((float) $service->base_price, 2) }}</td>
                                <td>{{ ucfirst($service->billing_unit) }}</td>
                                <td>
                                    <span class="badge {{ $service->is_active ? 'badge-success' : 'badge-secondary' }}">
                                        {{ $service->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('service-management.services.edit', $service) }}" class="btn btn-sm btn-info mb-1">Edit</a>
                                    @if ($service->is_active)
                                        <form method="POST" action="{{ route('service-management.services.destroy', $service) }}" class="d-inline" onsubmit="return confirm('Archive this service?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-warning mb-1">Archive</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">No services found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $services->links() }}
        </div>
    </div>
@endsection

@extends('backend.master')
@section('header_css')
    <link href="{{ url('assets') }}/plugins/select2/select2.min.css" rel="stylesheet" type="text/css" />
@endsection
@section('page_title', 'Edit Service')
@section('page_heading', 'Edit Service')
@section('content')
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="mb-1">Edit Service</h5>
                    <p class="mb-0 text-muted">Update service pricing, billing unit, and availability.</p>
                </div>
                <a href="{{ route('service-management.services.index') }}" class="btn btn-secondary">Back to Services</a>
            </div>
            <form method="POST" action="{{ route('service-management.services.update', $service) }}">
                @method('PUT')
                @include('backend.service_management.services._form')
                <button class="btn btn-success">Update Service</button>
            </form>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-body">
            <h5 class="mb-2">Linked Products</h5>
            <p class="text-muted">
                Attach optional or required products that should auto-load when this service is selected.
            </p>

            <form method="POST" action="{{ route('service-management.services.products.store', $service) }}" class="mb-4">
                @csrf
                <div class="row">
                    <div class="col-md-4 mb-2">
                        <label class="form-label">Product <span class="text-danger">*</span></label>
                        <select name="product_id" id="service_product_id" class="form-control @error('product_id') is-invalid @enderror" required></select>
                        @error('product_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="form-label">Quantity <span class="text-danger">*</span></label>
                        <input type="number" step="1" min="1" name="quantity_required" class="form-control @error('quantity_required') is-invalid @enderror" value="{{ old('quantity_required', 1) }}" required>
                        @error('quantity_required')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="form-label">Default Unit Price</label>
                        <input type="number" step="0.01" min="0" name="default_unit_price" class="form-control @error('default_unit_price') is-invalid @enderror" value="{{ old('default_unit_price') }}" placeholder="Product price">
                        @error('default_unit_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="form-label">Requirement</label>
                        <div class="form-control">
                            <label class="mb-0"><input type="checkbox" name="is_required" value="1" {{ old('is_required') ? 'checked' : '' }}> Required</label>
                        </div>
                    </div>
                    <div class="col-md-2 mb-2 d-flex align-items-end">
                        <button class="btn btn-primary btn-block">Attach Product</button>
                    </div>
                    <div class="col-md-12 mb-2">
                        <input name="note" class="form-control @error('note') is-invalid @enderror" value="{{ old('note') }}" placeholder="Note">
                        @error('note')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Default Unit Price</th>
                            <th>Required</th>
                            <th>Note</th>
                            <th style="width: 180px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($service->serviceProducts as $serviceProduct)
                            <tr>
                                <form method="POST" action="{{ route('service-management.services.products.update', [$service, $serviceProduct]) }}">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="product_id" value="{{ $serviceProduct->product_id }}">
                                    <td>
                                        <strong>{{ $serviceProduct->product->name ?? 'Product not found' }}</strong>
                                        <div class="small text-muted">
                                            SKU: {{ $serviceProduct->product->sku ?? 'N/A' }}
                                        </div>
                                    </td>
                                    <td>
                                        <input type="number" step="1" min="1" name="quantity_required" value="{{ (int) $serviceProduct->quantity_required }}" class="form-control" required>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" name="default_unit_price" value="{{ $serviceProduct->default_unit_price }}" class="form-control" placeholder="{{ number_format((float) ($serviceProduct->product ? ($serviceProduct->product->discount_price ?: $serviceProduct->product->price) : 0), 2) }}">
                                    </td>
                                    <td>
                                        <label class="mb-0"><input type="checkbox" name="is_required" value="1" {{ $serviceProduct->is_required ? 'checked' : '' }}> Required</label>
                                    </td>
                                    <td>
                                        <input type="text" name="note" value="{{ $serviceProduct->note }}" class="form-control">
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-info mb-1">Update</button>
                                </form>
                                        <form method="POST" action="{{ route('service-management.services.products.destroy', [$service, $serviceProduct]) }}" class="d-inline" onsubmit="return confirm('Remove this product from service?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-danger mb-1">Remove</button>
                                        </form>
                                    </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">No products attached to this service yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('footer_js')
    <script src="{{ url('assets') }}/plugins/select2/select2.min.js"></script>
    <script>
        $('#service_product_id').select2({
            width: '100%',
            placeholder: 'Search product by name, SKU, or code',
            allowClear: true,
            ajax: {
                url: '{{ route('product-management.search-products') }}',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        term: params.term || '',
                        exclude: @json($service->serviceProducts->pluck('product_id')->values()),
                    };
                },
                processResults: function (data) {
                    return data;
                },
            },
            templateResult: function (item) {
                if (!item.id) {
                    return item.text;
                }

                var price = item.price ? ' - ' + Number(item.price).toFixed(2) : '';
                var sku = item.sku ? ' [' + item.sku + ']' : '';

                return item.text + sku + price;
            }
        });
    </script>
@endsection

@if ($errors->any())
    <div class="alert alert-danger">
        <strong>Validation failed.</strong>
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label>Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $provider->name) }}" required>
            </div>
            <div class="col-md-3 mb-3">
                <label>Provider Type</label>
                <select name="provider_type" class="form-control">
                    @foreach ($providerTypes as $value => $label)
                        <option value="{{ $value }}" {{ old('provider_type', $provider->provider_type) === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 mb-3">
                <label>Integration Driver</label>
                <select name="integration_driver" class="form-control">
                    @foreach ($integrationDrivers as $value => $label)
                        <option value="{{ $value }}" {{ old('integration_driver', $provider->integration_driver) === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 mb-3">
                <label>Website ID</label>
                <input type="number" name="product_website_id" class="form-control" value="{{ old('product_website_id', $provider->product_website_id) }}">
            </div>
            <div class="col-md-3 mb-3">
                <label>Slug</label>
                <input type="text" name="slug" class="form-control" value="{{ old('slug', $provider->slug) }}" placeholder="Auto from name">
            </div>
            <div class="col-md-3 mb-3">
                <label>Service Scope</label>
                <input type="text" name="service_scope" class="form-control" value="{{ old('service_scope', $provider->service_scope) }}" placeholder="inside_dhaka, nationwide">
            </div>
            <div class="col-md-3 mb-3">
                <label>Status</label>
                <select name="status" class="form-control">
                    @foreach (['active' => 'Active', 'inactive' => 'Inactive'] as $value => $label)
                        <option value="{{ $value }}" {{ old('status', $provider->status ?: 'active') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label>Contact Person</label>
                <input type="text" name="contact_person" class="form-control" value="{{ old('contact_person', $provider->contact_person) }}">
            </div>
            <div class="col-md-4 mb-3">
                <label>Phone</label>
                <input type="text" name="phone" class="form-control" value="{{ old('phone', $provider->phone) }}">
            </div>
            <div class="col-md-4 mb-3">
                <label>Email</label>
                <input type="email" name="email" class="form-control" value="{{ old('email', $provider->email) }}">
            </div>
            <div class="col-md-12 mb-3">
                <label>Address</label>
                <textarea name="address" class="form-control" rows="3">{{ old('address', $provider->address) }}</textarea>
            </div>
        </div>

        <div class="d-flex justify-content-between">
            <a href="{{ route('delivery-management.providers.index') }}" class="btn btn-outline-secondary">Back</a>
            <button type="submit" class="btn btn-primary">
                <i class="feather-save"></i> Save Provider
            </button>
        </div>
    </div>
</div>

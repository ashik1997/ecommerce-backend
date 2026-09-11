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
            <div class="col-md-4 mb-3">
                <label>Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $zone->name) }}" required>
            </div>
            <div class="col-md-3 mb-3">
                <label>Slug</label>
                <input type="text" name="slug" class="form-control" value="{{ old('slug', $zone->slug) }}" placeholder="Auto from name">
            </div>
            <div class="col-md-2 mb-3">
                <label>Website ID</label>
                <input type="number" name="product_website_id" class="form-control" value="{{ old('product_website_id', $zone->product_website_id) }}">
            </div>
            <div class="col-md-3 mb-3">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="active" {{ old('status', $zone->status ?: 'active') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status', $zone->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="col-md-12 mb-3">
                <label>Description</label>
                <textarea name="description" rows="3" class="form-control">{{ old('description', $zone->description) }}</textarea>
            </div>
        </div>

        <div class="d-flex justify-content-between">
            <a href="{{ route('delivery-management.zones.index') }}" class="btn btn-outline-secondary">Back</a>
            <button type="submit" class="btn btn-primary">
                <i class="feather-save"></i> Save Zone
            </button>
        </div>
    </div>
</div>

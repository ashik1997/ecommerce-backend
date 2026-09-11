@csrf
<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label">Service Name <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $service->name ?? '') }}" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-2 mb-3">
        <label class="form-label">Type <span class="text-danger">*</span></label>
        <select name="type" class="form-control @error('type') is-invalid @enderror" required>
            @foreach ($types as $type)
                <option value="{{ $type }}" {{ old('type', $service->type ?? 'sale') === $type ? 'selected' : '' }}>{{ ucfirst($type) }}</option>
            @endforeach
        </select>
        @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-2 mb-3">
        <label class="form-label">Base Price <span class="text-danger">*</span></label>
        <input type="number" step="0.01" min="0" name="base_price" class="form-control @error('base_price') is-invalid @enderror" value="{{ old('base_price', $service->base_price ?? 0) }}" required>
        @error('base_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-2 mb-3">
        <label class="form-label">Billing Unit <span class="text-danger">*</span></label>
        <select name="billing_unit" class="form-control @error('billing_unit') is-invalid @enderror" required>
            @foreach ($billingUnits as $unit)
                <option value="{{ $unit }}" {{ old('billing_unit', $service->billing_unit ?? 'unit') === $unit ? 'selected' : '' }}>{{ ucfirst($unit) }}</option>
            @endforeach
        </select>
        @error('billing_unit')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    @isset($service)
        <div class="col-md-2 mb-3">
            <label class="form-label">Status</label>
            <div class="form-control">
                <label class="mb-0"><input type="checkbox" name="is_active" value="1" {{ old('is_active', $service->is_active) ? 'checked' : '' }}> Active</label>
            </div>
        </div>
    @endisset
    <div class="col-md-12 mb-3">
        <label class="form-label">Description</label>
        <textarea name="description" rows="3" class="form-control @error('description') is-invalid @enderror">{{ old('description', $service->description ?? '') }}</textarea>
        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

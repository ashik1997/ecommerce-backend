@php($isEdit = isset($affiliate) && $affiliate)
<div class="row">
    <div class="col-md-6 form-group">
        <label>Name <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $isEdit ? $affiliate->name : '') }}" required>
        @error('name')<small class="text-danger">{{ $message }}</small>@enderror
    </div>
    <div class="col-md-3 form-group">
        <label>Code <span class="text-danger">*</span></label>
        <input type="text" name="code" class="form-control text-uppercase" value="{{ old('code', $isEdit ? $affiliate->code : '') }}" required pattern="[A-Za-z0-9_-]+">
        <small class="text-muted">Only letters, numbers, dash and underscore. It will be saved uppercase.</small>
        @error('code')<small class="text-danger d-block">{{ $message }}</small>@enderror
    </div>
    <div class="col-md-3 form-group">
        <label>Status</label>
        <select name="status" class="form-control">
            @foreach(['active' => 'Active', 'inactive' => 'Inactive'] as $key => $label)
                <option value="{{ $key }}" {{ old('status', $isEdit ? $affiliate->status : 'active') == $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4 form-group">
        <label>Phone</label>
        <input type="text" name="phone" class="form-control" value="{{ old('phone', $isEdit ? $affiliate->phone : '') }}">
    </div>
    <div class="col-md-4 form-group">
        <label>Email</label>
        <input type="email" name="email" class="form-control" value="{{ old('email', $isEdit ? $affiliate->email : '') }}">
    </div>
    <div class="col-md-4 form-group">
        <label>Commission Base</label>
        <select name="default_commission_base" class="form-control">
            <option value="sale_amount" {{ old('default_commission_base', $isEdit ? $affiliate->default_commission_base : 'sale_amount') == 'sale_amount' ? 'selected' : '' }}>Sale Amount</option>
            <option value="gross_profit" {{ old('default_commission_base', $isEdit ? $affiliate->default_commission_base : '') == 'gross_profit' ? 'selected' : '' }}>Gross Profit</option>
        </select>
    </div>
    <div class="col-md-4 form-group">
        <label>Commission Type</label>
        <select name="default_commission_type" class="form-control">
            <option value="percentage" {{ old('default_commission_type', $isEdit ? $affiliate->default_commission_type : 'percentage') == 'percentage' ? 'selected' : '' }}>Percentage</option>
            <option value="fixed" {{ old('default_commission_type', $isEdit ? $affiliate->default_commission_type : '') == 'fixed' ? 'selected' : '' }}>Fixed</option>
        </select>
    </div>
    <div class="col-md-4 form-group">
        <label>Commission Value</label>
        <input type="number" step="0.01" min="0" name="default_commission_value" class="form-control" value="{{ old('default_commission_value', $isEdit ? $affiliate->default_commission_value : 0) }}">
    </div>
    <div class="col-md-12 form-group">
        <label>Note</label>
        <textarea name="note" class="form-control" rows="3">{{ old('note', $isEdit ? $affiliate->note : '') }}</textarea>
    </div>
</div>

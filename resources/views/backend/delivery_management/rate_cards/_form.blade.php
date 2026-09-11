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
                <label>Provider <span class="text-danger">*</span></label>
                <select name="provider_id" class="form-control" required>
                    <option value="">Select provider</option>
                    @foreach ($providers as $provider)
                        <option value="{{ $provider->id }}" {{ (string) old('provider_id', $rateCard->provider_id) === (string) $provider->id ? 'selected' : '' }}>{{ $provider->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label>Zone</label>
                <select name="zone_id" class="form-control">
                    <option value="">Any zone</option>
                    @foreach ($zones as $zone)
                        <option value="{{ $zone->id }}" {{ (string) old('zone_id', $rateCard->zone_id) === (string) $zone->id ? 'selected' : '' }}>{{ $zone->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label>Service Type</label>
                <select name="service_type_id" class="form-control">
                    <option value="">Any service</option>
                    @foreach ($serviceTypes as $serviceType)
                        <option value="{{ $serviceType->id }}" {{ (string) old('service_type_id', $rateCard->service_type_id) === (string) $serviceType->id ? 'selected' : '' }}>{{ $serviceType->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 mb-3">
                <label>Minimum Weight</label>
                <input type="number" step="0.01" name="minimum_weight" class="form-control" value="{{ old('minimum_weight', $rateCard->minimum_weight ?? 0) }}">
            </div>
            <div class="col-md-3 mb-3">
                <label>Maximum Weight</label>
                <input type="number" step="0.01" name="maximum_weight" class="form-control" value="{{ old('maximum_weight', $rateCard->maximum_weight) }}">
            </div>
            <div class="col-md-3 mb-3">
                <label>Base Charge</label>
                <input type="number" step="0.01" name="base_charge" class="form-control" value="{{ old('base_charge', $rateCard->base_charge ?? 0) }}">
            </div>
            <div class="col-md-3 mb-3">
                <label>Additional Weight Charge</label>
                <input type="number" step="0.01" name="additional_weight_charge" class="form-control" value="{{ old('additional_weight_charge', $rateCard->additional_weight_charge ?? 0) }}">
            </div>
            <div class="col-md-3 mb-3">
                <label>COD Charge Type</label>
                <select name="cod_charge_type" class="form-control">
                    <option value="">Select COD type</option>
                    @foreach ($codChargeTypes as $value => $label)
                        <option value="{{ $value }}" {{ old('cod_charge_type', $rateCard->cod_charge_type) === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 mb-3">
                <label>COD Charge Value</label>
                <input type="number" step="0.01" name="cod_charge_value" class="form-control" value="{{ old('cod_charge_value', $rateCard->cod_charge_value ?? 0) }}">
            </div>
            <div class="col-md-3 mb-3">
                <label>Return Charge</label>
                <input type="number" step="0.01" name="return_charge" class="form-control" value="{{ old('return_charge', $rateCard->return_charge ?? 0) }}">
            </div>
            <div class="col-md-3 mb-3">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="active" {{ old('status', $rateCard->status ?: 'active') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status', $rateCard->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
        </div>

        <div class="d-flex justify-content-between">
            <a href="{{ route('delivery-management.rate-cards.index') }}" class="btn btn-outline-secondary">Back</a>
            <button type="submit" class="btn btn-primary">
                <i class="feather-save"></i> Save Rate Card
            </button>
        </div>
    </div>
</div>

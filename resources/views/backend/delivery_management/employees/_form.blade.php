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
                <input type="text" name="name" class="form-control" value="{{ old('name', $employee->name) }}" required>
            </div>
            <div class="col-md-4 mb-3">
                <label>Linked User</label>
                <select name="user_id" class="form-control">
                    <option value="">No user linked</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" {{ (string) old('user_id', $employee->user_id) === (string) $user->id ? 'selected' : '' }}>
                            {{ $user->name }}{{ $user->phone ? ' - ' . $user->phone : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 mb-3">
                <label>Website ID</label>
                <input type="number" name="product_website_id" class="form-control" value="{{ old('product_website_id', $employee->product_website_id) }}">
            </div>
            <div class="col-md-2 mb-3">
                <label>Branch ID</label>
                <input type="number" name="branch_id" class="form-control" value="{{ old('branch_id', $employee->branch_id) }}">
            </div>

            <div class="col-md-3 mb-3">
                <label>Phone</label>
                <input type="text" name="phone" class="form-control" value="{{ old('phone', $employee->phone) }}">
            </div>
            <div class="col-md-3 mb-3">
                <label>Email</label>
                <input type="email" name="email" class="form-control" value="{{ old('email', $employee->email) }}">
            </div>
            <div class="col-md-3 mb-3">
                <label>NID</label>
                <input type="text" name="nid" class="form-control" value="{{ old('nid', $employee->nid) }}">
            </div>
            <div class="col-md-3 mb-3">
                <label>Joining Date</label>
                <input type="date" name="joining_date" class="form-control" value="{{ old('joining_date', optional($employee->joining_date)->format('Y-m-d')) }}">
            </div>

            <div class="col-md-3 mb-3">
                <label>Vehicle Type</label>
                <input type="text" name="vehicle_type" class="form-control" value="{{ old('vehicle_type', $employee->vehicle_type) }}" placeholder="Bike, Cycle, Van">
            </div>
            <div class="col-md-3 mb-3">
                <label>Vehicle Number</label>
                <input type="text" name="vehicle_number" class="form-control" value="{{ old('vehicle_number', $employee->vehicle_number) }}">
            </div>
            <div class="col-md-3 mb-3">
                <label>Current Status</label>
                <select name="current_status" class="form-control">
                    @foreach ($currentStatuses as $value => $label)
                        <option value="{{ $value }}" {{ old('current_status', $employee->current_status ?: 'available') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 mb-3">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="active" {{ old('status', $employee->status ?: 'active') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status', $employee->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <div class="col-md-3 mb-3">
                <label>Salary Type</label>
                <select name="salary_type" class="form-control">
                    <option value="">Select salary type</option>
                    @foreach ($salaryTypes as $value => $label)
                        <option value="{{ $value }}" {{ old('salary_type', $employee->salary_type) === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 mb-3">
                <label>Salary Amount</label>
                <input type="number" step="0.01" name="salary_amount" class="form-control" value="{{ old('salary_amount', $employee->salary_amount ?? 0) }}">
            </div>
            <div class="col-md-3 mb-3">
                <label>Commission Type</label>
                <select name="commission_type" class="form-control">
                    <option value="">Select commission type</option>
                    @foreach ($commissionTypes as $value => $label)
                        <option value="{{ $value }}" {{ old('commission_type', $employee->commission_type) === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 mb-3">
                <label>Commission Amount</label>
                <input type="number" step="0.01" name="commission_amount" class="form-control" value="{{ old('commission_amount', $employee->commission_amount ?? 0) }}">
            </div>
            <div class="col-md-3 mb-3">
                <label>Cash Collection Limit</label>
                <input type="number" step="0.01" name="cash_collection_limit" class="form-control" value="{{ old('cash_collection_limit', $employee->cash_collection_limit) }}">
            </div>
            <div class="col-md-3 mb-3">
                <label>Employee Profile ID</label>
                <input type="number" name="employee_profile_id" class="form-control" value="{{ old('employee_profile_id', $employee->employee_profile_id) }}">
            </div>
        </div>

        <div class="d-flex justify-content-between">
            <a href="{{ route('delivery-management.employees.index') }}" class="btn btn-outline-secondary">Back</a>
            <button type="submit" class="btn btn-primary">
                <i class="feather-save"></i> Save Employee
            </button>
        </div>
    </div>
</div>

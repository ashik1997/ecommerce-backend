@extends('backend.master')
@section('header_css')
    <link href="{{ url('assets') }}/plugins/select2/select2.min.css" rel="stylesheet" type="text/css" />
@endsection
@section('page_title', 'Employee Profiles')
@section('page_heading', 'Employee Profiles')
@section('content')
    <div class="card mb-3"><div class="card-body">
        <form method="POST" action="{{ route('hrat.employee-profiles.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-3 mb-2">
                    <label class="form-label">User</label>
                    @include('backend.hrat.partials.employee-select', ['employees' => $users, 'name' => 'user_id'])
                </div>
                <div class="col-md-2 mb-2">
                    <label class="form-label">Employee Code <span class="text-danger">*</span></label>
                    <input name="employee_code" class="form-control" placeholder="EMP-001" required>
                </div>
                <div class="col-md-2 mb-2">
                    <label class="form-label">Department</label>
                    <select name="department_id" class="form-control">
                        <option value="">Department</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}">{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label class="form-label">Designation</label>
                    <select name="designation_id" class="form-control">
                        <option value="">Designation</option>
                        @foreach ($designations as $designation)
                            <option value="{{ $designation->id }}">{{ $designation->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label class="form-label">Branch</label>
                    <select name="branch_id" class="form-control">
                        <option value="">Branch</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1 mb-2">
                    <label class="form-label">Type</label>
                    <select name="employment_type" class="form-control">
                        <option value="">Type</option>
                        <option value="permanent">Permanent</option>
                        <option value="probation">Probation</option>
                        <option value="contract">Contract</option>
                        <option value="part_time">Part Time</option>
                        <option value="intern">Intern</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label class="form-label">Joining Date</label>
                    <input name="joining_date" type="date" class="form-control">
                </div>
                <div class="col-md-2 mb-2">
                    <label class="form-label">Confirmation Date</label>
                    <input name="confirmation_date" type="date" class="form-control">
                </div>
                <div class="col-md-2 mb-2">
                    <label class="form-label">Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-control" required>
                        <option value="active">Active</option>
                        <option value="probation">Probation</option>
                        <option value="resigned">Resigned</option>
                        <option value="terminated">Terminated</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <label class="form-label">Reporting Manager</label>
                    @include('backend.hrat.partials.employee-select', ['employees' => $managers, 'name' => 'reporting_manager_user_id'])
                </div>
                <div class="col-md-2 mb-2">
                    <label class="form-label">Emergency Name</label>
                    <input name="emergency_contact_name" class="form-control" placeholder="Emergency name">
                </div>
                <div class="col-md-2 mb-2">
                    <label class="form-label">Emergency Phone</label>
                    <input name="emergency_contact_phone" class="form-control" placeholder="Emergency phone">
                </div>
                <div class="col-md-1 mb-2 d-flex align-items-end"><button class="btn btn-success">Save</button></div>
            </div>
        </form>
    </div></div>

    <div class="card"><div class="card-body">
        <table class="table table-bordered table-sm">
            <tr><th>User</th><th>Code</th><th>Department</th><th>Designation</th><th>Branch</th><th>Type</th><th>Joining</th><th>Status</th><th>Manager</th><th>Action</th></tr>
            @foreach ($profiles as $profile)
                <tr>
                    <form method="POST" action="{{ route('hrat.employee-profiles.update', $profile) }}">
                        @csrf @method('PUT')
                        <td style="min-width: 220px;">@include('backend.hrat.partials.employee-select', ['employees' => $users, 'name' => 'user_id', 'selected' => $profile->user_id])</td>
                        <td><input name="employee_code" value="{{ $profile->employee_code }}" class="form-control" required></td>
                        <td>
                            <select name="department_id" class="form-control">
                                <option value="">Department</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}" {{ (string) $profile->department_id === (string) $department->id ? 'selected' : '' }}>{{ $department->name }}</option>
                                @endforeach
                            </select>
                            <input type="hidden" name="department" value="{{ $profile->departmentInfo->name ?? $profile->department }}">
                        </td>
                        <td>
                            <select name="designation_id" class="form-control">
                                <option value="">Designation</option>
                                @foreach ($designations as $designation)
                                    <option value="{{ $designation->id }}" {{ (string) $profile->designation_id === (string) $designation->id ? 'selected' : '' }}>{{ $designation->name }}</option>
                                @endforeach
                            </select>
                            <input type="hidden" name="designation" value="{{ $profile->designationInfo->name ?? $profile->designation }}">
                        </td>
                        <td>
                            <select name="branch_id" class="form-control">
                                <option value="">Branch</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ (string) $profile->branch_id === (string) $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                            <input type="hidden" name="branch" value="{{ $profile->branchInfo->name ?? $profile->branch }}">
                        </td>
                        <td>
                            <select name="employment_type" class="form-control">
                                <option value="">Type</option>
                                @foreach (['permanent' => 'Permanent', 'probation' => 'Probation', 'contract' => 'Contract', 'part_time' => 'Part Time', 'intern' => 'Intern'] as $value => $label)
                                    <option value="{{ $value }}" {{ $profile->employment_type === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td><input name="joining_date" value="{{ optional($profile->joining_date)->format('Y-m-d') }}" type="date" class="form-control"></td>
                        <td>
                            <select name="status" class="form-control" required>
                                @foreach (['active' => 'Active', 'probation' => 'Probation', 'resigned' => 'Resigned', 'terminated' => 'Terminated', 'suspended' => 'Suspended'] as $value => $label)
                                    <option value="{{ $value }}" {{ $profile->status === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <label class="mt-1 mb-0"><input type="checkbox" name="is_active" value="1" {{ $profile->is_active ? 'checked' : '' }}> Active</label>
                        </td>
                        <td style="min-width: 220px;">@include('backend.hrat.partials.employee-select', ['employees' => $managers, 'name' => 'reporting_manager_user_id', 'selected' => $profile->reporting_manager_user_id])</td>
                        <td>
                            <input type="hidden" name="confirmation_date" value="{{ optional($profile->confirmation_date)->format('Y-m-d') }}">
                            <input type="hidden" name="leaving_date" value="{{ optional($profile->leaving_date)->format('Y-m-d') }}">
                            <input type="hidden" name="emergency_contact_name" value="{{ $profile->emergency_contact_name }}">
                            <input type="hidden" name="emergency_contact_phone" value="{{ $profile->emergency_contact_phone }}">
                            <input type="hidden" name="present_address" value="{{ $profile->present_address }}">
                            <input type="hidden" name="permanent_address" value="{{ $profile->permanent_address }}">
                            <input type="hidden" name="note" value="{{ $profile->note }}">
                            <button class="btn btn-sm btn-info mb-1">Update</button>
                    </form>
                            <form method="POST" action="{{ route('hrat.employee-profiles.destroy', $profile) }}" onsubmit="return confirm('Delete this employee profile?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                </tr>
            @endforeach
        </table>
        {{ $profiles->links() }}
    </div></div>
@endsection
@section('footer_js')
    <script src="{{ url('assets') }}/plugins/select2/select2.min.js"></script>
    <script>$('.select2').select2({ width: '100%' });</script>
@endsection

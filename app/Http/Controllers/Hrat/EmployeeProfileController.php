<?php

namespace App\Http\Controllers\Hrat;

use App\Http\Controllers\Controller;
use App\Models\Hrat\Branch;
use App\Models\Hrat\Department;
use App\Models\Hrat\Designation;
use App\Models\Hrat\EmployeeProfile;
use App\Models\User;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeeProfileController extends Controller
{
    public function index()
    {
        $profiles = EmployeeProfile::with(['user', 'reportingManager', 'departmentInfo', 'designationInfo', 'branchInfo'])->latest()->paginate(20);
        $users = $this->employees();
        $managers = $this->employees();
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $designations = Designation::where('is_active', true)->orderBy('name')->get();
        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        return view('backend.hrat.employee_profiles.index', compact('profiles', 'users', 'managers', 'departments', 'designations', 'branches'));
    }

    public function store(Request $request)
    {
        EmployeeProfile::create($this->withMasterNames($this->validated($request)) + [
            'is_active' => true,
            'created_by' => auth()->id(),
        ]);

        Toastr::success('Employee profile saved.', 'Success');
        return back();
    }

    public function update(Request $request, EmployeeProfile $employeeProfile)
    {
        $employeeProfile->update($this->withMasterNames($this->validated($request, $employeeProfile->id)) + [
            'is_active' => $request->boolean('is_active'),
            'updated_by' => auth()->id(),
        ]);

        Toastr::success('Employee profile updated.', 'Success');
        return back();
    }

    public function destroy(EmployeeProfile $employeeProfile)
    {
        $employeeProfile->delete();

        Toastr::success('Employee profile deleted.', 'Success');
        return back();
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'user_id' => [
                'nullable',
                'exists:users,id',
                Rule::unique('hrat_employee_profiles', 'user_id')->ignore($ignoreId),
            ],
            'employee_code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('hrat_employee_profiles', 'employee_code')->ignore($ignoreId),
            ],
            'department_id' => ['nullable', 'exists:hrat_departments,id'],
            'designation_id' => ['nullable', 'exists:hrat_designations,id'],
            'branch_id' => ['nullable', 'exists:hrat_branches,id'],
            'department' => ['nullable', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'branch' => ['nullable', 'string', 'max:255'],
            'employment_type' => ['nullable', 'in:permanent,probation,contract,part_time,intern'],
            'joining_date' => ['nullable', 'date'],
            'confirmation_date' => ['nullable', 'date', 'after_or_equal:joining_date'],
            'leaving_date' => ['nullable', 'date', 'after_or_equal:joining_date'],
            'reporting_manager_user_id' => ['nullable', 'exists:users,id'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:50'],
            'present_address' => ['nullable', 'string'],
            'permanent_address' => ['nullable', 'string'],
            'status' => ['required', 'in:active,probation,resigned,terminated,suspended'],
            'note' => ['nullable', 'string'],
        ]);
    }

    private function employees()
    {
        return User::where('status', 1)->whereIn('user_type', [1, 2])->orderBy('name')->get();
    }

    private function withMasterNames(array $data): array
    {
        $data['department'] = !empty($data['department_id'])
            ? optional(Department::find($data['department_id']))->name
            : ($data['department'] ?? null);
        $data['designation'] = !empty($data['designation_id'])
            ? optional(Designation::find($data['designation_id']))->name
            : ($data['designation'] ?? null);
        $data['branch'] = !empty($data['branch_id'])
            ? optional(Branch::find($data['branch_id']))->name
            : ($data['branch'] ?? null);

        return $data;
    }
}

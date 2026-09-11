<?php

namespace App\Http\Controllers\Hrat;

use App\Http\Controllers\Controller;
use App\Models\Hrat\EmployeeSalaryAssignment;
use App\Models\Hrat\EmployeeSalaryComponent;
use App\Models\Hrat\SalaryGrade;
use App\Models\Hrat\SalaryComponent;
use App\Models\User;
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EmployeeSalaryAssignmentController extends Controller
{
    public function index()
    {
        $assignments = EmployeeSalaryAssignment::with(['employee', 'salaryGrade', 'components.salaryComponent'])->latest()->paginate(20);
        $employees = User::where('status', 1)->whereIn('user_type', [1, 2])->orderBy('name')->get();
        $grades = SalaryGrade::where('is_active', true)->orderBy('grade_name')->get();
        $salaryComponents = SalaryComponent::where('is_active', true)->orderBy('name')->get();
        return view('backend.hrat.salary_assignments.index', compact('assignments', 'employees', 'grades', 'salaryComponents'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $this->ensureNoOverlap($data);

        EmployeeSalaryAssignment::create(array_merge($data, ['created_by' => auth()->id(), 'is_active' => true]));
        Toastr::success('Employee salary assignment saved.', 'Success');
        return back();
    }

    public function update(Request $request, EmployeeSalaryAssignment $employeeSalaryAssignment)
    {
        $data = $this->validated($request);
        $isActive = $request->boolean('is_active');
        $this->ensureNoOverlap($data, $employeeSalaryAssignment->id, $isActive);

        $employeeSalaryAssignment->update(array_merge($data, ['updated_by' => auth()->id(), 'is_active' => $isActive]));
        Toastr::success('Employee salary assignment updated.', 'Success');
        return back();
    }

    public function destroy(EmployeeSalaryAssignment $employeeSalaryAssignment)
    {
        $employeeSalaryAssignment->delete();
        Toastr::success('Employee salary assignment deleted.', 'Success');
        return back();
    }

    public function storeComponent(Request $request, EmployeeSalaryAssignment $employeeSalaryAssignment)
    {
        $data = $request->validate([
            'salary_component_id' => ['required', 'exists:hrat_salary_components,id'],
            'amount' => ['required', 'numeric', 'min:0'],
        ]);

        EmployeeSalaryComponent::updateOrCreate(
            [
                'employee_salary_assignment_id' => $employeeSalaryAssignment->id,
                'salary_component_id' => $data['salary_component_id'],
            ],
            [
                'amount' => $data['amount'],
                'is_active' => true,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]
        );

        Toastr::success('Salary package component saved.', 'Success');
        return back();
    }

    public function destroyComponent(EmployeeSalaryAssignment $employeeSalaryAssignment, EmployeeSalaryComponent $component)
    {
        if ((int) $component->employee_salary_assignment_id !== (int) $employeeSalaryAssignment->id) {
            abort(404);
        }

        $component->delete();
        Toastr::success('Salary package component deleted.', 'Success');
        return back();
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'employee_id' => ['required', 'exists:users,id'],
            'salary_grade_id' => ['required', 'exists:hrat_salary_grades,id'],
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
        ]);
    }

    private function ensureNoOverlap(array $data, ?int $ignoreId = null, bool $isActive = true): void
    {
        if (!$isActive) {
            return;
        }

        $from = Carbon::parse($data['effective_from'])->toDateString();
        $to = !empty($data['effective_to']) ? Carbon::parse($data['effective_to'])->toDateString() : '9999-12-31';
        $exists = EmployeeSalaryAssignment::where('employee_id', $data['employee_id'])
            ->where('is_active', true)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->whereDate('effective_from', '<=', $to)
            ->where(function ($query) use ($from) {
                $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $from);
            })
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'effective_from' => 'This employee already has an active salary assignment in the selected date range.',
            ]);
        }
    }
}

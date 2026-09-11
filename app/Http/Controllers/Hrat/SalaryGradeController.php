<?php

namespace App\Http\Controllers\Hrat;

use App\Http\Controllers\Controller;
use App\Models\Hrat\SalaryGrade;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

class SalaryGradeController extends Controller
{
    public function index()
    {
        $grades = SalaryGrade::latest()->paginate(20);
        return view('backend.hrat.salary_grades.index', compact('grades'));
    }

    public function store(Request $request)
    {
        SalaryGrade::create($this->validated($request) + ['created_by' => auth()->id(), 'is_active' => true]);
        Toastr::success('Salary grade saved.', 'Success');
        return back();
    }

    public function update(Request $request, SalaryGrade $salaryGrade)
    {
        $salaryGrade->update($this->validated($request) + ['updated_by' => auth()->id(), 'is_active' => $request->boolean('is_active')]);
        Toastr::success('Salary grade updated.', 'Success');
        return back();
    }

    public function destroy(SalaryGrade $salaryGrade)
    {
        $salaryGrade->delete();
        Toastr::success('Salary grade deleted.', 'Success');
        return back();
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'grade_name' => ['required', 'string', 'max:255'],
            'grade_code' => ['nullable', 'string', 'max:100'],
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
        ]);
    }
}

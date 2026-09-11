<?php

namespace App\Http\Controllers\Hrat;

use App\Http\Controllers\Controller;
use App\Models\Hrat\SalaryComponent;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SalaryComponentController extends Controller
{
    public function index()
    {
        $components = SalaryComponent::latest()->paginate(20);
        return view('backend.hrat.salary_components.index', compact('components'));
    }

    public function store(Request $request)
    {
        SalaryComponent::create($this->validated($request) + [
            'is_taxable' => $request->boolean('is_taxable'),
            'is_active' => true,
            'created_by' => auth()->id(),
        ]);

        Toastr::success('Salary component saved.', 'Success');
        return back();
    }

    public function update(Request $request, SalaryComponent $salaryComponent)
    {
        $salaryComponent->update($this->validated($request, $salaryComponent->id) + [
            'is_taxable' => $request->boolean('is_taxable'),
            'is_active' => $request->boolean('is_active'),
            'updated_by' => auth()->id(),
        ]);

        Toastr::success('Salary component updated.', 'Success');
        return back();
    }

    public function destroy(SalaryComponent $salaryComponent)
    {
        $salaryComponent->delete();
        Toastr::success('Salary component deleted.', 'Success');
        return back();
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:100', Rule::unique('hrat_salary_components', 'code')->ignore($ignoreId)],
            'type' => ['required', 'in:allowance,deduction'],
            'calculation_type' => ['required', 'in:fixed,percentage'],
            'default_amount' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
        ]);
    }
}

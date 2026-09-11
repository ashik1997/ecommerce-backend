<?php

namespace App\Http\Controllers\Hrat;

use App\Http\Controllers\Controller;
use App\Models\Hrat\Department;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{
    public function index()
    {
        $items = Department::latest()->paginate(20);
        return view('backend.hrat.masters.departments', compact('items'));
    }

    public function store(Request $request)
    {
        Department::create($this->validated($request) + ['is_active' => true, 'created_by' => auth()->id()]);
        Toastr::success('Department saved.', 'Success');
        return back();
    }

    public function update(Request $request, Department $department)
    {
        $department->update($this->validated($request, $department->id) + ['is_active' => $request->boolean('is_active'), 'updated_by' => auth()->id()]);
        Toastr::success('Department updated.', 'Success');
        return back();
    }

    public function destroy(Department $department)
    {
        $department->delete();
        Toastr::success('Department deleted.', 'Success');
        return back();
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('hrat_departments', 'name')->ignore($ignoreId)],
            'code' => ['nullable', 'string', 'max:100', Rule::unique('hrat_departments', 'code')->ignore($ignoreId)],
            'description' => ['nullable', 'string'],
        ]);
    }
}

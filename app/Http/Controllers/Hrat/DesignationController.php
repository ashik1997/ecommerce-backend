<?php

namespace App\Http\Controllers\Hrat;

use App\Http\Controllers\Controller;
use App\Models\Hrat\Designation;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DesignationController extends Controller
{
    public function index()
    {
        $items = Designation::latest()->paginate(20);
        return view('backend.hrat.masters.designations', compact('items'));
    }

    public function store(Request $request)
    {
        Designation::create($this->validated($request) + ['is_active' => true, 'created_by' => auth()->id()]);
        Toastr::success('Designation saved.', 'Success');
        return back();
    }

    public function update(Request $request, Designation $designation)
    {
        $designation->update($this->validated($request, $designation->id) + ['is_active' => $request->boolean('is_active'), 'updated_by' => auth()->id()]);
        Toastr::success('Designation updated.', 'Success');
        return back();
    }

    public function destroy(Designation $designation)
    {
        $designation->delete();
        Toastr::success('Designation deleted.', 'Success');
        return back();
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('hrat_designations', 'name')->ignore($ignoreId)],
            'code' => ['nullable', 'string', 'max:100', Rule::unique('hrat_designations', 'code')->ignore($ignoreId)],
            'description' => ['nullable', 'string'],
        ]);
    }
}

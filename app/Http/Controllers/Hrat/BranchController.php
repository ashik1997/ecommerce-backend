<?php

namespace App\Http\Controllers\Hrat;

use App\Http\Controllers\Controller;
use App\Models\Hrat\Branch;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BranchController extends Controller
{
    public function index()
    {
        $items = Branch::latest()->paginate(20);
        return view('backend.hrat.masters.branches', compact('items'));
    }

    public function store(Request $request)
    {
        Branch::create($this->validated($request) + ['is_active' => true, 'created_by' => auth()->id()]);
        Toastr::success('Branch saved.', 'Success');
        return back();
    }

    public function update(Request $request, Branch $branch)
    {
        $branch->update($this->validated($request, $branch->id) + ['is_active' => $request->boolean('is_active'), 'updated_by' => auth()->id()]);
        Toastr::success('Branch updated.', 'Success');
        return back();
    }

    public function destroy(Branch $branch)
    {
        $branch->delete();
        Toastr::success('Branch deleted.', 'Success');
        return back();
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('hrat_branches', 'name')->ignore($ignoreId)],
            'code' => ['nullable', 'string', 'max:100', Rule::unique('hrat_branches', 'code')->ignore($ignoreId)],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
        ]);
    }
}

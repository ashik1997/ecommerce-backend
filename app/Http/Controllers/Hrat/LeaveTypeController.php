<?php

namespace App\Http\Controllers\Hrat;

use App\Http\Controllers\Controller;
use App\Models\Hrat\LeaveType;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeaveTypeController extends Controller
{
    public function index()
    {
        $leaveTypes = LeaveType::latest()->paginate(20);
        return view('backend.hrat.leave_types.index', compact('leaveTypes'));
    }

    public function store(Request $request)
    {
        LeaveType::create($this->validated($request) + [
            'is_paid' => $request->boolean('is_paid', true),
            'is_active' => true,
            'created_by' => auth()->id(),
        ]);

        Toastr::success('Leave type saved.', 'Success');
        return back();
    }

    public function update(Request $request, LeaveType $leaveType)
    {
        $leaveType->update($this->validated($request, $leaveType->id) + [
            'is_paid' => $request->boolean('is_paid'),
            'is_active' => $request->boolean('is_active'),
            'updated_by' => auth()->id(),
        ]);

        Toastr::success('Leave type updated.', 'Success');
        return back();
    }

    public function destroy(LeaveType $leaveType)
    {
        $leaveType->delete();
        Toastr::success('Leave type deleted.', 'Success');
        return back();
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('hrat_leave_types', 'name')->ignore($ignoreId)],
            'code' => ['nullable', 'string', 'max:100', Rule::unique('hrat_leave_types', 'code')->ignore($ignoreId)],
            'annual_days' => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
        ]);
    }
}

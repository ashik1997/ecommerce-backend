<?php

namespace App\Http\Controllers\Hrat;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrat\ManualAttendanceRequest;
use App\Models\Hrat\AttendanceLog;
use App\Models\User;
use App\Services\Hrat\AttendanceLogService;
use Brian2694\Toastr\Facades\Toastr;

class ManualAttendanceController extends Controller
{
    public function index()
    {
        $employees = User::where('status', 1)->whereIn('user_type', [1, 2])->orderBy('name')->get();
        $logs = AttendanceLog::with('employee')->latest('attendance_datetime')->paginate(20);
        return view('backend.hrat.manual.index', compact('employees', 'logs'));
    }

    public function store(ManualAttendanceRequest $request, AttendanceLogService $service)
    {
        $data = $request->validated();

        $service->create($data + [
            'source' => 'manual',
            'is_manual_adjusted' => true,
            'created_by' => auth()->id(),
        ]);

        Toastr::success('Manual attendance saved and summary regenerated.', 'Success');
        return back();
    }
}

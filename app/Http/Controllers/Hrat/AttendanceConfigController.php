<?php

namespace App\Http\Controllers\Hrat;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrat\AttendanceConfigRequest;
use App\Models\Hrat\AttendanceConfig;
use App\Services\Hrat\AttendanceConfigService;
use Brian2694\Toastr\Facades\Toastr;

class AttendanceConfigController extends Controller
{
    public function index(AttendanceConfigService $service)
    {
        $config = $service->activeConfig();
        return view('backend.hrat.configs.index', compact('config'));
    }

    public function store(AttendanceConfigRequest $request)
    {
        $data = $request->validated();

        AttendanceConfig::query()->update(['is_active' => false]);
        AttendanceConfig::create($data + [
            'working_days' => array_map('intval', $request->working_days ?? [0, 1, 2, 3, 4, 5]),
            'weekly_holidays' => array_map('intval', $request->weekly_holidays ?? [6]),
            'timezone' => $data['timezone'] ?? config('app.timezone'),
            'allow_duplicate_employee_panel_entry' => $request->boolean('allow_duplicate_employee_panel_entry'),
            'allow_exit_without_entry' => $request->boolean('allow_exit_without_entry'),
            'is_active' => true,
            'created_by' => auth()->id(),
        ]);

        Toastr::success('Attendance configuration saved.', 'Success');
        return back();
    }
}

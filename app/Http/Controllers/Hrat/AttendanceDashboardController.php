<?php

namespace App\Http\Controllers\Hrat;

use App\Http\Controllers\Controller;
use App\Models\Hrat\AttendanceLog;
use App\Models\Hrat\DailyAttendanceSummary;
use App\Models\Hrat\ImportBatch;

class AttendanceDashboardController extends Controller
{
    public function index()
    {
        $today = now()->toDateString();
        $stats = [
            'present' => DailyAttendanceSummary::whereDate('attendance_date', $today)->where('is_present', true)->count(),
            'absent' => DailyAttendanceSummary::whereDate('attendance_date', $today)->where('is_absent', true)->count(),
            'late' => DailyAttendanceSummary::whereDate('attendance_date', $today)->where('is_late', true)->count(),
            'early_exit' => DailyAttendanceSummary::whereDate('attendance_date', $today)->where('is_early_exit', true)->count(),
            'overtime' => DailyAttendanceSummary::whereDate('attendance_date', $today)->where('overtime_minutes', '>', 0)->count(),
            'incomplete' => DailyAttendanceSummary::whereDate('attendance_date', $today)->where('status', 'incomplete')->count(),
        ];
        $recentManualEntries = AttendanceLog::with('employee')->where('source', 'manual')->latest()->limit(10)->get();
        $recentImports = ImportBatch::latest()->limit(10)->get();

        return view('backend.hrat.dashboard', compact('stats', 'recentManualEntries', 'recentImports'));
    }
}

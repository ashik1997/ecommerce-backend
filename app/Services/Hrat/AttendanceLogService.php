<?php

namespace App\Services\Hrat;

use App\Models\Hrat\AttendanceAdjustment;
use App\Models\Hrat\AttendanceLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceLogService
{
    public function __construct(private AttendanceSummaryService $summaryService)
    {
    }

    public function create(array $data, bool $audit = true): AttendanceLog
    {
        $date = Carbon::parse($data['attendance_date'])->toDateString();
        $time = Carbon::parse($data['attendance_time'])->format('H:i:s');
        $datetime = Carbon::parse($date . ' ' . $time);

        $exists = AttendanceLog::where('employee_id', $data['employee_id'])
            ->where('attendance_datetime', $datetime)
            ->where('punch_type', $data['punch_type'])
            ->where('source', $data['source'] ?? 'manual')
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages(['attendance_time' => 'Duplicate attendance log already exists.']);
        }

        return DB::transaction(function () use ($data, $date, $time, $datetime, $audit) {
            $log = AttendanceLog::create(array_merge($data, [
                'attendance_date' => $date,
                'attendance_time' => $time,
                'attendance_datetime' => $datetime,
                'source' => $data['source'] ?? 'manual',
                'created_by' => $data['created_by'] ?? auth()->id(),
            ]));

            if ($audit) {
                AttendanceAdjustment::create([
                    'employee_id' => $log->employee_id,
                    'attendance_date' => $log->attendance_date,
                    'attendance_log_id' => $log->id,
                    'adjustment_type' => 'create',
                    'new_data' => $log->toArray(),
                    'reason' => $data['reason'] ?? $data['note'] ?? 'Manual attendance entry',
                    'created_by' => auth()->id(),
                ]);
            }

            $this->summaryService->generateForEmployeeDate($log->employee_id, $log->attendance_date);

            return $log;
        });
    }
}

<?php

namespace App\Http\Controllers\Hrat;

use App\Http\Controllers\Controller;
use App\Models\Hrat\AttendanceAdjustment;
use App\Models\Hrat\AttendanceLog;
use App\Models\User;
use App\Services\Hrat\AttendanceSummaryService;
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceAdjustmentController extends Controller
{
    public function index()
    {
        $adjustments = AttendanceAdjustment::with(['employee', 'attendanceLog', 'creator', 'reviewer'])->latest()->paginate(30);
        $employees = User::where('status', 1)->whereIn('user_type', [1, 2])->orderBy('name')->get();
        $logs = AttendanceLog::with('employee')->latest('attendance_datetime')->limit(100)->get();

        return view('backend.hrat.adjustments.index', compact('adjustments', 'employees', 'logs'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $log = $this->validatedLog($data);

        AttendanceAdjustment::create(array_merge($data, [
            'old_data' => $log?->toArray(),
            'new_data' => $this->requestedPayload($data),
            'status' => 'pending',
            'created_by' => auth()->id(),
        ]));

        Toastr::success('Attendance adjustment request created.', 'Success');
        return back();
    }

    public function update(Request $request, AttendanceAdjustment $attendanceAdjustment)
    {
        if ($attendanceAdjustment->status !== 'pending') {
            Toastr::warning('Only pending adjustment requests can be updated.', 'Warning');
            return back();
        }

        $data = $this->validated($request);
        $log = $this->validatedLog($data);

        $attendanceAdjustment->update(array_merge($data, [
            'old_data' => $log?->toArray(),
            'new_data' => $this->requestedPayload($data),
        ]));

        Toastr::success('Attendance adjustment request updated.', 'Success');
        return back();
    }

    public function destroy(AttendanceAdjustment $attendanceAdjustment)
    {
        if ($attendanceAdjustment->status !== 'pending') {
            Toastr::warning('Only pending adjustment requests can be deleted.', 'Warning');
            return back();
        }

        $attendanceAdjustment->delete();

        Toastr::success('Attendance adjustment request deleted.', 'Success');
        return back();
    }

    public function approve(AttendanceAdjustment $attendanceAdjustment, AttendanceSummaryService $summaryService)
    {
        if ($attendanceAdjustment->status !== 'pending') {
            Toastr::warning('This adjustment is already reviewed.', 'Warning');
            return back();
        }

        if (!$this->pendingAdjustmentIsValid($attendanceAdjustment)) {
            return back();
        }

        try {
            DB::transaction(function () use ($attendanceAdjustment, $summaryService) {
                $log = $this->applyAdjustment($attendanceAdjustment);

                $attendanceAdjustment->update([
                    'attendance_log_id' => $log?->id ?: $attendanceAdjustment->attendance_log_id,
                    'new_data' => $log?->toArray() ?: $attendanceAdjustment->new_data,
                    'status' => 'approved',
                    'reviewed_by' => auth()->id(),
                    'reviewed_at' => now(),
                ]);

                $summaryService->generateForEmployeeDate($attendanceAdjustment->employee_id, $attendanceAdjustment->attendance_date);
            });
        } catch (ModelNotFoundException $exception) {
            Toastr::error('Selected attendance log no longer exists.', 'Error');
            return back();
        }

        Toastr::success('Attendance adjustment approved and summary regenerated.', 'Success');
        return back();
    }

    public function reject(Request $request, AttendanceAdjustment $attendanceAdjustment)
    {
        if ($attendanceAdjustment->status !== 'pending') {
            Toastr::warning('This adjustment is already reviewed.', 'Warning');
            return back();
        }

        $data = $request->validate(['review_note' => ['nullable', 'string']]);
        $attendanceAdjustment->update([
            'status' => 'rejected',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_note' => $data['review_note'] ?? null,
        ]);

        Toastr::success('Attendance adjustment rejected.', 'Success');
        return back();
    }

    private function applyAdjustment(AttendanceAdjustment $adjustment): ?AttendanceLog
    {
        if ($adjustment->adjustment_type === 'regenerate') {
            return $adjustment->attendanceLog;
        }

        if ($adjustment->adjustment_type === 'delete') {
            $log = AttendanceLog::findOrFail($adjustment->attendance_log_id);
            $adjustment->update(['old_data' => $log->toArray()]);
            $log->delete();
            return null;
        }

        $payload = [
            'employee_id' => $adjustment->employee_id,
            'attendance_date' => Carbon::parse($adjustment->attendance_date)->toDateString(),
            'attendance_time' => Carbon::parse($adjustment->requested_attendance_time)->format('H:i:s'),
            'attendance_datetime' => Carbon::parse($adjustment->attendance_date->format('Y-m-d') . ' ' . Carbon::parse($adjustment->requested_attendance_time)->format('H:i:s')),
            'punch_type' => $adjustment->requested_punch_type,
            'source' => 'manual',
            'is_manual_adjusted' => true,
            'note' => $adjustment->reason,
            'updated_by' => auth()->id(),
        ];

        if ($adjustment->adjustment_type === 'update') {
            $log = AttendanceLog::findOrFail($adjustment->attendance_log_id);
            $adjustment->update(['old_data' => $log->toArray()]);
            $log->update($payload);
            return $log->fresh();
        }

        return AttendanceLog::create($payload + [
            'employee_code' => (string) $adjustment->employee_id,
            'created_by' => auth()->id(),
        ]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'adjustment_type' => ['required', 'in:create,update,delete,regenerate'],
            'employee_id' => ['required', 'exists:users,id'],
            'attendance_log_id' => ['nullable', 'exists:hrat_attendance_logs,id'],
            'attendance_date' => ['required', 'date'],
            'requested_attendance_time' => ['nullable', 'required_unless:adjustment_type,delete,regenerate', 'date_format:H:i'],
            'requested_punch_type' => ['nullable', 'required_unless:adjustment_type,delete,regenerate', 'in:entry,exit'],
            'reason' => ['required', 'string'],
        ]);
    }

    private function validatedLog(array $data): ?AttendanceLog
    {
        if (!in_array($data['adjustment_type'], ['update', 'delete'], true) && empty($data['attendance_log_id'])) {
            return null;
        }

        if (in_array($data['adjustment_type'], ['update', 'delete'], true) && empty($data['attendance_log_id'])) {
            throw ValidationException::withMessages([
                'attendance_log_id' => 'Existing log is required for update or delete adjustments.',
            ]);
        }

        if (empty($data['attendance_log_id'])) {
            return null;
        }

        $log = AttendanceLog::findOrFail($data['attendance_log_id']);
        $requestedDate = Carbon::parse($data['attendance_date'])->toDateString();
        if ((int) $log->employee_id !== (int) $data['employee_id'] || Carbon::parse($log->attendance_date)->toDateString() !== $requestedDate) {
            throw ValidationException::withMessages([
                'attendance_log_id' => 'Selected log must belong to the requested employee and date.',
            ]);
        }

        return $log;
    }

    private function pendingAdjustmentIsValid(AttendanceAdjustment $adjustment): bool
    {
        try {
            $this->validatedLog([
                'adjustment_type' => $adjustment->adjustment_type,
                'employee_id' => $adjustment->employee_id,
                'attendance_log_id' => $adjustment->attendance_log_id,
                'attendance_date' => $adjustment->attendance_date,
            ]);
        } catch (ValidationException|ModelNotFoundException $exception) {
            Toastr::error($exception instanceof ValidationException
                ? collect($exception->errors())->flatten()->first()
                : 'Selected attendance log no longer exists.', 'Error');
            return false;
        }

        return true;
    }

    private function requestedPayload(array $data): array
    {
        return [
            'employee_id' => $data['employee_id'],
            'attendance_date' => $data['attendance_date'],
            'attendance_time' => $data['requested_attendance_time'] ?? null,
            'punch_type' => $data['requested_punch_type'] ?? null,
            'reason' => $data['reason'],
        ];
    }
}

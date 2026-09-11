<?php

namespace App\Services\Hrat;

use App\Models\Hrat\LeaveApplication;
use App\Models\Hrat\LeaveBalance;
use App\Models\Hrat\LeaveType;

class LeaveBalanceService
{
    public function balanceFor(int $employeeId, int $leaveTypeId, int $year): LeaveBalance
    {
        $leaveType = LeaveType::find($leaveTypeId);

        return LeaveBalance::firstOrCreate(
            [
                'employee_id' => $employeeId,
                'leave_type_id' => $leaveTypeId,
                'year' => $year,
            ],
            [
                'opening_days' => 0,
                'allocated_days' => $leaveType?->annual_days ?: 0,
                'used_days' => 0,
                'remaining_days' => $leaveType?->annual_days ?: 0,
                'created_by' => auth()->id(),
            ]
        );
    }

    public function canApply(int $employeeId, int $leaveTypeId, int $year, float $requestedDays): bool
    {
        return $this->balanceFor($employeeId, $leaveTypeId, $year)->remaining_days >= $requestedDays;
    }

    public function consume(LeaveApplication $application): void
    {
        $balance = $this->balanceFor(
            $application->employee_id,
            $application->leave_type_id,
            (int) $application->from_date->format('Y')
        );

        $used = (float) $balance->used_days + (float) $application->total_days;
        $balance->update([
            'used_days' => $used,
            'remaining_days' => max(0, ((float) $balance->opening_days + (float) $balance->allocated_days) - $used),
            'updated_by' => auth()->id(),
        ]);
    }

    public function release(LeaveApplication $application): void
    {
        $balance = $this->balanceFor(
            $application->employee_id,
            $application->leave_type_id,
            (int) $application->from_date->format('Y')
        );

        $used = max(0, (float) $balance->used_days - (float) $application->total_days);
        $available = (float) $balance->opening_days + (float) $balance->allocated_days;
        $balance->update([
            'used_days' => $used,
            'remaining_days' => max(0, $available - $used),
            'updated_by' => auth()->id(),
        ]);
    }
}

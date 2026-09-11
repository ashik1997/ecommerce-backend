<?php

namespace App\Models\Hrat;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AttendanceAdjustment extends Model
{
    protected $table = 'hrat_attendance_adjustments';
    protected $guarded = [];
    protected $casts = [
        'attendance_date' => 'date',
        'requested_attendance_time' => 'datetime:H:i:s',
        'old_data' => 'array',
        'new_data' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function attendanceLog()
    {
        return $this->belongsTo(AttendanceLog::class, 'attendance_log_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

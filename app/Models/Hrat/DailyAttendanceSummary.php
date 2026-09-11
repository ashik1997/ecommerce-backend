<?php

namespace App\Models\Hrat;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class DailyAttendanceSummary extends Model
{
    protected $table = 'hrat_daily_attendance_summaries';
    protected $guarded = [];
    protected $casts = [
        'attendance_date' => 'date',
        'schedule_snapshot' => 'array',
        'generated_at' => 'datetime',
        'is_present' => 'boolean',
        'is_absent' => 'boolean',
        'is_late' => 'boolean',
        'is_early_exit' => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }
}

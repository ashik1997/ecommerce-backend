<?php

namespace App\Models\Hrat;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AttendanceLog extends Model
{
    protected $table = 'hrat_attendance_logs';
    protected $guarded = [];
    protected $casts = [
        'attendance_date' => 'date',
        'attendance_datetime' => 'datetime',
        'is_manual_adjusted' => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }
}

<?php

namespace App\Models\Hrat;

use Illuminate\Database\Eloquent\Model;

class AttendanceConfig extends Model
{
    protected $table = 'hrat_attendance_configs';
    protected $guarded = [];
    protected $casts = [
        'working_days' => 'array',
        'weekly_holidays' => 'array',
        'is_active' => 'boolean',
        'allow_duplicate_employee_panel_entry' => 'boolean',
        'allow_exit_without_entry' => 'boolean',
    ];
}

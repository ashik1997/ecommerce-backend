<?php

namespace App\Models\Hrat;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class EmployeeSchedule extends Model
{
    protected $table = 'hrat_employee_schedules';
    protected $guarded = [];
    protected $casts = [
        'working_days' => 'array',
        'weekly_holidays' => 'array',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }
}

<?php

namespace App\Models\Hrat;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class LeaveBalance extends Model
{
    protected $table = 'hrat_leave_balances';
    protected $guarded = [];

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class, 'leave_type_id');
    }
}

<?php

namespace App\Models\Hrat;

use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    protected $table = 'hrat_leave_types';
    protected $guarded = [];
    protected $casts = [
        'is_paid' => 'boolean',
        'is_active' => 'boolean',
    ];
}

<?php

namespace App\Models\Hrat;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class EmployeeProfile extends Model
{
    protected $table = 'hrat_employee_profiles';
    protected $guarded = [];
    protected $casts = [
        'joining_date' => 'date',
        'confirmation_date' => 'date',
        'leaving_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reportingManager()
    {
        return $this->belongsTo(User::class, 'reporting_manager_user_id');
    }

    public function departmentInfo()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function designationInfo()
    {
        return $this->belongsTo(Designation::class, 'designation_id');
    }

    public function branchInfo()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}

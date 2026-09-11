<?php

namespace App\Models\Hrat;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class EmployeeSalaryAssignment extends Model
{
    protected $table = 'hrat_employee_salary_assignments';
    protected $guarded = [];
    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function salaryGrade()
    {
        return $this->belongsTo(SalaryGrade::class, 'salary_grade_id');
    }

    public function components()
    {
        return $this->hasMany(EmployeeSalaryComponent::class, 'employee_salary_assignment_id');
    }
}

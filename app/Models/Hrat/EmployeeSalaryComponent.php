<?php

namespace App\Models\Hrat;

use Illuminate\Database\Eloquent\Model;

class EmployeeSalaryComponent extends Model
{
    protected $table = 'hrat_employee_salary_components';
    protected $guarded = [];
    protected $casts = [
        'amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function assignment()
    {
        return $this->belongsTo(EmployeeSalaryAssignment::class, 'employee_salary_assignment_id');
    }

    public function salaryComponent()
    {
        return $this->belongsTo(SalaryComponent::class, 'salary_component_id');
    }
}

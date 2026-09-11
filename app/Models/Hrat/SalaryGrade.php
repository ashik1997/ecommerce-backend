<?php

namespace App\Models\Hrat;

use Illuminate\Database\Eloquent\Model;

class SalaryGrade extends Model
{
    protected $table = 'hrat_salary_grades';
    protected $guarded = [];
    protected $casts = ['is_active' => 'boolean'];
}

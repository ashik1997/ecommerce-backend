<?php

namespace App\Models\Hrat;

use Illuminate\Database\Eloquent\Model;

class SalaryComponent extends Model
{
    protected $table = 'hrat_salary_components';
    protected $guarded = [];
    protected $casts = [
        'default_amount' => 'decimal:2',
        'is_taxable' => 'boolean',
        'is_active' => 'boolean',
    ];
}

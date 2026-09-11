<?php

namespace App\Models\Hrat;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PayrollLine extends Model
{
    protected $table = 'hrat_payroll_lines';
    protected $guarded = [];
    protected $casts = ['meta' => 'array'];

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function payroll()
    {
        return $this->belongsTo(Payroll::class, 'payroll_id');
    }
}

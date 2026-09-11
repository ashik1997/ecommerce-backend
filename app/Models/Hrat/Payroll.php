<?php

namespace App\Models\Hrat;

use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    protected $table = 'hrat_payrolls';
    protected $guarded = [];
    protected $casts = [
        'from_date' => 'date',
        'to_date' => 'date',
        'approved_at' => 'datetime',
        'finalized_at' => 'datetime',
        'accounting_posted_at' => 'datetime',
        'payment_posted_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function lines()
    {
        return $this->hasMany(PayrollLine::class, 'payroll_id');
    }

    public function accountingTransactions()
    {
        return $this->hasMany(\App\Http\Controllers\Account\Models\AcTransaction::class, 'ref_payroll_id')->orderBy('id');
    }

    public function totalNetPayable(): float
    {
        return (float) $this->lines->sum('net_payable');
    }
}

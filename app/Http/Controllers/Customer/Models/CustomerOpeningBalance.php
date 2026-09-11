<?php

namespace App\Http\Controllers\Customer\Models;

use App\Http\Controllers\Account\Models\DbCustomerPayment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerOpeningBalance extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'opening_date' => 'date',
        'opening_amount' => 'decimal:4',
        'paid_amount' => 'decimal:4',
        'remaining_amount' => 'decimal:4',
        'posted_to_accounts' => 'boolean',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function payments()
    {
        return $this->hasMany(DbCustomerPayment::class, 'customer_opening_balance_id');
    }
}

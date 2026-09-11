<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommissionSettlement extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'settled_at' => 'datetime',
        'payable_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
    ];

    public function items()
    {
        return $this->hasMany(CommissionSettlementItem::class, 'commission_settlement_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function affiliate()
    {
        return $this->belongsTo(Affiliate::class, 'affiliate_id');
    }

    public function accountingTransaction()
    {
        return $this->belongsTo(\App\Http\Controllers\Account\Models\AcTransaction::class, 'accounting_transaction_id');
    }

    public function paymentTransaction()
    {
        return $this->belongsTo(\App\Http\Controllers\Account\Models\AcTransaction::class, 'payment_transaction_id');
    }
}


<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommissionSettlementItem extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function settlement()
    {
        return $this->belongsTo(CommissionSettlement::class, 'commission_settlement_id');
    }

    public function commissionEntry()
    {
        return $this->belongsTo(SalesCommissionEntry::class, 'sales_commission_entry_id');
    }
}

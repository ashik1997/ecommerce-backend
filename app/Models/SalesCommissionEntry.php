<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesCommissionEntry extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
        'reversed_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(ProductOrder::class, 'product_order_id');
    }

    public function orderProduct()
    {
        return $this->belongsTo(ProductOrderProduct::class, 'product_order_product_id');
    }

    public function rule()
    {
        return $this->belongsTo(SalesCommissionRule::class, 'sales_commission_rule_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function affiliate()
    {
        return $this->belongsTo(Affiliate::class, 'affiliate_id');
    }

    public function settlement()
    {
        return $this->belongsTo(CommissionSettlement::class, 'settlement_id');
    }
}

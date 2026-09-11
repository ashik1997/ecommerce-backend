<?php

namespace App\Models\Delivery;

use Illuminate\Database\Eloquent\Model;

class DeliverySettlement extends Model
{
    protected $guarded = [];

    protected $casts = [
        'settlement_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function provider()
    {
        return $this->belongsTo(DeliveryProvider::class, 'provider_id');
    }

    public function employee()
    {
        return $this->belongsTo(DeliveryEmployee::class, 'delivery_employee_id');
    }

    public function items()
    {
        return $this->hasMany(DeliverySettlementItem::class, 'delivery_settlement_id');
    }
}

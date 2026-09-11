<?php

namespace App\Models\Delivery;

use Illuminate\Database\Eloquent\Model;

class DeliverySettlementItem extends Model
{
    protected $guarded = [];

    public function settlement()
    {
        return $this->belongsTo(DeliverySettlement::class, 'delivery_settlement_id');
    }

    public function shipment()
    {
        return $this->belongsTo(DeliveryShipment::class, 'delivery_shipment_id');
    }
}

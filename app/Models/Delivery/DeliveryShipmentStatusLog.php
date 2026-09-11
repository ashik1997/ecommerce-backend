<?php

namespace App\Models\Delivery;

use Illuminate\Database\Eloquent\Model;

class DeliveryShipmentStatusLog extends Model
{
    protected $guarded = [];

    protected $casts = [
        'response_payload' => 'array',
    ];

    public function shipment()
    {
        return $this->belongsTo(DeliveryShipment::class, 'delivery_shipment_id');
    }
}

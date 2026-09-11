<?php

namespace App\Models\Delivery;

use Illuminate\Database\Eloquent\Model;

class DeliveryCodCollection extends Model
{
    protected $guarded = [];

    protected $casts = [
        'collected_at' => 'datetime',
        'submitted_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function shipment()
    {
        return $this->belongsTo(DeliveryShipment::class, 'delivery_shipment_id');
    }

    public function employee()
    {
        return $this->belongsTo(DeliveryEmployee::class, 'delivery_employee_id');
    }
}

<?php

namespace App\Models\Delivery;

use Illuminate\Database\Eloquent\Model;

class DeliveryAssignment extends Model
{
    protected $guarded = [];

    protected $casts = [
        'assigned_at' => 'datetime',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function shipment()
    {
        return $this->belongsTo(DeliveryShipment::class, 'delivery_shipment_id');
    }

    public function provider()
    {
        return $this->belongsTo(DeliveryProvider::class, 'provider_id');
    }

    public function employee()
    {
        return $this->belongsTo(DeliveryEmployee::class, 'delivery_employee_id');
    }
}

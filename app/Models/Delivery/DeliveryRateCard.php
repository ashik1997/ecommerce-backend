<?php

namespace App\Models\Delivery;

use Illuminate\Database\Eloquent\Model;

class DeliveryRateCard extends Model
{
    protected $guarded = [];

    public function provider()
    {
        return $this->belongsTo(DeliveryProvider::class, 'provider_id');
    }

    public function zone()
    {
        return $this->belongsTo(DeliveryZone::class, 'zone_id');
    }

    public function serviceType()
    {
        return $this->belongsTo(DeliveryServiceType::class, 'service_type_id');
    }
}

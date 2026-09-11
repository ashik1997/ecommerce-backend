<?php

namespace App\Models\Delivery;

use Illuminate\Database\Eloquent\Model;

class DeliveryZone extends Model
{
    protected $guarded = [];

    public function areas()
    {
        return $this->hasMany(DeliveryZoneArea::class, 'zone_id');
    }

    public function rateCards()
    {
        return $this->hasMany(DeliveryRateCard::class, 'zone_id');
    }
}

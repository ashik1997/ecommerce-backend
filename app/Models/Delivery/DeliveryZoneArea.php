<?php

namespace App\Models\Delivery;

use App\Models\District;
use App\Models\Upazila;
use Illuminate\Database\Eloquent\Model;

class DeliveryZoneArea extends Model
{
    protected $guarded = [];

    public function zone()
    {
        return $this->belongsTo(DeliveryZone::class, 'zone_id');
    }

    public function district()
    {
        return $this->belongsTo(District::class, 'district_id');
    }

    public function upazila()
    {
        return $this->belongsTo(Upazila::class, 'upazila_id');
    }
}

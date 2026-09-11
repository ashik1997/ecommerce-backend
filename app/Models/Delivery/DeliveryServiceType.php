<?php

namespace App\Models\Delivery;

use Illuminate\Database\Eloquent\Model;

class DeliveryServiceType extends Model
{
    protected $guarded = [];

    public function provider()
    {
        return $this->belongsTo(DeliveryProvider::class, 'provider_id');
    }
}

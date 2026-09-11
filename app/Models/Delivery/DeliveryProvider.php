<?php

namespace App\Models\Delivery;

use Illuminate\Database\Eloquent\Model;

class DeliveryProvider extends Model
{
    protected $guarded = [];

    protected $casts = [
        'config' => 'array',
    ];

    public function serviceTypes()
    {
        return $this->hasMany(DeliveryServiceType::class, 'provider_id');
    }

    public function shipments()
    {
        return $this->hasMany(DeliveryShipment::class, 'provider_id');
    }

    public function statusMaps()
    {
        return $this->hasMany(DeliveryProviderStatusMap::class, 'provider_id');
    }
}

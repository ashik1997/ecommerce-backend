<?php

namespace App\Models\Delivery;

use Illuminate\Database\Eloquent\Model;

class DeliveryProviderStatusMap extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_terminal' => 'boolean',
    ];

    public function provider()
    {
        return $this->belongsTo(DeliveryProvider::class, 'provider_id');
    }
}

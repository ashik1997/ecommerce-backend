<?php

namespace App\Models\Delivery;

use Illuminate\Database\Eloquent\Model;

class DeliveryStatus extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_terminal' => 'boolean',
    ];
}

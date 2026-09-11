<?php

namespace App\Models\Delivery;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class DeliveryEmployee extends Model
{
    protected $guarded = [];

    protected $casts = [
        'joining_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function shipments()
    {
        return $this->hasMany(DeliveryShipment::class, 'delivery_employee_id');
    }

    public function assignments()
    {
        return $this->hasMany(DeliveryAssignment::class, 'delivery_employee_id');
    }
}

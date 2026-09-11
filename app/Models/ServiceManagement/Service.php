<?php

namespace App\Models\ServiceManagement;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $table = 'srms_services';

    protected $guarded = [];

    protected $casts = [
        'base_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public const TYPES = ['rental', 'sale', 'mixed'];

    public const BILLING_UNITS = ['unit', 'meter', 'day', 'month'];

    public function serviceProducts()
    {
        return $this->hasMany(ServiceProduct::class, 'service_id');
    }

    public function serviceInstances()
    {
        return $this->hasMany(ServiceInstance::class, 'service_id');
    }
}

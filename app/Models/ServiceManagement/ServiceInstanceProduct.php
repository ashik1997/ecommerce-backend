<?php

namespace App\Models\ServiceManagement;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;

class ServiceInstanceProduct extends Model
{
    protected $table = 'srms_service_instance_products';

    protected $guarded = [];

    protected $casts = [
        'quantity_used' => 'decimal:4',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'is_required' => 'boolean',
    ];

    public function serviceInstance()
    {
        return $this->belongsTo(ServiceInstance::class, 'service_instance_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}

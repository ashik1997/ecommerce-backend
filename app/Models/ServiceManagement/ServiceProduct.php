<?php

namespace App\Models\ServiceManagement;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;

class ServiceProduct extends Model
{
    protected $table = 'srms_service_products';

    protected $guarded = [];

    protected $casts = [
        'quantity_required' => 'decimal:4',
        'default_unit_price' => 'decimal:2',
        'is_required' => 'boolean',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}

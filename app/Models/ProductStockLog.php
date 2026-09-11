<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductStockLog extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $casts = [
        'variant_data' => 'array',
    ];

    public const STOCK_IN_TYPES = ['purchase', 'initial', 'manual add', 'return', 'rental_return'];

    public const STOCK_OUT_TYPES = ['sales', 'waste', 'transfer', 'service_usage', 'rental_checkout'];
}

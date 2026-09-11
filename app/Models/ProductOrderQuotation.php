<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductOrderQuotation extends Model
{
    use HasFactory;

    protected $table = 'product_order_quotations';

    protected $guarded = [];

    protected $casts = [
        'other_charges' => 'array',
        'payments' => 'array',
        'request_data' => 'array',
        'delivery_info' => 'array',
        'courier_info' => 'array',
        'order_tracks' => 'array',
    ];

    public function order_products()
    {
        return $this->hasMany(ProductOrderQuotationProduct::class, 'product_order_quotation_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator');
    }

    public function customer()
    {
        return $this->belongsTo(\App\Http\Controllers\Customer\Models\Customer::class, 'customer_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(\App\Http\Controllers\Inventory\Models\ProductWarehouse::class, 'product_warehouse_id');
    }
}

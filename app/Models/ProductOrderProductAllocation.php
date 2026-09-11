<?php

namespace App\Models;

use App\Http\Controllers\Inventory\Models\ProductPurchaseOrder;
use App\Http\Controllers\Inventory\Models\ProductPurchaseOrderProduct;
use App\Http\Controllers\Inventory\Models\ProductWarehouse;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductOrderProductAllocation extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'meta' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(ProductOrder::class, 'product_order_id');
    }

    public function orderProduct()
    {
        return $this->belongsTo(ProductOrderProduct::class, 'product_order_product_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariantCombination::class, 'variant_id');
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(ProductPurchaseOrder::class, 'product_purchase_order_id');
    }

    public function purchaseOrderProduct()
    {
        return $this->belongsTo(ProductPurchaseOrderProduct::class, 'product_purchase_order_product_id');
    }

    public function purchaseUnit()
    {
        return $this->belongsTo(ProductPurchaseOrderProductUnit::class, 'product_purchase_order_product_unit_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(ProductWarehouse::class, 'product_warehouse_id');
    }
}


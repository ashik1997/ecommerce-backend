<?php

namespace App\Models;

use App\Http\Controllers\Inventory\Models\ProductPurchaseOrder;
use App\Http\Controllers\Inventory\Models\ProductPurchaseOrderProduct;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductOrderProduct extends Model
{
    use HasFactory;
    protected $guarded = [];

    protected $casts = [
        'item_meta' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(ProductOrder::class, 'product_order_id');
    }

    /**
     * Get the variant associated with this product order item
     */
    public function variant()
    {
        return $this->belongsTo(ProductVariantCombination::class, 'variant_id');
    }

    /**
     * Get the unit price associated with this product order item
     */
    public function unitPrice()
    {
        return $this->belongsTo(ProductUnitPricing::class, 'unit_price_id');
    }

    /**
     * Get the product associated with this order item
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
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

    public function soldUnits()
    {
        return $this->hasMany(ProductPurchaseOrderProductUnit::class, 'product_order_product_id');
    }

    public function allocations()
    {
        return $this->hasMany(ProductOrderProductAllocation::class, 'product_order_product_id');
    }
}

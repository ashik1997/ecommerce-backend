<?php

namespace App\Models;

use App\Http\Controllers\Inventory\Models\ProductPurchaseOrderProduct;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductPurchaseOrderProductUnit extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'extra_attributes' => 'array',
        'supplier_warranty_start_date' => 'date',
        'supplier_warranty_end_date' => 'date',
        'customer_warranty_start_date' => 'date',
        'customer_warranty_end_date' => 'date',
    ];

    public function product()
    {
        return $this->belongsTo(\App\Models\Product::class, 'product_id');
    }

    public function productPurchaseOrderProduct()
    {
        return $this->belongsTo(ProductPurchaseOrderProduct::class, 'product_purchase_order_product_id');
    }

    public function productPurchaseOrder()
    {
        return $this->belongsTo(\App\Http\Controllers\Inventory\Models\ProductPurchaseOrder::class, 'product_purchase_order_id');
    }

    public function variantCombination()
    {
        return $this->belongsTo(\App\Models\ProductVariantCombination::class, 'variant_combination_id');
    }

    public function saleOrder()
    {
        return $this->belongsTo(ProductOrder::class, 'sale_id');
    }

    public function saleOrderProduct()
    {
        return $this->belongsTo(ProductOrderProduct::class, 'product_order_product_id');
    }
}

<?php

namespace App\Http\Controllers\Inventory\Models;

use App\Models\Product;
use App\Models\ProductPurchaseOrderProductUnit;
use App\Models\ProductVariantCombination;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductPurchaseOrderProduct extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function variantCombination()
    {
        return $this->belongsTo(ProductVariantCombination::class, 'variant_combination_id');
    }

    /**
     * Individual barcode unit records for this line item.
     * Returns all units that have NOT been consumed (excludes sold/returned/lost/damaged).
     * 'pending' is not in the DB enum so MySQL stores it as '' or NULL in non-strict mode —
     * using whereNotIn ensures those rows are still returned correctly.
     */
    public function units()
    {
        return $this->hasMany(ProductPurchaseOrderProductUnit::class, 'product_purchase_order_product_id')
                    ->whereNotIn('unit_status', ['sold', 'returned', 'lost', 'damaged']);
    }

    public function allUnits()
    {
        return $this->hasMany(ProductPurchaseOrderProductUnit::class, 'product_purchase_order_product_id');
    }
}

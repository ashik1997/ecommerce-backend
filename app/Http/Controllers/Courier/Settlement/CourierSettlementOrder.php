<?php

namespace App\Http\Controllers\Courier\Settlement;

use App\Models\ProductOrder;
use Illuminate\Database\Eloquent\Model;

class CourierSettlementOrder extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_inventory_adjusted' => 'boolean',
        'is_accounting_posted' => 'boolean',
        'raw_response' => 'array',
        'notes' => 'array',
    ];

    public function settlement()
    {
        return $this->belongsTo(CourierSettlement::class, 'courier_settlement_id');
    }

    public function order()
    {
        return $this->belongsTo(ProductOrder::class, 'product_order_id');
    }
}

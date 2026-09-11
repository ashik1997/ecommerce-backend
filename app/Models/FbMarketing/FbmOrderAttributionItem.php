<?php

namespace App\Models\FbMarketing;

use Illuminate\Database\Eloquent\Model;

class FbmOrderAttributionItem extends Model
{
    protected $table = 'fbm_order_attribution_items';

    protected $fillable = [
        'fbm_order_attribution_id',
        'source_order_item_id',
        'product_id',
        'product_reference_snapshot',
        'quantity_snapshot',
        'unit_price_snapshot',
        'line_total_snapshot',
    ];

    protected $casts = [
        'source_order_item_id' => 'integer',
        'product_id' => 'integer',
        'quantity_snapshot' => 'float',
        'unit_price_snapshot' => 'float',
        'line_total_snapshot' => 'float',
    ];

    public function attribution()
    {
        return $this->belongsTo(FbmOrderAttribution::class, 'fbm_order_attribution_id');
    }
}

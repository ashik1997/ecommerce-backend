<?php

namespace App\Models\FbMarketing;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class FbmCatalogProductMapping extends Model
{
    public const STATUS_AUTOMATIC = 'automatic';
    public const STATUS_MANUAL = 'manual';
    public const STATUS_UNMATCHED = 'unmatched';
    public const STATUS_AMBIGUOUS = 'ambiguous';

    public const SOURCE_RETAILER_ID = 'retailer_id';
    public const SOURCE_MANUAL_OVERRIDE = 'manual_override';

    protected $table = 'fbm_catalog_product_mappings';
    protected $guarded = [];

    protected $casts = [
        'diagnostic_flags' => 'array',
        'provider_price_amount' => 'decimal:4',
        'is_available' => 'boolean',
        'last_seen_at' => 'datetime',
        'mapped_at' => 'datetime',
    ];

    protected $hidden = [
        'provider_product_item_id',
    ];

    public function catalog()
    {
        return $this->belongsTo(FbmCatalog::class, 'fbm_catalog_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function mapper()
    {
        return $this->belongsTo(User::class, 'mapped_by');
    }

    public function toSafeSummary(): array
    {
        return [
            'id' => (int) $this->id,
            'fbm_catalog_id' => (int) $this->fbm_catalog_id,
            'catalog_name' => optional($this->catalog)->asset_name,
            'product_id' => $this->product_id ? (int) $this->product_id : null,
            'product_name' => optional($this->product)->name,
            'product_status' => optional($this->product)->status,
            'retailer_id' => $this->retailer_id,
            'retailer_product_group_id' => $this->retailer_product_group_id,
            'item_name' => $this->item_name,
            'provider_availability' => $this->provider_availability,
            'provider_price_amount' => $this->provider_price_amount === null ? null : (float) $this->provider_price_amount,
            'provider_currency' => $this->provider_currency,
            'mapping_status' => (string) $this->mapping_status,
            'mapping_source' => $this->mapping_source,
            'diagnostic_flags' => is_array($this->diagnostic_flags) ? $this->diagnostic_flags : [],
            'is_available' => (bool) $this->is_available,
            'last_seen_at' => optional($this->last_seen_at)->toDateTimeString(),
            'mapped_by' => $this->mapped_by ? (int) $this->mapped_by : null,
            'mapped_at' => optional($this->mapped_at)->toDateTimeString(),
            'mapping_note' => $this->mapping_note,
        ];
    }
}

<?php

namespace App\Models\FbMarketing;

use Illuminate\Database\Eloquent\Model;

class FbmProductSet extends Model
{
    protected $table = 'fbm_product_sets';
    protected $guarded = [];

    protected $casts = [
        'filter_summary' => 'array',
        'item_count' => 'integer',
        'is_available' => 'boolean',
        'is_selected' => 'boolean',
        'last_seen_at' => 'datetime',
        'selected_at' => 'datetime',
    ];

    protected $hidden = [
        'provider_product_set_id',
    ];

    public function catalog()
    {
        return $this->belongsTo(FbmCatalog::class, 'fbm_catalog_id');
    }

    public function toSafeSummary(): array
    {
        return [
            'id' => (int) $this->id,
            'fbm_catalog_id' => (int) $this->fbm_catalog_id,
            'catalog_name' => optional($this->catalog)->asset_name,
            'set_name' => $this->set_name,
            'filter_summary' => is_array($this->filter_summary) ? $this->filter_summary : [],
            'item_count' => $this->item_count === null ? null : (int) $this->item_count,
            'is_available' => (bool) $this->is_available,
            'is_selected' => (bool) $this->is_selected,
            'selection_status' => (string) $this->selection_status,
            'planned_use' => (string) $this->planned_use,
            'consent_note' => (string) $this->consent_note,
            'last_seen_at' => optional($this->last_seen_at)->toDateTimeString(),
            'selected_at' => optional($this->selected_at)->toDateTimeString(),
        ];
    }
}

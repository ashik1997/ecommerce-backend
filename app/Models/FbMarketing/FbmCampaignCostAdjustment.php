<?php

namespace App\Models\FbMarketing;

use Illuminate\Database\Eloquent\Model;

class FbmCampaignCostAdjustment extends Model
{
    protected $table = 'fbm_campaign_cost_adjustments';

    protected $fillable = [
        'adjustment_uuid',
        'effective_date',
        'fbm_campaign_id',
        'fbm_ad_set_id',
        'fbm_ad_id',
        'currency',
        'cost_type',
        'label',
        'base_amount',
        'vat_amount',
        'tax_amount',
        'service_charge_amount',
        'total_amount',
        'status',
        'safe_note',
        'created_by',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'fbm_campaign_id' => 'integer',
        'fbm_ad_set_id' => 'integer',
        'fbm_ad_id' => 'integer',
        'base_amount' => 'float',
        'vat_amount' => 'float',
        'tax_amount' => 'float',
        'service_charge_amount' => 'float',
        'total_amount' => 'float',
    ];

    public function campaign()
    {
        return $this->belongsTo(FbmCampaign::class, 'fbm_campaign_id');
    }

    public function adSet()
    {
        return $this->belongsTo(FbmAdSet::class, 'fbm_ad_set_id');
    }

    public function ad()
    {
        return $this->belongsTo(FbmAd::class, 'fbm_ad_id');
    }
}

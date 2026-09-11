<?php

namespace App\Models\FbMarketing;

use Illuminate\Database\Eloquent\Model;

class FbmInsightDailySnapshot extends Model
{
    protected $table = 'fbm_insight_daily_snapshots';
    protected $guarded = [];

    protected $casts = [
        'snapshot_date' => 'date',
        'freshness_watermark' => 'date',
        'fetched_at' => 'datetime',
        'action_metrics' => 'array',
        'action_value_metrics' => 'array',
    ];

    protected $hidden = [
        'entity_provider_sync_key',
        'metrics_hash',
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

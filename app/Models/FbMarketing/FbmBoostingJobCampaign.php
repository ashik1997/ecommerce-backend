<?php

namespace App\Models\FbMarketing;

use Illuminate\Database\Eloquent\Model;

class FbmBoostingJobCampaign extends Model
{
    protected $table = 'fbm_boosting_job_campaigns';
    protected $guarded = [];

    protected $casts = [
        'fbm_boosting_job_id' => 'integer',
        'fbm_campaign_id' => 'integer',
        'fbm_ad_set_id' => 'integer',
        'fbm_ad_id' => 'integer',
        'allocation_percent' => 'float',
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
